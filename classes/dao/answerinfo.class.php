<?php
/*************************************************************************
Class AnswerInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class AnswerInfo {
	var $id;			# Primary key
	var $qid;
	var $slug;			# Slug
	var $title;			# Category title
	var $counts;
	var $position;		# Position
	var $properties;	# Properties
	var $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($qid,$slug, $title,$counts, $position, $properties, $status, $id = 0)
	{
		$this->id = $id;
		$this->qid = $qid;
		$this->slug = $slug;
		$this->title = stripslashes($title);
		$this->counts=$counts;
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
	function getQId() {
		return $this->qid;
	}	
	function setQId($nValue) {
		$this->qid=$nValue;
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
	function setTitle($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->title=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_title']=stripslashes($nValue);
	}
	function getCounts() {
		return $this->counts;
	}	
	function setCounts($nValue) {
		$this->counts=$nValue;
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
	function getQuestionTitle() {
		include_once(ROOT_PATH."classes/dao/questions.class.php");
		$question = new Questions();
		return $question->getTitleFromId($this->qid);
		}
}	
?>
