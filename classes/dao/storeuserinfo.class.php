<?php
/*************************************************************************
Class User Info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Author: Mai Minh
Last updated: 03/06/2025
**************************************************************************/
class StoreUserInfo {
	var $id;
	var $store_id;
	var $area_id;
	var $username;
	var $password;
	var $type;
	var $fullname;
	var $email;
	var $address;
	var $tel;
	var $cell;
	var $date_created;
	var $status;
	var $properties;

	function __construct($store_id,$area_id, $username, $password, $type, $fullname, $email, $address, $tel, $cell,  $date_created, $last_login, $status, $properties = '', $id = 0 )
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->area_id = $area_id;
		$this->username = trim($username);
		$this->password = $password;
		$this->type = $type;
		$this->fullname = $fullname;
		$this->email = $email;	
		$this->address= $address;
		$this->tel = $tel;
		$this->cell = $cell;		
		$this->date_created = $date_created;
		$this->last_login = $last_login;
		$this->status = $status;
		$this->properties = $properties;	
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue) {
		$this->id = $nValue;
	}
	function getStoreid()
	{
		return $this->store_id;
	}
	function setStoreid($nValue) {
		$this->store_id = $nValue;
	}
	function getAreaid()
	{
		return $this->area_id;
	}
	function setAreaid($nValue) {
		$this->area_id = $nValue;
	}
	function getUserName()
	{
		return $this->username;
	}
	function setUserName($nValue) {
		$this->username = $nValue;
	}
	function getPassword()
	{
		return $this->password;
	}
	function setPassword($nValue) {
		$this->password = $nValue;
	}
	function getType()
	{
		return $this->type;
	}
	function setType($nValue) 
	{
		$this->type = $nValue;
	}
	function getFullName()
	{
	  return $this->fullname;
	}
	function setFullName($nValue) {
		$this->fullname = $Value;
	}
	function getEmail()
	{
		return $this->email;
	}
	function setEmail($nValue)
	{
		$this->email = $nValue;
	}
	function getAddress() 
	{
		return $this->address;
	}
	function setAddress($nValue) {
		$this->address = $nValue;
	}
	function getTel() 
	{
		return $this->tel;
	}
	function setTel($nValue) 
	{
		$this->tel = $nValue;
	}
	function getCell() 
	{
		return $this->cell;
	}
	function setCell($nValue) 
	{
		$this->cell = $nValue;
	}
	
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue) 
	{
		$this->date_created = $nValue;
	}
	function getLastLogin()
	{
		return $this->last_login;
	}
	function setLastLogin($nValue) 
	{
		$this->last_login = $nValue;
	}
	function getStatus()
	{
		return( $this->status );
	}
	function setStatus($nValue) 
	{
		$this->status = $nValue;
	}
	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties=$nValue;
	}
	function getProperty($key)
	{
		return isset($this->properties[$key])?$this->properties[$key]:'';
	}
	function setProperty($key,$nValue)
	{
		$this->properties[$key]=$nValue;
	}
	function getStatusText() {
		global $messages;
		return $messages['status_user'][$this->status];
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status_user'][$this->status];
	}
	function isDeleted() {
		return ($this->status == S_DELETED?1:0);
	}
	function isEnabled() {
		return ($this->status == S_ENABLED?1:0);
	}
	function isDisabled() {
		return ($this->status == S_DISABLED?1:0);
	}
}
?>
