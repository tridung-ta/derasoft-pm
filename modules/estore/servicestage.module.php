<?php
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

$templateFile = 'sankhau.tpl.html';
$slug = $request->element('slug');
$template->assign('slug', $slug);
$slugActive = 'dich-vu-cua-mpx';
$template->assign('slugActive', $slugActive);

# Menu languages url
assignLangUrls($template, $menus, 152); // Service Stage    

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$menuObj = $menus->getObject(152);
$currentNav = [
    'name' => $menuObj->getNameByLang($lang),
    'url'  => $menuObj->getUrlByLang($lang),
];

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Dịch vụ', 'url' => '/dich-vu-cua-mpx'],
        ['name' => 'Mảng Sân Khấu',    'url' => "/$slug"],
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

#Block MPX Làm Gì Trong Mảng Sân Khấu?
$mpxDoingBlock = $statics->getObject(104);
$template->assign('mpxDoingBlock', $mpxDoingBlock);

#Các hạng mục triển lãm MPX cung cấp
$mpxProductCategoriesBlock = $statics->getObject(105);
$template->assign('mpxProductCategoriesBlock', $mpxProductCategoriesBlock); 

#Năng Lực Triển Khai Của MPX Trong Mảng Sân Khấu
$mpxExhibitionsBlock = $statics->getObject(106);
$template->assign('mpxExhibitionsBlock', $mpxExhibitionsBlock);

#Vì sao doanh nghiệp chọn MPX cho dự án triển lãm?
$mpxWhyChooseBlock = $statics->getObject(108);
$template->assign('mpxWhyChooseBlock', $mpxWhyChooseBlock);

#Quy trình triển khai dự án sân khấu
$deploymentProcessBlock = $statics->getObject(109);
$template->assign('deploymentProcessBlock', $deploymentProcessBlock);

#Hình ảnh thực tế dự án triển lãm
$exhibitionProjectsBlock = $statics->getObject(110);
$template->assign('exhibitionProjectsBlock', $exhibitionProjectsBlock);

#Câu hỏi thường gặp
$faqBlock = $statics->getObject(111);
$template->assign('faqBlock', $faqBlock);

#Cần Tư Vấn Giải Pháp Cho Dự Án Sân Khấu Của Bạn?
$contactBlock = $statics->getObject(112);
$template->assign('contactBlock', $contactBlock);

# Thiết bị công nghệ sử dụng trong sân khấu 
// Menu mảng sân khấu
$idMenu = 152;
$blocks = $pageblocks->getByPage($idMenu);

$listTechnologiesIds = isset($blocks['technologies_used_in_the_stage']) ? $blocks['technologies_used_in_the_stage']->getObjectIds() : '';

if ($listTechnologiesIds) {
    $listTechnologies = $products->getObjects(1, "p.`status` = '1' AND p.`id` IN ($listTechnologiesIds)", ['p.`id`' => 'DESC'], 99);
}
$template->assign('listTechnologies', $listTechnologies);

// Dự án tiêu biểu trong mảng sân khấu 
$listIdsFeaturedProject = isset($blocks['featured_exhibition_projects_in_the_stage']) ? $blocks['featured_exhibition_projects_in_the_stage']->getObjectIds() : '';

if ($listIdsFeaturedProject) {
    $featuredProjects = $articles->getObjects(1, "a.`status` = '1' AND a.`id` IN ($listIdsFeaturedProject)", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 99);
    $template->assign('featuredProjects', $featuredProjects);
}

# Banner
$bannerDesktop = $ads->getObject(95);
$template->assign('bannerDesktop', $bannerDesktop);

# Banner Mobile
$bannerMobile = $ads->getObject(104);
$template->assign('bannerMobile', $bannerMobile);   

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

$serviceStageMenu = $menus->getObject(152);
$template->assign('serviceStageMenu', $serviceStageMenu);

$childServiceStageMenu = array();
if ($serviceStageMenu) {
    $childServiceStageMenu = $menus->getObjects(
        1,
        "`parent_id` = " . (int)$serviceStageMenu->getId(),
        array('position' => 'ASC'),
        99
    );
}
$template->assign('childServiceStageMenu', $childServiceStageMenu);

if($lang == 'vn') {
    $pageTitle = $serviceStageMenu->getProperty('custom_titleSeo');
    $pageKeywords = $serviceStageMenu->getProperty('custom_meta_keyword');
    $pageDescription = $serviceStageMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $serviceStageMenu->getProperty('custom_en_meta_keyword') ?: $serviceStageMenu->getProperty('custom_meta_keyword');
    $pageDescription = $serviceStageMenu->getProperty('custom_en_intro') ?: $serviceStageMenu->getProperty('custom_intro');
    $pageTitle = $serviceStageMenu->getProperty('custom_en_titleSeo') ?: $serviceStageMenu->getProperty('custom_titleSeo');
}

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);