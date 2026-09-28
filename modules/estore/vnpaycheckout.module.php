<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
$customerId = !empty($_SESSION['store_customerId']) ? (int)$_SESSION['store_customerId'] : 0;
$csrfToken = (string)$request->element('csrf_token');
if ($customerId < 1 || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    exit;
}
$attempts = isset($_SESSION['editorial_vnpay_checkout_attempts']) && is_array($_SESSION['editorial_vnpay_checkout_attempts'])
    ? $_SESSION['editorial_vnpay_checkout_attempts'] : array();
$cutoff = time() - 600;
$attempts = array_values(array_filter($attempts, function ($timestamp) use ($cutoff) { return (int)$timestamp >= $cutoff; }));
if (count($attempts) >= 5) {
    header('Location: ' . $readingHubPath . '?payment_error=rate#membership');
    exit;
}
$attempts[] = time();
$_SESSION['editorial_vnpay_checkout_attempts'] = $attempts;

include_once(ROOT_PATH . 'classes/payment/editorialvnPaypayments.class.php');
$payments = new EditorialVnPayPayments($storeId);
$result = $payments->createCheckout(
    $customerId,
    (int)$request->element('plan_id'),
    isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',
    $lang === 'en' ? 'en' : 'vn'
);
if (empty($result['success'])) {
    $error = preg_replace('/[^a-z_]/', '', (string)($result['error'] ?? 'unknown'));
    header('Location: ' . $readingHubPath . '?payment_error=' . rawurlencode($error) . '#membership');
    exit;
}
header('Location: ' . $result['payment_url'], true, 303);
exit;
