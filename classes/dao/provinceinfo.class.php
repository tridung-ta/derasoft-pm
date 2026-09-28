<?php
/*************************************************************************
Class ProvinceInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh
**************************************************************************/
class ProvinceInfo {
	public $id;			# Primary key
	private $name_thanhpho;		
	private $type;			

	# Constructor
	public function __construct($name_thanhpho, $type, $id = 0)
	{
		$this->id = $id;
		$this->name_thanhpho = $name_thanhpho;
		$this->type = $type;
	}
	public function ProvinceInfo($name_thanhpho, $type, $id = 0)
	{
		$this->__construct($name_thanhpho, $type, $id);
	}

	public function getType() {
		return $this->type;
	}	

	public function getId() {
		return $this->id;
	}	
	public function setId($nValue) {
		$this->id=$nValue;
	}
	public function getName() {
		return $this->name_thanhpho;		
	}
	public function setName($nValue) {
		$this->name_thanhpho=stripslashes($nValue);
	}

}	
?>
