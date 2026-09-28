<?php

class UploadPdfInfo {
	var $id;			# Ad code (primary key)
	var $store_id;				
	var $name;			# Ad name
	var $category_id;			# Ad name
	var $link_pdf;			# Ad name
	var $position;		# Display order
	var $properties;	# Properties
	var $date_created;	# Date created
	var $date_updated;	# Date updated
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
    
	
	# Constructor
	function __construct($id = 0, $store_id = 0, $name = '', $link_pdf = '', $position = 0, $properties = '', $date_created = '', $date_updated = '', $status = 0, $category_id = 0)
	{
        $this->id = $id;
        $this->store_id = $store_id;
        $this->category_id = $category_id;
		$this->name = $name;
		$this->link_pdf = $link_pdf;
		$this->position = $position;
		$this->properties = unserialize($properties);
		$this->date_created = $date_created;
        $this->date_updated = $date_updated;
		$this->status = $status;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}	
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
    function getCategoryId() {
		return $this->category_id;
	}	
	function setCategoryId($nValue) {
		$this->category_id=$nValue;
	}
	function getLinkPdf() {
		return $this->link_pdf;
	}	
	function setLinkPdf($nValue) {
		$this->link_pdf=$nValue;
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
	{
		$this->date_updated=$nValue;
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
	function getStatusText() {
		global $amessages;
		return $amessages['status_text'][$this->status];
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}

	function getCategoryName() {
		include_once(ROOT_PATH."classes/dao/uploadpdfcategories.class.php");
		$pdfCategories = new UploadPdfCategories($this->store_id);
		return $pdfCategories->getNameFromId($this->category_id);
	}
}	
?>
