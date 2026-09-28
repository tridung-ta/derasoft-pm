<?php
/*************************************************************************
Class Orderlogs Info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class OrderLogInfo {
	var $id;
	var $store_id;
	var $order_id;
	var $user_id;
	var $action;
	var $date_created;
	var $ip;
	var $status;
	var $internal;
	var $properties;	# Properties
	function __construct($properties,$store_id, $order_id, $user_id, $action, $date_created, $ip, $status,$internal, $id = 0 )
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->order_id = $order_id;
		$this->user_id = $user_id;
		$this->action = $action;
		$this->date_created = $date_created;
		$this->ip = $ip;	
		$this->status = $status;
		$this->internal=$internal;	
        $this->properties=unserialize($properties);	
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue) {
		$this->id = $nValue;
	}
	function getStoreId()
	{
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id = $nValue;
	}
	function getOrderId()
	{
		return $this->order_id;
	}
	function setOrderId($nValue) {
		$this->order_id = $nValue;
	}
	function getUserId()
	{
		return $this->user_id;
	}
	function setUserId($nValue) {
		$this->user_id = $nValue;
	}
	
	function getAction()
	{
		return $this->action;
	}
	function setAction($nValue) {
		$this->action = $nValue;
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue) 
	{
		$this->date_created = $nValue;
	}
	function getIp()
	{
		return $this->ip;
	}
	function setIp($nValue) 
	{
		$this->ip = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue) 
	{
		$this->status = $nValue;
	}
	function getInternal()
	{
		return $this->internal;
	}
	function setInternal($nValue) 
	{
		$this->internal = $nValue;
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
	function getName()
	{
		include_once(ROOT_PATH."classes/dao/customers.class.php");
		$customers = new Customers($this->store_id);
		return $customers->getFullNameFromId($this->user_id);
	}
	function getUserName(){
		include_once(ROOT_PATH."classes/dao/users.class.php");
		$users = new Users($this->store_id);
		return $users->getUsername("`id` = '".$this->user_id."'");
	}
}
?>
