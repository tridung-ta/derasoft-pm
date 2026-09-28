<?php
/*************************************************************************
Class currency
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class CurrencyInfo {
	var $id;			# currency code (primary key)
	var $store_id;		
	var $name;			# Name method
	var $display;		# Currency title
	var $rate;			# Exchange rate to the primary currency
	var $decimal;		# Decimal of currency
	var $primary;		# Wether this currency is default or not
	var $position;		# Position
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	# Constructor
	function __construct($name, $display, $rate, $decimal=0, $primary=0, $position=0,$status=0, $store_id=0, $id = 0)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->name = $name;
		$this->display = $display;
		$this->rate = $rate;
		$this->decimal = $decimal;
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
	
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	
	function getDisplay() {
		return $this->display;
	}	
	function setDisplay($nValue) {
		$this->display=$nValue;
	}
	function getRate() {
		return $this->rate;
	}
	function setRate($nValue) {
		$this->rate=$nValue;
	}
	function getDecimal() {
		return $this->decimal;
	}
	function setDecimal($nValue) {
		$this->decimal=$nValue;
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
