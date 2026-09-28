<?php

$templateFile = 'company-formation.tpl.html';

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

$slug = $request->element("slug");