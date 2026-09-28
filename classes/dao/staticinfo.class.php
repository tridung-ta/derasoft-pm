<?php
/*************************************************************************
Class Staticinfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Coder: Mai Minh
Reviewed by: Mai Minh (14/06/2025)                                  
**************************************************************************/
class Staticinfo {
	public $id;				# Primary key
	public $store_id;		# Estore Id 
	public $menu_id;		# Menu id
	public $creator_id;		# Creator user id
	public $updater_id;		# Updater user id
	public $creator_name;	# Creator user name
	public $updater_name;	# Updater user name
	public $slug;			# Slug
	public $title;			# Title
	public $keyword;		# Keyword
	public $description;	# Description
	public $detail;			# Detail
	public $date_created;	# Date created
	public $date_updated;	# Date updated
	public $status;			# Status
	public $properties;		# Properties
	public $landing;
	function __construct($landing, $slug, $title, $keyword, $description, $detail, $date_created, $date_updated, $creator_name, $updater_name, $creator_id, $updater_id, $status='',  $properties = '', $store_id = 0,$menu_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->menu_id = $menu_id;
		$this->slug = $slug;
		$this->title = $title;
		$this->keyword = $keyword;
		$this->description = $description;
		$this->detail = $detail;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->creator_id = $creator_id;
		$this->updater_id = $updater_id;
		$this->creator_name = $creator_name;
		$this->updater_name = $updater_name;		
		$this->status = $status;
		$this->properties = unserialize($properties);
		$this->landing = $landing;
	}

	function getLanding() {
		return $this->landing;
	}
	function setLanding($nValue) {
		$this->landing = $nValue;
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

	function getMenuId() {
		return $this->menu_id;
	}
	function setMenuId($nValue) {
		$this->menu_id=$nValue;
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
	function getCreatorId() {
		return $this->creator_id;
	}	
	function setCreatorId($nValue) {
		$this->creator_id=$nValue;
	}
	function getUpdaterName() {
		return $this->updater_name;
	}	
	function setUpdaterName($nValue) {
		$this->updater_name=stripslashes($nValue);
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
		if($lang == 'vn')	$this->keyword = stripslashes($nValue);
		else $this->properties['custom_'.$lang.'_keyword']=stripslashes($nValue);
	}
	function getDescription($lang = 'vn') {
		if($lang == 'vn')	return $this->description;
		else return $this->properties['custom_'.$lang.'_description'];
	}
	function setDescription($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->description=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_description']=stripslashes($nValue);
	}
	function getDetail($lang = 'vn') {
		if($lang == 'vn')	return $this->detail;
		else return $this->properties['custom_'.$lang.'_detail'];
	}
	function setDetail($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->detail=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_detail']=stripslashes($nValue);
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
	function getUrl($lang='vn') {
		$url = '';
		if(URL_TYPE == 1) {	# Query string
			$url = '/'.SCRIPT.'?act=static&id='.$this->id;
			return $url;
		} elseif(URL_TYPE == 2) {	# SEO
			if($lang == 'en')	$url = '/en/'.$this->slug.'.htm';
			else $url = '/'.$this->slug.'.htm';
			return $url;
		} else return '';	
	}
	function getAvatar() {
		if($this->getProperty('avatarId')) {
			include_once(ROOT_PATH . "classes/dao/uploads.class.php");
			$uploads = new Uploads($this->store_id);
			$avatar = $uploads->getObject($this->getProperty('avatarId'));
			if($avatar) return $avatar;
		}
		return '';		
	}
	function getFiles() {
		if($this->getProperty('fileIds')) {
			include_once(ROOT_PATH . "classes/dao/uploads.class.php");
			$uploads = new Uploads($this->store_id);
			$files = $uploads->getObjects(1,'id IN ('.implode(',',$this->getProperty('fileIds').')',[],999));
			if($files) return $files;
		}
		return '';	
	}

	function getNameMenuById($id) {
		include_once(ROOT_PATH . "classes/dao/menus.class.php");
		$menus = new Menus($this->store_id);
		$menu = $menus->getObject($id);
		return $menu->name;
	}
}	
?>
