<?php
include_once(ROOT_PATH . 'classes/database/model.class.php');

class EditorialPaymentTransactions extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_PAID = 1;
    const STATUS_FAILED = 2;
    const STATUS_CANCELED = 3;

    var $table;
    var $_db;
    var $store_id;

    function __construct($store_id = 1, $database = '')
    {
        if (!$database) {
            global $db;
            $this->_db = $db;
        } else {
            $this->_db = $database;
        }
        $this->table = DB_PREFIX . 'editorial_payment_transactions';
        $this->store_id = max(1, (int)$store_id);
    }

    function createPending($customerId, $planId, $planDurationDays, $txnRef, $amount, $currency = 'VND')
    {
        $customerId = (int)$customerId;
        $planId = (int)$planId;
        $planDurationDays = (int)$planDurationDays;
        $txnRef = trim((string)$txnRef);
        $currency = strtoupper(trim((string)$currency));
        $amount = round((float)$amount, 2);
        if ($customerId < 1 || $planId < 1 || $planDurationDays < 1 || $planDurationDays > 3650 || $txnRef === '' || strlen($txnRef) > 100) return 0;
        if ($amount <= 0 || !preg_match('/^[A-Z]{3}$/', $currency)) return 0;
        $now = date('Y-m-d H:i:s');
        return $this->add(array(
            'store_id' => $this->store_id,
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'plan_duration_days' => $planDurationDays,
            'provider' => 'vnpay',
            'txn_ref' => $txnRef,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'status' => self::STATUS_PENDING,
            'date_created' => $now,
            'date_updated' => $now,
        ));
    }

    function getByTxnRef($txnRef)
    {
        $txnRef = trim((string)$txnRef);
        if ($txnRef === '' || strlen($txnRef) > 100) return 0;
        $safeRef = addslashes($txnRef);
        $rows = $this->select(
            '*',
            '`store_id` = ' . $this->store_id . " AND `provider` = 'vnpay' AND `txn_ref` = '" . $safeRef . "'",
            array(),
            0,
            1
        );
        return $rows ? $rows[0] : 0;
    }

    function getByTxnRefForUpdate($txnRef)
    {
        $txnRef = trim((string)$txnRef);
        if ($txnRef === '' || strlen($txnRef) > 100) return 0;
        $safeRef = addslashes($txnRef);
        $rows = $this->query(
            'SELECT * FROM `' . $this->table . '` WHERE `store_id` = ' . $this->store_id
            . " AND `provider` = 'vnpay' AND `txn_ref` = '" . $safeRef . "' LIMIT 1 FOR UPDATE"
        );
        return $rows ? $rows[0] : 0;
    }

    function updateProviderResult($id, $status, $fields = array())
    {
        $id = (int)$id;
        $status = (int)$status;
        if ($id < 1 || !in_array($status, array(self::STATUS_PAID, self::STATUS_FAILED, self::STATUS_CANCELED), true)) return 0;
        $allowed = array('provider_transaction_no', 'response_code', 'transaction_status', 'bank_code', 'pay_date', 'response_snapshot', 'processed_at', 'subscription_id');
        $data = array('status' => $status, 'date_updated' => date('Y-m-d H:i:s'));
        foreach ($allowed as $field) {
            if (array_key_exists($field, $fields)) $data[$field] = $fields[$field];
        }
        return $this->update(
            $data,
            '`store_id` = ' . $this->store_id . ' AND `id` = ' . $id . ' AND `status` = ' . self::STATUS_PENDING
        );
    }
}
?>
