<?php

// $refPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) ?: '';
// $cookieLang = $_COOKIE['lang'] ?? '';
// $lang = (preg_match('#^/en(?:/|$)#', $refPath) || $cookieLang === 'en') ? 'en' : 'vn';

// include_once(ROOT_PATH . 'classes/dao/products.class.php');
// include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');

// $products = new Products(1);
// $productCategories = new ProductCategories(1);

// $catId = (int)$request->element('category_id');
// $limit = (int)$request->element('limit') ?: 8;

// if (!$catId) {
//   echo json_encode(['success'=>false,'msg'=>'Missing category_id']); exit;
// }

// $conditon = "p.`status`='1' AND p.`category_id`='{$catId}'";
// $productList = $products->getObjects(1, $conditon, ['position'=>'ASC','id'=>'DESC'], $limit);

// $arrDiscoveryProduct = [];
// if ($productList) {
//   foreach ($productList as $product) {
//     $arrDiscoveryProduct[] = [
//       'slug'  => $product->getSlug(),
//       'name'  => $product->getName(),
//       'url'   => $product->getSlug(),
//       'image' => $product->getAvatar()
//     ];
//   }
// }

// if ($lang == 'vn') {
//   $updateMessage = 'Sản phẩm đang được cập nhật...';
// } else if ($lang == 'en') {
//   $updateMessage = 'Products are being updated...';
// }

// echo json_encode([
//   'success'=>true,
//   'products'=>$arrDiscoveryProduct,
//   'message'  => empty($arrDiscoveryProduct) ? $updateMessage : ''
// ]); 


  $refPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) ?: '';
  $cookieLang = $_COOKIE['lang'] ?? '';
  $lang = (preg_match('#^/en(?:/|$)#', $refPath) || $cookieLang === 'en') ? 'en' : 'vn';

  include_once(ROOT_PATH . 'classes/dao/products.class.php');
  include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');

  $products = new Products(1);
  $productCategories = new ProductCategories(1);

  $catId    = (int)$request->element('category_id');
  $parentId = (int)$request->element('parent_id');
  $limit    = (int)$request->element('limit') ?: 8;

  if (!$catId && !$parentId) {
    echo json_encode(['success'=>false,'msg'=>'Missing category_id or parent_id']); exit;
  }

  $condition = "p.`status`='1'";

  if ($catId) {
    $condition .= " AND p.`category_id`='{$catId}'";
  }
  else if ($parentId) {
    // Lấy tất cả category con (và cả chính parent nếu có sản phẩm gắn trực tiếp)
    $catIds = [$parentId];

    // lấy danh mục con trực tiếp và sâu hơn (dựa vào parent_id, list_parent_id)
    $children = $productCategories->getObjects(
      1,
      "status = 1 AND (parent_id = '{$parentId}' OR FIND_IN_SET('{$parentId}', `list_parent_id`))",
      ['position'=>'ASC','id'=>'ASC'],
      999
    );
    if ($children) {
      foreach ($children as $c) {
        $catIds[] = (int)$c->getId();
      }
    }
    $catIds = array_values(array_unique(array_filter($catIds, 'intval')));

    // Nếu không có id nào → trả empty
    if (empty($catIds)) {
      echo json_encode(['success'=>true,'products'=>[], 'message'=> ($lang==='en' ? 'Products are being updated...' : 'Sản phẩm đang được cập nhật...')]);
      exit;
    }

    $idsStr = implode(',', $catIds);
    $condition .= " AND p.`category_id` IN ($idsStr)";
  }

  $productList = $products->getObjects(1, $condition, ['position'=>'ASC','id'=>'DESC'], $limit);

  $arrDiscoveryProduct = [];
  if ($productList) {
    foreach ($productList as $product) {
      $slug = $product->getSlug();
      // Trả sẵn URL đúng ngôn ngữ
      $url  = ($lang === 'en') ? "/en/{$slug}" : "/{$slug}";
      $arrDiscoveryProduct[] = [
        'slug'  => $slug,
        'name'  => $product->getName(),
        'url'   => $url,
        'image' => $product->getAvatar()
      ];
    }
  }

  $updateMessage = ($lang === 'en')
    ? 'Products are being updated...'
    : 'Sản phẩm đang được cập nhật...';

  echo json_encode([
    'success'  => true,
    'products' => $arrDiscoveryProduct,
    'message'  => empty($arrDiscoveryProduct) ? $updateMessage : ''
  ]);

exit;