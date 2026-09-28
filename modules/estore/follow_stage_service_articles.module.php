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

$templateFile = 'follow_stage_service_articles.tpl.html';
$slugActive = 'dich-vu-cua-mpx';
$template->assign('slugActive', $slugActive);

# type: dùng chung cho cả dịch vụ và sản phẩm, để phân biệt khi search
$type = 'articles';
$template->assign('type', $type);

$slug = $request->element('slug');
$group = trim((string)$request->element('group'));

$articleGroupObj = null;

if ($group !== '') {
    $articleGroupObj = $articleGroups->getObject($group, "slug");
}

$template->assign('currentGroup', $group);

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

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
$template->assign('articleCategoryObj', $articleCategoryObj);

$template->assign('menuObj', $menuObj);

assignLangUrls($template, $menus, $menuObj->getId()); 

$baseCondition = "a.status = 1 AND a.category_id = " . (int)$articleCategoryObj->getParentId();

$condition = $baseCondition;

if ($articleGroupObj) {
    $groupId = (int)$articleGroupObj->getId();

    $condition .= " AND FIND_IN_SET($groupId, a.article_group_ids)";
}

# Get list articles services
$articlesServices = $articles->getObjects(1,$baseCondition,['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'],10);
$template->assign('articlesServices', $articlesServices);

# Lấy tất cả group id đang được sử dụng
$groupIds = [];
if ($articlesServices) {
    foreach ($articlesServices as $article) {
        $ids = explode(',', $article->getArticleGroupIds());
        foreach ($ids as $id) {
            $id = (int)trim($id);
            if ($id > 0) {
                $groupIds[$id] = $id;
            }
        }
    }
}
# Get list article groups
if (!empty($groupIds)) {
    $articleGroupList = $articleGroups->getObjects(1,"`status`='1' AND `id` IN (" . implode(',', $groupIds) . ")",['id' => 'DESC'],999);
} else {
    $articleGroupList = [];
}
$template->assign('articleGroupList', $articleGroupList);

$template->assign('lang',        $lang);
$template->assign('slug',        $slug);

// Breadcrumb & topNav
$catName = $articleCategoryObj->getName($lang);

$template->assign('catName', $catName);

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => $menuObj->getName($lang),    'url' => $menuObj->getUrlByLang($lang)],
    ],
    'en' => [
        ['name' => 'Home',   'url' => '/en'],
        ['name' => $menuObj->getName($lang),    'url' => $menuObj->getUrlByLang($lang)],
    ],
    'zh' => [
        ['name' => '首页',   'url' => '/zh'],
        ['name' => $menuObj->getName($lang),    'url' => $menuObj->getUrlByLang($lang)],
    ],
];


$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# H1 
$h1 = $articleCategoryObj->getProperty('custom_h1') ? $articleCategoryObj->getProperty('custom_h1') : $articleCategoryObj->getName($lang);
$template->assign('h1', $h1);

# Description
$description = $articleCategoryObj->getDescription($lang) ? $articleCategoryObj->getDescription($lang) : '';
$template->assign('description', $description);

// Articles list
$result = paginate(
    $request,
    $articles,
    $condition,
    $condition,
    ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'],
    1
);

$template->assign('items',  $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']);

# Dự án gấn đây
$recentProjects = $articles->getObjects(1, "a.`status` = '1'", ['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'], 4);
$template->assign('recentProjects', $recentProjects);   

# Danh mục dịch vụ sidebar 
$childCate = $menus->getObjects(1, "parent_id = 3 AND `status` = '1'", [], 999); 
$template->assign('childCate', $childCate);

// SEO
if ($lang === 'en') {
    $pageTitle       = $articleCategoryObj->getProperty('custom_en_titleSeo')     ?: $articleCategoryObj->getProperty('custom_titleSeo')    ?: $articleCategoryObj->getName($lang);
    $pageKeywords    = $articleCategoryObj->getProperty('custom_en_meta_keyword') ?: $articleCategoryObj->getProperty('custom_meta_keyword');
    $pageDescription = $articleCategoryObj->getProperty('custom_en_intro')        ?: $articleCategoryObj->getProperty('custom_intro');
} else {
    $pageTitle       = $articleCategoryObj->getProperty('custom_titleSeo') ?: $articleCategoryObj->getName($lang);
    $pageKeywords    = $articleCategoryObj->getProperty('custom_meta_keyword');
    $pageDescription = $articleCategoryObj->getProperty('custom_intro');
}

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);