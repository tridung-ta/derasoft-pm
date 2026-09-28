<?php

/*************************************************************************
Class RecruitmentInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Nguyen Anh Ngoc                        
Last updated: 07/10/2009
Checked by: Mai Minh (03/06/2025)
 **************************************************************************/
class RecruitmentInfo
{
	var $id;
	var $store_id;
	var $parent_id;
	var $name;
	var $slug;
	var $detail;
	var $status;
	var $properties;
	var $date_created;
	var $income;
	var $degree;
	var $experience;
	var $rank;
	var $number_recruits;
	var $date_exp;
	var $location;
	var $job_location;
	var $gender;
	var $age;
	var $mail;
	var $file;

	function __construct($file, $mail, $parent_id, $age, $gender, $job_location, $location, $store_id,$name,$slug, $detail, $status, $properties, $date_created, $income, $degree, $experience, $rank, $number_recruits, $date_exp, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->parent_id = $parent_id;
		$this->name = $name;
		$this->slug = $slug;
		$this->detail = $detail;
		$this->status = $status;
		$this->properties = unserialize($properties);
		$this->date_created = $date_created;
		$this->income = $income;
		$this->degree = $degree;
		$this->experience = $experience;
		$this->rank = $rank;
		$this->number_recruits = $number_recruits;
		$this->date_exp = $date_exp;
		$this->location = $location;
		$this->job_location = $job_location;
		$this->gender = $gender;
		$this->age = $age;
		$this->mail = $mail;
		$this->file = $file;
	}
	function getSlug()
	{
		return $this->slug;
	}
	function getDateExp()
	{
		return $this->date_exp;
	}
	function getNumberRecruits()
	{
		return $this->number_recruits;
	}
	function getRank()
	{
		return $this->rank;
	}
	function getExperience()
	{
		return $this->experience;
	}
	function getDegree()
	{
		return $this->degree;
	}
	function getIncome()
	{
		return $this->income;
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function getName()
	{
		return $this->name;
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->parent_id = $nValue;
	}
	function getParentId()
	{
		return $this->id;
	}
	function setParentId($nValue)
	{
		$this->parent_id = $nValue;
	}

	function getStoreId()
	{
		return $this->store_id;
	}
	function setStoreId($nValue)
	{
		$this->store_id = $nValue;
	}
	function getProperty($key)
	{
		if (isset($this->properties[$key])) return $this->properties[$key];
		return '';
	}
	function setProperty($key, $nValue)
	{
		$this->properties[$key] = $nValue;
	}

	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties = $nValue;
	}

	function getStatus()
	{
		return $this->status;
	}

	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getDetail()
	{
		return $this->detail;
	}
	function setDetail($nValue)
	{
		$this->detail = $nValue;
	}
	function getLocation()
	{
		return $this->location;
	}
	function setLocation($nValue)
	{
		$this->location = $nValue;
	}

	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getJobLocation()
	{
		return $this->job_location;
	}
	function setJobLocation($nValue)
	{
		$this->job_location = $nValue;
	}
	function getGender()
	{
		return $this->gender;
	}
	function setGender($nValue)
	{
		$this->gender = $nValue;
	}
	function getAge()
	{
		return $this->age;
	}
	function setAge($nValue)
	{
		$this->age = $nValue;
	}
	function getMail()
	{
		return $this->mail;
	}
	function setMail($nValue)
	{
		$this->mail = $nValue;
	}
	function getFile()
	{
		return $this->file;
	}
	function setFile($nValue)
	{
		$this->file = $nValue;
	}
}
