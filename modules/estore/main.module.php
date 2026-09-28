<?php

// Debug
// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// }
/* ===================== INIT ===================== */
include_once(ROOT_PATH . 'classes/dao/templates.class.php');

$estores   = new EStores();
$templates = new Templates();

$cId = 0;
$cCategory = '';
$pCategory = '';

$crpg = getCurrentPage();

/* ===================== CURRENT PAGE ===================== */
$currentpage = getCurrentURlLg($crpg);
$template->assign('currentpage', $currentpage);

/* ===================== LANG ===================== */
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

$lang = 'vn';

if (preg_match('#^/en(/|$)#', $requestUri)) {
    $lang = 'en';
}else if (preg_match('#^/zh(/|$)#', $requestUri)) {
    $lang = 'zh';
}

$template->assign('lang', $lang);

$messages = [];
if ($lang === 'en') {
    include ROOT_PATH . 'languages/en.php';
}else if ($lang === 'zh') {
    include ROOT_PATH . 'languages/zh.php';
} else {
    include ROOT_PATH . 'languages/vn.php';
}
$template->assign('messages', $messages);

/* ===================== CUSTOMER ===================== */
$CustomerId = $_SESSION["store_customerId"] ?? 0;
$template->assign('CustomerId', $CustomerId);

// Resolve CMS-generated article slugs before authentication and before any
// legacy module can fall back to the MPX index template. A one-segment route
// that is not owned by the editorial application is an article candidate;
// news_detail will render the normal 404 page when no article exists.
$editorialDispatchVersion = '20260925-force-article';
if (!headers_sent()) header('X-Dera-Editorial-Dispatcher: ' . $editorialDispatchVersion);
$dispatchPath = '/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$dispatchReservedPaths = array(
    '/', '/dang-nhap', '/dang-ky', '/login', '/register', '/verify-user',
    '/logout', '/huy-dang-ky', '/unsubscribe', '/khong-gian-doc',
    '/reading-space', '/van-tho', '/nghe-thuat', '/tin-tuc', '/video',
    '/tim-kiem'
);
$dispatchPathWithoutLang = preg_replace('#^/(en|zh)(?=/|$)#', '', $dispatchPath);
if (
    !in_array($dispatchPathWithoutLang, $dispatchReservedPaths, true)
    && preg_match('#^/([a-z0-9][a-z0-9-]{0,190})$#', $dispatchPathWithoutLang, $dispatchArticleMatch)
) {
    $act = 'news_detail';
    $_GET['act'] = $act;
    $_GET['slug'] = $dispatchArticleMatch[1];
    $_REQUEST['act'] = $act;
    $_REQUEST['slug'] = $dispatchArticleMatch[1];
}
/* Member gateway: public editorial content requires a signed-in customer. */
$memberPublicActs = array('login', 'signin', 'logout', 'verifyuser', 'forgotpassword', 'resetpassword', 'newsletterunsubscribe', 'vnpayipn');
if (!$CustomerId && !in_array(strtolower((string)$act), $memberPublicActs, true)) {
    $requestedPath = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';
    $requestedPath = str_replace(array("\r", "\n"), '', $requestedPath);
    if ($requestedPath === '' || $requestedPath[0] !== '/' || strpos($requestedPath, '//') === 0) {
        $requestedPath = '/';
    }
    if (strlen($requestedPath) > 1500) {
        $requestedPath = '/';
    }
    $memberLoginPath = $lang === 'en' ? '/en/login' : ($lang === 'zh' ? '/zh/login' : '/dang-nhap');
    header('Location: ' . $memberLoginPath . '?next=' . rawurlencode($requestedPath), true, 302);
    exit;
}

/* ===================== ESTORE ===================== */
$estore = $estores->getObject(1);
$template->assign('estore', $estore);

/* ===================== ESTORE STATUS ===================== */
// $act = '';
// if ($estore->getStatus() == S_EXPIRED) {
//     $act = 'suspended';
// } elseif ($estore->getStatus() != S_ENABLED) {
//     $act = 'disabled';
// }
// var_dump($estore, $act); die;

/* ===================== TEMPLATE ===================== */
$templateId   = $estore->getProperty('domain_template_id');
$userTemplate = $templates->getTemplateFolderFromId($templateId);

if (isset($_SESSION['template'])) {
    $userTemplate = $_SESSION['template'];
}
if ($request->element('template')) {
    $_SESSION['template'] = $request->element('template');
}
if (!$userTemplate) {
    $userTemplate = STANDARD_TEMPLATE;
}

$template_dir[] = ROOT_PATH . TEMPLATE_PATH . '/' . $userTemplate . '/';
$template->assign('userTemplate', $userTemplate);

/* ===================== URL ===================== */
if ($crpg == PROTOCOL . DOMAIN . ':443/') {
    $crpg = PROTOCOL . DOMAIN . ":443/index.html";
}
$template->assign('crpg', $crpg);

$currentUrlx  = getCurrentUrlNoLang($crpg);
$currentUrlx1 = PROTOCOL . DOMAIN . '/' . ltrim($currentUrlx, '/');

// detect ngôn ngữ hiện tại - dùng $requestUri thay vì $crpg
$currentLang = (preg_match('#^/en(/|$)#', $requestUri)) ? 'en' : 'vi';
$template->assign('currentLang', $currentLang);

// EN URL
$currentUrlx1_en = PROTOCOL . DOMAIN . '/en/' . ltrim($currentUrlx, '/');
$template->assign('currentUrlx1_en', $currentUrlx1_en);

