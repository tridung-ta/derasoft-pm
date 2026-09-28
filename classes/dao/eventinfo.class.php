<?php
/*************************************************************************
Class Event
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class EventInfo {
	var $Id;			# email code (primary key)
	var $store_id;		
	var $name;			# Name method
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $can_del;		# Can be deleted via CMS
	# Constructor
	function __construct($name, $status=0, $can_del=1,$store_id=0, $Id = 0)
	{
		$this->Id = $Id;
		$this->store_id=$store_id;
		$this->name = $name;
		$this->status = $status;
		$this->can_del = $can_del;
	}

	function getId() {
		return $this->Id;
	}	
	function setId($nValue) {
		$this->Id=$nValue;
	}	
	
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getStatus() {
		return $this->status;
	}
	function setStatus($nValue) {
		$this->status = $nValue;
	}
	function getCanDel() {
		return $this->can_del;
	}
	function setCanDel($nValue) {
		$this->can_del = $nValue;
	}
	function getStatusText() {
		global $amessages;
		return $amessages['status_text'][$this->status];
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getCanDelTextBackend() {
		if($this->can_del==1) return "Có";
		return "Không";
	}
}	
?>
