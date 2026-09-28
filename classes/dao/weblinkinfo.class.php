<?php
/*************************************************************************
Class QuestionInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class WeblinkInfo {
	var $id;			# Primary key
	var $name;			# Slug
	var $url;			# Category title
	var $properties;	# Properties
	var $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($name, $url, $properties, $status, $id = 0)
	{
		$this->id = $id;
		$this->name = $name;
		$this->url = $url;
		$this->properties = unserialize($properties);
		$this->status = $status;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getName($lang = 'vn') {
		if($lang == 'vn')	return $this->name;
		else return $this->properties['custom_'.$lang.'_name'];
	}
	function setName($nValue,$lang = 'vn') {
		if($lang == 'vn')	$this->name=stripslashes($nValue);
		else	$this->properties['custom_'.$lang.'_name']=stripslashes($nValue);
	}
	function getUrl() {
		return $this->url;
	}	
	function setUrl($nValue) {
		$this->url=$nValue;
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
