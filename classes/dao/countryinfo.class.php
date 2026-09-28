<?php
/*************************************************************************
Class CountryInfo
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Tran Thi My Xuyen
Updated by: Mai Minh (06/06/2025)
**************************************************************************/
class CountryInfo {
	var $id;			# Country code (primary key)
	var $store_id;		# Store id
	var $name;			# Name Country
	var $code;			# Country code
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	var $properties;	# Properties
	var $date_created;	# Date created
	
	# Constructor
	function __construct($name, $code, $date_created, $status=0, $position=0, $properties='',$store_id=0, $id = 0)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->name = $name;
		$this->code = $code;
		$this->status = $status;
		$this->position = $position;
		$this->date_created = $date_created;
		$this->properties = unserialize($properties);
	}

	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
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
	function getCode() {
		return $this->code;
	}
	function setCode($nValue) {
		$this->code=$nValue;
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
