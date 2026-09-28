<?php
# DeraCMS 4.0 Project
# Company: Derasoft
# Coder: Tien Le
# Review by: Mai Minh (03/06/2025)

class CustomProductOptionValueInfo
{
	var $id;			# Method code (primary key)
	var $store_id;
	var $option_id;
	var $value;
	var $price_modifier;
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	# Constructor
	function __construct( $status, $price_modifier, $value, $option_id, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->option_id = $option_id;
		$this->value = $value;
		$this->price_modifier = $price_modifier;
		$this->status = $status;
	}
	public function CustomProductOptionValueInfo($status, $price_modifier, $value, $option_id, $store_id = 0, $id = 0)
	{
		$this->__construct(   $status,$price_modifier, $value, $option_id,  $store_id, $id);
	}

	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
    function getOptionId()
	{
		return $this->option_id;
	}
	function setOptionId($nValue)
	{
		$this->option_id = $nValue;
	}
	function getValue()
	{
		return $this->value;
	}
	function setValue($nValue)
	{
		$this->value = $nValue;
	}
	function getPriceModifier()
	{
		return $this->price_modifier;
	}
	function setPriceModifier($nValue)
	{
		$this->price_modifier = $nValue;
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
}
