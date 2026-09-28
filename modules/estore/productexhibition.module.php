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

$templateFile = 'productexhibition.tpl.html';
$slug = $request->element('slug');
$template->assign('slug', $slug);
$slugActive = 'san-pham-cua-mpx';
$template->assign('slugActive', $slugActive);

# Menu languages url
assignLangUrls($template, $menus, 163); 

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$menuObj = $menus->getObject(163);
$currentNav = [
    'name' => $menuObj->getNameByLang($lang),
    'url'  => $menuObj->getUrlByLang($lang),
];

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Sản phẩm', 'url' => '/san-pham-cua-mpx'],
        ['name' => 'Triển lãm',    'url' => "/$slug"],
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

# Mẫu gian hàng triễn lam tiêu biểu
$listExhibitionSolutions = $products->getObjects(1, "p.`status` = '1' AND p.`category_id` = 186 ", ['p.`id`' => 'DESC'], 4);
$template->assign('listExhibitionSolutions', $listExhibitionSolutions);

# Phụ kiện gian hàng triển lãm
$exhibitionAccessories = $products->getObjects(1, "p.`status` = '1' AND p.`category_id` = 187 ", ['p.`id`' => 'DESC'], 4);
$template->assign('exhibitionAccessories', $exhibitionAccessories);

# GIẢI PHÁP HỘP ĐÈN QUẢNG CÁO
$solutionLightBox = $products->getObjects(1, "p.`status` = '1' AND p.`category_id` = 185 ", ['p.`id`' => 'DESC'], 4);
$template->assign('solutionLightBox', $solutionLightBox);

# Các dự án triển lãm tiêu biểu
$idMenu = 163;
$blocks = $pageblocks->getByPage($idMenu);
$featuredProjectIds = isset($blocks['featured_exhibition_projects']) ? $blocks['featured_exhibition_projects']->getObjectIds() : '';
if ($featuredProjectIds) {
    $listFeaturedProject = $articles->getObjects(1, "a.`status` = '1' AND a.`id` IN ($featuredProjectIds)", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 99);
}
$template->assign('listFeaturedProject', $listFeaturedProject);

# Cần tư vấn thiết kế gian hàng triển lãm?
$needHelpBlock = $statics->getObject(132);
$template->assign('needHelpBlock', $needHelpBlock);

# Banner Desktop
$bannerDesktop = $ads->getObject(98);
$template->assign('bannerDesktop', $bannerDesktop);

# Banner Mobile
$bannerMobile = $ads->getObject(99);
$template->assign('bannerMobile', $bannerMobile);

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

$productExhibitionMenu = $menus->getObject(163);
$template->assign('productExhibitionMenu', $productExhibitionMenu);

$childProductExhibitionMenu = array();
if ($productExhibitionMenu) {
    $childProductExhibitionMenu = $menus->getObjects(
        1,
        "`parent_id` = " . (int)$productExhibitionMenu->getId(),
        array('position' => 'ASC'),
        99
    );
}
$template->assign('childProductExhibitionMenu', $childProductExhibitionMenu);

# Menu Case study - Mảng triển lãm
$menuCaseStudy = $menus->getObject(160);
$template->assign('menuCaseStudy', $menuCaseStudy);

if($lang == 'vn') {
    $pageTitle = $productExhibitionMenu->getProperty('custom_titleSeo');
    $pageKeywords = $productExhibitionMenu->getProperty('custom_meta_keyword');
    $pageDescription = $productExhibitionMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $productExhibitionMenu->getProperty('custom_en_meta_keyword') ?: $productExhibitionMenu->getProperty('custom_meta_keyword');
    $pageDescription = $productExhibitionMenu->getProperty('custom_en_intro') ?: $productExhibitionMenu->getProperty('custom_intro');
    $pageTitle = $productExhibitionMenu->getProperty('custom_en_titleSeo') ?: $productExhibitionMenu->getProperty('custom_titleSeo');
}

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);