<?php
/**
 * Isolated validation smoke test for the editorial payment ledger.
 * Run locally: php cron/editorial-payment-smoke.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('ROOT_PATH', dirname(__DIR__) . '/');
define('DB_PREFIX', 'dc_');
require_once ROOT_PATH . 'classes/dao/editorialpaymenttransactions.class.php';
require_once ROOT_PATH . 'classes/payment/vnpaygateway.class.php';
require_once ROOT_PATH . 'classes/payment/editorialvnPaypayments.class.php';

class EditorialPaymentLedgerFake extends EditorialPaymentTransactions
{
    public $added = array();
    public $updated = array();

    function __construct()
    {
        $this->store_id = 1;
        $this->table = DB_PREFIX . 'editorial_payment_transactions';
    }

    function add($data = '', $pk = 'id', $pkValue = 'NULL')
    {
        $this->added = $data;
        return 42;
    }

    function update($data = '', $condition = '1<0')
    {
        $this->updated = array($data, $condition);
        return 1;
    }
}

$ledger = new EditorialPaymentLedgerFake();
$created = $ledger->createPending(7, 3, 30, 'MEM-TEST', 99000, 'vnd');
if ($created !== 42 || $ledger->added['plan_duration_days'] !== 30 || $ledger->added['amount'] !== '99000.00' || $ledger->added['currency'] !== 'VND') {
    fwrite(STDERR, "FAIL: valid pending transaction\n");
    exit(1);
}
if ($ledger->createPending(0, 3, 30, 'BAD', 99000, 'VND') !== 0 || $ledger->createPending(7, 3, 0, 'BAD', 99000, 'VND') !== 0) {
    fwrite(STDERR, "FAIL: invalid customer accepted\n");
    exit(1);
}
$ledger->updateProviderResult(42, EditorialPaymentTransactions::STATUS_PAID, array(
    'response_code' => '00',
    'ignored' => 'must-not-be-persisted',
));
if (isset($ledger->updated[0]['ignored']) || $ledger->updated[0]['response_code'] !== '00') {
    fwrite(STDERR, "FAIL: provider field allowlist\n");
    exit(1);
}

echo "OK: payment ledger validation and field allowlist\n";

$gateway = new VnPayGateway(
    'TESTCODE',
    'sandbox-secret',
    'https://example.test/thanh-toan/vnpay-return'
);
$createdAt = new DateTimeImmutable('2026-09-24 10:00:00', new DateTimeZone('Asia/Ho_Chi_Minh'));
$paymentUrl = $gateway->buildPaymentUrl('MEM-20260924-1', 99000, 'Thanh toan goi hoi vien', '127.0.0.1', 'vn', $createdAt);
parse_str((string)parse_url($paymentUrl, PHP_URL_QUERY), $signedParams);
if ($paymentUrl === '' || !$gateway->verifySignature($signedParams) || $signedParams['vnp_Amount'] !== '9900000') {
    fwrite(STDERR, "FAIL: VNPay payment signature\n");
    exit(1);
}
$signedParams['vnp_Amount'] = '10000000';
if ($gateway->verifySignature($signedParams)) {
    fwrite(STDERR, "FAIL: tampered VNPay payload accepted\n");
    exit(1);
}
$signedParams['vnp_HashSecret'] = 'must-not-be-saved';
$sanitized = $gateway->sanitizeResponse($signedParams);
if (isset($sanitized['vnp_HashSecret']) || isset($sanitized['vnp_SecureHash'])) {
    fwrite(STDERR, "FAIL: VNPay secret/hash entered response snapshot\n");
    exit(1);
}

echo "OK: VNPay HMAC-SHA512 signing, tamper detection and response filtering\n";

class EditorialPaymentDbFake
{
    public $queries = array();
    function query($sql)
    {
        $this->queries[] = $sql;
        return true;
    }
}

class EditorialPaymentPlansFake
{
    function getById($id)
    {
        return array('id' => (int)$id, 'code' => 'premium-30', 'price' => '99000.00', 'currency' => 'VND', 'duration_days' => 30, 'status' => 1);
    }
}

class EditorialPaymentSubscriptionsFake
{
    public $grantCount = 0;
    function getActiveForCustomer($customerId) { return 0; }
    function grant($customerId, $planId, $startsAt, $endsAt, $source, $grantedBy, $note)
    {
        ++$this->grantCount;
        return 88;
    }
}

class EditorialPaymentTransactionsFake
{
    public $payment;
    function __construct()
    {
        $this->payment = array(
            'id' => 42, 'customer_id' => 7, 'plan_id' => 3, 'plan_duration_days' => 30,
            'amount' => '99000.00', 'status' => EditorialPaymentTransactions::STATUS_PENDING,
        );
    }
    function createPending($customerId, $planId, $duration, $txnRef, $amount, $currency) { return 42; }
    function getByTxnRefForUpdate($txnRef) { return $this->payment; }
    function updateProviderResult($id, $status, $fields)
    {
        $this->payment['status'] = $status;
        return 1;
    }
}

function signVnPaySmokeParams($params, $secret)
{
    ksort($params);
    $parts = array();
    foreach ($params as $key => $value) $parts[] = urlencode($key) . '=' . urlencode($value);
    $params['vnp_SecureHash'] = hash_hmac('sha512', implode('&', $parts), $secret);
    return $params;
}

$paymentDb = new EditorialPaymentDbFake();
$paymentPlans = new EditorialPaymentPlansFake();
$paymentSubscriptions = new EditorialPaymentSubscriptionsFake();
$paymentTransactions = new EditorialPaymentTransactionsFake();
$paymentService = new EditorialVnPayPayments(1, $paymentDb, $paymentPlans, $paymentSubscriptions, $paymentTransactions, $gateway);
$checkout = $paymentService->createCheckout(7, 3, '127.0.0.1', 'vn');
if (empty($checkout['success']) || strpos($checkout['payment_url'], 'vnp_SecureHash=') === false) {
    fwrite(STDERR, "FAIL: VNPay checkout creation\n");
    exit(1);
}
$ipn = signVnPaySmokeParams(array(
    'vnp_Amount' => '9900000', 'vnp_BankCode' => 'NCB', 'vnp_PayDate' => '20260924101500',
    'vnp_ResponseCode' => '00', 'vnp_TmnCode' => 'TESTCODE', 'vnp_TransactionNo' => '123456789',
    'vnp_TransactionStatus' => '00', 'vnp_TxnRef' => $checkout['txn_ref'],
), 'sandbox-secret');
$ipnResult = $paymentService->processIpn($ipn);
if ($ipnResult['RspCode'] !== '00' || $paymentSubscriptions->grantCount !== 1 || $paymentTransactions->payment['status'] !== EditorialPaymentTransactions::STATUS_PAID) {
    fwrite(STDERR, "FAIL: VNPay successful IPN processing\n");
    exit(1);
}
$duplicateResult = $paymentService->processIpn($ipn);
if ($duplicateResult['RspCode'] !== '02' || $paymentSubscriptions->grantCount !== 1) {
    fwrite(STDERR, "FAIL: duplicate VNPay IPN granted access twice\n");
    exit(1);
}

echo "OK: VNPay checkout and idempotent subscription activation\n";
