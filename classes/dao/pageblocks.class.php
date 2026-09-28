<?php

include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/pageblockinfo.class.php');

class PageBlocks extends Model {

    public $table;
    public $_db;
    private $store_id;

    public function __construct($store_id = 0, $database = '') {
        if(!$database) {
            global $db;
            $this->_db = $db;
        } else $this->_db = $database;
        $this->table = DB_PREFIX."page_blocks";
        $this->store_id = $store_id;
    }
    public function PageBlocks($store_id = 0, $database = '') {
        $this->__construct($store_id, $database);
    }

    public function getObject($value = '0', $key = 'id', $condition = '1>0') {
        if(!$key || !$value) return '';
        $result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
        if($result) {
            $object = new PageBlockInfo(
                $result[0]['id'],
                $result[0]['store_id'],
                $result[0]['menu_id'],
                $result[0]['block_key'],
                $result[0]['block_label'],
                $result[0]['object_ids'],
                $result[0]['sort_order'],
                $result[0]['date_created'],
                $result[0]['date_updated'],
                $result[0]['status'],
                $result[0]['properties']
            );
            return $object;
        }
        return '';
    }

    public function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
        if(!$page) $page = 1;
        $start = ($page - 1) * $items_per_page;
        $results = $this->select('*', "`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
        if($results) {
            $objects = array();
            foreach($results as $result) {
                $objects[] = new PageBlockInfo(
                    $result['id'],
                    $result['store_id'],
                    $result['menu_id'],
                    $result['block_key'],
                    $result['block_label'],
                    $result['object_ids'],
                    $result['sort_order'],
                    $result['date_created'],
                    $result['date_updated'],
                    $result['status'],
                    $result['properties']
                );
            }
            return $objects;
        }
        return '';
    }

    public function addData($fields, $key = 'id') {
        $result = $this->add($fields, $key, 'NULL');
        if($result) return $result;
        return 0;
    }

    public function updateData($fields, $value = '', $key = 'id') {
        $result = $this->update($fields, "`store_id` = '".$this->store_id."' AND `$key` = '$value'");
        if($result) return $result;
        return 0;
    }

    public function changeStatus($id = 0, $status = '') {
        if(!$id) return 0;
        if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
        return 0;
    }

    public function cleanTrash() {
        $result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
        if($result) return 1;
        return 0;
    }

    public function getByPage($menu_id) {
        $rows = $this->getObjects(1,
            "`menu_id` = '$menu_id' AND `status` != ".S_DELETED,
            array('sort_order' => 'ASC'),
            1000
        );

        $result = [];
        foreach ($rows as $block) {
            $result[$block->block_key] = $block;
        }
        return $result;
    }

    public function saveBlock($menu_id, $block_key, $block_label, $object_ids, $sort_order = 0) {
        $existing = $this->getObject($menu_id, 'menu_id', "`block_key` = '$block_key'");
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            return $this->updateData([
                'block_label'  => $block_label,
                'object_ids'   => $object_ids,
                'sort_order'   => $sort_order,
                'date_updated' => $now,
            ], $existing->getId());
        } else {
            return $this->addData([
                'store_id'     => $this->store_id,
                'menu_id'      => $menu_id,
                'block_key'    => $block_key,
                'block_label'  => $block_label,
                'object_ids'   => $object_ids,
                'sort_order'   => $sort_order,
                'date_created' => $now,
                'date_updated' => $now,
                'status'       => 1,
                'properties'   => '',
            ]);
        }
    }

    public function updateBlock($block_id, $data) {
        return $this->updateData([
            'block_key'   => $data['block_key'],
            'block_label' => $data['block_label'],
            'object_ids'  => $data['object_ids'],
            'sort_order'  => (int)$data['sort_order'],
            'date_updated' => date('Y-m-d H:i:s'),
        ], (int)$block_id);
    }

    public function deleteBlock($block_id) {
        return $this->changeStatus((int)$block_id, S_DELETED);
    }

    public function countByPage($menu_id) {
        $result = $this->select('COUNT(*) as total',
            "`store_id` = '".$this->store_id."' AND `menu_id` = '$menu_id' AND `status` != ".S_DELETED);
        return $result ? $result[0]['total'] : 0;
    }
}
?>