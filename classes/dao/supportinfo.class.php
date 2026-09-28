<?php
/*************************************************************************
Class SupportInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Nguyen Anh Ngoc                                    
Last updated: 25/11/2009
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/	
class  SupportInfo {
	var $Id;			# Primary key
	var $paId;			# Parts Id
	var $store_id;
	var $fullname;		# Full name
	var $telephone;		# Telephone
	var $cellphone;		# Cellphone
	var $nick_yahoo;	# Nick yahoo
	var $nick_skype;	# Nick skype
	var $status;		# Status
	var $position;		# Position
	function __construct($paId=0, $store_id, $fullname='', $telephone='', $cellphone='', $nick_yahoo='', $nick_skype='', $status='', $position='', $Id=0) {
		$this->Id = $Id;
		$this->paId = $paId;
		$this->store_id = $store_id;
		$this->fullname = $fullname;
		$this->telephone = $telephone;
		$this->cellphone = $cellphone;
		$this->nick_yahoo = $nick_yahoo;
		$this->nick_skype = $nick_skype;
		$this->status = $status;
		$this->position = $position;
	}
	function getId() {
		return $this->Id;
	}	
	function setId($nValue) {
		$this->Id=$nValue;
	}
	function getParts() {
		return $this->paId;
	}
	function setParts() {
		$this->paId=$nValue;
	}
	function getStoreId() {
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	function getFullName() {
		return $this->fullname;
	}
	function setFullName() {
		$this->fullname=stripslashes($nValue);
	}
	function getTelephone() {
		return $this->telephone;
	}
	function setTelephone() {
		$this->telephone=$nValue;
	}
	function getCellphone() {
		return $this->cellphone;
	}
	function setCellphone() {
		$this->cellphone=$nValue;
	}
	function getNickYahoo() {
		return $this->nick_yahoo;
	}
	function setNickYahoo() {
		$this->nick_yahoo=$nValue;
	}
	function getNickSkype() {
		return $this->nick_skype;
	}
	function setNickSkype() {
		$this->nick_skype=$nValue;
	}
	function getPosition() {
		return $this->position;
	}
	function setPosition() {
		$this->position=$nValue;
	}
	function getStatus() {
		return $this->status;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function isEnabled() {
		return ($this->status == 1?1:0);
	}
	function isDeleted() {
		return ($this->status == 2?1:0);
	}
	function isDisabled() {
		return ($this->status == 0?1:0);
	}
	function checkYahoo($nick){
		$query=file("http://opi.yahoo.com/online?u=$nick&m=t");
		if (strpos($query[0],"NOT ONLINE")==0)
		return 1;
		return 0;
	}
	function checkSkype($nick){
		$query=file("http://mystatus.skype.com/bigclassic/$nick");
		if (strpos($query[0],"NOT ONLINE")==0)
		return 1;
		return 0;
	}
}
?>
