<?php
/*************************************************************************
Class WardInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class WardInfo {
	var $id;			# Ward code (primary key)
	var $store_id;		# Store id
	var $area_id;		# Area id
	var $area_name;		# Area name
	var $name;			# Name ward
	var $fullname;		# Full name 
	var $type;			# type ward
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	var $properties;	# Properties
	var $date_created;	# Date created	

	# Constructor
	function __construct($name, $fullname, $type, $date_created, $area_name, $area_id=0, $status=0, $position=0, $properties='',$store_id=0, $id = 0)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->area_id=$area_id;
		$this->area_name=$area_name;
		$this->name = $name;
		$this->fullname = $fullname;
		$this->type = $type;
		$this->status = $status;
		$this->position = $position;
		$this->date_created = $date_created;
		$this->properties = [];
		if (!empty($properties)) {
			$data = @unserialize($properties);
			if ($data !== false) {
				$this->properties = $data;
			}
		}
	}

	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getAreaId() {
		return $this->area_id;
	}	
	function setAreaId($nValue) {
		$this->area_id=$nValue;
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getFullName() {
		return $this->fullname;
	}	
	function setFullName($nValue) {
		$this->fullname=$nValue;
	}
	function getType() {
		return $this->type;
	}	
	function setType($nValue) {
		$this->type=$nValue;
	}
	function getAreaName() {
		return $this->area_name;
	}	
	function setAreaName($nValue) {
		$this->area_name=$nValue;
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
	function getPosition() {
		return $this->position;
	}
	function setPosition($nValue) {
		$this->position = $nValue;
	}
	function getStatusText() {
		global $amessages;
		return $amessages['status_text'][$this->status];
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}

}	
?>
