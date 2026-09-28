<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$templateFile = 'managepdf.tpl.html';

require_once ROOT_PATH . 'classes/dao/uploadpdf.class.php';
include_once(ROOT_PATH . 'classes/dao/uploadpdfcategories.class.php');
require_once ROOT_PATH . "classes/data/textfilter.class.php";
$pdf = new UploadPdf($storeId);
$pdfCategories = new UploadPdfCategories($storeId);

$topNav = array(
  $amessages['dash_board']      => '/' . ADMIN_SCRIPT . '?op=dashboard',
  $amessages['manage_website']  => '/' . ADMIN_SCRIPT . '?op=manage',
  "Quản lí PDF" => '/'.ADMIN_SCRIPT.'?op=manage&act=pdf',
  $amessages['add_new']         => ''
);
$template->assign('topNav', $topNav);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=pdf';
$listTabs = array(
  $amessages['list_item']   => $tabLink . '&mod=list',
  $amessages['add_new']     => '#',
  $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get article categories array for generating nested combo
$arrayCategories = $pdfCategories->getObjectsForCombo();
# Category combo box
$categoryCombo = $pdfCategories->generateNestedCombo($arrayCategories, $request->element('category_id'));
$template->assign('categoryCombo', $categoryCombo);

if ($_POST && $request->element('doo') == 'submit') {
  $validate = validateData($request);
  if ($validate['invalid']) {
    $template->assign('error', $validate);
  } else {
    $status   = (int)$request->element('status', 1);
    $position = (int)$request->element('position', 0);
    $name     = trim($request->element('name', ''));

    // Upload PDF
    $file = isset($_FILES['link_pdf']) ? $_FILES['link_pdf'] : null;
    $relPath = ''; // đường dẫn để lưu vào DB (web path)

    if ($file && $file['error'] === UPLOAD_ERR_OK) {
      $maxSize = 20 * 1024 * 1024; // 20MB
      if ($file['size'] > $maxSize) {
        $validate['invalid'] = 1;
        $validate['INPUT']['link_pdf']['error']   = 1;
        $validate['INPUT']['link_pdf']['message'] = 'File quá lớn (tối đa 20MB)';
        $template->assign('error', $validate);
      } else {
        // Bảo vệ tên file
        $tf = new TextFilter();
        $orig = $file['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $base = pathinfo($orig, PATHINFO_FILENAME);
        // slugify tên gốc
        $safeBase = preg_replace('/[^a-z0-9\-_]/i', '-', strtolower($base));
        $safeBase = trim($safeBase, '-_');
        // fallback nếu rỗng
        if ($safeBase === '') {
            $safeBase = 'file';
        }
        if ($ext !== 'pdf') {
          $validate['invalid'] = 1;
          $validate['INPUT']['link_pdf']['error']   = 1;
          $validate['INPUT']['link_pdf']['message'] = 'Chỉ cho phép PDF';
          $template->assign('error', $validate);
        } else {
            $safeBase = $tf->urlize($base, false, '-'); 
            $safeBase = preg_replace('/[^a-z0-9\-_]/i', '', $safeBase);
            $newName = $safeBase . '_' . time() . '.' . $ext;

          // Thư mục lưu PDF
          $destDir = ROOT_PATH . 'upload/files/';
          if (!is_dir($destDir)) @mkdir($destDir, 0755, true);

          $absPath = $destDir . $newName;
          if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            $validate['invalid'] = 1;
            $validate['INPUT']['link_pdf']['error']   = 1;
            $validate['INPUT']['link_pdf']['message'] = 'Không thể lưu file PDF';
            $template->assign('error', $validate);
          } else {
            // Web path để trả ra site
            $relPath = '/upload/files/' . $newName;
          }
        }
      }
    } else {
      $validate['invalid'] = 1;
      $validate['INPUT']['link_pdf']['error']   = 1;
      $validate['INPUT']['link_pdf']['message'] = 'Vui lòng chọn file PDF';
      $template->assign('error', $validate);
    }

    // Nếu ok thì lưu DB
    if (!$validate['invalid']) {
      $data = array(
        'store_id'     => $storeId,
        'name'         => $name,
        'category_id'  => $request->element('category_id'),
        'link_pdf'     => $newName,
        'position'     => $position,
        'properties'   => serialize([]),
        'date_created' => $request->element('date_created') ?: date('Y-m-d H:i:s'),
        'date_updated' => date('Y-m-d H:i:s'),
        'status'       => $status
      );
      // var_dump($data);die;
      $newId = $pdf->addData($data);

      if ($newId) {
        header('location:/' . ADMIN_SCRIPT . "?op=manage&act=pdf&mod=list&rcode=6");
        exit;
      } else {
        $validate['invalid'] = 1;
        $validate['message'] = 'Không thể lưu dữ liệu PDF vào DB';
        $template->assign('error', $validate);
      }
    }
  }
}

function validateData($request) {
  global $amessages;
  require_once ROOT_PATH . 'classes/data/validate.class.php';

  $validate = new Validate();
  $error = array('invalid' => 0, 'INPUT' => array());

  $error['INPUT']['status']   = $validate->pasteString($request->element('status'));
  $error['INPUT']['position'] = $validate->validNumber($request->element('position'), $amessages['position']);
  $error['INPUT']['name']     = $validate->pasteString($request->element('name'));

  if ($error['INPUT']['name']['value'] === '') {
    $error['invalid'] = 1;
    $error['INPUT']['name']['error']   = 1;
    $error['INPUT']['name']['message'] = 'Vui lòng nhập tên';
  }
  if ($error['INPUT']['position']['error']) {
    $error['invalid'] = 1;
  }

  return $error;
}
