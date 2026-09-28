<?php

if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

$refPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) ?: '';
$cookieLang = $_COOKIE['lang'] ?? '';
$lang = (preg_match('#^/en(?:/|$)#', $refPath) || $cookieLang === 'en') ? 'en' : 'vn';

// var_dump($lang);die;

include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$optionStructure = new OptionStructure(1);
$fieldValue = new OptionValue(1);
$products = new Products(1);
$productCategories = new ProductCategories(1);

$catId = (int)$request->element('category_id');

if (!$catId) {
  echo json_encode(['success'=>false,'msg'=>'Missing category_id']); exit;
}

$conditon = "`status`=1 AND `parent_id`<>0 AND FIND_IN_SET($catId, REPLACE(`list_parent_id`,' ',''))";

$productCategoryList = $productCategories->getObjects(1, $conditon, ['position'=>'ASC'], 12);

$arrCategoryProduct = [];
if ($productCategoryList) {
  foreach ($productCategoryList as $cate) {

    if ($lang == 'vn') {
      $cateName = $cate->getName();
    } else if ($lang == 'en') {
      $cateName = $optionStructure->getCustomOptionByModule('co_en_name', 'productlistcategory', $cate->getId());
    }

    $arrCategoryProduct[] = [
      'id'   => (int)$cate->getId(),
      'slug' => (string)$cate->getSlug(),
      'name' => (string)$cateName,
    ];
  }
}

$cateIds = [$catId];
if ($productCategoryList) {
  foreach ($productCategoryList as $cate) $cateIds[] = (int)$cate->getId();
}
$cateIds = array_values(array_unique(array_filter($cateIds, fn($v)=>$v>0)));

$arrProducts = [];
if (!empty($cateIds)) {
  $idsStr = implode(',', $cateIds);
  $condProducts = "p.`status`='1' AND p.`category_id` IN ($idsStr)";
  $productList = $products->getObjects(1, $condProducts, ['position'=>'ASC','id'=>'DESC'], 50);

  if ($productList) {
    foreach ($productList as $p) {
      $arrProducts[] = [
        'id'          => (int)$p->getId(),
        'slug'        => (string)$p->getSlug(),
        'name'        => (string)$p->getName(),
        'avatar'      => (string)($p->getAvatar() ?? $p->getProperty('avatar') ?? ''),
        'category_id' => (int)$p->getCategoryId(),
      ];
    }
  }
}

// 3) Trả MỘT JSON duy nhất
if (!headers_sent()) header('Content-Type: application/json; charset=UTF-8');
while (ob_get_level()) ob_end_clean();

echo json_encode([
  'success'            => true,
  'productcategories'  => $arrCategoryProduct,
  'products'           => $arrProducts,
], JSON_UNESCAPED_UNICODE);
exit;
