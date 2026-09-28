<?php
/*************************************************************************
Class Tracking Info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class TrackingInfo {
	var $id;
	var $store_id;
	var $username;
	var $action;
	var $date_created;
	var $ip;

	function __construct($store_id, $username, $action, $date_created, $ip, $id = 0 )
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->username = trim($username);
		$this->action = $action;
		$this->date_created = $date_created;
		$this->ip = $ip;	
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
	function getUsername()
	{
		return $this->username;
	}
	function setUsername($nValue) {
		$this->username = $nValue;
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
}
?>
