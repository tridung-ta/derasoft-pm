<?php
/*
DeraCMS 4.0 Project
Class Translator
Coder: Mai Minh
Last updated: 03/06/2025
*/
class Translator
{
	var $messages;
	
	#Constructor
	function __construct($messages) {
		$this->messages = $messages;
	}
	function msg($key='') {
		if(isset($this->messages[$key])) return $this->messages[$key];
		return '{'.$key.'}';
	}	
}
?>
