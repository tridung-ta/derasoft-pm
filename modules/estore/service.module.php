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

$templateFile = 'service.tpl.html';
$slugActive = 'dich-vu-cua-mpx';
$template->assign('slugActive', $slugActive);

$isProductCate = false;
$template->assign('isProductCate', $isProductCate);

$cateParent = $menus->getObject(3);
$template->assign('nameCateParent', $cateParent->getName($lang));


# type: dùng chung cho cả dịch vụ và sản phẩm, để phân biệt khi search
$type = 'products';
$template->assign('type', $type);

# Menu languages url
assignLangUrls($template, $menus, 3); // Service

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$slug = $request->element('slug');
if ($slug == 'dich-vu-cua-mpx') {
    $categoryObj = $productCategories->getObject(143);
} else {
    $categoryObj = $productCategories->getObject($slug, "slug");
}

$serviceCategoryId = $categoryObj->id;

$template->assign('lang',        $lang);
$template->assign('slug',        $slug);
$template->assign('categoryObj', $categoryObj);

// Breadcrumb & topNav
$catName = $categoryObj->getName($lang);
$template->assign('catName', $catName);

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Dịch vụ'  ,  'url' => "/$slug"],
    ],
    'en' => [
        ['name' => 'Home',   'url' => '/en'],
        ['name' => 'Services', 'url' => "/en/services"],
    ],
    'zh' => [
        ['name' => '首页',         'url' => '/zh'],
        ['name' => '服务',         'url' => "/zh/services"],
    ],
];

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));


// Dự án tiêu biểu trong mảng triển lãm 
// Menu dịch vụ 
$idMenu = 3;
$blocks = $pageblocks->getByPage($idMenu);

$listIdsFeaturedProject = isset($blocks['featured_exhibition_projects']) ? $blocks['featured_exhibition_projects']->getObjectIds() : '';

if ($listIdsFeaturedProject) {
    $featuredProjects = $articles->getObjects(1, "a.`status` = '1' AND a.`id` IN ($listIdsFeaturedProject)", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 99);
    $template->assign('featuredProjects', $featuredProjects);
}

#Block dịch vụ MPX
$serviceMPXblock = $statics->getObject(97);
$template->assign('serviceMPXblock', $serviceMPXblock);

#Block Năng lực triển khai của MPX
$mpxDeploymentCapabilitiesBlock = $statics->getObject(98);
$template->assign('mpxDeploymentCapabilitiesBlock', $mpxDeploymentCapabilitiesBlock);

#Các hạng mục MPX cung cấp
$mpxProductCategoriesBlock = $statics->getObject(99);
$template->assign('mpxProductCategoriesBlock', $mpxProductCategoriesBlock);

#Quy trình triển khai dự án
$deploymentProcessBlock = $statics->getObject(100);
$template->assign('deploymentProcessBlock', $deploymentProcessBlock);

#Hình Ảnh Thực Tế Dự Án Sân Khấu
$galleryBlock = $statics->getObject(101);
$template->assign('galleryBlock', $galleryBlock);

#Block Câu hỏi thường gặp 
$faqBlock = $statics->getObject(102);
$template->assign('faqBlock', $faqBlock);

#Cần Tư Vấn Giải Pháp Cho Dự Án Sân Khấu Của Bạn?
$contactBlock = $statics->getObject(103);
$template->assign('contactBlock', $contactBlock);

// Thiết bị và công nghệ sử dụng
$listIdsTechnology = isset($blocks['technologies_used']) ? $blocks['technologies_used']->getObjectIds() : '';
if ($listIdsTechnology){
    $technologies = $products->getObjects(1,"p.`status` = '1' AND p.`id` IN ($listIdsTechnology)",['p.`id`' => 'DESC'],99);
    $template->assign('technologies', $technologies);
}

# Banner
$bannerDesktop = $ads->getObject(94);
$template->assign('bannerDesktop', $bannerDesktop);

# Banner Mobile
$bannerMobile = $ads->getObject(102);
$template->assign('bannerMobile', $bannerMobile);

$serviceMenu = $menus->getObject(3);
$template->assign('serviceMenu', $serviceMenu);

$childServiceMenu = array();
if ($serviceMenu) {
    $childServiceMenu = $menus->getObjects(
        1,
        "`parent_id` = " . (int)$serviceMenu->getId(),
        array('position' => 'ASC'),
        99
    );
}
$template->assign('childServiceMenu', $childServiceMenu);


if($lang == 'vn') {
    $pageTitle = $serviceMenu->getProperty('custom_titleSeo');
    $pageKeywords = $serviceMenu->getProperty('custom_meta_keyword');
    $pageDescription = $serviceMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $serviceMenu->getProperty('custom_en_meta_keyword') ?: $serviceMenu->getProperty('custom_meta_keyword');
    $pageDescription = $serviceMenu->getProperty('custom_en_intro') ?: $serviceMenu->getProperty('custom_intro');
    $pageTitle = $serviceMenu->getProperty('custom_en_titleSeo') ?: $serviceMenu->getProperty('custom_titleSeo');
}
$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);