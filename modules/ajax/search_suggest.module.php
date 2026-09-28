<?php
// modules/ajax/search_suggest.module.php
// Trả về: HTML list <a class="suggest-item" href="/slug">Tên</a>

header('Content-Type: text/html; charset=UTF-8');

include_once(ROOT_PATH.'classes/dao/searchs.class.php');
$searchs = new Search($storeId);

$kw = trim((string)$request->element('kw', ''));
if ($kw === '') { echo ''; exit; }

// Chuẩn hoá đơn giản
$kwEsc  = addslashes($kw);
// Nếu có hàm changeTitle (khử dấu ra slug) thì dùng thêm
$kwSlug = function_exists('changeTitle') ? addslashes(changeTitle($kw)) : $kwEsc;

// Điều kiện: chỉ lấy product
$cond =
  "`status`=1 AND `type`='product' AND (" .
  "`title`  LIKE '%{$kwEsc}%' OR " .
  "`keyword`LIKE '%{$kwEsc}%' OR " .
  "`slug`   LIKE '%{$kwSlug}%'" .
  ")";

// Lấy tối đa 5
$list = $searchs->getObjects(1, $cond, ['id'=>'DESC'], 5);

if (!$list) { echo ''; exit; }

$out = '';
foreach ($list as $it) {
  $name = htmlspecialchars($it->getTitle(), ENT_QUOTES, 'UTF-8');
  $slug = htmlspecialchars($it->getSlug(),  ENT_QUOTES, 'UTF-8');
  $url  = '/'.$slug; // product_detail đang bắt slug gốc
  $out .= '<a class="suggest-item" href="'.$url.'" style="display:flex;gap:8px;padding:8px;text-decoration:none;color:#111;">'
        . '<span>'.$name.'</span>'
        . '</a>';
}

echo $out;
exit;
