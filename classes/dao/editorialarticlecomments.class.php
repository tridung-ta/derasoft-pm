<?php
include_once(ROOT_PATH . 'classes/database/model.class.php');

class EditorialArticleComments extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_DELETED = 3;

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
        $this->table = DB_PREFIX . 'editorial_article_comments';
        $this->store_id = max(1, (int)$store_id);
    }

    function addPending($articleId, $customerId, $content)
    {
        $articleId = (int)$articleId;
        $customerId = (int)$customerId;
        $content = trim((string)$content);
        if ($articleId < 1 || $customerId < 1 || $content === '') return 0;
        $now = date('Y-m-d H:i:s');
        return $this->add(array(
            'store_id' => $this->store_id,
            'article_id' => $articleId,
            'customer_id' => $customerId,
            'content' => $content,
            'status' => self::STATUS_PENDING,
            'date_created' => $now,
            'date_updated' => $now,
        ));
    }

    function getApproved($articleId, $page = 1, $itemsPerPage = 10)
    {
        $articleId = (int)$articleId;
        $page = max(1, (int)$page);
        $itemsPerPage = max(1, min(50, (int)$itemsPerPage));
        if ($articleId < 1) return array();
        $start = ($page - 1) * $itemsPerPage;
        $condition = '`store_id` = ' . $this->store_id
            . ' AND `article_id` = ' . $articleId
            . ' AND `status` = ' . self::STATUS_APPROVED;
        $rows = $this->select('*', $condition, array('date_created' => 'DESC'), $start, $itemsPerPage);
        return $rows ? $rows : array();
    }

    function countApproved($articleId)
    {
        $articleId = (int)$articleId;
        if ($articleId < 1) return 0;
        return (int)$this->countItems(
            'id',
            '`store_id` = ' . $this->store_id
            . ' AND `article_id` = ' . $articleId
            . ' AND `status` = ' . self::STATUS_APPROVED
        );
    }

    function countRecentByCustomer($customerId, $minutes = 10)
    {
        $customerId = (int)$customerId;
        $minutes = max(1, min(60, (int)$minutes));
        if ($customerId < 1) return 0;
        return (int)$this->countItems(
            'id',
            '`store_id` = ' . $this->store_id
            . ' AND `customer_id` = ' . $customerId
            . ' AND `date_created` >= DATE_SUB(NOW(), INTERVAL ' . $minutes . ' MINUTE)'
        );
    }

    function getModerationItems($status = -1, $page = 1, $itemsPerPage = 20)
    {
        $status = (int)$status;
        $page = max(1, (int)$page);
        $itemsPerPage = max(1, min(100, (int)$itemsPerPage));
        $start = ($page - 1) * $itemsPerPage;
        $condition = '`store_id` = ' . $this->store_id;
        if (in_array($status, array(0, 1, 2, 3), true)) $condition .= ' AND `status` = ' . $status;
        $rows = $this->select('*', $condition, array('date_created' => 'DESC'), $start, $itemsPerPage);
        return $rows ? $rows : array();
    }

    function countModerationItems($status = -1)
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
