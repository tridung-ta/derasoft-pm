<?php
/*************************************************************************
Class Resource Info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Mai Minh                                
Last updated: 03/06/2025                             
**************************************************************************/
class ResourceInfo {
	var $id;			# Primary key
	var $store_id;		# Estore Id 
	var $type;			# Resource type		1-Image, 2-Flash, 3-File, 4-Video, 5-Youtube
	var $block;			# Slug
	var $title;			# Vietnamese name
	var $keywords;		# Vietnamese keywords
	var $sapo;			# Vietnamese sapo
	var $details;		# Vietnamese details
	var $status;		# Status
	var $position;		#Position
	var $properties;	# Properties
	function __construct($store_id, $slug,$block, $title, $keywords,$sapo,$details, $status,$position,  $properties = '',$Id = 0)
	{
		$this->Id = $Id;
		$this->store_id = $store_id;
		$this->slug = $slug;
		$this->block = $block;
		$this->title = $title;
		$this->keywords = $keywords;
		$this->sapo = $sapo;
		$this->details = $details;
		$this->status = $status;
		$this->position = $position;
		$this->properties = $properties;
	}

	function getId() {
		return $this->Id;
	}	
	function setId($nValue) {
		$this->$Id=$nValue;
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
		$this->$slug=stripslashes($nValue);
	}
	function getBlock() {
		return $this->block;
	}	
	function setBlock($nValue) {
		$this->$block=$nValue;
	}
	function getTitle() {
		return $this->title;
	}
	function setTitle($nValue) {
		$this->$title=stripslashes($nValue);
	}
	function getKeywords($nValue) {
		return $this->keywords;
	}
	function setKeywords( $nValue) {
		$this->$keywords=stripslashes($nValue);
	}
	function getSapo() {
		return $this->sapo;
	}
	function setSapo($nValue) {
		$this->$sapo=stripslashes($nValue);
	}
	function getDetails() {
		return $this->details;
	}
	function setDetails( $nVlaue) {
		$this->$details=stripslashes($nValue);
	}
	function getStatus() {
		return $this->status;
	}
	function getStatusText() {
		global $amessages;
		return $amessages['status_text'][$this->status];
	}	
	function setStatus($nValue) {
		$this->$status = $nValue;
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
	function getPosition() {
		return $this->position;
	}
	function setPosition($nValue) {
		$this->position = $nValue;
	}
	
	function isImage() {
		$img = $this->avatar;
		if($this->avatar) $img = $this->avatar;
		if(preg_match("/jpg|JPEG|png|bmp|gif/",$img)) return 1;
		return 0;
	}
	function isFlash() {
		$img = $this->avatar;
		if($this->avatar) $img = $this->avatar;
		if(preg_match("/.swf/",$img)) return 1;
		return 0;
	}
}	
?>
