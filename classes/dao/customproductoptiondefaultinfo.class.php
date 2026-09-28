<?php
# DeraCMS 4.0 Project
# Company: Derasoft Co., Ltd
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025)

class CustomProductOptionDefaultInfo
{
	var $id;
	var $store_id;
	var $name;
	var $value_default;
	var $status;
	function __construct($status, $value_default, $name, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->name = $name;
		$this->value_default = $value_default;
		$this->status = $status;
	}
	public function CustomProductOptionDefaultiInfo( $status, $value_default, $name, $store_id = 0, $id = 0)
	{
		$this->__construct( $status,$value_default, $name, $store_id, $id);
	}


	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getName()
	{
		return $this->name;
	}
	function setName($nValue)
	{
		$this->name = $nValue;
	}
	function getValueDefault()
	{
		return $this->value_default;
	}
	function setValueDefault($nValue)
	{
		$this->value_default = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getStatusText()
	{
		global $amessages;
		return $amessages['status_text'][$this->status];
	}

	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}

	// function getChildren($page = 1, $condition = "`status` = '1'", $sort = array('position' => 'asc'), $items_per_page = 100) {
	// 	include_once(ROOT_PATH."classes/dao/optionobject.class.php");
	// 	$optionObject = new OptionObject($this->store_id);
	// 	$optionObjectItems = $optionObject->getObjects($page,"`parent_id` = '".$this->id."' AND $condition",$sort,items_per_page);
	// 	return $optionObjectItems;
	// }
}
