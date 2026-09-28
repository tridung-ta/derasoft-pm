<?php
/*************************************************************************
Class Article
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025 
Coder: Mai Minh 
**************************************************************************/
class ProductFeatureInfo {
	public $id;				# Primary key
	public $store_id;		# Estore id
    public $name;
    public $slug;
    public $status;
    public $avatar;
    public  $description;
    public $pid;
	public $properties;
    public $date_created;
	
	public $avatarImg = null;

	# Constructor
	function __construct($name, $slug, $status, $avatar, $description, $pid, $properties, $date_created, $store_id, $id = 0)
	{
        $this->name = $name;
        $this->slug = $slug;
        $this->status = $status;
        $this->avatar = $avatar;
        $this->description = $description;
        $this->pid = $pid;
		$this->properties = unserialize($properties);
        $this->date_created = $date_created;
        $this->store_id = $store_id;
        $this->id = $id;
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
	function getName() {
		return $this->name;		
	}
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getPid() {
		return $this->pid;		
	}
	function setPid($nValue) {
		$this->pid=$nValue;
	}
	function getSlug() {
		return $this->slug;		
	}
	function setSlug($nValue) {
		$this->slug=stripslashes($nValue);
	}
	function getDescription($lang = 'vn') {
		if($lang == 'vn')	return $this->description;
		else return $this->properties['custom_'.$lang.'_description'];
	}
	function setDescription($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->description=stripslashes($nValue);
		else $this->properties['custom_'.$lang.'_description']=stripslashes($nValue);
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
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
	// function getFiles() {
	// 	if($this->getProperty('fileIds')) {
	// 		include_once(ROOT_PATH . "classes/dao/uploads.class.php");
	// 		$uploads = new Uploads($this->store_id);
	// 		$files = $uploads->getObjects(1,'id IN ('.implode(',',$this->getProperty('fileIds').')',[],999));
	// 		if($files) return $files;
	// 	}
	// 	return '';	
	// }
}	
?>
