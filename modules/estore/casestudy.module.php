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
$template->assign('menus', $menus);
$productCategories = new ProductCategories($storeId);
$articleCategories = new ArticleCategories($storeId);

$templateFile = 'casestudy.tpl.html';
$slugActive = 'case-study-cua-mpx';
$template->assign('slugActive', $slugActive);

# type: dùng chung cho cả dịch vụ và sản phẩm, để phân biệt khi search
$type = 'articles';
$template->assign('type', $type);

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

# Get category of case study
$childOfCategoryCaseStudy = $articleCategories->getObjects(1, "c.parent_id = 71 AND c.`status` = '1'", [], 999);

$slug = $request->element('slug');
if ($slug == 'case-study-cua-mpx') {
    $navConfig = [
        'vn' => [
            ['name' => 'Trang chủ', 'url' => '/'],
            ['name' => 'Case Study',    'url' => "/case-study-cua-mpx"],
        ],
        'en' => [
            ['name' => 'Home',   'url' => '/en'],
            ['name' => 'Case Study', 'url' => "/en/case-study"],
        ],
        'zh' => [
            ['name' => '首页', 'url' => '/zh'],
            ['name' => '案例', 'url' => "/zh/case-study"],
        ],
    ];
    # Menu languages url
    assignLangUrls($template, $menus, 6); // Case study
    
    $categoryObj = $articleCategories->getObject(71);

    $categoryIds = [];
    foreach ($childOfCategoryCaseStudy as $cat) {
        $categoryIds[] = (int)$cat->id;
    }
    $categoryIds[] = 71;
    if (empty($categoryIds)) {
        $categoryIds[] = 0;
    }
    $categoryIdsStr = implode(',', $categoryIds);
    $condition = "a.status = 1 AND a.category_id IN ($categoryIdsStr)";

    $menuObject = $menus->getObject(6);

} else {
    $route = resolveCurrentRoute(
        $request,
        $menus,
        $articleCategories,
        $productCategories
    );

    if (!$route || $route['routeType'] != 'article_category') {
        $templateFile = '404.tpl.html';
        return;
    }
    
    $lang = $route['lang'];
    $slug = $route['slug'];
    
    $menuObject = $route['menu'];
    $categoryObj = $route['articleCategory'];
    $currentNav = [
        'name' => $menuObject->getNameByLang($lang),
        'url'  => $menuObject->getUrlByLang($lang),
    ];
    
    $navConfig = [
        'vn' => [
            ['name' => 'Trang chủ', 'url' => '/'],
            ['name' => 'Case Study', 'url' => '/case-study-cua-mpx'],
            $currentNav,
        ],
        'en' => [
            ['name' => 'Home',   'url' => '/en'],
            ['name' => 'Case Study', 'url' => '/en/case-study'],
            $currentNav,
        ],
        'zh' => [
            ['name' => '首页', 'url' => '/zh'],
            ['name' => '案例', 'url' => '/zh/case-study'],
            $currentNav,
        ],
    ];
    assignLangUrls($template, $menus, $menuObject->getId()); // Case study

    $condition = "a.status = 1 AND a.category_id = $categoryObj->id";
}
    
$template->assign('menuObject', $menuObject);
$template->assign('lang',        $lang);
$template->assign('slug',        $slug);
$template->assign('categoryObj', $categoryObj);

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# H1 
$h1 = $categoryObj->getH1($lang);
$template->assign('h1', $h1);

# Description
$description = $categoryObj->getDescription($lang);
$template->assign('description', $description);

# Get list of child case study categories
$childOfCategory = $articleCategories->getObjects(1, "c.parent_id = $categoryObj->id AND c.parent_id != 0 AND c.`status` = '1'", [], 999);
$template->assign('childOfCategory', $childOfCategory);

// Articles list
$result = paginate(
    $request,
    $articles,
    $condition,
    $condition,
    ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'],
    6
);

$template->assign('items',  $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']);

# Danh mục dịch vụ sidebar 
$childCate = $menus->getObjects(1, "parent_id = 3 AND `status` = '1'", [], 999); 
$template->assign('childCate', $childCate);

# Dự án gấn đây
$recentProjects = $articles->getObjects(1, "a.`status` = '1'", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 4);
$template->assign('recentProjects', $recentProjects);   

// SEO
if ($lang === 'en') {
    $pageTitle       = $categoryObj->getProperty('custom_en_titleSeo')     ?: $categoryObj->getProperty('custom_titleSeo')    ?: $categoryObj->name;
    $pageKeywords    = $categoryObj->getProperty('custom_en_meta_keyword') ?: $categoryObj->getProperty('custom_meta_keyword');
    $pageDescription = $categoryObj->getProperty('custom_en_intro')        ?: $categoryObj->getProperty('custom_intro');
}else if ($lang === 'zh') {
    $pageTitle       = $categoryObj->getProperty('custom_zh_titleSeo')     ?: $categoryObj->getProperty('custom_titleSeo')    ?: $categoryObj->name;
    $pageKeywords    = $categoryObj->getProperty('custom_zh_meta_keyword') ?: $categoryObj->getProperty('custom_meta_keyword');
    $pageDescription = $categoryObj->getProperty('custom_zh_intro')        ?: $categoryObj->getProperty('custom_intro');
}
 else {
    $pageTitle       = $categoryObj->getProperty('custom_titleSeo') ?: $categoryObj->name;
    $pageKeywords    = $categoryObj->getProperty('custom_meta_keyword');
    $pageDescription = $categoryObj->getProperty('custom_intro');
}

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);