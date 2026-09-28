<?php
# DeraCMS 4.0 Project
# Company: derasoft
# Coder: Tien Le
# Revied by: Mai Minh (03/06/2025)

class OptionObjectInfo
{
	var $id;
	var $parent_id;		
	var $store_id;
	var $name;
	var $status;
	function __construct($status, $name, $store_id = 0, $parent_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->parent_id = $parent_id;
		$this->store_id = $store_id;
		$this->name = $name;
		$this->status = $status;
	}
	public function OptionObjectInfo( $status, $name, $store_id = 0, $parent_id = 0, $id = 0)
	{
		$this->__construct( $status, $name, $store_id, $parent_id, $id);
	}

	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getParentId() {
		return $this->parent_id;
	}
	function setParentId($nValue) {
		$this->parent_id=$nValue;
	}
	function getName()
	{
		return $this->name;
	}
	function setName($nValue)
	{
		$this->name = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	public function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}

	function getChildren($page = 1, $condition = "`status` = '1'", $sort = array('position' => 'asc'), $items_per_page = 100) {
		include_once(ROOT_PATH."classes/dao/optionobject.class.php");
		$optionObject = new OptionObject($this->store_id);
		$optionObjectItems = $optionObject->getObjects($page,"`parent_id` = '".$this->id."' AND $condition",$sort,items_per_page);
		return $optionObjectItems;
	}
}
