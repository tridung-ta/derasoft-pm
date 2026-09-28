<?php
/*************************************************************************
Class Method delivery
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 8/5/2012
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
class ShippingMethodInfo {
	var $Id;			# Method code (primary key)
	var $store_id;		
	var $name;			# Name method
	var $price;			# Price method
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	var $properties;	# Properties
	# Constructor
	function __construct($name, $price, $status=0, $position=0, $properties='',$store_id=0, $Id = 0)
	{
		$this->Id = $Id;
		$this->store_id=$store_id;
		$this->name = $name;
		$this->price = $price;
		$this->status = $status;
		$this->position = $position;
		$this->properties = unserialize($properties);
	}

	function getId() {
		return $this->Id;
	}	
	function setId($nValue) {
		$this->Id=$nValue;
	}	
	
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	
	function getPrice() {
		return $this->price;		
	}
	function setPrice($nValue) {
		$this->price=$nValue;
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
