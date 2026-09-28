<?php
/*************************************************************************
Class ArticleCategory
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 08/09/2010
Author: Tran Thi My Xuyen
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
class ArticleCategoryInfo {
	public $id;			# Primary key
	public $parent_id;		# Parent category id
	public $parent_name;	# Parent caregory name
	public $store_id;		# Estore id
	public $slug;			# Slug
	public $name;			# Category name
	public $keyword;		# Keyword
	public $description;	# Description
	public $landing;		# Display category page as landing page with detail content
	public $detail;		# Detail content - used for landing page
	public $check_duplicate_title;	# Check duplicate article title
	public $sort_key;		# Default sort key - used for frontend display
	public $sort_direction;# Default sort direction - used for frontend display
	public $layout;		# Display type - 1 column many rows, or many columns many rows - frontend
	public $ipp;			# Items per page - used for frontend display
	public $article_count;	# Num articles
	public $position;		# Position
	public $viewed;		# Number of views
	public $properties;	# Properties
	public $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($slug, $name, $keyword, $description, $landing, $detail, $sort_key, $sort_direction, $layout, $ipp, $position, $viewed, $article_count, $parent_name, $properties, $status, $check_duplicate_title, $store_id = 0, $parent_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->parent_id = $parent_id;
		$this->store_id = $store_id;
		$this->slug = $slug;
		$this->parent_name = $parent_name;
		$this->name = $name;
		$this->keyword = $keyword;
		$this->description = $description;
		$this->landing = $landing;
		$this->detail = $detail;
		$this->check_duplicate_title = $check_duplicate_title;
		$this->sort_key = $sort_key;
		$this->sort_direction = $sort_direction;
		$this->layout = $layout;
		$this->ipp = $ipp;
		$this->article_count = $article_count;
		$this->position = $position;
		$this->viewed = $viewed;
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
	function getParentName() {
		global $amessages;
		if(!$this->parent_id) return $amessages['root'];
		return $this->parent_name;
	}
	function getStoreId() {
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	function getCheckDuplicateTitle() {
		return $this->check_duplicate_title;
	}
	function setCheckDuplicateTitle($nValue) {
		$this->check_duplicate_title=$nValue;
	}
	function getSortKey() {
		return $this->sort_key;
	}
	function setSortKey($nValue) {
		$this->sort_key=$nValue;
	}
	function getSortDirection() {
		return $this->sort_direction;
	}
	function setSortDirection($nValue) {
		$this->sort_direction=$nValue;
	}
	function getLayout() {
		return $this->layout;
	}
	function setLayout($nValue) {
		$this->layout=$nValue;
	}
	function getIpp() {
		return $this->ipp;
	}
	function setIpp($nValue) {
		$this->ipp=$nValue;
	}
	function getLanding() {
		return $this->landing;
	}
	function setLanding($nValue) {
		$this->landing=$nValue;
	}
	function getSlug() {
		return $this->slug;
	}	
	function setSlug($nValue) {
		$this->slug=$nValue;
	}
	function getArticleCount() {
		return $this->article_count;
	}	
	function setArticleCount($nValue) {
		$this->article_count=$nValue;
	}
	function getName($lang='vn') {
		if($lang=='vn')	return $this->name;
		elseif(isset($this->properties['custom_'.$lang.'_name'])) return $this->properties['custom_'.$lang.'_name'];
	}
	function setName($nValue,$lang='vn') {
		if($lang=='vn') $this->name=stripslashes($nValue);
		else $this->properties['custom_'.$lang.'_name']=stripslashes($nValue);
	}
	function getKeyword($lang='vn') {
		if($lang=='vn')	return $this->keyword;
		elseif(isset($this->properties['custom_'.$lang.'_keyword'])) return $this->properties['custom_'.$lang.'_keyword'];
	}
	function setKeyword($nValue,$lang='vn') {
		if($lang=='vn')$this->keyword=stripslashes($nValue);
		else  $this->properties['custom_'.$lang.'_keyword']=stripslashes($nValue);
	}
	function getDescription($lang='vn') {
		if($lang=='vn')	return $this->description;
		elseif(isset($this->properties['custom_'.$lang.'_description'])) return $this->properties['custom_'.$lang.'_description'];
	}
	function setDescription($nValue,$lang='vn') {
		if($lang=='vn')$this->description=stripslashes($nValue);		
		else  $this->properties['custom_'.$lang.'_description']=stripslashes($nValue);
	}
	function getDetail($lang='vn') {
		if($lang=='vn')	return $this->detail;
		elseif(isset($this->properties['custom_'.$lang.'_detail'])) return $this->properties['custom_'.$lang.'_detail'];
	}
	function setDetail($nValue,$lang='vn') {
		if($lang=='vn')$this->detail=stripslashes($nValue);		
		else  $this->properties['custom_'.$lang.'_detail']=stripslashes($nValue);
	}	
	function getH1($lang='vn') {
		if($lang=='vn')	return $this->properties['custom_h1'];
		elseif(isset($this->properties['custom_'.$lang.'_h1'])) return $this->properties['custom_'.$lang.'_h1'];
	}
	function getPosition() {
		return $this->position;
	}	
	function setPosition($nValue) {
		$this->position=$nValue;
	}
	function getViewed() {
		return $this->viewed;
	}	
	function setViewed($nValue) {
		$this->viewed=$nValue;
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
	function getChildren($page = 1, $condition = "`status` = '1'", $sort = array('position' => 'asc'), $items_per_page = 100) {
		include_once(ROOT_PATH."classes/dao/articlecategories.class.php");
		$articleCategories = new ArticleCategories($this->store_id);
		$articleCategoryItems = $articleCategories->getObjects($page,"`parent_id` = '".$this->id."' AND $condition",$sort,$items_per_page);
		return $articleCategoryItems;
	}

function getUrl($lang = 'vn'){
	include_once(ROOT_PATH."classes/dao/menus.class.php");
	$menus = new Menus($this->store_id);
	$menuInfo = $menus->getObject($this->id,'route_id');

	if (!$menuInfo) return '';

	return $menuInfo->getUrlByLang($lang);
}

}	
?>
