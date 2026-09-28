<?php
/*************************************************************************
Class User Info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Author: Mai Minh
Last updated: 03/06/2025 
**************************************************************************/
class OnlineUser {
	var $id;
	var $store_id;
	var $sid;
	var $uid;
	var $username;
	var $utype;
	var $ip;
	var $last_updated;
	var $last_page;

	function __construct($store_id, $sid, $uid, $username, $utype, $ip, $last_updated, $last_page, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->sid = $sid;
		$this->uid = $uid;
		$this->username = $username;
		$this->utype = $utype;
		$this->ip = $ip;
		$this->last_updated = $last_updated;
		$this->last_page = $last_page;
	}
	function getStoreid()
	{
		return $this->store_id;
	}
	function getUId()
	{
		return $this->uid;
	}
	function getUsername()
	{
		return $this->username;
	}
	function getUType()
	{
		return $this->utype;
	}
	function getSId()
	{
		return $this->sid;
	}
	function getId()
	{
		return $this->id;
	}
	function getIp() 
	{
		return $this->ip;
	}
	function getLastUpdated() 
	{
		return $this->last_updated;
	}
	function getLastPage() 
	{
		return $this->last_page;
	}
}
?>
