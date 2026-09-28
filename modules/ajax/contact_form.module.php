<?php

// ===== Chỉ trả JSON =====
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

include_once(ROOT_PATH . 'classes/dao/contacts.class.php');
$contacts = new Contacts(1);

// ===== Lấy dữ liệu =====
$name            = trim($request->element('name', ''));
$email           = trim($request->element('email', ''));
$phone_number    = trim($request->element('phone_number', ''));
$address         = trim($request->element('address', ''));
$description         = trim($request->element('description', ''));
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
    if (!$name || !$phone_number || !$email) {
        $response['message'] = 'Thiếu thông tin bắt buộc.';
        echo json_encode($response);
        exit;
    }
    
    // ===== Chuẩn bị dữ liệu insert =====
    $data = [
        'name'            => $name,
        'email'           => $email ?: null,
        'phone_number'    => $phone_number,
        'address'         => $address,
        'description'     => $description,
        'status'          => 0,
    ];
    
    // ===== Insert DB =====
    $ok = $contacts->addData($data);
    
    if ($ok) {
        $response['success'] = true;
        $response['message'] = 'Gửi yêu cầu thành công. Chúng tôi sẽ liên hệ với bạn sớm nhất có thể.';
    } else {
        $response['message'] = 'Không thể lưu dữ liệu vào hệ thống.';
    }
    
    echo json_encode($response);
    exit;
} else {
    $response['message'] = 'Xác minh reCAPTCHA thất bại.';
    echo json_encode($response);
    exit;
}

// ===== Validate bắt buộc =====
if (!$name || !$phone_number || !$email) {
    $response['message'] = 'Thiếu thông tin bắt buộc.';
    echo json_encode($response);
    exit;
}

// ===== Chuẩn bị dữ liệu insert =====
$data = [
    'name'            => $name,
    'email'           => $email ?: null,
    'phone_number'    => $phone_number,
    'address'         => $address,
    'description'     => $description,
    'status'          => 1,
];

// ===== Insert DB =====
$ok = $contacts->addData($data);

if ($ok) {
    $response['success'] = true;
    $response['message'] = 'Gửi yêu cầu thành công. Chúng tôi sẽ liên hệ với bạn sớm nhất có thể.';
} else {
    $response['message'] = 'Không thể lưu dữ liệu vào hệ thống.';
}

echo json_encode($response);
exit;
