<?php
/*************************************************************************
Class Addon Info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class AddonInfo {
	var $id;			# language code (primary key)
	var $store_id;
	var $name;			# Name
	var $description;	# Description
	var $event;			# Event
	var $position;		# position
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	# Constructor
	function __construct($name, $description, $event, $position=0,$status=0, $store_id=0, $id = 0)
	{
		$this->id = $id;
		$this->store_id=$store_id;
		$this->name = $name;
		$this->description = $description;
		$this->event = $event;
		$this->position = $position;
		$this->status = $status;
	}

	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}	
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getDescription() {
		return $this->description;
	}
	function setDescription($nValue) {
		$this->description=$nValue;
	}
	function getEvent() {
		return $this->event;
	}
	function setEvent($nValue) {
		$this->event=$nValue;
	}
	function getEventName() {
		include_once(ROOT_PATH.'classes/dao/events.class.php');
		$events = new Events($this->store_id);
		return $events->getNameFromId($this->event);
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
}	
?>
