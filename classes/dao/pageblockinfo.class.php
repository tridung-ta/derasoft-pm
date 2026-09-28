<?php
class PageBlockInfo {
    public $id;
    public $store_id;
	public $menu_id;
    public $block_key;
    public $block_label;
	public $object_ids;
	public $sort_order;
	public $date_created;
	public $date_updated;
	public $status;
	public $properties;
	function __construct ($id = 0, $store_id = 0, $menu_id = 0, $block_key = '', $block_label = '', $object_ids = '', $sort_order = 0, $date_created = '', $date_updated = '', $status = 0, $properties = '')
	{
        $this->id = $id;
        $this->store_id = $store_id;
        $this->menu_id = $menu_id;
        $this->block_key = $block_key;
        $this->block_label = $block_label;
        $this->object_ids = $object_ids;
        $this->sort_order = $sort_order;
        $this->date_created = $date_created;
        $this->date_updated = $date_updated;
        $this->status = $status;
        $this->properties = unserialize($properties);
	}
	public function PageBlockInfo()
	{
		$this->__construct($id = 0, $store_id = 0, $menu_id = 0, $object_ids = '', $sort_order = 0, $date_created = '', $date_updated = '', $status = 0, $properties = '');
	}

    public function getId() {
        return $this->id;
    }

    public function setId($nValue) {
        $this->id = $nValue;
    }

    public function getStoreId() {
        return $this->store_id;
    }

    public function setStoreId($nValue) {
        $this->store_id = $nValue;
    }

    public function getMenuId() {
        return $this->menu_id;
    }

    public function setMenuId($nValue) {
        $this->menu_id = $nValue;
    }

    public function getBlockKey() {
        return $this->block_key;
    }

    public function setBlockKey($nValue) {
        $this->block_key = $nValue;
    }

    public function getBlockLabel() {
        return $this->block_label;
    }

    public function setBlockLabel($nValue) {
        $this->block_label = $nValue;
    }

    public function getObjectIds() {
        return $this->object_ids;
    }

    public function setObjectIds($nValue) {
        $this->object_ids = $nValue;
    }

    public function getSortOrder() {
        return $this->sort_order;
    }

    public function setSortOrder($nValue) {
        $this->sort_order = $nValue;
    }

    public function getDateCreated() {
        return $this->date_created;
    }

    public function setDateCreated($nValue) {
        $this->date_created = $nValue; 
    }

    public function getDateUpdated() {
        return $this->date_updated;
    }

    public function setDateUpdated($nValue) {
        $this->date_updated = $nValue;
    }
	
    public function getStatus() {
        return $this->status;
    }

    public function setStatus($nValue) {
		$this->status=$nValue;
	}
	public function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
    }	
}
?>
