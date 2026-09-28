<?php
$templateFile = 'tool.tpl.html';

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include_once(ROOT_PATH . 'classes/dao/articles.class.php');
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
$menus = new Menus($storeId);


$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Công cụ", "url" => "/cong-cu"],
];
if ($topNav) $template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# Công cụ và hướng dẫn kỹ thuật
$toolArticle = $articles->getObjects(1,"a.status = '1' AND a.category_id = 43",array("a.position" => "ASC"),10);
$template->assign('toolArticle', $toolArticle);

# Hướng dẫn kỹ thuật phổ biến
$popularTechnicalGuide = $articles->getObjects(1,"a.status = '1' AND a.category_id = 37",array("a.position" => "DESC"),10);
$template->assign('popularTechnicalGuide', $popularTechnicalGuide);

# Menu công cụ
$menusDetail = $menus->getObject(111);

$pageKeywords = $menusDetail->getProperty('custom_meta_keyword');
$pageDescription = $menusDetail->getProperty('custom_captionSeo');
$pageTitle = $menusDetail->getProperty('custom_titleSeo');

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);