<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/menus.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articlegroups.class.php');
include_once(ROOT_PATH . 'classes/dao/users.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialarticleratings.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialentitlement.class.php');

$uploadAlbums      = new UploadAlbums($storeId);
$uploads           = new Uploads($storeId);
$template->assign('uploads', $uploads);
$statics           = new StaticPage($storeId);
$products          = new Products($storeId);
$articles          = new Articles($storeId);
$menus             = new Menus($storeId);
$productCategories = new ProductCategories($storeId);
$articleCategories = new ArticleCategories($storeId);
$articleGroups      = new ArticleGroups($storeId);
$users             = new Users($storeId);
$editorialRatings  = new EditorialArticleRatings($storeId);
$editorialEntitlement = new EditorialEntitlement($storeId);

$templateFile = 'news-detail.tpl.html';
$slug = $request->element('slug');
if ($slug === '') {
    $articlePath = '/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if (preg_match('#^/(?:en/|zh/)?([a-z0-9][a-z0-9-]{0,190})$#', $articlePath, $articlePathMatch)) {
        $slug = $articlePathMatch[1];
    }
}

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}
$langCondition = $lang === 'en' ? " AND a.slug_en <> '' AND a.lang LIKE '%en%'" : ($lang === 'zh' ? " AND a.slug_zh <> '' AND a.lang LIKE '%zh%'" : '');

switch ($lang) {
    case 'en':
        $slugField = 'slug_en';
        break;
    case 'zh':
        $slugField = 'slug_zh';
        break;
    default:
        $slugField = 'slug';
        break;
}

$objectInfo = $articles->getObject($slug, $slugField);
$resolvedArticleId = 0;
if (!$objectInfo && $slug !== '') {
    $safeSlug = addslashes($slug);
    $resolvedRows = $db->query(
        "SELECT `id` FROM `" . DB_PREFIX . "articles` " .
        "WHERE (`store_id` = " . (int)$storeId . " OR `store_id` = 0) " .
        "AND `" . $slugField . "` = '" . $safeSlug . "' LIMIT 1"
    );
    if ($resolvedRows) {
        $resolvedRow = $db->fetchArray($resolvedRows, 1);
        $resolvedArticleId = !empty($resolvedRow['id']) ? (int)$resolvedRow['id'] : 0;
        $db->freeResult($resolvedRows);
    }
    if ($resolvedArticleId > 0) $objectInfo = $articles->getObject($resolvedArticleId);
}
$template->assign('objectInfo', $objectInfo);

// A stale or malformed article URL must render the normal 404 page instead
// of calling methods on an empty DAO result.
if (!$objectInfo || !is_object($objectInfo) || !method_exists($objectInfo, 'getId')) {
    $templateFile = '404.tpl.html';
    $template->assign('pageTitle', '404 | ' . $estore->getName());
    $template->assign('titlePage', '404');
    $template->assign('pageDescription', 'Article not found');
    return;
}

$customerId = !empty($_SESSION['store_customerId']) ? (int)$_SESSION['store_customerId'] : 0;
$isPremiumArticle = $editorialEntitlement->isPremiumArticle($objectInfo);
$activeSubscription = $isPremiumArticle && $customerId > 0 ? $editorialEntitlement->getActiveSubscription($customerId) : 0;
$hasPremiumAccess = (bool)$activeSubscription;
$canReadArticle = !$isPremiumArticle || $hasPremiumAccess;
$articleDetailHtml = $canReadArticle ? $objectInfo->getDetail($lang) : '';
$template->assign('isPremiumArticle', $isPremiumArticle);
$template->assign('hasPremiumAccess', $hasPremiumAccess);
$template->assign('canReadArticle', $canReadArticle);
$template->assign('activeSubscription', $activeSubscription);
$template->assign('articleDetailHtml', $articleDetailHtml);

