<?php
include_once(ROOT_PATH . 'classes/database/model.class.php');

class EditorialNewsletterSubscribers extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_UNSUBSCRIBED = 2;
    const STATUS_BLOCKED = 3;

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
        $this->table = DB_PREFIX . 'editorial_newsletter_subscribers';
        $this->store_id = max(1, (int)$store_id);
    }

    function normalizeEmail($email)
    {
        return strtolower(trim((string)$email));
    }

    function getByEmail($email)
    {
        $email = $this->normalizeEmail($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 0;
        $rows = $this->select(
            '*',
            '`store_id` = ' . $this->store_id . " AND `email` = '" . addslashes($email) . "'",
            array(),
            0,
            1
        );
        return $rows ? $rows[0] : 0;
    }

    function savePending($email, $language, $source, $confirmationTokenHash, $unsubscribeTokenHash)
    {
        $email = $this->normalizeEmail($email);
        $language = in_array($language, array('vn', 'en', 'zh'), true) ? $language : 'vn';
        $source = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$source));
        $source = $source !== '' ? substr($source, 0, 50) : 'footer';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return array('result' => 'invalid');
        if (!preg_match('/^[a-f0-9]{64}$/', $confirmationTokenHash)) return array('result' => 'invalid');
        if (!preg_match('/^[a-f0-9]{64}$/', $unsubscribeTokenHash)) return array('result' => 'invalid');

        $existing = $this->getByEmail($email);
        if ($existing) {
            $status = (int)$existing['status'];
            if ($status === self::STATUS_BLOCKED) return array('result' => 'blocked', 'id' => (int)$existing['id']);
            if ($status === self::STATUS_ACTIVE) return array('result' => 'active', 'id' => (int)$existing['id']);
            $now = date('Y-m-d H:i:s');
            $updated = $this->update(array(
                'language' => $language,
                'status' => self::STATUS_PENDING,
                'source' => $source,
                'confirmation_token_hash' => $confirmationTokenHash,
                'unsubscribe_token_hash' => $unsubscribeTokenHash,
                'consented_at' => $now,
                'confirmed_at' => null,
                'unsubscribed_at' => null,
                'date_updated' => $now,
            ), '`store_id` = ' . $this->store_id . ' AND `id` = ' . (int)$existing['id']);
            return array('result' => $updated ? 'pending' : 'error', 'id' => (int)$existing['id']);
        }

        $now = date('Y-m-d H:i:s');
        $id = $this->add(array(
            'store_id' => $this->store_id,
            'email' => $email,
            'language' => $language,
            'status' => self::STATUS_PENDING,
            'source' => $source,
            'confirmation_token_hash' => $confirmationTokenHash,
            'unsubscribe_token_hash' => $unsubscribeTokenHash,
            'consented_at' => $now,
            'confirmed_at' => null,
            'unsubscribed_at' => null,
            'date_created' => $now,
            'date_updated' => $now,
        ));
        return array('result' => $id ? 'pending' : 'error', 'id' => (int)$id);
    }

    function unsubscribeByTokenHash($tokenHash)
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $tokenHash)) return 0;
        $now = date('Y-m-d H:i:s');
        return $this->update(
            array(
                'status' => self::STATUS_UNSUBSCRIBED,
                'confirmation_token_hash' => null,
                'unsubscribed_at' => $now,
                'date_updated' => $now,
            ),
            '`store_id` = ' . $this->store_id
                . " AND `unsubscribe_token_hash` = '" . $tokenHash . "'"
                . ' AND `status` IN (' . self::STATUS_PENDING . ',' . self::STATUS_ACTIVE . ')'
        );
    }

    function getByUnsubscribeTokenHash($tokenHash)
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $tokenHash)) return 0;
        $rows = $this->select(
            'id,status',
            '`store_id` = ' . $this->store_id
                . " AND `unsubscribe_token_hash` = '" . $tokenHash . "'",
            array(),
            0,
            1
        );
        return $rows ? $rows[0] : 0;
    }

    function getAdminItems($status = -1, $page = 1, $itemsPerPage = 30)
    {
        $status = (int)$status;
        $page = max(1, (int)$page);
        $itemsPerPage = max(1, min(100, (int)$itemsPerPage));
        $condition = '`store_id` = ' . $this->store_id;
        if (in_array($status, array(0, 1, 2, 3), true)) $condition .= ' AND `status` = ' . $status;
        return $this->select('*', $condition, array('date_created' => 'DESC'), ($page - 1) * $itemsPerPage, $itemsPerPage) ?: array();
    }

    function countAdminItems($status = -1)
    {
        $status = (int)$status;
        $condition = '`store_id` = ' . $this->store_id;
        if (in_array($status, array(0, 1, 2, 3), true)) $condition .= ' AND `status` = ' . $status;
        return (int)$this->countItems('id', $condition);
    }

    function changeStatus($id, $status)
    {
        $id = (int)$id;
        $status = (int)$status;
        if ($id < 1 || !in_array($status, array(0, 1, 2, 3), true)) return 0;
        return $this->update(
            array('status' => $status, 'date_updated' => date('Y-m-d H:i:s')),
            '`store_id` = ' . $this->store_id . ' AND `id` = ' . $id
        );
    }
}
?>
