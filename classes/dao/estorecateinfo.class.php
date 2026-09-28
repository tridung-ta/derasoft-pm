<?php
/*************************************************************************
Class Order Item
----------------------------------------------------------------
DeraCMS 4.0 Project
Name: Tran Thi My Xuyen
Last Update: 19/04/2012
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
class EstoreCateInfo {
	var $id;		# Item ID
	var $sid;		# Restaurant ID
	var $cat_id;		# time start

	# Constructor
	function __construct($sid, $cat_id, $id = 0)
	{
		$this->id = $id;
		$this->sid = $sid;
		$this->cat_id = $cat_id;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getSId() {
		return $this->sid;
	}	
	function setSId($nValue) {
		$this->sid=$nValue;
	}
	function getCatId() {
		return $this->cat_id;
	}	
	function setCatId($nValue) {
		$this->cat_id=$nValue;
	}
	function getCatName() {
		include_once(ROOT_PATH."classes/dao/estorecategories.class.php");
		$estoreCategories = new EstoreCategories($this->sid);
		return $estoreCategories->getNameFromId($this->cat_id);
	}
	
}	
?>
