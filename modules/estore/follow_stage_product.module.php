<?php

include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/menus.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');

$uploadAlbums      = new UploadAlbums($storeId);
$uploads           = new Uploads($storeId);
$template->assign('uploads', $uploads);
$statics           = new StaticPage($storeId);
$products          = new Products($storeId);
$articles          = new Articles($storeId);
$menus             = new Menus($storeId);
$productCategories = new ProductCategories($storeId);
$articleCategories = new ArticleCategories($storeId);

$templateFile = 'follow_stage_product.tpl.html';
$slugActive = 'san-pham-cua-mpx';
$template->assign('slugActive', $slugActive);

# type: dùng chung cho cả dịch vụ và sản phẩm, để phân biệt khi search
$type = 'products';
$template->assign('type', $type);

$isFollowStageProduct = 1;
$template->assign('isFollowStageProduct', $isFollowStageProduct);

$isProductCate = true;
$template->assign('isProductCate', $isProductCate);


$route = resolveCurrentRoute(
    $request,
    $menus,
    $articleCategories,
    $productCategories
);

if (!$route || $route['routeType'] != 'product_category') {
    $templateFile = '404.tpl.html';
    return;
}

$lang = $route['lang'];
$slug = $route['slug'];

// Guard: slug rỗng -> 404 luôn, khỏi query DB
if (empty($slug)) {
    $templateFile = '404.tpl.html';
    return;
}

$menuObj = $route['menu'];
$categoryObj = $route['productCategory'];

assignLangUrls($template, $menus, $menuObj->getId());

if (!$categoryObj) {
    $templateFile = '404.tpl.html';
    return;
}

$template->assign('menuObj', $menuObj);
$template->assign('categoryObj', $categoryObj);
$template->assign('lang', $lang);
$template->assign('slug', $slug);

$menuName = $lang == 'en'
    ? ($menuObj->getProperty('custom_en_name') ?: $menuObj->name)
    : $menuObj->name;

$template->assign('menuName', $menuName);

$currentNav = [
    'name' => $menuObj->getNameByLang($lang),
    'url'  => $menuObj->getUrlByLang($lang),
];

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Sản phẩm', 'url' => '/san-pham-cua-mpx'],
        ['name' => 'Sân khấu',    'url' => "/thiet-bi-san-khau"],
        $currentNav,
    ],

    'en' => [
        ['name' => 'Home', 'url' => '/en'],
        ['name' => 'Products', 'url' => '/en/products'],
        ['name' => 'Stage Equipment', 'url' => '/en/stage-equipment'],
        $currentNav,
    ],

    'zh' => [
        ['name' => '首页', 'url' => '/zh'],
        ['name' => '产品', 'url' => '/zh/products'],
        ['name' => '展览产品', 'url' => '/zh/stage-equipment'],
        $currentNav,
    ],
];

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# H1 
$h1 = $categoryObj->getH1($lang);
$template->assign('h1', $h1);

# Description
$description = $categoryObj->getDescription($lang);
$template->assign('description', $description);

// Products list
$result = paginate(
    $request,
    $products,
    "status = 1 AND category_id = $categoryObj->id",
    "p.status = 1 AND p.category_id = $categoryObj->id",
    ['p.id' => 'DESC'],
    6
);

$template->assign('items',  $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']);

# Get list of child stage categories
$childCate = $menus->getObjects(1, "parent_id = 162 AND `status` = '1'", [], 999);
$template->assign('childCate', $childCate);

# Menu object
$menuObject = $menus->getObject('/'.$slug, 'url');
$template->assign('menuObject', $menuObject);

// SEO
if ($lang === 'en') {
    $pageTitle       = $categoryObj->getProperty('custom_en_titleSeo')     ?: $categoryObj->getProperty('custom_titleSeo')    ?: $categoryObj->name;
    $pageKeywords    = $categoryObj->getProperty('custom_en_meta_keyword') ?: $categoryObj->getProperty('custom_meta_keyword');
    $pageDescription = $categoryObj->getProperty('custom_en_intro')        ?: $categoryObj->getProperty('custom_intro');
} else {    
    $pageTitle       = $categoryObj->getProperty('custom_titleSeo') ?: $categoryObj->name;
    $pageKeywords    = $categoryObj->getProperty('custom_meta_keyword');
    $pageDescription = $categoryObj->getProperty('custom_intro');
}

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);