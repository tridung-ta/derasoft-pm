<?php
/*************************************************************************
Class MenuGroupInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd 
Update: 22/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
class MenuCategoryInfo {
	var $mcId;			# Primary key
	var $name;			# Vietnamese name
	var $status;		# Status
	var $store_id;	
	var $properties;
	
	function __construct($name, $status, $properties, $store_id, $mcId = 0)
	{
		$this->mcId = $mcId;	
		$this->store_id = $store_id;
		$this->name = $name;
		$this->status = $status;
		$this->properties = unserialize($properties);
	}

	function getId() {
		return $this->mcId;
	}	
	function setId($nValue) {
		$this->mcId=$nValue;
	}
	
	function getProperty($key)
	{
		if(isset($this->properties['0'][$id][$key])) return ''.$this->properties['0'][$id][$key];
		return '';
	}
	function getProperties()
	{
		return $this->properties;
	}
	
	function setProperties($nValue)
	{
		$this->properties=$nValue;
	}
	
	function getStoreId() {
		return $this->store_id;
	}	
	
	function StoreId($nValue) {
		$this->store_id=$nValue;
	}
	
	function getActiveMenus() {
		include_once(ROOT_PATH."classes/dao/menus.class.php");
		$menus = new Menus($this->store_id);
		$rowsPages = $menus->getNumItems('id', "`mgid` = '".$this->id."' AND `status` = '1'");
		return $rowsPages['rows'];
	}	

	function getNumMenus() {
		include_once(ROOT_PATH."classes/dao/menus.class.php");
		$menus = new Menus($this->store_id);
		$rowsPages = $menus->getNumItems('id', "`mgid` = '".$this->id."'");
		return $rowsPages['rows'];
	}

	function getName() {
		return $this->name;		
	}
	function setName($nValue) {
		$this->name=stripslashes($nValue);
	}
	
	function getStatus() {
		return $this->status;
	}
	
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	
	function setStatus($nValue) {
		$this->status = $nValue;
	}
}
?>
