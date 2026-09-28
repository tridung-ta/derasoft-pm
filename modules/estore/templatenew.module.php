<?php 

$slug = $request->element('slug');
// if($slug == 'template-new1') {
//     $templateFile = "template-new1.tpl.html";
// }else if($slug == 'template-new2') {
//     $templateFile = "template-new2.tpl.html";
// }else {
//     $templateFile = "template-new.tpl.html";
// }

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/menus.class.php');

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$statics = new StaticPage($storeId);
$products = new Products($storeId);
$articles = new Articles($storeId);
$menus = new Menus($storeId);

$templateFile = 'template-new1.tpl.html';
$slugActive = 'gioi-thieu';
$template->assign('slugActive', $slugActive);

$slug = $request->element('slug');
$template->assign('slug', $slug);

$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ',   'url' => '/'],
        ['name' => 'Giới thiệu',  'url' => '/gioi-thieu'],
    ],
    'en' => [
        ['name' => 'Home',        'url' => '/en'],
        ['name' => 'About Us',    'url' => '/en/introduction'],
    ],
    'zh' => [
        ['name' => '首页',         'url' => '/zh'],
        ['name' => '关于我们',     'url' => '/zh/introduction'],
    ],
];

$topNav = $navConfig[$lang];

$template->assign('lang', $lang);
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# Menu languages url
assignLangUrls($template, $menus, 2); // Introduction

# Danh sách sản phẩm, dịch vụ set_about_page = 1
$listProductsAboutPage = $products->getObjects(1, "p.`status` = '1' AND p.`set_about_page` = '1'", array('p.`position`' => 'ASC'), 6);
$template->assign('listProductsAboutPage', $listProductsAboutPage);

# Block Chào Mừng Đến Với MPX
$introBlock = $statics->getObject(95);
if ($introBlock) {
    $template->assign('intro', $introBlock);
}

$introMenu = $menus->getObject(2);
$template->assign('introMenu', $introMenu);
if($lang == 'vn') {
    $pageTitle = $introMenu->getProperty('custom_titleSeo');
    $pageKeywords = $introMenu->getProperty('custom_meta_keyword');
    $pageDescription = $introMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $introMenu->getProperty('custom_en_meta_keyword') ?: $introMenu->getProperty('custom_meta_keyword');
    $pageDescription = $introMenu->getProperty('custom_en_intro') ?: $introMenu->getProperty('custom_intro');
    $pageTitle = $introMenu->getProperty('custom_en_titleSeo') ?: $introMenu->getProperty('custom_titleSeo');
}

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);


?>

