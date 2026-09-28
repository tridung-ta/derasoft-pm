<?php
/*************************************************************************
Class UploadAlbumInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 08/09/2010
Author: Mai Minh
Checked by: Mai Minh (19/06/2025)
**************************************************************************/
class UploadAlbumInfo {
	public $id;				# Primary key
	public $store_id;		# Store ID
	public $folder;			# Folder
	public $name;			# Album name
	public $date_created;	# Date created
	public $properties;		# Properties
	public $status;			# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($name, $folder, $status, $date_created, $properties, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->folder = $folder;
		$this->name = $name;
		$this->date_created = $date_created;		
		$this->properties = unserialize($properties);
		$this->status = $status;
		
		# Check if gallery upload folder defined in constant.inc.php is exists. If not exists, create it
		if (!file_exists(ROOT_PATH.GALLERY_FOLDER)) mkdir(ROOT_PATH.GALLERY_FOLDER,DEFAULT_DIR_CHMODE);
		
		# Check if the gallery upload for this estore exists. If not exists, create it
		if (!file_exists(ROOT_PATH.GALLERY_FOLDER."/".$this->store_id)) mkdir(ROOT_PATH.GALLERY_FOLDER."/".$this->store_id,DEFAULT_DIR_CHMODE);
		
		# Check if the folder for this album exists. If not, create it
		if (!file_exists(ROOT_PATH.GALLERY_FOLDER."/".$this->store_id."/".$this->folder)) mkdir(ROOT_PATH.GALLERY_FOLDER."/".$this->store_id."/".$this->folder,DEFAULT_DIR_CHMODE);
		
		# If creating folder failed, set error for this album
		if (!file_exists(ROOT_PATH.GALLERY_FOLDER."/".$this->store_id."/".$this->folder)) $this->setProperty('create_album_error',1);
		else $this->setProperty('create_album_error',0);
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
	function getFolder() {
		return $this->folder;
	}	
	function setFolder($nValue) {
		$this->folder=$nValue;
	}
	function getRelativeFolder() {
		return GALLERY_FOLDER."/".$this->store_id."/".$this->folder."/";	
	}
	function getAbsoluteFolder() {
		return ROOT_PATH.GALLERY_FOLDER."/".$this->store_id."/".$this->folder."/";	
	}
	function getName() {
		return $this->name;
	}
	function setName($nValue) {
		$this->name=$nValue;
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
	
	function uploadFiles($postArray){
		
	}
}	
?>