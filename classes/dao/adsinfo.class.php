<?php
/*************************************************************************
Class Ad
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 21/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
class AdsInfo {
	var $aId;			# Ad code (primary key)
	var $store_id;				
	var $gid;			# Group of the ad
	var $avatar;		# Ad avatar
	var $file_ids;		# Ad file ids
	var $logo_url;		# Ad logo URL
	var $url;			# Ad URL
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	var $viewed;		# Number of views
	var $date_created;	# Date created
	var $date_updated;
	var $properties;	# Properties
	var $name;
	var $content;

	var $avatarImg = null;
	
	# Constructor
	function __construct($name, $avatar, $file_ids, $logo_url, $url , $status=0, $position=0, $viewed=0,$date_created='',$date_updated='',$properties='', $content='',$gid = 0, $store_id=0,$aId = 0)
	{
		$this->aId = $aId;
		$this->store_id=$store_id;
		$this->gid = $gid;
		$this->avatar = $avatar;
		$this->file_ids = $file_ids;
		$this->logo_url = $logo_url;
		$this->url = $url;
		$this->status = $status;
		$this->position = $position;
		$this->viewed = $viewed;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->properties = unserialize($properties);
		$this->name=$name;
		$this->content=$content;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}	
	
	function getId() {
		return $this->aId;
	}	
	function setId($nValue) {
		$this->aId=$nValue;
	}	
	
	function getContent() {
		return $this->content;		
	}
	function setContent($nValue) {
		$this->content=stripslashes($nValue);
	}
	function getGId() {
		return $this->gid;		
	}
	function setGId($nValue) {
		$this->gid=$nValue;
	}
	function getCatName() {
		include_once(ROOT_PATH."classes/dao/adscategories.class.php");
		$adsCategories = new AdsCategories($this->store_id);
		return $adsCategories->getNameFromId($this->gid);
	}
	function getLogoUrl() {
		if($this->logo_url) return $this->logo_url;
	}
	function setLogoUrl($nValue) {
		$this->logo_url=$nValue;
	}
	function getViewed() {
		return $this->viewed;
	}	
	function setViewed($nValue) {
		$this->viewed=$nValue;
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
	}
	function getDateUpdated()
	{
		return $this->date_updated;
	}
	function setDateUpdated($nValue)
	{		$this->date_updated=$nValue;
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
	function getUrl() {
		return $this->url;		
	}
	function setUrl($nValue) {
		$this->url=$nValue;
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
	function getAvatar() {
		return $this->avatar;	
	}
	function setAvatar($avatar) {
		$this->avatar = $avatar;
	}

	function getFileIds() {
		return $this->file_ids;	
	}
	function setFileIds($file_ids) {
		$this->file_ids = $file_ids;
	}

	public function getAvatarImage($uploads) {
		$avatarId = $this->getAvatar();
		return $avatarId ? $uploads->getObject($avatarId) : null;
	}

	public function getFileImages($uploads) {
		$fileIds = $this->getFileIds();
		if (!$fileIds) return array();

		$ids = explode(',', $fileIds);
		$images = array();

		foreach ($ids as $id) {
			$img = $uploads->getObject(trim($id));
			if ($img) $images[] = $img;
		}

		return $images;
	}
}	
?>
