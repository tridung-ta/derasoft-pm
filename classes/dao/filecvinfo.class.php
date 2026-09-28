<?php

/*************************************************************************
Class Staticinfo
----------------------------------------------------------------
DeraCMS Project
Company: Derasoft Co., Ltd                                  
Name: Tran Thi Kim Que                                  
Last updated: 15/10/2009                                  
 **************************************************************************/
class FileCvInfo
{

	public $id;
	private $fullname;
	private $email;
	private $tel;
	private $url;
	private $created;
	private $store_id;
	private $status;
	private $pid;
	function __construct($fullname, $email, $tel, $url, $created, $store_id, $status = '', $pid = '', $id = 0)
	{
		$this->id = $id;
		$this->fullname = $fullname;
		$this->email = $email;
		$this->tel = $tel;
		$this->url = $url;
		$this->created = $created;
		$this->store_id = $store_id;
		$this->status = $status;
		$this->pid = $pid;
	}
	public function FileCvInfo($fullname, $email, $tel, $url,  $created, $store_id, $status = '', $pid = '', $id = 0)
	{
		$this->__construct($fullname, $email, $tel, $url, $created, $store_id, $status, $pid, $id);
	}
	public function getUrl()
	{
		return $this->url;
	}
	public function getId()
	{
		return $this->id;
	}
	public function setId($nValue)
	{
		$this->id = $nValue;
	}

	public function getPId()
	{
		return $this->pid;
	}
	public function setPId($nValue)
	{
		$this->pid = $nValue;
	}
	public function getFullname()
	{
		return $this->fullname;
	}
	public function setFullname($nValue)
	{
		$this->fullname = stripslashes($nValue);
	}
	public function getEmail()
	{
		return $this->email;
	}
	public function setEmail($nValue)
	{
		$this->email = $nValue;
	}
	public function getTel()
	{
		return $this->tel;
	}
	public function setTel($nValue)
	{
		$this->tel = $nValue;
	}

	public function getDateCreated()
	{
		return $this->created;
	}
	public function setDateCreated($nValue)
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
