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

const NEWS_CATEGORY_ID = 73; // Tin tức

$templateFile = 'news.tpl.html';
$slugActive = 'tin-tuc';
$template->assign('slugActive', $slugActive);

assignLangUrls($template, $menus, 8);

# type: dùng chung cho cả dịch vụ và sản phẩm, để phân biệt khi search
$type = 'articles';
$template->assign('type', $type);

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$slug = $request->element('slug');
$categoryObj = $articleCategories->getObject($slug, "slug");

$template->assign('lang',        $lang);
$template->assign('slug',        $slug);
$template->assign('categoryObj', $categoryObj);

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Tin tức',    'url' => "/tin-tuc"],
    ],
    'en' => [
        ['name' => 'Home',   'url' => '/en'],
        ['name' => 'News', 'url' => "/en/news"],
    ],
    'zh' => [
        ['name' => '首页',   'url' => '/zh'],
        ['name' => '新闻', 'url' => "/zh/news"],
    ]
];

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

// Articles list
$result = paginate(
    $request,
    $articles,
    "a.status = 1 AND a.category_id = '$categoryObj->id'",
    "a.status = 1 AND a.category_id = '$categoryObj->id'",
    ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'],
    6
);

$template->assign('items',  $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']);

// Bài viết gần đây
$recentArticles = $articles->getObjects(1, "a.`status` = '1'", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 4);
$template->assign('recentArticles', $recentArticles);

// Dịch vụ phổ biến
$listPopularServices = $products->getObjects(1,"p.`status` = '1' AND p.properties LIKE '" . buildSerializedLike('custom_is_popular', '1') . "'",['p.`id`' => 'DESC'],4);
$template->assign('listPopularServices', $listPopularServices);

# Menu object
$menuObject = $menus->getObject(8);
$template->assign('menuObject', $menuObject);

// SEO
if($lang == 'vn') {
    $pageTitle = $menuObject->getProperty('custom_titleSeo');
    $pageKeywords = $menuObject->getProperty('custom_meta_keyword');
    $pageDescription = $menuObject->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $menuObject->getProperty('custom_en_meta_keyword') ?: $menuObject->getProperty('custom_meta_keyword');
    $pageDescription = $menuObject->getProperty('custom_en_intro') ?: $menuObject->getProperty('custom_intro');
    $pageTitle = $menuObject->getProperty('custom_en_titleSeo') ?: $menuObject->getProperty('custom_titleSeo');
}else if($lang == 'zh') {
    $pageKeywords = $menuObject->getProperty('custom_zh_meta_keyword') ?: $menuObject->getProperty('custom_meta_keyword');
    $pageDescription = $menuObject->getProperty('custom_zh_intro') ?: $menuObject->getProperty('custom_intro');
    $pageTitle = $menuObject->getProperty('custom_zh_titleSeo') ?: $menuObject->getProperty('custom_titleSeo');
}

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);

$childCate = $menus->getObjects(1, "parent_id = 3 AND `status` = '1'", [], 999); 
$template->assign('childCate', $childCate);