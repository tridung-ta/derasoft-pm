<?php

include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/users.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/ads.class.php');
include_once(ROOT_PATH . 'classes/dao/adscategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/menus.class.php');

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$articles = new Articles($storeId);
$articleCategories = new ArticleCategories($storeId);
$users = new Users($storeId);
$template->assign('users', $users);
$statics = new StaticPage($storeId);
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$ads = new Ads($storeId);
$adsCategories = new AdsCategories($storeId);
$menus = new Menus($storeId);
$lang = $request->element('lang');

switch ($lang) {
    case 'en':
        $urlField = 'url_en';
        $slugField = 'slug_en';
        break;

    case 'zh':
        $urlField = 'url_zh';
        $slugField = 'slug_zh';
        break;

    default:
        $urlField = 'url';
        $slugField = 'slug';
        break;
}

$slug = $request->element("slug");

# Menu object
$menuObj = $menus->getObject('/' . $slug, $urlField);

$routeType = $menuObj ? $menuObj->getRouteType() : '';
$routeId   = $menuObj ? (int)$menuObj->getRouteId() : 0;

$categoryInfo = null;
$categoryArticle = null;
$categorySubService = null;
$categorySubProduct = null;

# Danh mục sản phẩm
if ($routeType == 'product_category' && $routeId > 0) {
    $categoryInfo = $productCategories->getObject($routeId);

    # Danh mục dịch vụ con
    if ($categoryInfo && in_array(143, explode(',', $categoryInfo->getListParentId()))) {
        $categorySubService = $categoryInfo;
        $template->assign('categorySubService', $categorySubService);
    }

    # Danh mục sản phẩm con
    if ($categoryInfo && in_array(145, explode(',', $categoryInfo->getListParentId()))) {
        $categorySubProduct = $categoryInfo;
        $template->assign('categorySubProduct', $categorySubProduct);
    }
}

# Danh mục bài viết
if ($routeType == 'article_category' && $routeId > 0) {
    $categoryArticle = $articleCategories->getObject($routeId);
}

# Chi tiết sản phẩm
$productObj = $products->getObject($slug, $slugField);

# Chi tiết bài viết
$articleObj = $articles->getObject($slug, $slugField);

# Trang tĩnh
$static = $statics->getObject($slug, "slug", "s.`landing` = 1");

if ($slug == 'gioi-thieu') {
    include_once(ROOT_PATH . 'modules/estore/intro.module.php');
    return;

} else if ($categoryInfo) {

    if (in_array(177, explode(',', $categoryInfo->getListParentId()))) {
        include_once(ROOT_PATH . 'modules/estore/follow_stage_product.module.php');
        return;
    } else if (
        in_array(183, explode(',', $categoryInfo->getListParentId())) ||
        in_array(184, explode(',', $categoryInfo->getListParentId()))
    ) {
        include_once(ROOT_PATH . 'modules/estore/follow_exhibition_product.module.php');
        return;
    }

} else if ($categoryArticle) {

    if (in_array(71, explode(',', $categoryArticle->getParentId()))) {
        include_once(ROOT_PATH . 'modules/estore/casestudy.module.php');
        return;

    } else if (in_array(86, explode(',', $categoryArticle->getParentId()))) {
        include_once(ROOT_PATH . 'modules/estore/follow_exhibition_service.module.php');
        return;

    } else if (in_array(87, explode(',', $categoryArticle->getParentId()))) {
        include_once(ROOT_PATH . 'modules/estore/follow_stage_service.module.php');
        return;

    } else if (in_array(73, explode(',', $categoryArticle->getParentId()))) {
        include_once(ROOT_PATH . 'modules/estore/news.module.php');
        return;

    } else if (
        in_array(79, explode(',', $categoryArticle->getParentId())) ||
        in_array(80, explode(',', $categoryArticle->getParentId()))
    ) {
        include_once(ROOT_PATH . 'modules/estore/follow_exhibition_service_articles.module.php');
        return;

    } else if (in_array(83, explode(',', $categoryArticle->getParentId()))) {
        include_once(ROOT_PATH . 'modules/estore/follow_stage_service_articles.module.php');
        return;
    }

} else if ($categorySubService) {

    include_once(ROOT_PATH . 'modules/estore/service.module.php');
    return;

} else if ($categorySubProduct) {

    include_once(ROOT_PATH . 'modules/estore/product.module.php');
    return;

} else if ($productObj && $productObj->getStatus() == 1) {
    
    include_once(ROOT_PATH . 'modules/estore/productdetail.module.php');
    return;

} else if ($articleObj) {

    if ($articleObj->getStatus() == 1) {
        include_once(ROOT_PATH . 'modules/estore/news_detail.module.php');
        return;
    } else {
        $templateFile = '404.tpl.html';
        return;
    }

} else if ($static) {

    include_once(ROOT_PATH . 'modules/estore/static.module.php');
    return;

} else {

    $templateFile = '404.tpl.html';
    return;
}