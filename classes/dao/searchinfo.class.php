<?php
/*************************************************************************
Class Search
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025 
Coder: Mai Minh 
**************************************************************************/
class SearchInfo {
	var $id;		# Primary key
	var $store_id;		# Estore id
	var $slug;		# Slug
	var $title;		#Title
	var $keyword;		#Keyword
	var $sapo;		# Sapo
	var $detail;		# Detail
	var $status;		# Status
	var $search_id;		# Id product,article,static
	var $type;      	#Type
	var $url;
	var $tag;

	# Constructor
	function __costruct($slug, $title, $keyword, $sapo, $detail, $status,$search_id,$type,$url,$tag, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->slug = $slug;
		$this->title = $title;
		$this->keyword = $keyword;
		$this->sapo = $sapo;
		$this->detail = $detail;
		$this->status = $status;
		$this->search_id = $search_id;
        $this->type = $type;
        $this->url = $url;
        $this->tag = $tag;
	}
	function getTag() {
		return $this->tag;
	}	
	function setTag($nValue) {
		$this->tag=$nValue;
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
	function getTitle($lang = 'vn') {
		if($lang == 'vn')	return $this->title;
		else return $this->properties['custom_'.$lang.'_title'];
	}
	function setTitle($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->title=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_title']=stripslashes($nValue);
	}
	function getKeyword($lang = 'vn') {
		if($lang == 'vn')	return $this->keyword;
		else return $this->properties['custom_'.$lang.'_keyword'];		
	}
	function setKeyword($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->keyword=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_keyword']=stripslashes($nValue);
	}
	function getSapo($lang = 'vn') {
		if($lang == 'vn')	return $this->sapo;
		else return $this->properties['custom_'.$lang.'_sapo'];
	}
	function setSapo($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->sapo=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_sapo']=stripslashes($nValue);
	}
	function getDetails($lang = 'vn') {
		if($lang == 'vn')	return $this->detail;
		elseif($lang == 'en')	return $this->properties['custom_'.$lang.'_details'];
		else return $this->properties['custom_'.$lang.'_detail'];
	}
	function setDetails($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->detail=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_details']=stripslashes($nValue);
	}
	function getStatus() {
		return $this->status;
	}
	function setStatus($nValue) {
		$this->status = $nValue;
	}
    function getUrl() {
		return $this->url;
	}
	function setUrl($nValue) {
		$this->url = $nValue;
	}
	function getSearchId() {
		return $this->search_id;
	}
	function setSearchId($nValue) {
		$this->search_id = $nValue;
	}
    function getType() {
		return $this->type;
	}
	function setType($nValue) {
		$this->type = $nValue;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
}	
?>
