<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$templateFile = 'managepdf.tpl.html';

require_once ROOT_PATH . 'classes/dao/uploadpdf.class.php';
include_once(ROOT_PATH . 'classes/dao/uploadpdfcategories.class.php');
$pdf = new UploadPdf($storeId);
$pdfCategories = new UploadPdfCategories($storeId);


// Nav + Tabs
$topNav = array(
  $amessages['dash_board']      => '/' . ADMIN_SCRIPT . '?op=dashboard',
  $amessages['manage_website']  => '/' . ADMIN_SCRIPT . '?op=manage',
  "Quản lí PDF"                 => '/' . ADMIN_SCRIPT . '?op=manage&act=pdf',
  $amessages['edit_item']       => ''
);
$template->assign('topNav', $topNav);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=pdf';
$listTabs = array(
  $amessages['list_item']   => $tabLink . '&mod=list',
  $amessages['edit_item']     => $tabLink . '&mod=edit',
  $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$result_code = (int)$request->element('rcode');
$error_code  = (int)$request->element('ecode');
if ($result_code) $template->assign('result_code', $result_code);
if ($error_code)  $template->assign('error_code',  $error_code);

// Lấy ID & object
$id = (int)$request->element('id');
if (!$id) {
  header('location:/' . ADMIN_SCRIPT . "?op=manage&act=pdf&mod=list&rcode=4"); // invalid id
  exit;
}
$item = $pdf->getObject($id);
if (!$item) {
  header('location:/' . ADMIN_SCRIPT . "?op=manage&act=pdf&mod=list&rcode=5"); // not found
  exit;
}

$template->assign('id', $id);
$template->assign('item', $item);

$arrayCategories = $pdfCategories->getObjectsForCombo();

$selectedCatId = (int)$request->element('category_id');
if (!$selectedCatId && $item) {
  $selectedCatId = (int)$item->getCategoryId();
}

$categoryCombo = $pdfCategories->generateNestedCombo($arrayCategories, $selectedCatId);
if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

// Submit
if ($_POST && $request->element('doo') == 'submit') {
  $validate = validateData($request);
  if ($validate['invalid']) {
    $template->assign('error', $validate);

    # Category combo box
			$categoryCombo = $pdfCategories->generateNestedCombo($arrayCategories,$request->element('category_id'));
			if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
  } else {
    $status   = (int)$request->element('status', 1);
    $position = (int)$request->element('position', 0);
    $catId = (int)$request->element('category_id', 0);
    $name     = trim($request->element('name', ''));

    $file = isset($_FILES['link_pdf']) ? $_FILES['link_pdf'] : null;
    $newRelPath = ''; // nếu có upload mới
    $oldRelPath = $item->getLinkPdf(); // có thể là '/upload/files/xxx.pdf' hoặc chỉ tên

    # Category combo box
    $categoryCombo = $pdfCategories->generateNestedCombo($arrayCategories,$request->element('category_id'));
    if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
      if ($file['error'] === UPLOAD_ERR_OK) {
        $maxSize = 20 * 1024 * 1024; // 20MB
        if ($file['size'] > $maxSize) {
          $validate['invalid'] = 1;
          $validate['INPUT']['link_pdf']['error']   = 1;
          $validate['INPUT']['link_pdf']['message'] = 'File quá lớn (tối đa 20MB)';
          $template->assign('error', $validate);
        } else {
          $orig = $file['name'];
          $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
          $base = pathinfo($orig, PATHINFO_FILENAME);
          if ($ext !== 'pdf') {
            $validate['invalid'] = 1;
            $validate['INPUT']['link_pdf']['error']   = 1;
            $validate['INPUT']['link_pdf']['message'] = 'Chỉ cho phép PDF';
            $template->assign('error', $validate);
          } else {
            // Tạo tên file: ten-goc_time.pdf
            $safeBase = preg_replace('/[^a-z0-9\-_]/i', '-', strtolower($base));
            $safeBase = preg_replace('/-+/', '-', $safeBase);
            $safeBase = trim($safeBase, '-_');
            if ($safeBase === '') $safeBase = 'file';

            $newName = $safeBase . '_' . time() . '.' . $ext;
            $destDir = ROOT_PATH . 'upload/files/';
            if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
            $absPath = $destDir . $newName;

            if (!move_uploaded_file($file['tmp_name'], $absPath)) {
              $validate['invalid'] = 1;
              $validate['INPUT']['link_pdf']['error']   = 1;
              $validate['INPUT']['link_pdf']['message'] = 'Không thể lưu file PDF';
              $template->assign('error', $validate);
            } else {
              $newRelPath = '/upload/files/' . $newName;
            }
          }
        }
      } else {
        $validate['invalid'] = 1;
        $validate['INPUT']['link_pdf']['error']   = 1;
        $validate['INPUT']['link_pdf']['message'] = 'Upload lỗi (mã ' . $file['error'] . ')';
        $template->assign('error', $validate);
      }
    }

    if (!$validate['invalid']) {
      $data = array(
        'name'         => $name,
        'position'     => $position,
        'status'       => $status,
        'category_id'       => $catId,
        'date_created' => $request->element('date_created') ?: date('Y-m-d H:i:s'),
        'date_updated' => date('Y-m-d H:i:s'),
      );
      // Chỉ set link_pdf nếu có file mới
      if ($newRelPath !== '') {
        $data['link_pdf'] = $newName;

        // Xoá file cũ an toàn (nếu tồn tại)
        if (!empty($oldRelPath)) {
          // Hỗ trợ cả trường hợp DB lưu chỉ tên file
          $oldWebPath = (strpos($oldRelPath, '/upload/') === 0) ? $oldRelPath : ('/upload/files/' . ltrim($oldRelPath, '/'));
          $oldFsPath  = ROOT_PATH . ltrim($oldWebPath, '/');
          if (is_file($oldFsPath)) @unlink($oldFsPath);
        }
      }

      $result = $pdf->updateData($data, $id, 'id');
      if ($result) {
        header('location:/'.ADMIN_SCRIPT."?op=manage&act=pdf&mod=edit&id=$id&lang=$lang&rcode=7");
        exit;
      } else {
        $validate['invalid'] = 1;
        $validate['message'] = 'Không thể cập nhật dữ liệu PDF';
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
