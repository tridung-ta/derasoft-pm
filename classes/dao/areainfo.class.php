<?php
/*************************************************************************
Class AreaInfo
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Tran Thi My Xuyen
Updated by: Mai Minh (03/06/2025)
**************************************************************************/
class AreaInfo {
	public $id;			# Area code (primary key)
	public $store_id;		# Store id
	public $country_id;	# Country id
	public $country_name;	# Country name
	public $slug;			# Slug
	public $name;			# Name
	public $fullname;		# Full name
	public $type;			# Type
	public $is_central;		# Truc thuoc trung uong
	public $status;		# 0-Disabled, 1-Active, 2-Deleted
	public $position;		# Display order
	public $properties;	# Properties
	public $date_created;	# Date created
	
	# Constructor
	function __construct($name, $slug, $fullname, $type, $is_central, $date_created, $country_id=0, $country_name='',$status=0, $position=0, $properties='',$store_id=0, $id = 0)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->country_id=$country_id;
		$this->country_name=$country_name;
		$this->name = $name;
		$this->slug = $slug;
		$this->fullname = $fullname;
		$this->type = $type;
		$this->is_central = $is_central;
		$this->status = $status;
		$this->position = $position;
		$this->date_created = $date_created;
		$this->properties = unserialize($properties);
	}

	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getCountryId() {
		return $this->country_id;
	}	
	function setCountryId($nValue) {
		$this->country_id=$nValue;
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getFullName() {
		return $this->fullname;
	}	
	function setFullName($nValue) {
		$this->fullname=$nValue;
	}
	function getSlug() {
		return $this->slug;
	}	
	function setSlug($nValue) {
		$this->slug=$nValue;
	}
	function getType() {
		return $this->type;
	}	
	function setType($nValue) {
		$this->type=$nValue;
	}
	function getIsCentral() {
		return $this->is_central;
	}	
	function setIsCentral($nValue) {
		$this->is_central=$nValue;
	}
	function getCountryName() {
		return $this->country_name;
	}	
	function setCountryName($nValue) {
		$this->country_name=$nValue;
	}
	function getProperty($key)
	{
		if(isset($this->properties[$key])) return $this->properties[$key];
		return '';
	}
	function setProperty($key,$nValue)
	{
		$this->properties[$key]=$nValue;
	}
	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties=$nValue;
	}
	function getStatus() {
		return $this->status;
	}
	function setStatus($nValue) {
		$this->status = $nValue;
	}
	function getPosition() {
		return $this->position;
	}
	function setPosition($nValue) {
		$this->position = $nValue;
	}
	function getStatusText() {
		global $amessages;
		return $amessages['status_text'][$this->status];
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
}	
?>