assignLangUrls($template, $articles, $objectInfo->id, 'article');
if ($lang == 'en') {
    if (!$objectInfo->hasLang('en') || empty($objectInfo->getSlugEn())) {
        $templateFile = '404.tpl.html';
    }
} elseif ($lang == 'zh') {
    if (!$objectInfo->hasLang('zh') ||  empty($objectInfo->getSlugZh())) {
        $templateFile = '404.tpl.html';
    }
}
$categoryObj = $articleCategories->getObject($objectInfo->category_id);

$isCaseStudy = false;
if($categoryObj->getId() == 71){
    $isCaseStudy = true;
}
$template->assign('isCaseStudy', $isCaseStudy);

# Increase viewed
$articleId = $objectInfo->getId();

if (!isset($_SESSION['viewed_articles'])) {
    $_SESSION['viewed_articles'] = [];
}

if (!in_array($articleId, $_SESSION['viewed_articles'])) {
    $articles->increaseViewed($articleId);
    $_SESSION['viewed_articles'][] = $articleId;
}


$slugActive = $categoryObj->getSlug();
$template->assign('slugActive', $slugActive);
    
$type = 'articles';
$template->assign('type', $type);

$isDetail = true;
$template->assign('isDetail', $isDetail);

$userInfo = $users->getObject($objectInfo->poster_id);
$articleAuthor = null;
if ($userInfo && $userInfo->isEnabled()) {
    $articleAuthorName = trim((string)$userInfo->getFullName());
    if ($articleAuthorName !== '') {
        $articleAuthor = array(
            'id' => (int)$userInfo->getId(),
            'name' => $articleAuthorName,
            'url' => $lang === 'vn'
                ? '/tac-gia/' . (int)$userInfo->getId()
                : '/' . $lang . '/author/' . (int)$userInfo->getId()
        );
    }
}
$template->assign('articleAuthor', $articleAuthor);

$articleTagIds = array_filter(array_map('intval', explode(',', (string)$objectInfo->getArticleGroupIds())));
$articleTagObjects = method_exists($articleGroups, 'getActiveObjectsByIds')
    ? $articleGroups->getActiveObjectsByIds($articleTagIds)
    : array();
$articleTags = array();
foreach ($articleTagObjects as $articleTagObject) {
    $tagSlug = trim((string)$articleTagObject->getSlug());
    if ($tagSlug === '') continue;
    $articleTags[] = array(
        'name' => $articleTagObject->getNameByLang($lang),
        'url' => $lang === 'vn'
            ? '/chu-de/' . rawurlencode($tagSlug)
            : '/' . $lang . '/tag/' . rawurlencode($tagSlug)
    );
}
$template->assign('articleTags', $articleTags);

$template->assign('lang',        $lang);
$template->assign('slug',        $slug);
$template->assign('objectInfo',     $objectInfo);
$template->assign('categoryObj', $categoryObj);

// Breadcrumb & topNav
$proName = $objectInfo->getTitle($lang);
$template->assign('proName', $proName);

$menuObject = null;

if ($categoryObj) {
    $menuObject = $menus->getObject($categoryObj->getId(), 'route_id');
}

$template->assign('menuObject', $menuObject);

$topNav = [];

if ($lang == 'vn') {
    $topNav[] = [
        'name' => 'Trang chủ',
        'url'  => '/'
    ];
}elseif($lang == 'zh'){
    $topNav[] = [
        'name' => '首页',
        'url'  => '/zh'
    ];
} else {
    $topNav[] = [
        'name' => 'Home',
        'url'  => '/en'
    ];
}

$menuParent = null;
if ($menuObject && $menuObject->getParentId()) {
    $menuParent = $menus->getObject($menuObject->getParentId());
}

$menuGrandParent = null;
if ($menuParent && $menuParent->getParentId()) {
    $menuGrandParent = $menus->getObject($menuParent->getParentId());
}

if ($menuGrandParent) {
    $topNav[] = [
        'name' => $menuGrandParent->getNameByLang($lang),
        'url'  => $menuGrandParent->getUrlByLang($lang)
    ];
}

if ($menuParent) {
    $topNav[] = [
        'name' => $menuParent->getNameByLang($lang),
        'url'  => $menuParent->getUrlByLang($lang)
    ];
}

