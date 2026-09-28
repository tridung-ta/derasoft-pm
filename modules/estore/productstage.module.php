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
include_once(ROOT_PATH . 'classes/dao/pageblocks.class.php');
include_once(ROOT_PATH . 'classes/dao/ads.class.php');

$uploadAlbums      = new UploadAlbums($storeId);
$uploads           = new Uploads($storeId);
$template->assign('uploads', $uploads);
$statics           = new StaticPage($storeId);
$products          = new Products($storeId);
$articles          = new Articles($storeId);
$menus             = new Menus($storeId);
$productCategories = new ProductCategories($storeId);
$pageblocks        = new PageBlocks($storeId);
$ads               = new Ads($storeId);

$templateFile = 'productstage.tpl.html';
$slug = $request->element('slug');
$template->assign('slug', $slug);
$slugActive = 'san-pham-cua-mpx';
$template->assign('slugActive', $slugActive);

# Menu languages url
assignLangUrls($template, $menus, 162); // Product Stage

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$menuObj = $menus->getObject(162);
$currentNav = [
    'name' => $menuObj->getNameByLang($lang),
    'url'  => $menuObj->getUrlByLang($lang),
];

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Sản phẩm', 'url' => '/san-pham-cua-mpx'],
        ['name' => 'Sân khấu',    'url' => "/$slug"],
    ],
    'en' => [
        ['name' => 'Home',   'url' => '/en'],
        ['name' => 'Products', 'url' => '/en/products'],
        $currentNav,
    ],
    'zh' => [
        ['name' => '首页', 'url' => '/zh'],
        ['name' => '产品', 'url' => '/zh/products'],
        $currentNav,
    ],
];

# MPX cung cấp những giải pháp triễn lãm nào?
$mpx_exhibition_solutions = $statics->getObject(129);
$template->assign('mpx_exhibition_solutions', $mpx_exhibition_solutions);


// Menu sản phẩm->sân khấu
$idMenu = 162;
$blocks = $pageblocks->getByPage($idMenu);

# Danh mục thiết bị sân khấu
$stage_equipment_category = $statics->getObject(133);
$template->assign('stage_equipment_category', $stage_equipment_category);

# Ứng dụng thực tế
$real_world_applications = $statics->getObject(134);
$template->assign('real_world_applications', $real_world_applications);

# Thiết bị nổi bật
$featuredProductIds = isset($blocks['featured_equipment']) ? $blocks['featured_equipment']->getObjectIds() : '';
if ($featuredProductIds) {
    $listFeaturedProduct = $products->getObjects(1, "p.`status` = '1' AND p.`id` IN ($featuredProductIds)", ['p.`id`' => 'DESC'], 99);
}
$template->assign('listFeaturedProduct', $listFeaturedProduct);

# Case Study tiêu biểu
$featuredCaseStudiesId = isset($blocks['featured_case_studies']) ? $blocks['featured_case_studies']->getObjectIds() : '';
if ($featuredCaseStudiesId) {
    $listFeaturedCaseStudies = $articles->getObjects(1, "a.`status` = '1' AND a.`id` IN ($featuredCaseStudiesId)", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 99);
}
$template->assign('listFeaturedCaseStudies', $listFeaturedCaseStudies);

# Cần tư vấn thiết kế gian hàng sân khấu?
$needHelpBlock = $statics->getObject(135);
$template->assign('needHelpBlock', $needHelpBlock);

# Banner Desktop
$bannerDesktop = $ads->getObject(100);
$template->assign('bannerDesktop', $bannerDesktop);

# Banner Mobile
$bannerMobile = $ads->getObject(101);
$template->assign('bannerMobile', $bannerMobile);

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

$productStageMenu = $menus->getObject(162);
$template->assign('productStageMenu', $productStageMenu);

$childStageMenu = array();
if ($productStageMenu) {
    $childStageMenu = $menus->getObjects(
        1,
        "`parent_id` = " . (int)$productStageMenu->getId(),
        array('position' => 'ASC'),
        99
    );
}
$template->assign('childStageMenu', $childStageMenu);

if($lang == 'vn') {
    $pageTitle = $productStageMenu->getProperty('custom_titleSeo');
    $pageKeywords = $productStageMenu->getProperty('custom_meta_keyword');
    $pageDescription = $productStageMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $productStageMenu->getProperty('custom_en_meta_keyword') ?: $productStageMenu->getProperty('custom_meta_keyword');
    $pageDescription = $productStageMenu->getProperty('custom_en_intro') ?: $productStageMenu->getProperty('custom_intro');
    $pageTitle = $productStageMenu->getProperty('custom_en_titleSeo') ?: $productStageMenu->getProperty('custom_titleSeo');
}

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);