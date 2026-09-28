<?php
/*************************************************************************
Class QuestionInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh
**************************************************************************/
class QuestionInfo {
	var $id;			# Primary key
	var $slug;			# Slug
	var $title;			# Category title
	var $position;		# Position
	var $properties;	# Properties
	var $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($slug, $title, $position, $properties, $status, $id = 0)
	{
		$this->id = $id;
		$this->slug = $slug;
		$this->title = $title;
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
	function getSlug() {
		return $this->slug;
	}	
	function setSlug($nValue) {
		$this->slug=$nValue;
	}
	function getTitle($lang = 'vn') {
		if($lang == 'vn')	return $this->title;
		else return $this->properties['custom_'.$lang.'_title'];
	}
	function setTitle($lang = 'vn', $nValue) {
		if($lang == 'vn')	$this->title=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_title']=stripslashes($nValue);
	}
	function getPosition() {
		return $this->position;
	}	
	function setPosition($nValue) {
		$this->position=$nValue;
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
	function totalCountsOfAnswerFromQId(){
		include_once(ROOT_PATH."classes/dao/answers.class.php");
		$answers = new Answers();
		$results = $answers->select('SUM(id)', "status=1 AND id =".$this->id);
		if($results) {
			
			return $results;
		}
		return 0;

		
		}
}	
?>
