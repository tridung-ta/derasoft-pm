
<?php

// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
//         ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);
//     }

$templateFile = 'forgetpass.tpl.html';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(ROOT_PATH . 'classes/dao/customers.class.php');
include_once(ROOT_PATH . 'classes/dao/passwordreset.class.php');

$customers = new Customers(1);
$passwordReset = new PasswordReset(1);

$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Quên mật khẩu", "url" => "/quen-mat-khau"],
];
$template->assign('topNav', $topNav);

if ($_POST) {

    $error = [];

    if (!rateLimitByIP('forgot_pass', 5, 300)) {
        $error['rate'] = 'Bạn thao tác quá nhanh, vui lòng thử lại sau';
    }

    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $error['email'] = 'Email không được rỗng';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'Email không hợp lệ';
    } elseif (strlen($email) > 255) {
        $error['email'] = 'Email quá dài';
    }

    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    if (!$recaptchaResponse) {
        $error['captcha'] = 'Vui lòng xác minh Recaptcha';
    } else {
        $secretKey = '6Lfcw3wsAAAAAIn3k2myXVOUk8QmpNACEHdcCE_B';

        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptchaResponse}");
        $captchaData = json_decode($verify, true);

        if (empty($captchaData['success'])) {
            $error['captcha'] = 'Xác minh Recaptcha thất bại';
        }
    }

    if (!$error) {
        $customerId = $customers->getIdFromEmail($email);

        $customerName = $customers->getFullNameFromId($customerId);

        if (!$customerName) {
            $customerName = $email;
        }

        if ($customerId && $passwordReset->canRequest($customerId)) {

            $token = $passwordReset->createToken($customerId);

            $link = "https://" . DOMAIN . "/reset-password?token=" . $token;

            $subject = "Khôi phục mật khẩu";
            $html = "
                <p>Xin chào {$customerName},</p>
                <p>Bạn đã yêu cầu đặt lại mật khẩu.</p>
                <p><a href='{$link}'>Đây là đường dẫn đặt lại mật khẩu</a></p>
                <p>Link hết hạn sau 15 phút.</p>
            ";
            // var_dump($html);die;

            sendMail($email, $subject, $html, 'Digitrust');
            
        }

        $template->assign('success', 
            'Chúng tôi đã gửi link đặt lại mật khẩu, hãy kiểm tra nếu email tồn tại.'
        );

    } else {

        // giống account-manage
        $errorList = array_values($error);
        $template->assign('error', $errorList);

        // giữ lại input
        $template->assign('formData', $_POST);
    }
}

$title = "Quên mật khẩu";

$template->assign('pageKeywords', $title);
$template->assign('pageDescription', $title);
$template->assign('pageTitle', $title);
$template->assign('titlePage', $title);