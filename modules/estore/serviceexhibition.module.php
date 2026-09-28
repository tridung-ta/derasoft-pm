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

$templateFile = 'trienlam.tpl.html';
$slug = $request->element('slug');
$template->assign('slug', $slug);
$slugActive = 'dich-vu-cua-mpx';
$template->assign('slugActive', $slugActive);

# Menu languages url
assignLangUrls($template, $menus, 148); // Service Exhibition

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$menuObj = $menus->getObject(148);
$currentNav = [
    'name' => $menuObj->getNameByLang($lang),
    'url'  => $menuObj->getUrlByLang($lang),
];

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Dịch vụ', 'url' => '/dich-vu-cua-mpx'],
        ['name' => 'Mảng triển lãm',    'url' => "/$slug"],
    ],
    'en' => [
        ['name' => 'Home',   'url' => '/en'],
        ['name' => 'Services', 'url' => '/en/services'],
        $currentNav,
    ],
    'zh' => [
        ['name' => '首页', 'url' => '/zh'],
        ['name' => '服务', 'url' => '/zh/services'],
        $currentNav,
    ],
];

# Thiết bị công nghệ sử dụng trong triển lãm
// Menu mảng sân khấu
$idMenu = 148;
$blocks = $pageblocks->getByPage($idMenu);

$listTechnologiesIds = isset($blocks['technologies_used_in_the_exhibition']) ? $blocks['technologies_used_in_the_exhibition']->getObjectIds() : '';

if ($listTechnologiesIds) {
    $listTechnologies = $products->getObjects(1, "p.`status` = '1' AND p.`id` IN ($listTechnologiesIds)", ['p.`id`' => 'DESC'], 99);
}
$template->assign('listTechnologies', $listTechnologies);

# MPX Làm Gì Trong Mảng Triển Lãm?
$mpxDoingBlock = $statics->getObject(113);
$template->assign('mpxDoingBlock', $mpxDoingBlock);

# Các hạng mục triển lãm MPX cung cấp
$mpxProductCategoriesBlock = $statics->getObject(114);
$template->assign('mpxProductCategoriesBlock', $mpxProductCategoriesBlock);

# Năng lực triển khai của MPX trong mảng triển lãm?
$mpxExhibitionsBlock = $statics->getObject(115);
$template->assign('mpxExhibitionsBlock', $mpxExhibitionsBlock);

# Vì sao doanh nghiệp chọn MPX cho dự án triển lãm?
$mpxWhyChooseBlock = $statics->getObject(116);
$template->assign('mpxWhyChooseBlock', $mpxWhyChooseBlock);

# Quy trình triển khai dự án triển lãm
$deploymentProcessBlock = $statics->getObject(117);
$template->assign('deploymentProcessBlock', $deploymentProcessBlock);

# Hình ảnh thực tế dự án triển lãm
$exhibitionImagesBlock = $statics->getObject(118);
$template->assign('exhibitionImagesBlock', $exhibitionImagesBlock);

// Dự án tiêu biểu trong mảng triển lãm
$listIdsFeaturedProject = isset($blocks['featured_exhibition_projects_in_the_exhibition']) ? $blocks['featured_exhibition_projects_in_the_exhibition']->getObjectIds() : '';

if ($listIdsFeaturedProject) {
    $featuredProjects = $articles->getObjects(1, "a.`status` = '1' AND a.`id` IN ($listIdsFeaturedProject)", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 99);
    $template->assign('featuredProjects', $featuredProjects);
}
# Thiết bị công nghệ sử dụng trong triển lãm
// Menu mảng sân khấu
$idMenu = 148;
$blocks = $pageblocks->getByPage($idMenu);

$listTechnologiesIds = isset($blocks['technologies_used_in_the_exhibition']) ? $blocks['technologies_used_in_the_exhibition']->getObjectIds() : '';

if ($listTechnologiesIds) {
    $listTechnologies = $products->getObjects(1, "p.`status` = '1' AND p.`id` IN ($listTechnologiesIds)", ['p.`id`' => 'DESC'], 99);
}
$template->assign('listTechnologies', $listTechnologies);

# Câu hỏi thường gặp
$faqBlock = $statics->getObject(119);
$template->assign('faqBlock', $faqBlock);

# Cần Tư Vấn Giải Pháp Cho Dự Án Triển Lãm Của Bạn?
$needHelpBlock = $statics->getObject(120);
$template->assign('needHelpBlock', $needHelpBlock);

# Banner
$bannerDesktop = $ads->getObject(96);
$template->assign('bannerDesktop', $bannerDesktop);

# Banner Mobile
$bannerMobile = $ads->getObject(103);
$template->assign('bannerMobile', $bannerMobile);

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

$serviceExhibitionMenu = $menus->getObject(148);
$template->assign('serviceExhibitionMenu', $serviceExhibitionMenu);

$childServiceExhibitionMenu = array();
if ($serviceExhibitionMenu) {
    $childServiceExhibitionMenu = $menus->getObjects(
        1,
        "`parent_id` = " . (int)$serviceExhibitionMenu->getId(),
        array('position' => 'ASC'),
        99
    );
}
$template->assign('childServiceExhibitionMenu', $childServiceExhibitionMenu);

if($lang == 'vn') {
    $pageTitle = $serviceExhibitionMenu->getProperty('custom_titleSeo');
    $pageKeywords = $serviceExhibitionMenu->getProperty('custom_meta_keyword');
    $pageDescription = $serviceExhibitionMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $serviceExhibitionMenu->getProperty('custom_en_meta_keyword') ?: $serviceExhibitionMenu->getProperty('custom_meta_keyword');
    $pageDescription = $serviceExhibitionMenu->getProperty('custom_en_intro') ?: $serviceExhibitionMenu->getProperty('custom_intro');
    $pageTitle = $serviceExhibitionMenu->getProperty('custom_en_titleSeo') ?: $serviceExhibitionMenu->getProperty('custom_titleSeo');
}

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);