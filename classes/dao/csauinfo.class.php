<?php

/*************************************************************************
Class Staticinfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Tran Thi Kim Que                                  
Last updated: 15/10/2009
Reviewe by: Mai Minh (03/06/2025)                              
 **************************************************************************/
class CsauInfo
{
	public $id;
	private $fullname;
	private $avatar;
	private $details;
	private $created;
	private $store_id;
	private $status;
	private $cat_id;

	function __construct($fullname = '', $avatar = '', $details = '', $created = '', $store_id, $status = '', $cat_id, $id = 0)
	{
		$this->id = $id;
		$this->fullname = $fullname;
		$this->avatar = $avatar;
		$this->details = $details;
		$this->created = $created;
		$this->store_id = $store_id;
		$this->status = $status;
		$this->cat_id = $cat_id;
	}
	public function CsauInfo($fullname = '', $avatar, $details = '', $created = '', $store_id, $status = '', $cat_id, $id = 0)
	{
		$this->__construct($fullname, $avatar, $details, $created, $store_id, $status, $cat_id, $id);
	}
	public function getCatId()
	{
		return $this->cat_id;
	}
	public function getId()
	{
		return $this->id;
	}
	public function setId($nValue)
	{
		$this->id = $nValue;
	}
	public function getFullName()
	{
		return $this->fullname;
	}
	public function setFullName($nValue)
	{
		$this->fullname = $nValue;
	}
	public function getAvatar()
	{
		return $this->avatar;
	}
	public function setAvatar($nValue)
	{
		$this->avatar = $nValue;
	}
	public function getDetails()
	{
		return $this->details;
	}
	public function setDetails($nValue)
	{
		$this->details = $nValue;
	}
	public function getCreated()
	{
		return $this->created;
	}
	public function setCreated($nValue)
	{
		$this->created = $nValue;
	}
	public function getStoreId()
	{
		return $this->store_id;
	}
	public function setStoreId($nValue)
	{
		$this->store_id = $nValue;
	}
	public function getStatus()
	{
		return $this->status;
	}
	public function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	public function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}
}
