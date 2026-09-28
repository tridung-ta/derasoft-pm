<?php
$templateFile = 'cart.tpl.html';

include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/carts.class.php");
include_once(ROOT_PATH . "classes/dao/cartitems.class.php");
include_once(ROOT_PATH . "classes/dao/products.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$products = new Products($storeId);
$template->assign('products', $products);
$carts = new Carts($storeId);
$cartItems = new CartItems($storeId);

#Breadcrumb
$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Giỏ hàng", "url" => "/gio-hang"],
];
if ($topNav) $template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

$listCartItems = [];
$cartId = $carts->getCurrentCartId();
if ($cartId) {
    $listCartItems = $cartItems->getObjects(1, "cart_id = '$cartId'", array(), 999);
}
$template->assign('listCartItems', $listCartItems);


# Danh sách id cate thuộc Hạ tầng
$listCategoryIdInfrastructure = [136,139,140];
$template->assign('listCategoryIdInfrastructure', $listCategoryIdInfrastructure);

// $pageKeywords = $menusDetail->getProperty('custom_meta_keyword');
// $pageDescription = $menusDetail->getProperty('custom_titleSeo');
// $pageTitle = $menusDetail->getProperty('custom_titleSeo');

// $template->assign('pageKeywords', $pageKeywords);
// $template->assign('pageDescription', $pageDescription);
// $template->assign('pageTitle', $pageTitle);
// $template->assign('titlePage', $pageTitle);