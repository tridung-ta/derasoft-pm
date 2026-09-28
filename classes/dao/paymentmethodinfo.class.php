<?php
/*************************************************************************
Class Payment Method info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class PaymentMethodInfo {
	var $Id;			# Method code (primary key)
	var $store_id;		
	var $modulel;		# Module name
	var $name;			# Name method
	var $fee;			# Price method
	var $currencies;	# List of supported currencies, seperated by |, e.g VND|USD|EUR|JPY
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	var $properties;	# Properties
	# Constructor
	function __construct($module, $name, $fee, $currencies, $status=0, $position=0, $properties='',$store_id=0, $Id = 0)
	{
		$this->Id = $Id;
		$this->store_id=$store_id;
		$this->module = $module;
		$this->name = $name;
		$this->fee = $fee;
		$this->currencies = $currencies;
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
	
	function getModule() {
		return $this->module;
	}	
	function setModule($nValue) {
		$this->module=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getFee() {
		return $this->fee;		
	}
	function setFee($nValue) {
		$this->fee=$nValue;
	}
	function getCurrencies() {
		return $this->currencies;		
	}
	function setCurrencies($nValue) {
		$this->currencies=$nValue;
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
