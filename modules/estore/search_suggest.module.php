<?php
// modules/estore/search_suggest.module.php
header('Content-Type: text/html; charset=utf-8');

include_once(ROOT_PATH.'classes/dao/products.class.php');
$products = new Products($storeId);

$kw = trim($_GET['kw'] ?? '');
if ($kw === '') { echo ''; exit; }

$kwEsc  = addslashes($kw);
$kwSlug = function_exists('changeTitle') ? addslashes(changeTitle($kw)) : $kwEsc;

// tuỳ cột bảng của bạn, mình giả định có: name/title, slug, keyword, status
$cond = "`status`=1 AND (" .
        "`slug` LIKE '%{$kwSlug}%' OR `name` LIKE '%{$kwEsc}%' OR `keyword` LIKE '%{$kwEsc}%')";

$list = $products->getObjects(1, $cond, ['id'=>'DESC'], 5);

$out = '';
if ($list) {
  foreach ($list as $p) {
    $name = htmlspecialchars($p->getName(), ENT_QUOTES, 'UTF-8');
    $slug = htmlspecialchars($p->getSlug(), ENT_QUOTES, 'UTF-8');
    $thumb = $p->getAvatar() ? '/upload/'.$storeId.'/products/l_'.$p->getAvatar() : '';
    $out .= '<a class="suggest-item" href="/'.$slug.'" style="display:flex;gap:8px;padding:8px 10px;text-decoration:none;color:#111">';
    if ($thumb) $out .= '<img src="'.$thumb.'" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:4px">';
    $out .= '<span>'.$name.'</span></a>';
  }
}
echo $out; 
exit;
