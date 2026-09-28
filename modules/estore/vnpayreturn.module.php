<?php
$templateFile = 'vnpay-return.tpl.html';
include_once(ROOT_PATH . 'classes/payment/vnpaygateway.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialpaymenttransactions.class.php');
$gateway = VnPayGateway::fromEnvironment();
$transactions = new EditorialPaymentTransactions($storeId);
$signatureValid = $gateway->verifySignature($_GET) && $gateway->matchesMerchant($_GET['vnp_TmnCode'] ?? '');
$transaction = $signatureValid ? $transactions->getByTxnRef($_GET['vnp_TxnRef'] ?? '') : 0;
if ($transaction && (int)$transaction['customer_id'] !== (int)$_SESSION['store_customerId']) $transaction = 0;
$reportedSuccess = $signatureValid
    && (string)($_GET['vnp_ResponseCode'] ?? '') === '00'
    && (string)($_GET['vnp_TransactionStatus'] ?? '') === '00';
$template->assign('paymentSignatureValid', $signatureValid);
$template->assign('paymentTransaction', $transaction);
$template->assign('paymentReportedSuccess', $reportedSuccess);
$template->assign('pageTitle', $lang === 'en' ? 'Payment result' : ($lang === 'zh' ? '&#25903;&#20184;&#32467;&#26524;' : 'K&#7871;t qu&#7843; thanh to&#225;n'));
$template->assign('pageDescription', 'VNPay payment result');
$template->assign('titlePage', 'VNPay');
