<?php

/*************************************************************************
Class ImgInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 07/11/2010
Author: Mai Minh
Reviewed by: Mai Minh (03/06/2025)

 **************************************************************************/
class ImgInfo
{
	var $id;			# Primary key
	var $name;			# Slug
	var $status;			# Category title
	var $url_l;	# Properties
	var $url_a;	# Properties
	var $store_id;
	var $date_created;
	var $cat_id;
	
	# Constructor
	function __construct($url_l, $url_a, $status, $name, $store_id,$date_created,$cat_id, $id = 0)
	{
		$this->id = $id;
		$this->name = $name;
		$this->status = $status;
		$this->url_l = $url_l;
		$this->url_a = $url_a;
		$this->store_id = $store_id;
		$this->date_created = $date_created;
		$this->cat_id = $cat_id;
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created = $nValue;
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getCatId()
	{
		return $this->cat_id;
	}
	function setCatId($nValue)
	{
		$this->cat_id = $nValue;
	}
	function getName()
	{
		return $this->name;
	}
	function setName($nValue)
	{
		$this->name = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getUrlL()
	{
		return $this->url_l;
	}
	function setUrlL($nValue)
	{
		$this->url_l = $nValue;
	}
	function getUrlA()
	{
		return $this->url_a;
	}
	function setUrlA($nValue)
	{
		$this->url_a = $nValue;
	}
	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}
}
