<?php
# DeraCMS 4.0 Project
# Company: Derasoft Co., Ltd
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025

class CustomProductOptionInfo
{
	var $id;			# Method code (primary key)
	var $store_id;
	var $product_id;
	var $product;
	var $name;
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	
# Constructor
	function __construct( $status, $name, $product, $product_id, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->product_id = $product_id;
		$this->product = $product;
		$this->name = $name;
		$this->status = $status;
	}
	public function CustomProductOptionInfo($status, $name, $product, $product_id, $store_id = 0, $id = 0)
	{
		$this->__construct(   $status, $name, $product, $product_id,  $store_id, $id);
	}

	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getProductId()
	{
		return $this->product_id;
	}
	function setProductId($nValue)
	{
		$this->product_id = $nValue;
	}
	function getProduct()
	{
		return $this->product;
	}
	function setProduct($nValue)
	{
		$this->product = $nValue;
	}
	function getName()
	{
		return $this->name;
	}
	function setName($nValue)
	{
		$this->name = $nValue;
	}
	// 	function getDefaultId()
	// {
	// 	return $this->default_id;
	// }
	// function setDefaultId($nValue)
	// {
	// 	$this->default_id = $nValue;
	// }
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
