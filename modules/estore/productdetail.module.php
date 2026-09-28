<?php
    //   ini_set('display_errors', 1);
    //     ini_set('display_startup_errors', 1);
    //     error_reporting(E_ALL);
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/menus.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/comments.class.php');

$uploadAlbums      = new UploadAlbums($storeId);
$uploads           = new Uploads($storeId);
$template->assign('uploads', $uploads);
$statics           = new StaticPage($storeId);
$products          = new Products($storeId);
$articles          = new Articles($storeId);
$menus             = new Menus($storeId);
$productCategories = new ProductCategories($storeId);
$comments          = new Comments($storeId);

$templateFile = 'productdetail.tpl.html';

$slug = $request->element('slug');
// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

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

$objectInfo = $products->getObject($slug, $slugField);
$template->assign('objectInfo', $objectInfo);

assignLangUrls($template, $products, $objectInfo->id, 'product');
if ($lang == 'en') {
    if (!$objectInfo->hasLang('en') || empty($objectInfo->getSlugEn())) {
        $templateFile = '404.tpl.html';
    }
} elseif ($lang == 'zh') {
    if (!$objectInfo->hasLang('zh') ||  empty($objectInfo->getSlugZh())) {
        $templateFile = '404.tpl.html';
    }
}

$categoryObj = $productCategories->getObject($objectInfo->category_id);

# Increase viewed

$productId = $objectInfo->getId();

if (!isset($_SESSION['viewed_products'])) {
    $_SESSION['viewed_products'] = [];
}

if (!in_array($productId, $_SESSION['viewed_products'])) {
    $products->increaseViewed($productId);
    $_SESSION['viewed_products'][] = $productId;
}

$arrayServiceChildCategories = getListCategoryId($productCategories, CATEGORY_SERVICE_PARENT_ID);
$arrayProductChildCategories = getListCategoryId($productCategories, CATEGORY_PRODUCT_PARENT_ID);
if (in_array($categoryObj->getId(), $arrayServiceChildCategories)) {
    $slugActive = 'dich-vu-cua-mpx';
    $cateParent = $menus->getObject(3);
} else if (in_array($categoryObj->getId(), $arrayProductChildCategories)) {
    $cateParent = $menus->getObject(5);
    $slugActive = 'san-pham-cua-mpx';
}
$template->assign('slugActive', $slugActive);
$template->assign('nameCateParent', $cateParent->getName($lang));
    
$isProduct = true;
$template->assign('isProduct', $isProduct);

$isDetail = true;
$template->assign('isDetail', $isDetail);


$type = 'products';
$template->assign('type', $type);

$template->assign('lang',        $lang);
$template->assign('slug',        $slug);
$template->assign('objectInfo',     $objectInfo);
$template->assign('categoryObj', $categoryObj);
$template->assign('comments',    $comments);

// Breadcrumb & topNav
$proName = $objectInfo->getName($lang);
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
   
// Bài viết gần đây
$recentArticles = $articles->getObjects(1, "p.`status` = '1'", ['p.`id`' => 'DESC'], 4);
$template->assign('recentArticles', $recentArticles);

// Dịch vụ phổ biến
$listPopularServices = $products->getObjects(1,"p.`status` = '1' AND p.properties LIKE '" . buildSerializedLike('custom_is_popular', '1') . "'",['p.`id`' => 'DESC'],4);
$template->assign('listPopularServices', $listPopularServices);

// Thống kê đánh giá
$totalCount = $objectInfo->getCommentCount();
$starStats = [];

for ($star = 1; $star <= 5; $star++) {
    $count = $comments->countCommentsByStar($objectInfo->getId(), $star, 'products');
    $starStats[$star] = [
        'count'   => $count,
        'percent' => $totalCount > 0 ? round(($count / $totalCount) * 100) : 0,
    ];
}

$template->assign('starStats', $starStats);
$template->assign('totalCount', $totalCount);

// Danh sách bình luận
$items_per_page = 6;
$result = paginate(
    $request,
    $comments,
    "status = 1 AND pid = " . $objectInfo->getId(),
    "status = 1 AND pid = " . $objectInfo->getId(),
    ['id' => 'DESC'],
    $items_per_page
);

# meta avatar
$avatarObject = $objectInfo->getAvatarImage($uploads);
if ($avatarObject) {
    $logoimg1 = PROTOCOL . DOMAIN .'/'.$avatarObject->getPath().'/'.$avatarObject->getUrlL();
    $template->assign('logoimg1', $logoimg1);
}

$template->assign('items',  $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']); 
$template->assign('itemsPerPage', $items_per_page);   

// SEO
if ($lang === 'en') {
    $pageTitle       = $objectInfo->getProperty('custom_en_titleSeo')     ?: $objectInfo->getProperty('custom_titleSeo')    ?: $objectInfo->name;
    $pageKeywords    = $objectInfo->getProperty('custom_en_meta_keyword') ?: $objectInfo->getProperty('custom_meta_keyword');
    $pageDescription = $objectInfo->getProperty('custom_en_captionSeo')        ?: $objectInfo->getProperty('custom_captionSeo');
} else {
    $pageTitle       = $objectInfo->getProperty('custom_titleSeo') ?: $objectInfo->name;
    $pageKeywords    = $objectInfo->getProperty('custom_meta_keyword');
    $pageDescription = $objectInfo->getProperty('custom_captionSeo');
}

# schema
$descriptionSchema = html_entity_decode($objectInfo->getDescription($lang),ENT_QUOTES | ENT_HTML5,'UTF-8');
$template->assign('descriptionSchema', $descriptionSchema);

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);


