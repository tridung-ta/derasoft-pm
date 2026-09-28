<?php
/*************************************************************************
Class SlugInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Coder: Mai Minh
Reviewed by: Mai Minh (06/08/2025)                                  
**************************************************************************/
class SlugInfo {
	public $id;				# Primary key
	public $store_id;		# Estore Id 
	public $creator_id;		# Creator user id
	public $updater_id;		# Updater user id
	public $creator_name;	# Creator user name
	public $updater_name;	# Updater user name
	public $slug;			# Slug
	public $module;			# Module
	public $object_id;		# Object ID in module
	public $date_created;	# Date created
	public $date_updated;	# Date updated
	public $status;			# Status
	public $properties;		# Properties
	function __construct($slug, $module, $object_id, $date_created, $date_updated, $creator_name, $updater_name, $creator_id, $updater_id, $status='',  $properties = '', $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->slug = $slug;
		$this->module = $module;
		$this->object_id = $object_id;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->creator_id = $creator_id;
		$this->updater_id = $updater_id;
		$this->creator_name = $creator_name;
		$this->updater_name = $updater_name;		
		$this->status = $status;
		$this->properties = unserialize($properties);
	}

	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getStoreId() {
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	function getSlug() {
		return $this->slug;
	}	
	function setSlug($nValue) {
		$this->slug=stripslashes($nValue);
	}
	function getCreatorName() {
		return $this->creator_name;
	}	
	function setCreatorName($nValue) {
		$this->creator_name=stripslashes($nValue);
	}
	function getUpdaterName() {
		return $this->updater_name;
	}	
	function setUpdaterName($nValue) {
		$this->updater_name=stripslashes($nValue);
	}
	function getModule() {
		return $this->module;
	}
	function setModule($nValue) {
		$this->module=stripslashes($nValue);
	}
	function getObjectId() {
		return $this->object_id;
	}
	function setOnjectId($nValue) {
		$this->object_id = $object_id;
	}
	function getDateCreated() {
		return $this->date_created;
	}
	function setDateCreated( $nVlaue) {
		$this->date_created=$nValue;
	}
	function getDateUpdated() {
		return $this->date_updated;
	}
	function setDateUpdated( $nVlaue) {
		$this->date_updated=$nValue;
	}
	function getStatus() {
		return $this->status;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}	
	function setStatus($nValue) {
		$this->status = $nValue;
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
}	
?>
