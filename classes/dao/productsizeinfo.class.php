<?php
/*************************************************************************
Class QuestionInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class ProductSizeInfo {
	var $id;			# Primary key
	var $pid;			# Slug	
	var $po_id;			# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished
	var $value;
	var $chenhlech;
	# Constructor
	function __construct($pid,$poid,$id,$value,$chenhlech=0)
	{
		$this->pid = $pid;		
		$this->po_id = $poid;
		$this->value = $value;
		$this->chenhlech = $chenhlech;
		$this->id = $id;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getPid() {
		return $this->pid;
		
	}
	function getPoid() {
		return $this->po_id;
		
	}
	function getvalue() {
		return $this->value;
	}	
	function setvalue($nValue) {
		$this->value=$nValue;
	}

	function getChenhlech() {
		return $this->chenhlech;
	}	
	function setChenhlech($nValue) {
		$this->chenhlech=$nValue;
	}

	
}	
?>
