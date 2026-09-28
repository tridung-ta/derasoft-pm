<?php

// New editorial home. The complete legacy MPX home remains below for rollback.
include ROOT_PATH.'modules/estore/editorialhome.module.php';
return;

$templateFile = 'index.tpl.html';
# Get keywords, sort key, sort direction, current page
$sort_key = $request->element('sk');
if (!$sort_key) $sort_key = 'created';
$sort_direction = $request->element('sd');
if (!$sort_direction) $sort_direction = 'desc';
$page = $request->element('pg');
if (!$page) $page = 1;

# Get the product list
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/users.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/ads.class.php');
include_once(ROOT_PATH . 'classes/dao/adscategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$articles = new Articles(1);
$articleCategories = new ArticleCategories(1);
$articles = new Articles($storeId);
$users = new Users($storeId);
$template->assign('users', $users);
$statics = new StaticPage($storeId);
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$ads = new Ads($storeId);
$adsCategories = new AdsCategories($storeId);

# Menu languages url
assignLangUrls($template, $menus, 1); // Home

# Banner
$bannerDesktop = $ads->getObject(35);
$template->assign('bannerDesktop', $bannerDesktop);

# Banner Mobile
$bannerMobile = $ads->getObject(106);
$template->assign('bannerMobile', $bannerMobile);   

# Dịch vụ chuyên nghiệp
$professionalStageServiceId = 152;
$professionalExhibitionServiceId = 148;

$listProfessionalService = $menus->getObjects(
    1,
    "parent_id IN ($professionalStageServiceId, $professionalExhibitionServiceId) AND status = 1",
    ["position" => "ASC"],
    6
);
$template->assign('listProfessionalService', $listProfessionalService);
$descriptionProfessionalService = "Demo: chuyên cung cấp các hệ thống sân khấu, giàn truss, gian hàng triễn lảm, hộp đèn quảng cáo và giải pháp trưng bày sáng tạo với công nghệ module, kỹ thuật cơ khí chính xác và tiêu chuẩn quốc tế";
$template->assign('descriptionProfessionalService', $descriptionProfessionalService);

# Các Dự án tiêu biểu
$featuredProjects = $articles->getObjects(1,"a.`status` = '1' AND a.properties LIKE '" . buildSerializedLike('custom_typical_project', '1') . "'",['COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'],4);
$template->assign('featuredProjects', $featuredProjects);
$descriptionFeaturedProject = "Demo: chuyên cung cấp các hệ thống sân khấu, giàn truss, gian hàng triển lãm...";
$template->assign('descriptionFeaturedProject', $descriptionFeaturedProject);

# Khách hàng tiểu biểu của mpx
$customerListMpxId = 8;
$customerListMpx = $ads->getObjects(1, "`status` = '1' AND gid = '$customerListMpxId' ", array("position" => "ASC"), 999);
$template->assign('customerListMpx', $customerListMpx);
$groupBannerCustomerListMpx = $adsCategories->getObject($customerListMpxId);
$template->assign('groupBannerCustomerListMpx', $groupBannerCustomerListMpx);

$pageTitle = $estore->getProperty('custom_meta_title');
$pageKeywords = $estore->getProperty('custom_meta_keywords');
$pageDescription = $estore->getProperty('custom_meta_description');

$template->assign('pageTitle',       $pageTitle);
$template->assign('titlePage',       $pageTitle);
$template->assign('pageKeywords',    $pageKeywords);
$template->assign('pageDescription', $pageDescription);