$template->assign('currentUrlx', $currentUrlx);
$template->assign('isHome', $isHome);
$template->assign('currentUrlx1', $currentUrlx1);
$template->assign('rootUrl', PROTOCOL . DOMAIN);
$publicBase = $lang === 'en' ? '/en' : ($lang === 'zh' ? '/zh' : '');
$publicHome = $publicBase !== '' ? $publicBase . '/' : '/';
$template->assign('publicBase', $publicBase);
$template->assign('publicHome', $publicHome);
$readingHubPath = $lang === 'en' ? '/en/reading-space' : ($lang === 'zh' ? '/zh/reading-space' : '/khong-gian-doc');
$template->assign('readingHubPath', $readingHubPath);

// Default language switch URLs for home and public section pages.
// Detail modules may override these later with translated object slugs.
$languagePath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$languagePath = preg_replace('#^/(en|zh)(?=/|$)#', '', $languagePath);
$languagePath = '/' . ltrim((string) $languagePath, '/');
if ($languagePath === '//') $languagePath = '/';
$languageSuffix = $languagePath === '/' ? '' : $languagePath;
$template->assign('urlVi', $languagePath);
$template->assign('urlEn', '/en' . $languageSuffix);
$template->assign('urlZh', '/zh' . $languageSuffix);

/* ===================== ORDER ===================== */
$orderOn = $estore->getProperty('order_on');
$template->assign('orderOn', $orderOn);

/* ===================== DAO ===================== */
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH . "classes/dao/currencies.class.php");
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/menus.class.php');
include_once(ROOT_PATH . 'classes/dao/menucategories.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/orders.class.php');
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');

/* ===================== OBJECT ===================== */
$optionStructure   = new OptionStructure($storeId);
$fieldValue        = new OptionValue($storeId);
$products          = new Products($storeId);
$articles          = new Articles($storeId);
$articleCategories = new ArticleCategories($storeId);
$productCategories = new ProductCategories($storeId);
$currencies        = new Currencies($storeId);
$statics           = new StaticPage($storeId);
$searchs           = new Search($storeId);
$menus             = new Menus($storeId);
$menuCategories    = new MenuCategories();
$productOptions    = new ProductOptions($storeId);
$orders            = new Orders($storeId);
$imgs              = new Imgs();

$template->assign('menus', $menus);
$template->assign('productOptions', $productOptions);
$template->assign('imgs', $imgs);
$template->assign('optionStructure', $optionStructure);
$slugActive = '';
$template->assign('slugActive', $slugActive);

# Danh sách các danh mục con
const CATEGORY_SERVICE_PARENT_ID = 143; // Dịch vụ
const CATEGORY_PRODUCT_PARENT_ID = 145; // Sản phẩm

$type = '';
$template->assign('type', $type);
$isProductCate = false;
$template->assign('isProductCate', $isProductCate);
$isProduct = false;
$template->assign('isProduct', $isProduct);

/* ===================== STATUS MODULE ===================== */
if ($act) {
    include_once(ROOT_PATH . 'modules/estore/' . strtolower($act) . '.module.php');
}

/* ===================== CUSTOMER NAME ===================== */
if ($CustomerId) {
    $customerName = $customers->getUserNameFromId($CustomerId);
    if ($customerName) {
        $template->assign('customerName', $customerName);
    }
}

/* ===================== CART ===================== */
include_once(ROOT_PATH . "classes/dao/carts.class.php");
include_once(ROOT_PATH . "classes/dao/cartitems.class.php");

$carts     = new Carts($storeId);
$cartItems = new CartItems($storeId);

$cartId = $carts->getCurrentCartId();
$totalQuantityCart = $cartId ? $cartItems->getTotalQuantity($cartId) : 0;

if ($totalQuantityCart) {
    $template->assign('totalQuantityCart', $totalQuantityCart);
}

$menu = $menus->getObjects(1, "`status` = 1", array("position" => "ASC"), 999);
if ($menu) {
    $template->assign('menu', $menu);
}

$menuTree = [];
$menuByParent = [];

if (!empty($menu)) {
    foreach ($menu as $item) {
        $parentId = $item->getParentId() ? (int)$item->getParentId() : 0;
        $menuByParent[$parentId][] = $item;
    }

    if (!function_exists('buildMenuTree')) {
        function buildMenuTree($parentId, $menuByParent)
        {
            $branch = [];

            if (!empty($menuByParent[$parentId])) {
                foreach ($menuByParent[$parentId] as $item) {
                    $children = buildMenuTree($item->getId(), $menuByParent);

                    $branch[] = [
                        'item' => $item,
                        'children' => $children
                    ];
                }
            }

            return $branch;
        }
    }

    $menuTree = buildMenuTree(0, $menuByParent);
}
$template->assign('menuTree', $menuTree);

# Danh sách bài viết gần đây 
$recentArticlesBlock = $articles->getObjects(1, "a.`status` = '1'", array('a.`id`' => 'DESC'), 10);
$template->assign('recentArticlesBlock', $recentArticlesBlock);

# Danh sách dịch vụ footer
$services_footer = $menus->getObjects(1, "`status` = '1' AND `parent_id` = '3'", array(), 999);
$template->assign('services_footer', $services_footer);

# Logo
$logoimg = PROTOCOL . DOMAIN . $estore->getProperty('store_logo');
$template->assign('logoimg', $logoimg);
