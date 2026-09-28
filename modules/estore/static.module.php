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

$templateFile = 'static.tpl.html';
// $slugActive = 'tin-tuc';
// $template->assign('slugActive', $slugActive);
$template->assign('static', $static);

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en'])) {
    $lang = 'vn';
}

$slug = $request->element('slug');

$template->assign('lang',        $lang);
$template->assign('slug',        $slug);

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Chính sách bảo mật',    'url' => "/$slug"],
    ],
    'en' => [
        ['name' => 'Home',   'url' => '/en'],
        ['name' => 'Privacy policy', 'url' => "/en/$slug"],
    ],
];

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

// Bài viết gần đây
$recentArticles = $articles->getObjects(1, "a.`status` = '1'", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 4);
$template->assign('recentArticles', $recentArticles);

// Dịch vụ phổ biến
$listPopularServices = $products->getObjects(1,"p.`status` = '1' AND p.properties LIKE '" . buildSerializedLike('custom_is_popular', '1') . "'",['p.`id`' => 'DESC'],4);
$template->assign('listPopularServices', $listPopularServices);

// SEO
if ($lang === 'en') {
    $pageTitle       = $static->getProperty('custom_en_titleSeo')     ?: $static->getProperty('custom_titleSeo')    ?: $static->getTitle($lang);
    $pageKeywords    = $static->getProperty('custom_en_meta_keyword') ?: $static->getProperty('custom_meta_keyword');
    $pageDescription = $static->getProperty('custom_en_intro')        ?: $static->getProperty('custom_intro');
} else {
    $pageTitle       = $static->getProperty('custom_titleSeo') ?: $static->getTitle($lang);
    $pageKeywords    = $static->getProperty('custom_meta_keyword');
    $pageDescription = $static->getProperty('custom_intro');
}

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);