<?php

// ===== Chỉ trả JSON =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

include_once(ROOT_PATH . 'classes/dao/comments.class.php');
$comments = new Comments(1);

// ===== Lấy dữ liệu =====

$star          = (int) $request->element('star', 0);
$fullname            = trim($request->element('fullname', ''));
$email           = trim($request->element('email', ''));
$tel    = trim($request->element('tel', ''));
$address         = trim($request->element('address', ''));
$details     = trim($request->element('details', ''));
$pid             = (int) $request->element('pid', 0);
$object_name     = trim($request->element('object_name', ''));
$type     = trim($request->element('type', ''));
$recaptcha       = $request->element('g-recaptcha-response', '');

// ===== Validate reCAPTCHA tồn tại =====
if (!$recaptcha) {
    $response['message'] = 'Vui lòng xác nhận bạn không phải robot.';
    echo json_encode($response);
    exit;
}

// ===== Verify với Google =====
$secretKey = "6Ld6z_wsAAAAAO7VyDc108lX6rOfhZLP54D2RYCn";
$verifyURL = "https://www.google.com/recaptcha/api/siteverify";

$postData = http_build_query([
    'secret'   => $secretKey,
    'response' => $recaptcha,
    'remoteip' => $_SERVER['REMOTE_ADDR']
]);

$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => $postData
    ]
];

$context = stream_context_create($opts);
$result  = @file_get_contents($verifyURL, false, $context);
$captchaSuccess = json_decode($result, true);


if (!empty($captchaSuccess['success'])) {    
    // ===== Validate bắt buộc =====
    if (!$fullname || !$tel || !$email) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit;
    }
    
    // ===== Chuẩn bị dữ liệu insert =====
    $data = [
        'fullname'            => $fullname,
        'email'           => $email ?: null,
        'tel'    => $tel,
        'address'         => $address,
        'details'     => $details,
        'star'          => $star,
        'pid'             => $pid,
        'product_name'     => $object_name,
        'type'     => $type,
        'status'          => 0,
        'store_id'       => 1,
    ];
    
    // ===== Insert DB =====
    $ok = $comments->addData($data);

    if ($ok) {
        $response['success'] = true;
        if ($lang == 'vn') {
            $response['message'] = 'Cảm ơn! Đánh giá sẽ hiển thị sau khi được kiểm duyệt.';
        } else {
            $response['message'] = 'Thank you! Your review will be visible once approved.';
        }
    } else {
        if ($lang == 'vn') {
            $response['message'] = 'Không thể lưu đánh giá vào hệ thống.';
        } else {
            $response['message'] = 'Unable to save your review. Please try again later.';
        }
    }
    
    echo json_encode($response);
    exit;
} else {
    $response['message'] = 'Xác minh reCAPTCHA thất bại.';
    echo json_encode($response);
    exit;
}

// ===== Validate bắt buộc =====
if (!$fullname || !$tel || !$email) {
    if ($lang == 'vn') {
        $response['message'] = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } else {
        $response['message'] = 'Please fill in all required fields.';
    }
    echo json_encode($response);
    exit;
}

// ===== Chuẩn bị dữ liệu insert =====
$data = [
    'fullname'            => $fullname,
    'email'           => $email ?: null,
    'tel'    => $tel,
    'address'         => $address,
    'details'     => $details,
    'star'          => $star,
    'pid'             => $pid,
    'product_name'     => $object_name,
    'type'     => $type,
    'status'          => 0,
    'store_id'       => 1,
];

// ===== Insert DB =====
$ok = $comments->addData($data);

if ($ok) {
    $response['success'] = true;
    if ($lang == 'vn') {
        $response['message'] = 'Cảm ơn! Đánh giá sẽ hiển thị sau khi được kiểm duyệt.';
    } else {
        $response['message'] = 'Thank you! Your review will be visible once approved.';
    }
} else {
    if ($lang == 'vn') {
        $response['message'] = 'Không thể lưu đánh giá vào hệ thống.';
    } else {
        $response['message'] = 'Unable to save your review. Please try again later.';
    }
}

echo json_encode($response);
exit;
