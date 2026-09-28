<?php
/*************************************************************************
Class EstoreCategory
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh
**************************************************************************/
class EstoreCategoryInfo {
	var $id;			# Primary key
	var $parent_id;		# Parent category
	var $slug;			# Slug
	var $name;			# Category name
	var $position;		# Position
	var $properties;	# Properties
	var $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($slug, $name, $position, $properties, $status, $parent_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->parent_id = $parent_id;
		$this->slug = $slug;
		$this->name = $name;
		$this->position = $position;
		$this->properties = unserialize($properties);
		$this->status = $status;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getParentId() {
		return $this->parent_id;
	}
	function setParentId($nValue) {
		$this->parent_id=$nValue;
	}
	function getSlug() {
		return $this->slug;
	}	
	function setSlug($nValue) {
		$this->slug=$nValue;
	}
	function getName() {
		return $this->name;		
	}
	function setName($nValue) {
		$this->name=stripslashes($nValue);
	}
	function getPosition() {
		return $this->position;
	}	
	function setPosition($nValue) {
		$this->position=$nValue;
	}
	function getNumEstores() {
		include_once(ROOT_PATH."classes/dao/estores.class.php");
		$Estores = new Estores($this->store_id);
		$rowsPages = $Estores->getNumItems('id', "`cat_id` = '".$this->id."'");
		return $rowsPages['rows'];
	}
	function getNumActiveEstores() {
		include_once(ROOT_PATH."classes/dao/estores.class.php");
		$Estores = new Estores($this->store_id);
		$rowsPages = $Estores->getNumItems('id', "`cat_id` = '".$this->id."' AND `status` ='1'");
		return $rowsPages['rows'];
	}
	function getProperty($key)
	{
		if(isset($this->properties[$key])) return ''.$this->properties[$key];
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
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getUrl($page = 1, $keywords = '', $sort_key = 'position', $sort_direction = 'asc') {
		$url = '';
		if(URL_TYPE == 1 || $page > 1) {	# Query string
			$url = '/'.SCRIPT.'?act=category&id='.$this->id.'&pg='.$page.'&kw='.$keywords.'&sk='.$sort_key.'&sd='.$sort_direction;
			return $url;
		} elseif(URL_TYPE == 2) {	# SEO
			$url = '/ac'.$this->id.'/'.$this->slug.'.html';
			return $url;
		} else return '';	
	}
	function getChildren($page = 1, $condition = "`status` = '1'", $sort = array('position' => 'asc'), $items_per_page = 100) {
		include_once(ROOT_PATH."classes/dao/Estorecategories.class.php");
		$estoreCategories = new EstoreCategories();
		$estoreCategoryItems = $estoreCategories->getObjects($page,"`parent_id` = '".$this->id."' AND $condition",$sort,items_per_page);
		return $EstoreCategoryItems;
	}
}	
?>
