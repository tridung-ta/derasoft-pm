<?php
/*************************************************************************
Class DisctrictInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class DisctrictInfo {
	var $id;			# Primary key
	var $name_quanhuyen;		
	var $type;			
	var $tpid;
	# Constructor
	 function __construct($name_quanhuyen, $type, $tpid, $id = 0)
	{
		$this->id = $id;
		$this->name_quanhuyen = $name_quanhuyen;
		$this->type = $type;
		$this->tpid = $tpid;
	}
	 function DisctrictInfo($name_quanhuyen, $type, $tpid, $id = 0)
	{
		$this->__construct($name_quanhuyen, $type, $tpid, $id);
	}
	 function getType() {
		return $this->type;		
	}
	 function getId() {
		return $this->id;
	}	
	 function setId($nValue) {
		$this->id=$nValue;
	}
	 function getName() {
		return $this->name_quanhuyen;		
	}
	 function setName($nValue) {
		$this->name_quanhuyen=stripslashes($nValue);
	}
	 function getProvinceid() {
		return $this->tpid;		
	}
	 function setProvinceid($nValue) {
		$this->tpid=stripslashes($nValue);
	}
}	
?>

