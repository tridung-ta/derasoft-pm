<?php

class PasswordResetInfo {
	var $id;			# Primary key
	var $store_id;		# Estore id
	var $customer_id;		# Parent group id
	var $token;			# Group name
	var $expired_at;		# Address
	var $date_created;	# Date created
	var $date_updated;	# Date created
	var $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($status, $date_updated, $date_created, $expired_at, $token, $customer_id=0, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->customer_id = $customer_id;
		$this->token = $token;
		$this->expired_at = $expired_at;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->status = $status;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getStoreId() {
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	function getCustomerId() {
		return $this->customer_id;
	}
	function setCustomerId($nValue) {
		$this->customer_id=$nValue;
	}
	function getToken() {
		return $this->token;		
	}
	function setToken($nValue) {
		$this->token=$nValue;
	}
    function getExpiredAt() {
		return $this->expired_at;		
	}
	function setExpiredAt($nValue) {
		$this->expired_at=$nValue;
	}	
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
	}
    function getDateUpdated()
	{
		return $this->date_updated;
	}
	function setDateUpdated($nValue)
	{
		$this->date_updated=$nValue;
	}
	function getStatus() {
		return $this->status;
	}
	function setStatus($nValue) {
		$this->status = $nValue;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
}	
?>
