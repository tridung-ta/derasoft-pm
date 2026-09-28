<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$templateFile = 'order-info.tpl.html';

include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/carts.class.php");
include_once(ROOT_PATH . "classes/dao/cartitems.class.php");
include_once(ROOT_PATH . "classes/dao/products.class.php");
include_once(ROOT_PATH . "classes/dao/customers.class.php");
include_once(ROOT_PATH . "classes/dao/countries.class.php");
include_once(ROOT_PATH . "classes/dao/areas.class.php");
include_once(ROOT_PATH . "classes/data/validate.class.php");
include_once(ROOT_PATH . "languages/admin/vn.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$products = new Products($storeId);
$template->assign('products', $products);
$carts = new Carts($storeId);
$cartItems = new CartItems($storeId);
$customers = new Customers($storeId);
$countries = new Countries($storeId);
$areas = new Areas($storeId);
$validate = new Validate();

$slug = $request->element('slug');
$template->assign('slug', $slug);

#Breadcrumb
$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Thông tin hóa đơn", "url" => "/thong-tin-hoa-don"],
];
if ($topNav) $template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# Lấy thông tin giỏ hàng hiện tại
$cartId = $carts->getCurrentCartId();
$cartInfo = null;

if ($cartId) {
    $cartInfo = $carts->getObject($cartId);
    $template->assign('cart', $cartInfo);
}

# Kiểm tra đã đăng nhập chưa
$isLogin = 0;
$customerInfo = null;

if ($cartInfo && $cartInfo->getCustomerId() > 0) {
    $isLogin = 1;
    $customerInfo = $customers->getObject($cartInfo->getCustomerId());
}
$template->assign('customerInfo', $customerInfo);
$template->assign('isLogin', $isLogin);
 
# Lấy danh sách Quốc Gia
$listCountries = $countries->getObjects(1, "status = 1", array("position" => "ASC"), 999);
$template->assign('listCountries', $listCountries);

# Lấy danh sách Tỉnh/Thành phố thuộc Việt Nam
$listAreas = $areas->getObjects(1, "a.status = 1 AND a.country_id = 192", array("position" => "ASC"), 999);
$template->assign('listAreas', $listAreas);

$listCartItems = [];
$cartViewData = [];

$cartId = $carts->getCurrentCartId();

$totalQuantity = 0;
$subtotal = 0;

if ($cartId) {
    $listCartItems = $cartItems->getObjects(1, "cart_id = '$cartId'", array(), 999);
    foreach ($listCartItems as $item) {
        // Lấy giá theo year
        switch ($item->getYear()) {
            case 1:
                $price = $item->getPrice();
                break;
            case 2:
                $price = $item->getPrice2Year();
                break;
            case 3:
                $price = $item->getPrice3Year();
                break;
            default:
                $price = 0;
        }
        $quantity = $item->getQuantity();
        $currentPrice = $price * $quantity;
        $originalPrice = $item->getPrice() * $quantity * $item->getYear();
        $savePrice = $originalPrice - $currentPrice;
        $detailProduct = $products->getObject($item->getProductId());
                
        $cartViewData[] = [
            'name' => $detailProduct->getName(),
            'productId' => $item->getProductId(),
            'quantity' => $quantity,
            'year' => $item->getYear(),
            'currentPrice' => $currentPrice,
            'originalPrice' => $originalPrice,
            'savePrice' => $savePrice,
        ];

        $totalQuantity += $quantity;
        $subtotal += $currentPrice;
    }
}

// VAT + total
$vat = $subtotal * 0.08;
$totalFinal = $subtotal + $vat;
// assign sang template
$template->assign('cartViewData', $cartViewData); // ⚠ đổi sang cartViewData
$template->assign('totalQuantity', $totalQuantity);
$template->assign('subtotal', $subtotal);
$template->assign('vat', $vat);
$template->assign('totalFinal', $totalFinal);

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