<?php
$templateFile = 'infrastructure.tpl.html';

include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');

$statics = new StaticPage($storeId);
$productCategories = new ProductCategories($storeId);

$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Hạ tầng", "url" => "/ha-tang"],
];
if ($topNav) $template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

# Component Hạ tầng web
$webInfrastructure = $statics->getObject(91, 'id');
if ($webInfrastructure) {$template->assign('webInfrastructure', $webInfrastructure);}

#  Hạ tầng cate
$webInfrastructureCate = $productCategories->getObject(135);
$template->assign('webInfrastructureCate', $webInfrastructureCate);

$pageKeywords = $webInfrastructureCate->getProperty('custom_meta_keyword');
$pageDescription = $webInfrastructureCate->getProperty('custom_captionSeo');
$pageTitle = $webInfrastructureCate->getProperty('custom_titleSeo');

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);