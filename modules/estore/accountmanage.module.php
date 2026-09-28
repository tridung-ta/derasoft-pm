<?php
$templateFile = 'account-manage.tpl.html';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH.'classes/dao/customergroups.class.php');
include_once(ROOT_PATH.'classes/dao/countries.class.php');
include_once(ROOT_PATH.'classes/dao/areas.class.php');
include_once(ROOT_PATH.'classes/dao/wards.class.php');

$customers = new Customers(1);
$customerGroups = new CustomerGroups(1);
$countries = new Countries(1);
$areas = new Areas(1);
$wards = new Wards(1);


$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Quản lý tài khoản", "url" => "/quan-li-tai-khoan"],
];
if ($topNav) $template->assign('topNav', $topNav);

$CustomerId = $_SESSION["store_customerId"] ?? 0;

if (!$CustomerId) {
    header("Location: /dang-nhap");
    exit;
}

$customerInfo = $customers->getObject($CustomerId);

if (!$customerInfo) {
    header("Location: /dang-nhap");
    exit;
}

$template->assign('customerInfo', $customerInfo);

if ($_POST) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $company = trim($_POST['company'] ?? '');

    $error = [];

    // if (!$email) $error['email'] = 'Email không được rỗng';
    // if (!$tel) $error['tel'] = 'Số điện thoại không được rỗng';
    // if (!$fullname) $error['fullname'] = 'Tên không được rỗng';

    // ===== FULLNAME =====
    if (!$fullname) {
        $error['fullname'] = 'Tên không được rỗng';
    } elseif (strlen($fullname) < 2) {
        $error['fullname'] = 'Tên tối thiểu 2 ký tự';
    } elseif (strlen($fullname) > 100) {
        $error['fullname'] = 'Tên quá dài';
    }

    // ===== EMAIL =====
    if (!$email) {
        $error['email'] = 'Email không được rỗng';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'Email không hợp lệ';
    } elseif (strlen($email) > 255) {
        $error['email'] = 'Email quá dài';
    } else {
        // check trùng (trừ chính nó)
        if ($email !== $currentEmail) {
        if ($customers->checkDuplicate($email, 'email', "id != $CustomerId")) {
            $error['email'] = 'Email đã tồn tại';
        }
    }
    }

    // ===== PHONE =====
    if (!$tel) {
        $error['tel'] = 'Số điện thoại không được rỗng';
    } elseif (!preg_match('/^(0|\+84)[0-9]{9}$/', $tel)) {
        $error['tel'] = 'Số điện thoại không hợp lệ';
    }

    // ===== ADDRESS =====
    if ($address && strlen($address) > 255) {
        $error['address'] = 'Địa chỉ quá dài';
    }

    // ===== COMPANY =====
    if ($company && strlen($company) > 255) {
        $error['company'] = 'Tên công ty quá dài';
    }

    if (!$error) {
        $data = [
            'fullname' => $fullname,
            'email' => $email,
            'tel' => $tel,
            'address' => $address,
            'company' => $company,
            'date_updated' => date("Y-m-d H:i:s")
        ];

        $customers->updateData($data, $CustomerId);
        $template->assign('success', 'Cập nhật thành công');
        $customerInfo = $customers->getObject($CustomerId);
        $template->assign('customerInfo', $customerInfo);
    } else {
        $errorList = array_values($error);
        $template->assign('error', $errorList);
        // giữ lại dữ liệu vừa nhập
        $template->assign('formData', $_POST);
    }

    $template->assign('customerInfo', $customerInfo);
}

if (isset($_GET['success'])) {
    $template->assign('success', 'Cập nhật thành công');
}

$accManage = "Quản lý tài khoản";

$pageKeywords = $accManage;
$pageDescription = $accManage;
$pageTitle = $accManage;

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);