<?php
# DeraCMS 4.0 Project
# Company: Derasoft
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025)

class OptionValueInfo
{
	var $id;			# Method code (primary key)
	var $store_id;
	var $field_id;
	var $key_id;
	var $field_value;
	var $status;
	var $field_name;
	# Constructor
	function __construct($field_name, $status, $field_id, $key_id, $field_value, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->field_id = $field_id;
		$this->key_id = $key_id;
		$this->field_value = $field_value;
		$this->status = $status;
		$this->field_name = $field_name;

	}
	public function OptionValueInfo($field_name, $status, $field_id, $key_id, $field_value, $store_id = 0, $id = 0)
	{
		$this->__construct( $field_name,$status, $field_id, $key_id, $field_value, $store_id, $id);
	}

	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getFieldId()
	{
		return $this->field_id;
	}
	function setFieldId($nValue)
	{
		$this->field_id = $nValue;
	}
	function getKeyId()
	{
		return $this->key_id;
	}
	function setKeyId($nValue)
	{
		$this->key_id = $nValue;
	}
	function getFieldValue()
	{
		return $this->field_value;
	}
	function setFieldValue($nValue)
	{
		$this->field_value = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getStatusText()
	{
		global $amessages;
		return $amessages['status_text'][$this->status];
	}
	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getFieldName()
	{
		return $this->field_name;
	}
	function setFieldName($nValue)
	{
		$this->field_name = $nValue;
	}
}
