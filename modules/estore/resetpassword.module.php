<?php
$templateFile = 'resetpassword.tpl.html';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(ROOT_PATH . 'classes/dao/customers.class.php');
include_once(ROOT_PATH . 'classes/dao/passwordreset.class.php');

$customers = new Customers(1);
$passwordReset = new PasswordReset(1);

$token = $_GET['token'] ?? '';

if (!$token) {
    header("Location: /404.html");
    exit;
}

$data = $passwordReset->getValidToken($token);
if (!$data) {
    die("Link không hợp lệ hoặc đã hết hạn");
}

$customerId = (int)$data['customer_id'];

// breadcrumb
$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Đặt lại mật khẩu", "url" => "/reset-password?token={$token}"],
];
$template->assign('topNav', $topNav);

if ($_POST) {

    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');
    $error = [];

    if (!$password || strlen($password) < 6) {
        $error[] = "Mật khẩu tối thiểu 6 ký tự";
    }
    if ($password !== $confirm) {
        $error[] = "Mật khẩu không khớp";
    }

    if (!$error) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $customers->updateData([
            'password' => $hashedPassword
        ], $customerId);

        // xóa token sau khi dùng
        $passwordReset->deleteByCustomer($customerId);

        // toast success
        $template->assign('success', 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập ngay.');

        // chống submit lại
        $_POST = [];
    } else {
        // toast error
        $template->assign('error', $error);
    }
}

$title = "Đặt lại mật khẩu";
$template->assign('pageKeywords', $title);
$template->assign('pageDescription', $title);
$template->assign('pageTitle', $title);
$template->assign('titlePage', $title);