<?php
/*************************************************************************
Class Order Item
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class OrderItem {
	var $id;			# Order Item ID
	var $order_id;		# Order ID
	var $product_id;	# Product ID
	var $name;			# Product name
	var $quantity;		# Quantity
	var $price;			# Unit price
	var $properties;	# Properties (size, color,...)

	# Constructor
	function __construct($product_id, $name, $quantity, $price, $properties, $order_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->order_id = $order_id;
		$this->product_id = $product_id;
		$this->name = $name;
		$this->quantity = $quantity;
		$this->price = $price;
		$this->properties = unserialize($properties);
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getOrderId() {
		return $this->order_id;
	}	
	function setOrderId($nValue) {
		$this->order_id=$nValue;
	}
	function getProductId() {
		return $this->product_id;
	}	
	function setProductId($nValue) {
		$this->product_id=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getQuantity() {
		return $this->quantity;		
	}
	function setQuantity($nValue) {
		$this->quantity=$nValue;
	}
	function getPrice() {
		return $this->price;
	}
	function setPrice($nValue) {
		$this->price=$nValue;
	}
	function getProperty($key)
	{
		if(isset($this->properties[$key])) return ''.$this->properties[$key];
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
	
	function getProSKU() {
		include_once(ROOT_PATH."classes/dao/products.class.php");
		$products = new Products($this->store_id);
		return $products->getSKUFromId($this->product_id);
	}
	function getProWeight($storeId) {
		include_once(ROOT_PATH."classes/dao/products.class.php");
		$products = new Products($storeId);
		$productItem = $products->getObject($this->product_id,'id');
		if ($productItem) $weight = $productItem->getProperty('weight');
		return $weight;
	}
	function getProAvatar($storeId) {
		include_once(ROOT_PATH."classes/dao/products.class.php");
		$products = new Products($storeId);
		$proItem = $products->getObject($this->product_id,'id');
		if($proItem) return $proItem->getProperty('avatar');
		else return '';
	}
	function getTotalPrice() {
		return $this->price*$this->quantity;
	}
	function getUrlPro($storeId) {
		include_once(ROOT_PATH."classes/dao/products.class.php");
		$products = new Products($storeId);
		$productItem = $products->getObject($this->product_id,'id');
		if ($productItem) $url = $productItem->getUrl();
		return $url;
	}
}	
?>
