<?php
/*************************************************************************
V49 - Search & Discovery 2.0
**************************************************************************/
include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');

$uploads = new Uploads($storeId);
$articles = new Articles($storeId);
$template->assign('uploads', $uploads);

$q = trim((string)$request->element('q'));
if (function_exists('mb_substr')) $q = mb_substr($q, 0, 120, 'UTF-8');
else $q = substr($q, 0, 120);
$type = strtolower(trim((string)$request->element('type', 'all')));
$category = strtolower(trim((string)$request->element('category')));
$dateFrom = trim((string)$request->element('from'));
$dateTo = trim((string)$request->element('to'));
if (!in_array($type, array('all', 'literature', 'arts', 'news', 'video'), true)) $type = 'all';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = '';

$categoryRows = array();
$categoryResult = $db->query(
    "SELECT id, slug, name FROM `" . DB_PREFIX . "article_categories` " .
    "WHERE store_id IN (0," . (int)$storeId . ") AND status=1 ORDER BY position, id"
);
if ($categoryResult) {
    while ($row = $db->fetchArray($categoryResult, 1)) $categoryRows[] = $row;
    $db->freeResult($categoryResult);
}

$groups = array(
    'literature' => array('tho', 'van-xuoi'),
    'arts' => array('am-nhac', 'my-thuat', 'san-khau-nghe-thuat', 'van-hoa'),
    'news' => array('tin-tuc', 'tin-tuc-moi'),
    'video' => array('video')
);
$editorialSlugs = array_merge($groups['literature'], $groups['arts'], $groups['news'], $groups['video']);
$categoryIdsBySlug = array();
$searchCategories = array();
foreach ($categoryRows as $row) {
    $categoryIdsBySlug[$row['slug']] = (int)$row['id'];
    if (in_array($row['slug'], $editorialSlugs, true)) $searchCategories[] = $row;
}
if ($category !== '' && !isset($categoryIdsBySlug[$category])) $category = '';

$conditions = array("a.status = 1");
$scopeSlugs = $type !== 'all' ? $groups[$type] : $editorialSlugs;
$scopeIds = array();
foreach ($scopeSlugs as $slug) if (isset($categoryIdsBySlug[$slug])) $scopeIds[] = $categoryIdsBySlug[$slug];
if ($category !== '') {
    $conditions[] = 'a.category_id = ' . (int)$categoryIdsBySlug[$category];
} elseif ($scopeIds) {
    $conditions[] = 'a.category_id IN (' . implode(',', array_unique($scopeIds)) . ')';
} else {
    $conditions[] = '1 = 0';
}

if ($lang === 'en') $conditions[] = "a.slug_en <> '' AND a.lang LIKE '%en%'";
if ($lang === 'zh') $conditions[] = "a.slug_zh <> '' AND a.lang LIKE '%zh%'";
if ($q !== '') {
    $safeQ = mysqli_real_escape_string($db->connection, $q);
    $conditions[] = "(a.title LIKE '%$safeQ%' OR a.description LIKE '%$safeQ%' OR a.keyword LIKE '%$safeQ%' OR a.properties LIKE '%$safeQ%')";
}
if ($dateFrom !== '') {
    $safeFrom = mysqli_real_escape_string($db->connection, $dateFrom);
    $conditions[] = "DATE(COALESCE(a.publish_at,a.date_created)) >= '$safeFrom'";
}
if ($dateTo !== '') {
    $safeTo = mysqli_real_escape_string($db->connection, $dateTo);
    $conditions[] = "DATE(COALESCE(a.publish_at,a.date_created)) <= '$safeTo'";
}

$condition = implode(' AND ', $conditions);
$result = paginate(
    $request,
    $articles,
    $condition,
    $condition,
    array('COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC', 'a.id' => 'DESC'),
    12
);

$curatedVideos = array(
    array('id' => 'Q8ucXj2pDbo', 'title' => 'Không gian văn hóa nghệ thuật: Bảo tàng Mỹ thuật Việt Nam'),
    array('id' => 'q-r5WwQukik', 'title' => 'Phim tài liệu 60 năm Bảo tàng Mỹ thuật Việt Nam'),
    array('id' => 'vx54SQs3A1M', 'title' => 'Trải nghiệm mỹ thuật với ứng dụng iMuseum VFA'),
    array('id' => 'tJTkPdk3Mks', 'title' => 'Không gian mỹ thuật đương đại')
);
$videoResults = array();
if (($type === 'all' || $type === 'video') && $category === '' && $dateFrom === '' && $dateTo === '') {
    foreach ($curatedVideos as $video) {
        if ($q === '' || (function_exists('mb_stripos') ? mb_stripos($video['title'], $q, 0, 'UTF-8') !== false : stripos($video['title'], $q) !== false)) {
            $videoResults[] = $video;
        }
    }
}

$queryParams = array('q' => $q, 'type' => $type, 'category' => $category, 'from' => $dateFrom, 'to' => $dateTo);
$template->assign('searchQuery', $q);
$template->assign('searchType', $type);
$template->assign('searchCategory', $category);
$template->assign('searchFrom', $dateFrom);
$template->assign('searchTo', $dateTo);
$template->assign('searchCategories', $searchCategories);
$template->assign('searchItems', $result['items']);
$template->assign('searchPage', $result['page']);
$template->assign('searchTotalPages', $result['totalPages']);
$template->assign('searchTotalRows', $result['totalRows']);
$template->assign('searchVideoResults', $videoResults);
$template->assign('searchQueryString', http_build_query(array_filter($queryParams, function ($value) { return $value !== ''; })));

$templateFile = 'editorial-search.tpl.html';
$slugActive = 'search';
$template->assign('slugActive', $slugActive);
$searchLabel = $lang === 'en' ? 'Search' : ($lang === 'zh' ? '搜索' : 'Tìm kiếm');
$template->assign('pageTitle', ($q !== '' ? $searchLabel . ': ' . $q : $searchLabel) . ' | ' . $estore->getName());
$template->assign('titlePage', $searchLabel);
$template->assign('pageKeywords', $q);
$template->assign('pageDescription', $searchLabel . ($q !== '' ? ': ' . $q : ''));
$topNav = array(array('name' => $lang === 'en' ? 'Home' : ($lang === 'zh' ? '首页' : 'Trang chủ'), 'url' => $publicHome), array('name' => $searchLabel, 'url' => $publicBase . '/tim-kiem'));
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));
?>
