<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH.'classes/dao/customergroups.class.php');
include_once(ROOT_PATH.'classes/dao/countries.class.php');
include_once(ROOT_PATH.'classes/dao/areas.class.php');
include_once(ROOT_PATH.'classes/dao/wards.class.php');
include_once(ROOT_PATH.'classes/dao/carts.class.php');
include_once(ROOT_PATH.'classes/dao/cartitems.class.php');
include_once(ROOT_PATH.'includes/functions.inc.php');
include_once(ROOT_PATH.'includes/editorial_mail.inc.php');

$customers = new Customers(1);
$customerGroups = new CustomerGroups(1);
$countries = new Countries(1);
$areas = new Areas(1);
$wards = new Wards(1);
$carts = new Carts(1);
$cartItems = new CartItems();

if (!editorialEmailVerificationRequired()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Xác thực email đang tạm tắt. Bạn có thể đăng nhập trực tiếp.']);
    exit;
}

if (($_POST['op'] ?? '') === 'resend_verify') {

    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        echo json_encode([
            "success" => false,
            "message" => "Email không hợp lệ"
        ]);
        exit;
    }

    $user = $customers->getObject($email, 'email');

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "Không tìm thấy tài khoản"
        ]);
        exit;
    }

    if ($user->getStatus() == 1) {
        echo json_encode([
            "success" => false,
            "message" => "Tài khoản đã được xác thực"
        ]);
        exit;
    }

    $expired = strtotime($user->getVerifyExpiredAt());

    $sentTime = $expired - 86400;

    if ($sentTime > time() - 60) {
        echo json_encode([
            "success" => false,
            "message" => "Vui lòng chờ 60 giây để gửi lại"
        ]);
        exit;
    }

    // tạo token mới
    $token = bin2hex(random_bytes(32));

    $customers->updateData(
        [
            "verify_token" => $token,
            "verify_expired_at" => date("Y-m-d H:i:s", strtotime("+1 day"))
        ],
        $user->getId()
    );

    $link = "https://" . DOMAIN . "/verify-user?token=" . $token;

    sendMail($email, "Xác nhận tài khoản", "
        <p>Click để xác nhận:</p>
        <a href='{$link}'>Xác nhận tài khoản</a>
    ");

    echo json_encode([
        "success" => true,
        "message" => "Đã gửi lại email xác nhận"
    ]);
    exit;
}
