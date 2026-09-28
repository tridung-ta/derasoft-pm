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

$templateFile = 'solution.tpl.html';
$slugActive = 'giai-phap';
$template->assign('slugActive', $slugActive);

# type: dùng chung cho cả dịch vụ và sản phẩm, để phân biệt khi search
$type = 'articles';
$template->assign('type', $type);

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en'])) {
    $lang = 'vn';
}

$slug = $request->element('slug');
if ($slug == 'giai-phap') {
    $categoryObj = $articleCategories->getObject(69);
} else {
    $categoryObj = $articleCategories->getObject($slug, "slug");
}
$solutionCategoryId = $categoryObj->id;

$template->assign('lang',        $lang);
$template->assign('slug',        $slug);
$template->assign('categoryObj', $categoryObj);

// Breadcrumb & topNav
$catName = $categoryObj->getName($lang);
$template->assign('catName', $catName);

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => $catName,    'url' => "/$slug"],
    ],
    'en' => [
        ['name' => 'Home',   'url' => '/en'],
        ['name' => $catName, 'url' => "/en/$slug"],
    ],
];

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

// Articles list
$result = paginate(
    $request,
    $articles,
    "a.status = 1 AND a.category_id = '$solutionCategoryId'",
    "a.status = 1 AND a.category_id = '$solutionCategoryId'",
    ['a.id' => 'DESC'],
    6
);

$template->assign('items',  $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']);

// Sub-categories
// $cateSubProduct = CATEGORY_PRODUCT_PARENT_ID; // Sản phẩm
// $subCategories = $productCategories->getObjects(1,"FIND_IN_SET($cateSubProduct, list_parent_id)",[],999);
// $template->assign('subCategories', $subCategories); 

// Bài viết gần đây
$recentArticles = $articles->getObjects(1, "a.`status` = '1'", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 4);
$template->assign('recentArticles', $recentArticles);

// Dịch vụ phổ biến
$listPopularServices = $products->getObjects(1,"p.`status` = '1' AND p.properties LIKE '" . buildSerializedLike('custom_is_popular', '1') . "'",['p.`id`' => 'DESC'],4);
$template->assign('listPopularServices', $listPopularServices);

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