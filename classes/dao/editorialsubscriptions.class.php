<?php
include_once(ROOT_PATH . 'classes/database/model.class.php');

class EditorialSubscriptions extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_CANCELED = 3;
    const STATUS_REVOKED = 4;

    var $table;
    var $_db;
    var $store_id;
    var $table_available = null;

    function __construct($store_id = 1, $database = '')
    {
        if (!$database) {
            global $db;
            $this->_db = $db;
        } else {
            $this->_db = $database;
        }
        $this->table = DB_PREFIX . 'editorial_subscriptions';
        $this->store_id = max(1, (int)$store_id);
    }

    function tableExists()
    {
        if ($this->table_available !== null) return $this->table_available;
        $result = $this->_db->query("SHOW TABLES LIKE '" . addslashes($this->table) . "'");
        $this->table_available = $result && $this->_db->numRows($result) > 0;
        if ($result) $this->_db->freeResult($result);
        return $this->table_available;
    }

    function getActiveForCustomer($customerId, $at = '')
    {
        if (!$this->tableExists()) return 0;
        $customerId = (int)$customerId;
        if ($customerId < 1) return 0;
        $timestamp = $at !== '' ? strtotime($at) : time();
        if ($timestamp === false) return 0;
        $moment = date('Y-m-d H:i:s', $timestamp);
        $rows = $this->select(
            '*',
            '`store_id` = ' . $this->store_id
                . ' AND `customer_id` = ' . $customerId
                . ' AND `status` = ' . self::STATUS_ACTIVE
                . " AND `starts_at` <= '" . $moment . "'"
                . " AND `ends_at` > '" . $moment . "'",
            array('ends_at' => 'DESC'),
            0,
            1
        );
        return $rows ? $rows[0] : 0;
    }

    function grant($customerId, $planId, $startsAt, $endsAt, $source = 'manual', $grantedBy = 0, $note = '')
    {
        if (!$this->tableExists()) return 0;
        $customerId = (int)$customerId;
        $planId = (int)$planId;
        $start = strtotime((string)$startsAt);
        $end = strtotime((string)$endsAt);
        $allowedSources = array('manual', 'payment', 'promo', 'migration');
        $source = strtolower(trim((string)$source));
        if ($customerId < 1 || $planId < 1 || $start === false || $end === false || $end <= $start) return 0;
        if (!in_array($source, $allowedSources, true)) return 0;
        $now = date('Y-m-d H:i:s');
        return $this->add(array(
            'store_id' => $this->store_id,
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'status' => self::STATUS_ACTIVE,
            'source' => $source,
            'starts_at' => date('Y-m-d H:i:s', $start),
            'ends_at' => date('Y-m-d H:i:s', $end),
            'granted_by' => (int)$grantedBy > 0 ? (int)$grantedBy : null,
            'note' => mb_substr(trim((string)$note), 0, 500),
            'date_created' => $now,
            'date_updated' => $now,
        ));
    }

    function revoke($id)
    {
        if (!$this->tableExists()) return 0;
        $id = (int)$id;
        if ($id < 1) return 0;
        return $this->update(
            array('status' => self::STATUS_REVOKED, 'date_updated' => date('Y-m-d H:i:s')),
            '`store_id` = ' . $this->store_id . ' AND `id` = ' . $id . ' AND `status` IN (0,1)'
        );
    }

    function getAdminItems($status = -1, $limit = 100)
    {
        if (!$this->tableExists()) return array();
        $status = (int)$status;
        $limit = max(1, min(200, (int)$limit));
        $condition = 's.store_id = ' . $this->store_id;
        if (in_array($status, array(0, 1, 2, 3, 4), true)) $condition .= ' AND s.status = ' . $status;
        $sql = 'SELECT s.*, p.code AS plan_code, p.name AS plan_name, c.username, c.email '
            . 'FROM `' . $this->table . '` s '
            . 'LEFT JOIN `' . DB_PREFIX . 'editorial_membership_plans` p ON p.id = s.plan_id AND p.store_id = s.store_id '
            . 'LEFT JOIN `' . DB_PREFIX . 'customers` c ON c.id = s.customer_id AND c.store_id IN (0, s.store_id) '
            . 'WHERE ' . $condition . ' ORDER BY s.date_created DESC, s.id DESC LIMIT ' . $limit;
        return $this->query($sql) ?: array();
    }
}
?>
