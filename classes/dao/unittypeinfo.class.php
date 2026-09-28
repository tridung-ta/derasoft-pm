<?php
/*************************************************************************
Class UnitTypeInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 14/06/2025
Author: Mai Minh 
**************************************************************************/
class UnitTypeInfo {
	var $id;			# Ward code (primary key)
	var $store_id;		# Store id
	var $name;			# Tên hiển thị: "Cái", "Kg"
	var $description;			# unit description
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	var $properties;	# Properties
	var $date_created;	# Date created	

	# Constructor
	function __construct($id, $store_id, $name, $description, $status=0, $position=0, $properties='', $date_created)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->name = $name;
		$this->description = $description;
		$this->status = $status;
		$this->position = $position;
		$this->properties = unserialize($properties);
		$this->date_created = $date_created;
	}

	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getDescription() {
		return $this->description;
	}	
	function setDescription($nValue) {
		$this->description=$nValue;
	}
	function getStatus() {
		return $this->status;
	}
	function setStatus($nValue) {
		$this->status = $nValue;
	}
	function getStatusText() {
		global $amessages;
		return $amessages['status_text'][$this->status];
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getPosition() {
		return $this->position;
	}
	function setPosition($nValue) {
		$this->position = $nValue;
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
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
	}
}	
?>
