<?php
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/comments.class.php');
$comments = new Comments(1);
$products = new Products(1);
$articles = new Articles(1);
$val = $request->element('valtest');
$slugValue = $request->element('slugValue');

$productItems = $products->getObject($slugValue, "slug");
$articleItems = $articles->getObject($slugValue, "slug");
$pid = '3';
if ($productItems) {
	$pid = '1';
}
if ($articleItems) {
	$pid = '2';
}
if ($val == '0') {
	$conditionL =  "`status` = 1 AND `slug` = '$slugValue' AND `pid` = $pid";
} else {
	$conditionL = "`star` = $val AND `status` = 1 AND `slug` = '$slugValue' AND `pid` = $pid";
}

$listComment = $comments->getObjects(1, "$conditionL", array("id" => "DESC"), 999);
$arrayFinalHome = [];
foreach ($listComment as $value) {
	$item['id'] = $value->getId();
	$item['fullname'] = $value->getFullName();
	$item['detail'] = $value->getDetails();
	$item['star'] = $value->getStars();
	$item['date'] = date("d-m-Y H:i:s", strtotime($value->getDateCreated()));
	array_push($arrayFinalHome, $item);
}

$result = array("arrayFinalHome" => $arrayFinalHome,"conditionL" => $conditionL);
echo json_encode($result);
