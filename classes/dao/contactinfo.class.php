<?php

class contactInfo {
	var $id;			# Ad code (primary key)
	var $store_id;				
    var $type_of_service;			# Ad name
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order	# Properties
	var $name;			# Ad name			# Ad name
	var $email;
	var $description;
    var $phone_number;
    var $company_name;
	var $expected_budget;
    var $properties;
	var $date_created;	# Date created
	var $date_updated;	# Date updated
	
	# Constructor
// Dòng 20 — thêm = null vào $properties
	function __construct(
		$id = 0,
		$store_id = 0,
		$type_of_service = 0,
		$status = 0,
		$position = 0,
		$name = '',
		$email = '',
		$description = '',
		$phone_number = '',
		$company_name = '',
		$expected_budget = '',
		$date_created = '',
		$date_updated = '',
		$properties = null   // ← thêm = null
	) {
		$this->id = $id;
		$this->store_id = $store_id;
		$this->type_of_service = $type_of_service;
		$this->status = $status;
		$this->position = $position;
		$this->name = $name;
		$this->email = $email;
		$this->description = $description;
		$this->phone_number = $phone_number;
		$this->company_name = $company_name;
		$this->expected_budget = $expected_budget;
		$this->properties = !empty($properties) ? unserialize($properties) : []; // ← fix null
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}	
	function getEmail() {
		return $this->email;
	}	
	function setEmail($nValue) {
		$this->email=$nValue;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
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
    function getPhoneNumber() {
        return $this->phone_number;        
    }       
    function setPhoneNumber($nValue) {
        $this->phone_number = $nValue;
    }
	function getDescription() {
		return $this->description;
	}
	function setDescription($nValue) {
		$this->description = $nValue;
	}
	function getCompanyName() {
		return $this->company_name;
	}
	function setCompanyName($nValue) {
		$this->company_name = $nValue;
	}
	function getExpectedBudget() {
		return $this->expected_budget;
	}
	function setExpectedBudget($nValue) {
		$this->expected_budget = $nValue;
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
}	
?>
