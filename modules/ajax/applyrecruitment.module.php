<?php

if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (!defined('ROOT_PATH')) exit;
header('Content-Type: application/json; charset=utf-8');

include_once(ROOT_PATH . 'classes/dao/recruitment.class.php');

$recruitments = new Recruitments(1);

$email = trim($_POST['email'] ?? '');
$slug  = trim($_POST['slug'] ?? '');
$rid   = (int)($_POST['recruitment_id'] ?? 0);

// --- reCAPTCHA v2 verify ---
$token = $_POST['g-recaptcha-response'] ?? '';
if (!$token) { echo json_encode(['ok'=>false,'error'=>'captcha_missing']); exit; }

$secret = getenv('RECAPTCHA_SECRET_KEY') ?: '';
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query([
    'secret'   => $secret,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
  ]),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 5,
]);
$resp = curl_exec($ch);
curl_close($ch);

$data = json_decode($resp ?: '', true);
if (empty($data['success'])) {
  echo json_encode(['ok'=>false,'error'=>'captcha_fail']); exit;
}

// validate
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok'=>false,'error'=>'invalid_email']); exit; }
if (empty($_FILES['cv']['name']) || ($_FILES['cv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
  echo json_encode(['ok'=>false,'error'=>'nofile']); exit;
}

// lấy tin TD cha theo id hoặc slug
$parent = $rid ? $recruitments->getObject($rid, 'id') : ($slug ? $recruitments->getObject($slug, 'slug') : '');
if (!$parent) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

// --- DUPLICATE EMAIL (per recruitment) ---
$email = strtolower($email); // đồng bộ format khi lưu
if ($recruitments->checkDuplicateEmail($email, (int)$parent->getId())) {
  echo json_encode(['ok'=>false,'error'=>'duplicate']); exit;
}

// lưu file
$dir = rtrim(ROOT_PATH, '/\\') . '/upload/fileCV/';
if (!is_dir($dir)) @mkdir($dir, 0775, true);

$ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf','doc','docx','png','jpg','jpeg'], true)) { echo json_encode(['ok'=>false,'error'=>'type']); exit; }

$base  = preg_replace('/[^a-z0-9\._-]/i','_', pathinfo($_FILES['cv']['name'], PATHINFO_FILENAME));
$fname = date('Ymd_His').'_'.$base.'.'.$ext;

if (!move_uploaded_file($_FILES['cv']['tmp_name'], $dir.$fname)) {
  echo json_encode(['ok'=>false,'error'=>'upload']); exit;
}

// thêm bản ghi con (application)
$ok = $recruitments->addData([
  'store_id'       => 1,
  'parent_id'      => (int)$parent->getId(),
  'name'           => $parent->getName(),
  'slug'           => 'apply-'.$parent->getSlug().'-'.time(),
  'mail'           => $email,
  'file'           => $fname,
  'detail'         => '',
  'properties'     => serialize(['source' => 'career_form']),
  'status'         => 1,
  'date_created'   => date('Y-m-d H:i:s'),
  'income' => '', 
  'degree' => '', 
  'experience' => '', 
  'rank' => '',
  'location' => '', 
  'number_recruits' => 0, 
  'job_location' => '',
  'age' => '', 
  'gender' => '', 
  'date_exp' => date('Y-m-d H:i:s'),
]);

if (!$ok) { @unlink($dir.$fname); echo json_encode(['ok'=>false,'error'=>'db']); exit; }

echo json_encode(['ok'=>true]);
exit;
