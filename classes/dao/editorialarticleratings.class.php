<?php
include_once(ROOT_PATH . 'classes/database/model.class.php');

class EditorialArticleRatings extends Model
{
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
        $this->table = DB_PREFIX . 'editorial_article_ratings';
        $this->store_id = max(1, (int)$store_id);
    }

    function getSummary($articleId)
    {
        $articleId = (int)$articleId;
        if ($articleId < 1) return array('average' => 0, 'count' => 0);
        $rows = $this->select(
            'COUNT(`id`) AS rating_count, AVG(`rating`) AS rating_average',
            '`store_id` = ' . $this->store_id . ' AND `article_id` = ' . $articleId
        );
        if (!$rows) return array('average' => 0, 'count' => 0);
        return array(
            'average' => round((float)$rows[0]['rating_average'], 1),
            'count' => (int)$rows[0]['rating_count'],
        );
    }

    function getMemberRating($articleId, $customerId)
    {
        $articleId = (int)$articleId;
        $customerId = (int)$customerId;
        if ($articleId < 1 || $customerId < 1) return 0;
        $rows = $this->select(
            '`rating`',
            '`store_id` = ' . $this->store_id . ' AND `article_id` = ' . $articleId . ' AND `customer_id` = ' . $customerId,
            array(), 0, 1
        );
        return $rows ? (int)$rows[0]['rating'] : 0;
    }

    function saveMemberRating($articleId, $customerId, $rating)
    {
        $articleId = (int)$articleId;
        $customerId = (int)$customerId;
        $rating = (int)$rating;
        if ($articleId < 1 || $customerId < 1 || $rating < 1 || $rating > 5) return 0;
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `' . $this->table . '` '
            . '(`store_id`, `article_id`, `customer_id`, `rating`, `date_created`, `date_updated`) VALUES ('
            . $this->store_id . ', ' . $articleId . ', ' . $customerId . ', ' . $rating . ", '" . $now . "', '" . $now . "') "
            . 'ON DUPLICATE KEY UPDATE `rating` = VALUES(`rating`), `date_updated` = VALUES(`date_updated`)';
        return $this->_db->query($sql) ? 1 : 0;
    }
}
