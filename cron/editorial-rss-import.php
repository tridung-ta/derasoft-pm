<?php
/**
 * DeraCMS editorial RSS importer.
 * CLI only. Default is dry-run; pass --commit to create waiting articles.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', dirname(__DIR__) . '/');
include_once ROOT_PATH . 'includes/constant.inc.php';
include_once ROOT_PATH . 'includes/config.inc.php';
include_once ROOT_PATH . 'includes/functions.inc.php';
include_once ROOT_PATH . 'classes/database/mysql.class.php';
include_once ROOT_PATH . 'classes/dao/articles.class.php';
include_once ROOT_PATH . 'classes/dao/articlecategories.class.php';
include_once ROOT_PATH . 'classes/dao/uploads.class.php';
include_once ROOT_PATH . 'classes/dao/uploadalbums.class.php';
include_once ROOT_PATH . 'classes/data/textfilter.class.php';

date_default_timezone_set(defined('TIME_ZONE') ? TIME_ZONE : 'Asia/Ho_Chi_Minh');
$query_count = 0;
$db = new DB();
$storeId = 1;
$commit = in_array('--commit', $argv, true);
$insecure = in_array('--insecure', $argv, true);
if ($commit && $insecure) {
    fwrite(STDERR, "--insecure is allowed for dry-run only.\n");
    exit(2);
}
$limit = 8;
foreach ($argv as $argument) {
    if (strpos($argument, '--limit=') === 0) {
        $limit = max(1, min(30, (int) substr($argument, 8)));
    }
}

$sources = include __DIR__ . '/editorial-rss-sources.php';
$articles = new Articles($storeId);
$categories = new ArticleCategories($storeId);
$uploads = new Uploads($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$listCategories = in_array('--list-categories', $argv, true);
if ($listCategories) {
    $availableCategories = $categories->getObjects(1, "c.status = '1'", array('id' => 'ASC'), 0);
    if ($availableCategories) {
        foreach ($availableCategories as $availableCategory) {
            echo $availableCategory->getId() . "\t" . $availableCategory->getSlug() . PHP_EOL;
        }
    }
    exit(0);
}
$categoryCache = array();
$stats = array('seen' => 0, 'new' => 0, 'duplicate' => 0, 'filtered' => 0, 'invalid' => 0, 'missing_category' => 0, 'failed' => 0);

function rssFetch($url, $insecure = false)
{
    if (!filter_var($url, FILTER_VALIDATE_URL) || stripos($url, 'https://') !== 0) {
        throw new RuntimeException('Only HTTPS RSS URLs are accepted.');
    }
    $curl = curl_init($url);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 18,
        CURLOPT_SSL_VERIFYPEER => !$insecure,
        CURLOPT_SSL_VERIFYHOST => $insecure ? 0 : 2,
        CURLOPT_USERAGENT => 'DeraCMS-Editorial-RSS/1.0',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ));
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    unset($curl);
    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('RSS request failed: HTTP ' . $status . ($error ? ' - ' . $error : ''));
    }
    if (strlen($body) > 2097152) {
        throw new RuntimeException('RSS response exceeds 2 MB.');
    }
    return $body;
}

function rssText($value, $maxLength = 0)
{
    $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', trim($text));
    if ($maxLength > 0 && mb_strlen($text, 'UTF-8') > $maxLength) {
        $text = rtrim(mb_substr($text, 0, $maxLength - 1, 'UTF-8')) . '…';
    }
    return $text;
}

function rssHostAllowed($url, $allowedHosts)
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (!$host || !is_array($allowedHosts)) return false;
    foreach ($allowedHosts as $allowedHost) {
        $allowedHost = strtolower(trim((string) $allowedHost));
        if ($allowedHost !== '' && ($host === $allowedHost || substr($host, -strlen('.' . $allowedHost)) === '.' . $allowedHost)) {
            return true;
        }
    }
    return false;
}

function rssImageUrl($item, $source)
{
    $candidates = array();
    $media = $item->children('http://search.yahoo.com/mrss/');
    if (isset($media->content)) $candidates[] = (string) $media->content->attributes()->url;
    if (isset($media->thumbnail)) $candidates[] = (string) $media->thumbnail->attributes()->url;
    if (isset($item->enclosure)) {
        $enclosure = $item->enclosure->attributes();
        if (isset($enclosure->url)) $candidates[] = (string) $enclosure->url;
    }
    $descriptionHtml = html_entity_decode((string) $item->description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $descriptionHtml, $match)) $candidates[] = $match[1];

    foreach ($candidates as $candidate) {
        $candidate = trim(html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (filter_var($candidate, FILTER_VALIDATE_URL)
            && stripos($candidate, 'https://') === 0
            && rssHostAllowed($candidate, isset($source['image_hosts']) ? $source['image_hosts'] : array())) {
            return $candidate;
        }
    }
    return '';
}

function rssFetchImage($url, $allowedHosts)
{
    if (!rssHostAllowed($url, $allowedHosts)) throw new RuntimeException('Image host is not allowed.');
    $curl = curl_init($url);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'DeraCMS-Editorial-RSS/1.1',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ));
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $contentType = strtolower((string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
    $error = curl_error($curl);
    unset($curl);
    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('Image request failed: HTTP ' . $status . ($error ? ' - ' . $error : ''));
    }
    if (strlen($body) < 128 || strlen($body) > 5242880 || strpos($contentType, 'image/') !== 0) {
        throw new RuntimeException('Invalid image response.');
    }
    $info = @getimagesizefromstring($body);
    if (!$info || $info[0] < 200 || $info[1] < 120 || ((int) $info[0] * (int) $info[1]) > 25000000) {
        throw new RuntimeException('Image dimensions are not accepted.');
    }
    $extensions = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) $extensions[IMAGETYPE_WEBP] = 'webp';
    if (!isset($extensions[$info[2]])) throw new RuntimeException('Unsupported image format.');
    return array($body, $extensions[$info[2]]);
}

function rssGetYearAlbum($uploadAlbums, $storeId)
{
    $year = date('Y');
    $album = $uploadAlbums->getObject($year, 'name');
    if (!$album) {
        $albumId = $uploadAlbums->addData(array(
            'store_id' => $storeId,
            'name' => $year,
            'status' => 1,
            'folder' => $year,
            'date_created' => date('Y-m-d H:i:s'),
            'properties' => serialize(array()),
        ));
        $album = $albumId ? $uploadAlbums->getObject($albumId) : null;
    }
    if (!$album || $album->getProperty('create_album_error')) throw new RuntimeException('Upload album is not writable.');
    return $album;
}

function rssStoreImage($imageUrl, $source, $title, $hash, $uploads, $uploadAlbums, $storeId)
{
    list($body, $extension) = rssFetchImage($imageUrl, isset($source['image_hosts']) ? $source['image_hosts'] : array());
    $album = rssGetYearAlbum($uploadAlbums, $storeId);
    $folder = $album->getAbsoluteFolder();
    $base = 'rss-' . substr($hash, 0, 16) . '-' . substr(hash('sha256', $imageUrl), 0, 8);
    $original = $base . '_o.' . $extension;
    if (file_put_contents($folder . $original, $body, LOCK_EX) === false) throw new RuntimeException('Cannot write imported image.');

    $data = array(
        'store_id' => $storeId,
        'album_id' => $album->getId(),
        'status' => 1,
        'url_o' => KEEP_ORIGINAL_IMAGE_FILE ? $original : '',
        'url_l' => '', 'url_m' => '', 'url_t' => '', 'url_a' => '',
        'type' => 1,
        'object' => 'article',
        'name' => rssText($title, 220),
        'date_created' => date('Y-m-d H:i:s'),
    );
    $variants = array(
        'url_l' => array(defined('CREATE_LARGE_IMAGE') && CREATE_LARGE_IMAGE, 'l', DEFAULT_LARGE_SIZE, DEFAULT_LARGE_SQUARE),
        'url_m' => array(defined('CREATE_MEDIUM_IMAGE') && CREATE_MEDIUM_IMAGE, 'm', DEFAULT_MEDIUM_SIZE, DEFAULT_MEDIUM_SQUARE),
        'url_t' => array(defined('CREATE_THUMBNAIL_IMAGE') && CREATE_THUMBNAIL_IMAGE, 't', DEFAULT_THUMBNAIL_SIZE, DEFAULT_THUMBNAIL_SQUARE),
        'url_a' => array(defined('CREATE_AVATAR_IMAGE') && CREATE_AVATAR_IMAGE, 'a', DEFAULT_AVATAR_SIZE, DEFAULT_AVATAR_SQUARE),
    );
    foreach ($variants as $field => $variant) {
        if (!$variant[0]) continue;
        $filename = $base . '_' . $variant[1] . '.' . $extension;
        resize($folder, $folder, $original, $filename, $variant[2], $variant[3], DEFAULT_PHOTO_QUALITY);
        if (is_file($folder . $filename)) $data[$field] = $filename;
    }
    if (!$data['url_l']) $data['url_l'] = $original;
    if (!$data['url_m']) $data['url_m'] = $data['url_l'];
    if (!$data['url_t']) $data['url_t'] = $data['url_m'];
    if (!$data['url_a']) $data['url_a'] = $data['url_t'];
    $uploadId = $uploads->addData($data);
    if (!$uploadId) {
        foreach (array_unique(array_filter(array($original, $data['url_l'], $data['url_m'], $data['url_t'], $data['url_a']))) as $filename) {
            if (is_file($folder . $filename)) @unlink($folder . $filename);
        }
        throw new RuntimeException('Cannot save imported image record.');
    }
    if (!KEEP_ORIGINAL_IMAGE_FILE && is_file($folder . $original)) @unlink($folder . $original);
    $upload = $uploads->getObject($uploadId);
    return array(
        'avatarId' => $uploadId,
        'avatarUrl' => $upload ? '/' . $upload->getPath() . '/' . $upload->getUrlA() : '',
        'avatarLargeUrl' => $upload ? '/' . $upload->getPath() . '/' . $upload->getUrlL() : '',
        'editorial_image_source_url' => $imageUrl,
    );
}

function rssSlug($title, $hash)
{
    $slug = mb_strtolower($title, 'UTF-8');
    $ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) : false;
    if ($ascii !== false) $slug = $ascii;
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');
    if (!$slug) $slug = 'tin-moi';
    return substr($slug, 0, 120) . '-' . substr($hash, 0, 8);
}

function rssTranslateChunk($text, $targetLanguage)
{
    $url = 'https://api.mymemory.translated.net/get?' . http_build_query(array(
        'q' => $text,
        'langpair' => 'vi|' . $targetLanguage,
        'mt' => 1,
    ), '', '&', PHP_QUERY_RFC3986);
    $curl = curl_init($url);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 18,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'DeraCMS-Editorial-Translator/1.0',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ));
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    unset($curl);
    if ($body === false || $status !== 200) {
        throw new RuntimeException('Translation request failed: HTTP ' . $status . ($error ? ' - ' . $error : ''));
    }
    $payload = json_decode($body, true);
    $translated = isset($payload['responseData']['translatedText']) ? $payload['responseData']['translatedText'] : '';
    $translated = rssText(html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($translated === '') throw new RuntimeException('Translation response is empty.');
    return $translated;
}

function rssTranslateText($text, $targetLanguage)
{
    $text = rssText($text);
    if ($text === '') return '';
    $parts = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) $parts = array($text);
    $chunks = array();
    $chunk = '';
    foreach ($parts as $part) {
        if (strlen($part) > 430) {
            if ($chunk !== '') { $chunks[] = $chunk; $chunk = ''; }
            while (strlen($part) > 430) {
                $cut = mb_strcut($part, 0, 430, 'UTF-8');
                $space = mb_strrpos($cut, ' ', 0, 'UTF-8');
                if ($space !== false && $space > 100) $cut = mb_substr($cut, 0, $space, 'UTF-8');
                $chunks[] = trim($cut);
                $part = trim(mb_substr($part, mb_strlen($cut, 'UTF-8'), null, 'UTF-8'));
            }
        }
        $candidate = trim($chunk . ' ' . $part);
        if ($chunk !== '' && strlen($candidate) > 430) {
            $chunks[] = $chunk;
            $chunk = trim($part);
        } else {
            $chunk = $candidate;
        }
    }
    if ($chunk !== '') $chunks[] = $chunk;
    $translated = array();
    foreach ($chunks as $item) $translated[] = rssTranslateChunk($item, $targetLanguage);
    return trim(implode(' ', $translated));
}

function rssLocalizedDetail($description, $sourceName, $sourceUrl, $language)
{
    $sourceLabel = $language === 'en' ? 'Source' : '来源';
    $readLabel = $language === 'en' ? 'Read the original article' : '阅读原文';
    return '<p>' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>' . $sourceLabel . ':</strong> ' . htmlspecialchars($sourceName, ENT_QUOTES, 'UTF-8') . '.</p>'
        . '<p><a href="' . htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer nofollow">' . $readLabel . '</a></p>';
}

function rssTranslationData($title, $description, $sourceName, $sourceUrl, $hash)
{
    $titleEn = rssTranslateText($title, 'en');
    $descriptionEn = rssTranslateText($description, 'en');
    $titleZh = rssTranslateText($title, 'zh-CN');
    $descriptionZh = rssTranslateText($description, 'zh-CN');
    return array(
        'slug_en' => rssSlug($titleEn, $hash),
        'slug_zh' => rssSlug('zh-' . substr($hash, 0, 12), $hash),
        'properties' => array(
            'custom_en_title' => $titleEn,
            'custom_en_description' => $descriptionEn,
            'custom_en_detail' => rssLocalizedDetail($descriptionEn, $sourceName, $sourceUrl, 'en'),
            'custom_zh_title' => $titleZh,
            'custom_zh_description' => $descriptionZh,
            'custom_zh_detail' => rssLocalizedDetail($descriptionZh, $sourceName, $sourceUrl, 'zh'),
        ),
    );
}

function rssCategorySlug($title, $description, $fallback)
{
    $text = mb_strtolower($title . ' ' . $description, 'UTF-8');
    $maps = array(
        'am-nhac' => array('âm nhạc', 'ca sĩ', 'nhạc sĩ', 'concert', 'album'),
        'my-thuat' => array('mỹ thuật', 'hội họa', 'triển lãm', 'điêu khắc', 'nhiếp ảnh'),
        'san-khau-nghe-thuat' => array('sân khấu', 'kịch', 'tuồng', 'chèo', 'cải lương'),
        'tho' => array('thơ', 'thi ca', 'nhà thơ'),
        'van-xuoi' => array('văn học', 'tiểu thuyết', 'truyện ngắn', 'nhà văn', 'tản văn'),
        'van-hoa' => array('văn hóa', 'di sản', 'lễ hội', 'truyền thống'),
    );
    foreach ($maps as $slug => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($text, $keyword, 0, 'UTF-8') !== false) return $slug;
        }
    }
    return $fallback;
}

function rssMatchesSourceFilters($title, $description, $source)
{
    if (empty($source['include_keywords'])) return true;
    $text = mb_strtolower($title . ' ' . $description, 'UTF-8');
    foreach ($source['include_keywords'] as $keyword) {
        if (mb_strpos($text, mb_strtolower($keyword, 'UTF-8'), 0, 'UTF-8') !== false) return true;
    }
    return false;
}

function rssCategoryId($slug, $categories, &$cache)
{
    if (isset($cache[$slug])) return $cache[$slug];
    $category = $categories->getObject($slug, 'slug', "c.status = '1'");
    if (!$category && $slug === 'tin-tuc-moi') {
        $category = $categories->getObject('tin-tuc', 'slug', "c.status = '1'");
    }
    $cache[$slug] = $category ? (int) $category->getId() : 0;
    return $cache[$slug];
}

$backfillTranslations = in_array('--backfill-translations', $argv, true);
if ($backfillTranslations) {
    if (!$commit) {
        fwrite(STDERR, "Use --commit together with --backfill-translations.\n");
        exit(2);
    }
    $pending = $articles->getObjects(
        1,
        "a.properties LIKE '%editorial_imported%' AND (a.slug_en IS NULL OR a.slug_en = '' OR a.slug_zh IS NULL OR a.slug_zh = '' OR a.lang NOT LIKE '%en%' OR a.lang NOT LIKE '%zh%')",
        array('id' => 'ASC'),
        $limit
    );
    $translatedCount = 0;
    $failedCount = 0;
    foreach ($pending as $article) {
        try {
            $properties = $article->getProperties();
            if (!is_array($properties)) $properties = array();
            $sourceName = isset($properties['editorial_source_name']) ? $properties['editorial_source_name'] : '';
            $sourceUrl = isset($properties['editorial_source_url']) ? $properties['editorial_source_url'] : '';
            $hash = isset($properties['editorial_source_hash']) ? $properties['editorial_source_hash'] : hash('sha256', (string) $article->getId());
            $translation = rssTranslationData($article->getTitle('vn'), $article->getDescription('vn'), $sourceName, $sourceUrl, $hash);
            $properties = array_merge($properties, $translation['properties'], array(
                'editorial_translation_provider' => 'MyMemory',
                'editorial_translation_updated_at' => date('Y-m-d H:i:s'),
            ));
            $updated = $articles->updateData(array(
                'slug_en' => $translation['slug_en'],
                'slug_zh' => $translation['slug_zh'],
                'lang' => 'vn,en,zh',
                'properties' => serialize($properties),
                'date_updated' => date('Y-m-d H:i:s'),
            ), $article->getId());
            if (!$updated) throw new RuntimeException('Cannot update article.');
            $translatedCount++;
            echo '[TRANSLATED] #' . $article->getId() . ' ' . $article->getTitle('vn') . PHP_EOL;
        } catch (Throwable $translationError) {
            $failedCount++;
            fwrite(STDERR, '[TRANSLATION] #' . $article->getId() . ': ' . $translationError->getMessage() . PHP_EOL);
        }
    }
    echo sprintf("Done: translated=%d failed=%d mode=backfill\n", $translatedCount, $failedCount);
    exit($failedCount ? 1 : 0);
}

foreach ($sources as $source) {
    if (empty($source['enabled'])) continue;
    try {
        $xmlBody = rssFetch($source['url'], $insecure);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlBody, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if (!$xml || empty($xml->channel->item)) throw new RuntimeException('Invalid or empty RSS document.');
        $processed = 0;
        foreach ($xml->channel->item as $item) {
            if ($processed >= $limit) break;
            $stats['seen']++;
            $title = rssText($item->title, 220);
            $description = rssText($item->description, 500);
            $sourceUrl = trim((string) $item->link);
            $imageUrl = rssImageUrl($item, $source);
            if (!$title || !filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
                $stats['invalid']++;
                $stats['failed']++;
                continue;
            }
            if (!rssMatchesSourceFilters($title, $description, $source)) {
                $stats['filtered']++;
                continue;
            }
            $hash = hash('sha256', $sourceUrl);
            $duplicate = $articles->getObjects(1, "a.properties LIKE '%editorial_source_hash%' AND a.properties LIKE '%" . $hash . "%'", array(), 1);
            if ($duplicate) {
                $stats['duplicate']++;
                $existingArticle = $duplicate[0];
                if ($commit && $imageUrl && !$existingArticle->getProperty('avatarId')) {
                    try {
                        $existingProperties = $existingArticle->getProperties();
                        if (!is_array($existingProperties)) $existingProperties = array();
                        $existingProperties = array_merge(
                            $existingProperties,
                            rssStoreImage($imageUrl, $source, $title, $hash, $uploads, $uploadAlbums, $storeId)
                        );
                        if ($articles->updateData(array('properties' => serialize($existingProperties), 'date_updated' => date('Y-m-d H:i:s')), $existingArticle->getId())) {
                            echo '[IMAGE-BACKFILLED] ' . $title . PHP_EOL;
                        }
                    } catch (Throwable $imageError) {
                        fwrite(STDERR, '[IMAGE] ' . $title . ': ' . $imageError->getMessage() . PHP_EOL);
                    }
                }
                continue;
            }
            $categorySlug = rssCategorySlug($title, $description, $source['default_category']);
            $categoryId = rssCategoryId($categorySlug, $categories, $categoryCache);
            if (!$categoryId) $categoryId = rssCategoryId($source['default_category'], $categories, $categoryCache);
            if (!$categoryId) {
                $stats['missing_category']++;
                $stats['failed']++;
                continue;
            }
            $published = strtotime((string) $item->pubDate);
            $publishAt = $published ? date('Y-m-d H:i:s', $published) : date('Y-m-d H:i:s');
            $properties = array(
                'editorial_imported' => 1,
                'editorial_source_name' => $source['name'],
                'editorial_source_url' => $sourceUrl,
                'editorial_source_hash' => $hash,
                'editorial_image_source_url' => $imageUrl,
                'editorial_translation_pending' => 1,
            );
            if ($commit && $imageUrl) {
                try {
                    $properties = array_merge($properties, rssStoreImage($imageUrl, $source, $title, $hash, $uploads, $uploadAlbums, $storeId));
                } catch (Throwable $imageError) {
                    fwrite(STDERR, '[IMAGE] ' . $title . ': ' . $imageError->getMessage() . PHP_EOL);
                }
            }
            $detail = '<p>' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><strong>Nguồn:</strong> ' . htmlspecialchars($source['name'], ENT_QUOTES, 'UTF-8') . '.</p>'
                . '<p><a href="' . htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer nofollow">Đọc bài gốc tại nguồn</a></p>';
            $translation = array('slug_en' => '', 'slug_zh' => '', 'properties' => array());
            if ($commit) {
                try {
                    $translation = rssTranslationData($title, $description, $source['name'], $sourceUrl, $hash);
                    $translation['properties']['editorial_translation_provider'] = 'MyMemory';
                    $translation['properties']['editorial_translation_updated_at'] = date('Y-m-d H:i:s');
                    $translation['properties']['editorial_translation_pending'] = 0;
                    $properties = array_merge($properties, $translation['properties']);
                } catch (Throwable $translationError) {
                    fwrite(STDERR, '[TRANSLATION] ' . $title . ': ' . $translationError->getMessage() . PHP_EOL);
                }
            }
            $articleLanguages = ($translation['slug_en'] !== '' && $translation['slug_zh'] !== '') ? 'vn,en,zh' : 'vn';
            $data = array(
                'store_id' => $storeId,
                'category_id' => $categoryId,
                'slug' => rssSlug($title, $hash),
                'slug_en' => $translation['slug_en'],
                'slug_zh' => $translation['slug_zh'],
                'title' => $title,
                'keyword' => '',
                'description' => $description,
                'detail' => $detail,
                'lang' => $articleLanguages,
                'viewed' => 0,
                'star' => 0,
                'article_group_ids' => '',
                'date_created' => date('Y-m-d H:i:s'),
                'date_updated' => date('Y-m-d H:i:s'),
                'properties' => serialize($properties),
                'position' => 1,
                'status' => defined('S_WAITING') ? S_WAITING : 3,
                'home' => 0,
                'publish_at' => $publishAt,
            );
            if ($commit && !$articles->addData($data)) {
                $stats['failed']++;
                continue;
            }
            $stats['new']++;
            $processed++;
            echo ($commit ? '[IMPORTED] ' : '[DRY-RUN] ') . $title . ' -> ' . $categorySlug . ($imageUrl ? ' [image]' : ' [no-image]') . PHP_EOL;
        }
    } catch (Throwable $error) {
        $stats['failed']++;
        fwrite(STDERR, '[' . $source['name'] . '] ' . $error->getMessage() . PHP_EOL);
    }
}

echo sprintf("Done: seen=%d new=%d duplicate=%d filtered=%d invalid=%d missing_category=%d failed=%d mode=%s\n", $stats['seen'], $stats['new'], $stats['duplicate'], $stats['filtered'], $stats['invalid'], $stats['missing_category'], $stats['failed'], $commit ? 'commit' : 'dry-run');
exit($stats['failed'] ? 1 : 0);
