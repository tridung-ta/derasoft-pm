<?php
include_once(ROOT_PATH . 'classes/dao/editorialnewslettersubscribers.class.php');

$token = strtolower(trim((string)$request->element('token')));
$tokenIsValid = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$tokenHash = $tokenIsValid ? hash('sha256', $token) : '';
$subscribers = new EditorialNewsletterSubscribers($storeId);
$subscription = $tokenHash !== '' ? $subscribers->getByUnsubscribeTokenHash($tokenHash) : 0;
$unsubscribeState = 'confirm';

if (!$tokenIsValid || !$subscription) {
    $unsubscribeState = 'invalid';
    http_response_code(404);
} elseif ((int)$subscription['status'] === EditorialNewsletterSubscribers::STATUS_UNSUBSCRIBED) {
    $unsubscribeState = 'done';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$request->element('csrf_token'))) {
        $unsubscribeState = 'csrf';
        http_response_code(403);
    } elseif ($subscribers->unsubscribeByTokenHash($tokenHash)) {
        $unsubscribeState = 'done';
    } else {
        $unsubscribeState = 'error';
        http_response_code(500);
    }
}

$unsubscribePath = $lang === 'vn' ? '/huy-dang-ky' : '/' . $lang . '/unsubscribe';
$template->assign('unsubscribeToken', $tokenIsValid ? $token : '');
$template->assign('unsubscribeState', $unsubscribeState);
$template->assign('unsubscribePath', $unsubscribePath);
$template->assign('urlVi', '/huy-dang-ky?token=' . rawurlencode($token));
$template->assign('urlEn', '/en/unsubscribe?token=' . rawurlencode($token));
$template->assign('urlZh', '/zh/unsubscribe?token=' . rawurlencode($token));
$unsubscribeTitle = $lang === 'en' ? 'Unsubscribe' : ($lang === 'zh' ? '取消订阅' : 'Hủy đăng ký bản tin');
$template->assign('pageTitle', $unsubscribeTitle);
$template->assign('titlePage', $unsubscribeTitle);
$template->assign('pageDescription', $unsubscribeTitle);
$template->assign('pageKeywords', '');
$templateFile = 'editorial-newsletter-unsubscribe.tpl.html';
?>
