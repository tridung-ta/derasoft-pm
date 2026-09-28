<?php
$templateFile = 'verifyuser.tpl.html';

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

// breadcrumb
$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Xác nhận đăng ký", "url" => "/verify-user?token={$token}"],
];
$template->assign('topNav', $topNav);

$user = $customers->getObject($token, 'verify_token');

if (!$user) {
    $template->assign('error', ['Token không hợp lệ hoặc đã dùng']);
    return;
}

if (strtotime($user->getVerifyExpiredAt()) < time()) {
    $template->assign('error', ['Token đã hết hạn']);
    return;
}

if ($user->getStatus()) {
    $template->assign('success', 'Tài khoản đã được xác nhận trước đó');
    return;
}

$customers->updateData(
    [
        "status" => 1,
        "verify_token" => null,
        "verify_expired_at" => null
    ],
    $user->getId()
);

$template->assign('success', 'Xác nhận thành công! Bạn có thể đăng nhập.');

$title = "Xác nhận đăng ký";
$template->assign('pageKeywords', $title);
$template->assign('pageDescription', $title);
$template->assign('pageTitle', $title);
$template->assign('titlePage', $title);