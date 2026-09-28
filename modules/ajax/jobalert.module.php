<?php

if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

  header('Content-Type: application/json; charset=utf-8');

  include_once(ROOT_PATH.'classes/dao/comments.class.php');
  $comments = new Comments(1);

  $fullname = trim($_POST['fullname'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $token    = $_POST['g-recaptcha-response'] ?? '';

  // Validate
  if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok'=>false,'error'=>'invalid_input']); exit;}
  if ($token === '') { echo json_encode(['ok'=>false,'error'=>'captcha_missing']); exit; }

  // Verify reCAPTCHA
  $secret = getenv('RECAPTCHA_SECRET_KEY') ?: '';
  if ($secret === '') { echo json_encode(['ok'=>false,'error'=>'captcha_secret_missing']); exit; }

  $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
  $postData  = http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']);

  $resp = null;
  if (function_exists('curl_init')) {
    $ch = curl_init($verifyUrl);
    curl_setopt_array($ch, [
      CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$postData,
      CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
  } else if (ini_get('allow_url_fopen')) {
    $ctx  = stream_context_create(['http'=>[
      'method'=>'POST','header'=>"Content-type: application/x-www-form-urlencoded\r\n",
      'content'=>$postData,'timeout'=>5
    ]]);
    $resp = file_get_contents($verifyUrl, false, $ctx);
  }
  $data = json_decode($resp ?: '', true);
  if (empty($data['success'])) { echo json_encode(['ok'=>false,'error'=>'captcha_fail']); exit; }

  # DUPLICATE EMAIL 
    $email = strtolower($email);
    $condition = "`store_id` = 1 AND `email` = '".addslashes($email)."' AND `details` = 'job_alert' AND `pid` = 0";

    $rows = $comments->select('id', $condition, [], 0, 1); // lấy tối đa 1 dòng

    if ($rows && count($rows) > 0) { echo json_encode(['ok'=>false,'error'=>'duplicate']); exit;}

  $newId = $comments->addData([
    'store_id' => 1,
    'fullname' => $fullname,
    'email'    => $email,
    'details'  => 'job_alert', 
    'created'  => date('Y-m-d H:i:s'),
    'status'   => 1,
    'pid'      => 0,
  ]);

  if (!$newId) { echo json_encode(['ok'=>false,'error'=>'db']); exit; }

  echo json_encode(['ok'=>true]); exit;
