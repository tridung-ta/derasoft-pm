<?php
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/menus.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . "classes/dao/ads.class.php");
include_once(ROOT_PATH . 'classes/dao/pageblocks.class.php');

$uploadAlbums      = new UploadAlbums($storeId);
$uploads           = new Uploads($storeId);
$template->assign('uploads', $uploads);
$statics           = new StaticPage($storeId);
$products          = new Products($storeId);
$articles          = new Articles($storeId);
$menus             = new Menus($storeId);
$productCategories = new ProductCategories($storeId);
$ads               = new Ads($storeId);
$pageblocks        = new PageBlocks($storeId);

$templateFile = 'product.tpl.html';
$slugActive = 'san-pham-cua-mpx';
$template->assign('slugActive', $slugActive);

$cateParent = $menus->getObject(5);
$template->assign('nameCateParent', $cateParent->getName($lang));

# type: dùng chung cho cả dịch vụ và sản phẩm, để phân biệt khi search
$type = 'products';
$template->assign('type', $type);

# is Product Cate 
$isProductCate = true;
$template->assign('isProductCate', $isProductCate);

# Menu languages url
assignLangUrls($template, $menus, 5); // Product

// Lang
$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$slug = $request->element('slug');
if ($slug == 'san-pham-cua-mpx') {
    $categoryObj = $productCategories->getObject(145);
} else {
    $categoryObj = $productCategories->getObject($slug, "slug");
}
$productCateId = $categoryObj->id;

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
        ['name' => 'Products', 'url' => "/en/products"],
    ],
    'zh' => [
        ['name' => '首页', 'url' => '/zh'],
        ['name' => '产品', 'url' => "/zh/products"],
    ],
];

$topNav = $navConfig[$lang];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

// Product list
if  ($slug == 'san-pham-cua-mpx') {
    $arrayProductChildCategories = getListCategoryId($productCategories, CATEGORY_PRODUCT_PARENT_ID);
    $inClause = implode(',', array_map('intval', $arrayProductChildCategories));
    $result = paginate(
        $request,
        $products,
        "status = 1 AND category_id IN ($inClause)",
        "p.status = 1 AND p.category_id IN ($inClause)",
        ['p.id' => 'DESC'],
        6
    );
}else {
    $arrayCategoryId = getListCategoryId($productCategories, $productCateId);
    $inClause = implode(',', array_map('intval', $arrayCategoryId));
    $result = paginate(
        $request,
        $products,
        "status = 1 AND category_id IN ($inClause)",
        "p.status = 1 AND p.category_id IN ($inClause)",
        ['p.id' => 'DESC'],
        6
    );  
}

$template->assign('items',  $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']);    

# Block MPX CUNG CẤP NHỮNG NHÓM THIẾT BỊ NÀO?
$serviceMPXblock = $statics->getObject(121);
$template->assign('serviceMPXblock', $serviceMPXblock);

# THIẾT BỊ SÂN KHẤU & CƠ KHÍ CHUYỂN ĐỘNG
$stage_mechanical_equipment = $statics->getObject(122);
$template->assign('stage_mechanical_equipment', $stage_mechanical_equipment);

# THIẾT BỊ & GIẢI PHÁP TRIỂN LÃM
$exhibition_equipment_solutions = $statics->getObject(123);
$template->assign('exhibition_equipment_solutions', $exhibition_equipment_solutions);

# SẢN PHẨM MPX PHÙ HỢP VỚI NHỮNG NHU CẦU NÀO?
$mpx_suitable_needs_title = $statics->getObject(124);
$template->assign('mpx_suitable_needs_title', $mpx_suitable_needs_title);

// Menu dịch vụ 
$idMenu = 5;
$blocks = $pageblocks->getByPage($idMenu);

// Sản phẩm nổi bật
$listIdFeaturedPro = isset($blocks['featured_products']) ? $blocks['featured_products']->getObjectIds() : '';
if ($listIdFeaturedPro){
    $featuredProducts = $products->getObjects(1,"p.`status` = '1' AND p.`id` IN ($listIdFeaturedPro)",['p.`id`' => 'DESC'],99);
    $template->assign('featuredProducts', $featuredProducts);
} 

# Vì sao nên chọn sản phẩm MPX?
$why_choose_mpx_title = $statics->getObject(125);
$template->assign('why_choose_mpx_title', $why_choose_mpx_title);

// Thiết bị MPX đã được ứng dụng 
$listIdProductUsed = isset($blocks['technologies_mpx_used']) ? $blocks['technologies_mpx_used']->getObjectIds() : '';
if ($listIdProductUsed){
    $usedProducts = $products->getObjects(1,"p.`status` = '1' AND p.`id` IN ($listIdProductUsed)",['p.`id`' => 'DESC'],99);
    $template->assign('usedProducts', $usedProducts);
} 

# Quy trình thực hiện dự án triển lãm
$implementation_process_title = $statics->getObject(126);
$template->assign('implementation_process_title', $implementation_process_title);

# Câu hỏi thường gặp 
$faq_block = $statics->getObject(127);
$template->assign('faq_block', $faq_block);

# Cần tư vấn giải pháp cho dự án triển lãm của bạn?
$need_solutions = $statics->getObject(128);
$template->assign('need_solutions', $need_solutions);

# Banner
$bannerDesktop = $ads->getObject(97);
$template->assign('bannerDesktop', $bannerDesktop);

# Banner Mobile
$bannerMobile = $ads->getObject(105);
$template->assign('bannerMobile', $bannerMobile);   

$productMenu = $menus->getObject(5);
$template->assign('productMenu', $productMenu);

$childProductMenu = array();
if ($productMenu) {
    $childProductMenu = $menus->getObjects(
        1,
        "`parent_id` = " . (int)$productMenu->getId(),
        array('position' => 'ASC'),
        99
    );
}
$template->assign('childProductMenu', $childProductMenu);

if($lang == 'vn') {
    $pageTitle = $productMenu->getProperty('custom_titleSeo');
    $pageKeywords = $productMenu->getProperty('custom_meta_keyword');
    $pageDescription = $productMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $productMenu->getProperty('custom_en_meta_keyword') ?: $productMenu->getProperty('custom_meta_keyword');
    $pageDescription = $productMenu->getProperty('custom_en_intro') ?: $productMenu->getProperty('custom_intro');
    $pageTitle = $productMenu->getProperty('custom_en_titleSeo') ?: $productMenu->getProperty('custom_titleSeo');
}

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);