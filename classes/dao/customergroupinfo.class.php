<?php
/*************************************************************************
Class CustomerInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Reviewed by: Mai Minh (09/06/2025)
**************************************************************************/
class CustomerGroupInfo {
	var $id;			# Primary key
	var $store_id;		# Estore id
	var $parent_id;		# Parent group id
	var $name;			# Group name
	var $address;		# Address
	var $properties;	# Properties(about, cel)
	var $date_created;	# Date created
	var $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($name, $properties, $date_created, $status, $parent_id=0, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->parent_id = $area_id;
		$this->name = $name;
		$this->address = $address;
		$this->properties = unserialize($properties);
		$this->date_created = $date_created;
		$this->status = $status;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getStoreId() {
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	function getParentId() {
		return $this->parent_id;
	}
	function setParentId($nValue) {
		$this->parent_id=$nValue;
	}
	function getName() {
		return $this->name;		
	}
	function setName($nValue) {
		$this->name=$nValue;
	}	
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
	}
	function getProperty($key)
	{
		if(isset($this->properties[$key])) return $this->properties[$key];
		return '';
	}
	function setProperty($key,$nValue)
	{
		$this->properties[$key]=$nValue;
	}
	
	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties=$nValue;
	}
	function getStatus() {
		return $this->status;
	}
	function setStatus($nValue) {
		$this->status = $nValue;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
}	
?>
