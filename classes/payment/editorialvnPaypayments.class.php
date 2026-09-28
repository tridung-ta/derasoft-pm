<?php
include_once(ROOT_PATH . 'classes/dao/editorialmembershipplans.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialsubscriptions.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialpaymenttransactions.class.php');
include_once(ROOT_PATH . 'classes/payment/vnpaygateway.class.php');

class EditorialVnPayPayments
{
    var $store_id;
    var $db;
    var $plans;
    var $subscriptions;
    var $transactions;
    var $gateway;

    function __construct($storeId = 1, $database = '', $plans = null, $subscriptions = null, $transactions = null, $gateway = null)
    {
        global $db;
        $this->store_id = max(1, (int)$storeId);
        $this->db = $database ?: $db;
        $this->plans = $plans ?: new EditorialMembershipPlans($this->store_id, $this->db);
        $this->subscriptions = $subscriptions ?: new EditorialSubscriptions($this->store_id, $this->db);
        $this->transactions = $transactions ?: new EditorialPaymentTransactions($this->store_id, $this->db);
        $this->gateway = $gateway ?: VnPayGateway::fromEnvironment();
    }

    function createCheckout($customerId, $planId, $ipAddress, $locale = 'vn')
    {
        $customerId = (int)$customerId;
        $plan = $this->plans->getById((int)$planId);
        if ($customerId < 1) return array('success' => false, 'error' => 'login_required');
        if (!$this->gateway->isConfigured()) return array('success' => false, 'error' => 'not_configured');
        if (!$plan || (int)$plan['status'] !== EditorialMembershipPlans::STATUS_ACTIVE) return array('success' => false, 'error' => 'invalid_plan');
        if (strtoupper((string)$plan['currency']) !== 'VND' || (float)$plan['price'] <= 0) return array('success' => false, 'error' => 'invalid_price');

        $txnRef = 'M' . date('YmdHis') . strtoupper(bin2hex(random_bytes(6)));
        $transactionId = $this->transactions->createPending(
            $customerId,
            (int)$plan['id'],
            (int)$plan['duration_days'],
            $txnRef,
            (float)$plan['price'],
            'VND'
        );
        if (!$transactionId) return array('success' => false, 'error' => 'save_failed');

        $orderInfo = 'Thanh toan goi hoi vien ' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$plan['code']);
        $paymentUrl = $this->gateway->buildPaymentUrl($txnRef, (float)$plan['price'], $orderInfo, $ipAddress, $locale);
        if ($paymentUrl === '') return array('success' => false, 'error' => 'url_failed');
        return array('success' => true, 'transaction_id' => (int)$transactionId, 'txn_ref' => $txnRef, 'payment_url' => $paymentUrl);
    }

    function processIpn($params)
    {
        if (!$this->gateway->verifySignature($params) || !$this->gateway->matchesMerchant($params['vnp_TmnCode'] ?? '')) {
            return array('RspCode' => '97', 'Message' => 'Invalid signature');
        }
        $txnRef = trim((string)($params['vnp_TxnRef'] ?? ''));
        $amountRaw = (string)($params['vnp_Amount'] ?? '');
        if ($txnRef === '' || !preg_match('/^\d{1,12}$/', $amountRaw)) {
            return array('RspCode' => '99', 'Message' => 'Invalid request');
        }

        if (!$this->db->query('START TRANSACTION')) return array('RspCode' => '99', 'Message' => 'Transaction error');
        try {
            $payment = $this->transactions->getByTxnRefForUpdate($txnRef);
            if (!$payment) {
                $this->db->query('ROLLBACK');
                return array('RspCode' => '01', 'Message' => 'Order not found');
            }
            if ((int)$payment['status'] !== EditorialPaymentTransactions::STATUS_PENDING) {
                $this->db->query('ROLLBACK');
                return array('RspCode' => '02', 'Message' => 'Order already confirmed');
            }
            $expectedAmount = (int)round((float)$payment['amount'] * 100);
            if ((int)$amountRaw !== $expectedAmount) {
                $this->db->query('ROLLBACK');
                return array('RspCode' => '04', 'Message' => 'Invalid amount');
            }

            $responseCode = trim((string)($params['vnp_ResponseCode'] ?? ''));
            $transactionStatus = trim((string)($params['vnp_TransactionStatus'] ?? ''));
            $isPaid = $responseCode === '00' && $transactionStatus === '00';
            $providerTransactionNo = trim((string)($params['vnp_TransactionNo'] ?? ''));
            if ($isPaid && !preg_match('/^[1-9]\d{0,14}$/', $providerTransactionNo)) {
                $this->db->query('ROLLBACK');
                return array('RspCode' => '99', 'Message' => 'Invalid transaction number');
            }
            $snapshot = json_encode($this->gateway->sanitizeResponse($params), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $payDate = $this->parsePayDate($params['vnp_PayDate'] ?? '');
            $resultFields = array(
                'provider_transaction_no' => $isPaid ? mb_substr($providerTransactionNo, 0, 100) : null,
                'response_code' => mb_substr($responseCode, 0, 10),
                'transaction_status' => mb_substr($transactionStatus, 0, 10),
                'bank_code' => mb_substr(trim((string)($params['vnp_BankCode'] ?? '')), 0, 30),
                'pay_date' => $payDate,
                'response_snapshot' => $snapshot ?: '{}',
                'processed_at' => date('Y-m-d H:i:s'),
            );

            if (!$isPaid) {
                $status = $responseCode === '24' ? EditorialPaymentTransactions::STATUS_CANCELED : EditorialPaymentTransactions::STATUS_FAILED;
                if (!$this->transactions->updateProviderResult((int)$payment['id'], $status, $resultFields)) throw new RuntimeException('Payment result update failed');
                if (!$this->db->query('COMMIT')) throw new RuntimeException('Commit failed');
                return array('RspCode' => '00', 'Message' => 'Confirm Success');
            }

            $durationDays = (int)$payment['plan_duration_days'];
            if ($durationDays < 1 || $durationDays > 3650) throw new RuntimeException('Invalid duration snapshot');
            $customerId = (int)$payment['customer_id'];
            $customerLock = $this->db->query(
                'SELECT `id` FROM `' . DB_PREFIX . 'customers` WHERE `id` = ' . $customerId
                . ' AND `store_id` IN (0,' . $this->store_id . ') LIMIT 1 FOR UPDATE'
            );
            if (!$customerLock) throw new RuntimeException('Customer lock failed');
            $active = $this->subscriptions->getActiveForCustomer($customerId);
            $startTimestamp = time();
            if ($active && strtotime((string)$active['ends_at']) > $startTimestamp) $startTimestamp = strtotime((string)$active['ends_at']);
            $startsAt = date('Y-m-d H:i:s', $startTimestamp);
            $endsAt = date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days', $startTimestamp));
            $subscriptionId = $this->subscriptions->grant(
                $customerId,
                (int)$payment['plan_id'],
                $startsAt,
                $endsAt,
                'payment',
                0,
                'VNPay ' . $txnRef
            );
            if (!$subscriptionId) throw new RuntimeException('Subscription grant failed');
            $resultFields['subscription_id'] = (int)$subscriptionId;
            if (!$this->transactions->updateProviderResult((int)$payment['id'], EditorialPaymentTransactions::STATUS_PAID, $resultFields)) {
                throw new RuntimeException('Paid result update failed');
            }
            if (!$this->db->query('COMMIT')) throw new RuntimeException('Commit failed');
            return array('RspCode' => '00', 'Message' => 'Confirm Success');
        } catch (Throwable $error) {
            $this->db->query('ROLLBACK');
            return array('RspCode' => '99', 'Message' => 'Unknown error');
        }
    }

    private function parsePayDate($value)
    {
        $value = trim((string)$value);
        if (!preg_match('/^\d{14}$/', $value)) return null;
        $date = DateTimeImmutable::createFromFormat('!YmdHis', $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
        return $date ? $date->format('Y-m-d H:i:s') : null;
    }
}
?>
