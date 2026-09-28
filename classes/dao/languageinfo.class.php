<?php
/*************************************************************************
Class language
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06.2025
Coder: Mai Minh
**************************************************************************/
class LanguageInfo {
	var $id;			# language code (primary key)
	var $store_id;
	var $prefix;		# 2 character prefix, e.g vn, en, jp, cn,...		
	var $name;			# Name
	var $currency;		# Currency for this language
	var $primary;		# Wether this language is default or not
	var $position;		# position
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	# Constructor
	function __construct($prefix, $name, $currency, $primary=0, $position=0,$status=0, $store_id=0, $id = 0)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->prefix = $prefix;
		$this->name = $name;
		$this->currency = $currency;
		$this->primary = $primary;
		$this->position = $position;
		$this->status = $status;
	}

	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}	
	
	function getPrefix() {
		return $this->prefix;
	}	
	function setPrefix($nValue) {
		$this->prefix=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	
	function getCurrency() {
		return $this->currency;
	}
	function setCurrency($nValue) {
		$this->currency=$nValue;
	}
	function getCurrencyName() {
		include_once(ROOT_PATH.'classes/dao/currencies.class.php');
		$currencies = new Currencies($this->store_id);
		return $currencies->getNameFromId($this->currency);
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
	function getPrimary() {
		return $this->primary;
	}
	function setPrimary($nValue) {
		$this->primary = $nValue;
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
