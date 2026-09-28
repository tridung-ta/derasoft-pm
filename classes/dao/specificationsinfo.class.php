<?php
/*************************************************************************
Class SpecificationsInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Nguyen Anh Ngoc                        
Last updated: 07/10/2009
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
class SpecificationsInfo {
	var $id;			# Primary key
	var $parent_id;
	var $store_id;
	var $mc_id;
	var $name;
	var $url;
	var $position;
	var $status;
	var $properties;
	var $date_created;
	var $cat_id;

	function __construct($parent_id,$store_id,$mc_id,$name,$url,$position,$status,$properties,$date_created,$cat_id,$id =0)
	{
		$this->id = $id;
		$this->parent_id = $parent_id;
		$this->store_id = $store_id;
		$this->mc_id= $mc_id;
		$this->name = $name;
		$this->url = $url;
		$this->position = $position;
		$this->status = $status;
		$this->properties = unserialize($properties);
		$this->date_created = $date_created;
		$this->cat_id = $cat_id;
	}
	function getCatId() {
		return $this->cat_id;
	}	
	function setCatId($nValue){
		$this->cat_id=$nValue;
	}
	function getMcId() {
		return $this->mc_id;
	}	
	function setMcId($nValue){
		$this->mc_id=$nValue;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue){
		$this->id=$nValue;
	}
	function getParentId() {
		return $this->parent_id;
	}
	function setParentId($nValue) {
		$this->parent_id=$nValue;
	}
	function getStoreId() {
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	function getName($lang = 'vn') {
		if($lang == 'vn')	return $this->name;
		else return $this->properties['custom_'.$lang.'_name'];
	}
	function setName($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->name=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_name']=stripslashes($nValue);
	}
	function getCId() {
		return $this->cId;
	}
	function setCId($nValue) {
		$this->cId = $nValue;
	}
	function getUrl($lang = 'vn') {
		if($lang == 'vn')	return $this->url;
		else	return $this->properties['custom_'.$lang.'_url'];
	}
	function setUrl($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->url=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_url']=stripslashes($nValue);
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
	
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}

}	
?>
