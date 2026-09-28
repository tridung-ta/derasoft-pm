<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array('RspCode' => '99', 'Message' => 'Invalid method'));
    exit;
}
include_once(ROOT_PATH . 'classes/payment/editorialvnPaypayments.class.php');
$payments = new EditorialVnPayPayments($storeId);
$result = $payments->processIpn($_GET);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
