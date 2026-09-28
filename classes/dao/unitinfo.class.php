<?php
/*************************************************************************
Class UnitInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 11/06/2025
Author: Mai Minh 
**************************************************************************/
class UnitInfo {
	var $id;			# Ward code (primary key)
	var $store_id;		# Store id
	var $unit_code;		# Mã kỹ thuật: "piece", "kg"
	var $symbol;		# Ký hiệu: "kg", "m", "h"
	var $name;			# Tên hiển thị: "Cái", "Kg"
	var $type;			# type unit
	var $conversion_rate_to_base;			# Tỷ lệ quy đổi sang đơn vị chuẩn (VD: 1g = 0.001kg)
	var $base_unit_code;			# Mã của đơn vị gốc (VD: 'kg' nếu đây là 'g')
	var $description;			# unit description
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	var $properties;	# Properties
	var $date_created;	# Date created	

	# Constructor
	function __construct($id, $store_id, $unit_code, $symbol, $name, $type, $conversion_rate_to_base, $base_unit_code, $description, $status=0, $position=0, $properties='', $date_created)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->unit_code=$unit_code;
		$this->symbol=$symbol;
		$this->name = $name;
		$this->type = $type;
		$this->conversion_rate_to_base = $conversion_rate_to_base;
		$this->base_unit_code = $base_unit_code;
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
	function getUnitCode() {
		return $this->unit_code;
	}	
	function setUnitCode($nValue) {
		$this->unit_code=$nValue;
	}
	function getSymbol() {
		return $this->symbol;
	}	
	function setSymbol($nValue) {
		$this->symbol=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getType() {
		return $this->type;
	}	
	function setType($nValue) {
		$this->type=$nValue;
	}
	function getConversionRateToBase() {
		return $this->conversion_rate_to_base;
	}	
	function setConversionRateToBase($nValue) {
		$this->conversion_rate_to_base=$nValue;
	}
	function getBaseUnitCode() {
		return $this->base_unit_code;
	}	
	function setBaseUnitCode($nValue) {
		$this->base_unit_code=$nValue;
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