if ($menuObject) {
    $topNav[] = [
        'name' => $menuObject->getNameByLang($lang),
        'url'  => $menuObject->getUrlByLang($lang)
    ];
}

// Bài viết
$topNav[] = [
    'name' => $proName,
    'url'  => $objectInfo->getUrl($lang),
];

$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));
   
$childCate = $menus->getObjects(1, "parent_id = 3 AND `status` = '1'", [], 999); 
$template->assign('childCate', $childCate);

// Bài viết nhiều lượt xem nhất
$recentArticles = $articles->getObjects(1, "a.`status` = '1' AND a.`id` != '" . $objectInfo->getId() . "'" . $langCondition, ['a.`viewed`' => 'DESC'], 4);
$template->assign('recentArticles', $recentArticles);

// Dịch vụ phổ biến
// $listPopularServices = $products->getObjects(1,"p.`status` = '1' AND p.properties LIKE '" . buildSerializedLike('custom_is_popular', '1') . "'",['p.`id`' => 'DESC'],4);
// $template->assign('listPopularServices', $listPopularServices);

// Bài viết CaseStudy mới nhất
$recentCaseStudies = $articles->getObjects(1, "a.`status` = '1' AND a.`id` != '" . $objectInfo->getId() . "' AND a.`category_id` IN (71, 77, 78)" . $langCondition, ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 4);
$template->assign('recentCaseStudies', $recentCaseStudies);

// Keep member ratings isolated from legacy product/comment rating data.
$editorialRatingSummary = method_exists($editorialRatings, 'getSummary')
    ? $editorialRatings->getSummary($objectInfo->getId())
    : array('average' => 0, 'count' => 0);
$editorialMemberRating = !empty($_SESSION['store_customerId']) && method_exists($editorialRatings, 'getMemberRating')
    ? $editorialRatings->getMemberRating($objectInfo->getId(), (int)$_SESSION['store_customerId'])
    : 0;
$template->assign('editorialRatingSummary', $editorialRatingSummary);
$template->assign('editorialMemberRating', $editorialMemberRating);

# meta avatar
if ($objectInfo->getAvatarImage($uploads) != null) {
    $avatarObject = $objectInfo->getAvatarImage($uploads);
    $logoimg1 = PROTOCOL . DOMAIN .'/'.$avatarObject->getPath().'/'.$avatarObject->getUrlL();
    $template->assign('logoimg1', $logoimg1);
}

#related articles
$recentArticlesBlock = $articles->getObjects(1, "a.`status` = '1' AND a.id != " . $objectInfo->getId() . " AND a.category_id = " . $objectInfo->getCategoryId() . $langCondition, ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 10);
$template->assign('recentArticlesBlock', $recentArticlesBlock);

//Schema
$descriptionSchema = html_entity_decode($objectInfo->getDescription($lang),ENT_QUOTES | ENT_HTML5,'UTF-8');
$template->assign('descriptionSchema', $descriptionSchema);

// SEO
if ($lang === 'en') {
    $pageTitle       = $objectInfo->getProperty('custom_en_titleSeo')     ?: $objectInfo->getProperty('custom_titleSeo')    ?: $objectInfo->title;
    $pageKeywords    = $objectInfo->getProperty('custom_en_meta_keyword') ?: $objectInfo->getProperty('custom_meta_keyword');
    $pageDescription = $objectInfo->getProperty('custom_en_captionSeo')        ?: $objectInfo->getProperty('custom_captionSeo');
} elseif ($lang === 'zh') {
    $pageTitle       = $objectInfo->getProperty('custom_zh_titleSeo') ?: $objectInfo->getTitle('zh');
    $pageKeywords    = $objectInfo->getProperty('custom_zh_meta_keyword');
    $pageDescription = $objectInfo->getProperty('custom_zh_captionSeo') ?: $objectInfo->getDescription('zh');
} else {
    $pageTitle       = $objectInfo->getProperty('custom_titleSeo') ?: $objectInfo->title;
    $pageKeywords    = $objectInfo->getProperty('custom_meta_keyword');
    $pageDescription = $objectInfo->getProperty('custom_captionSeo');
}

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);
