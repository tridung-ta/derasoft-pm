<?php

include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/menus.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articlegroups.class.php');

$articleGroups = new ArticleGroups($storeId);
$uploadAlbums      = new UploadAlbums($storeId);
$uploads           = new Uploads($storeId);
$template->assign('uploads', $uploads);
$statics           = new StaticPage($storeId);
$products          = new Products($storeId);
$articles          = new Articles($storeId);
$menus             = new Menus($storeId);
$productCategories = new ProductCategories($storeId);
$articleCategories = new ArticleCategories($storeId);

$templateFile = 'follow_exhibition_service.tpl.html';
$slugActive = 'dich-vu-cua-mpx';
$template->assign('slugActive', $slugActive);

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

$menuObj = $route['menu'];
$articleCategoryObj = $route['articleCategory'];

$template->assign('lang', $lang);
$template->assign('slug', $slug);
$template->assign('menuObj', $menuObj);
$template->assign('articleCategoryObj', $articleCategoryObj);

# get child category
$childCategories = $articleCategories->getObjects(
    1,
    "c.parent_id = " . $articleCategoryObj->getId(),
    ['id' => 'ASC']
);
$template->assign('childCategories', $childCategories[0]);

$isShowBlockService = in_array($articleCategoryObj->getId(), [79, 80]);
$template->assign('isShowBlockService', $isShowBlockService);

$menuName = $lang == 'en'
    ? ($menuObj->getProperty('custom_en_name') ?: $menuObj->name)
    : $menuObj->name;

$template->assign('menuName', $menuName);

# Parent category
$parentCategory = $articleCategories->getObject($articleCategoryObj->getParentId());
$template->assign('parentCategory', $parentCategory);

$parentMenu = null;

if ($parentCategory) {
    $parentMenu = getMenuByRoute(
        $menus,
        'article_category',
        $parentCategory->getId()
    );
}

$parentNav = [
    'name' => $parentMenu ? $parentMenu->getNameByLang($lang) : '',
    'url'  => $parentMenu ? $parentMenu->getUrlByLang($lang) : '#',
];

$currentNav = [
    'name' => $menuObj->getNameByLang($lang),
    'url'  => $menuObj->getUrlByLang($lang),
];

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Dịch vụ', 'url' => '/dich-vu-cua-mpx'],
        $parentNav,
        $currentNav,
    ],

    'en' => [
        ['name' => 'Home', 'url' => '/en'],
        ['name' => 'Services', 'url' => '/en/services'],
        $parentNav,
        $currentNav,
    ],

    'zh' => [
        ['name' => '首页', 'url' => '/zh'],
        ['name' => '服务', 'url' => '/zh/services'],
        $parentNav,
        $currentNav,
    ],
];


assignLangUrls($template, $menus, $menuObj->getId());

# Block case study
$caseStudy = $articles->getObjects(
    1,
    "a.`status`='1' AND a.`category_id`=78",
    ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'],
    10
);
$template->assign('caseStudy', $caseStudy);

# Get list articles services
$articlesServices = $articles->getObjects(
    1,
    "a.`status`='1' AND a.`category_id`=" . $articleCategoryObj->getId(),
    ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'],
    10
);
$template->assign('articlesServices', $articlesServices);

# Lấy tất cả group id đang được sử dụng
$groupIds = [];

foreach ($articlesServices as $article) {
    foreach (explode(',', $article->getArticleGroupIds()) as $id) {
        $id = (int)$id;
        if ($id) {
            $groupIds[$id] = $id;
        }
    }
}

$articleGroupList = [];

if ($groupIds) {
    $articleGroupList = $articleGroups->getObjects(
        1,
        "`status`='1' AND `id` IN (" . implode(',', $groupIds) . ")",
        ['id' => 'DESC'],
        999
    );
}

$template->assign('articleGroupList', $articleGroupList);

# Content page
$contentPage = '';

$staticMap = [
    149 => 136,
    150 => 137,
    151 => 138,
];

if (isset($staticMap[$menuObj->getId()])) {
    $staticObject = $statics->getObject($staticMap[$menuObj->getId()]);
    $contentPage = $staticObject ? $staticObject->getDetail($lang) : '';

    if ($staticObject) {
        $userInfo = $users->getObject($staticObject->getCreatorId());
        $template->assign('userInfo', $userInfo);
    }
}

$template->assign('contentPage', $contentPage);

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# SEO
if ($lang == 'en') {
    $pageTitle = $menuObj->getProperty('custom_en_titleSeo')
        ?: $menuObj->getProperty('custom_titleSeo')
        ?: $menuObj->name;

    $pageKeywords = $menuObj->getProperty('custom_en_meta_keyword')
        ?: $menuObj->getProperty('custom_meta_keyword');

    $pageDescription = $menuObj->getProperty('custom_en_intro')
        ?: $menuObj->getProperty('custom_intro');
} else {
    $pageTitle = $menuObj->getProperty('custom_titleSeo') ?: $menuObj->name;
    $pageKeywords = $menuObj->getProperty('custom_meta_keyword');
    $pageDescription = $menuObj->getProperty('custom_intro');
}

$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);
$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);