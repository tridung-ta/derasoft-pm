<?php
/*************************************************************************
Class Custom Field info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class FieldInfo {
	var $Id;			# Method code (primary key)
	var $store_id;		
	var $module;		# Module name
	var $name;			# Name method
	var $title;			# Title of custom field
	var $class;			# CSS class name
	var $type;			# 1-textbox, 2-textarea, 3-list, 4-combo, 5-radio, 6-checkbox
	var $value;			# List value
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $position;		# Display order
	# Constructor
	function __construct($module, $name, $title, $class, $type, $value='',$status=0, $position=0, $store_id=0, $Id = 0)
	{
		$this->Id = $Id;
		$this->store_id=$store_id;
		$this->module = $module;
		$this->name = $name;
		$this->title = $title;
		$this->class = $class;
		$this->type = $type;
		$this->value = $value;
		$this->status = $status;
		$this->position = $position;
	}

	function getId() {
		return $this->Id;
	}	
	function setId($nValue) {
		$this->Id=$nValue;
	}	
	
	function getModule() {
		return $this->module;
	}	
	function setModule($nValue) {
		$this->module=$nValue;
	}
	function getName() {
		return $this->name;
	}	
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getTitle() {
		return $this->title;
	}	
	function setTitle($nValue) {
		$this->title=$nValue;
	}
	function getClass() {
		return $this->class;
	}	
	function setClass($nValue) {
		$this->class=$nValue;
	}
	
	function getType() {
		return $this->type;		
	}
	function setType($nValue) {
		$this->type=$nValue;
	}
	function getValue() {
		return unserialize($this->value);
	}
	function getBackEndValue() {
		$valueList = unserialize($this->value);
		$new_array = [];

		foreach ($valueList as $key => $value) {
			$new_array[] = $key . ':' . $value . "\n";
		}
		return implode('', $new_array);
	}
	function setValue($nValue) {
		$this->value=$nValue;
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
	function getTypeTextBackend() {
		global $amessages;
		return $amessages['field_type'][$this->type];
	}
	function displayHTML($value, $defaultValue = []) {
		switch($this->type) {
			case "1":	# Textbox
				return "<p id=\"css".$this->name."\"><label for=\"".$this->name."\">".$this->title."</label>
<input type=\"text\" value=\"$value\" name=\"".$this->name."\" id=\"".$this->name."\"".($this->class?" class=\"".$this->class."\"":"")." /></p>";
				break;
			case "2":	# Textarea
				return "<p id=\"css".$this->name."\"><label for=\"".$this->name."\">".$this->title."</label>
<textarea rows=\"10\" cols=\"20\" name=\"".$this->name."\" id=\"".$this->name."\"".($this->class?" class=\"".$this->class."\"":"").">$value</textarea></p>";
				break;
			case "3":	# WYSIWYG
				return "<p id=\"css".$this->name."\"><label for=\"".$this->name."\">".$this->title."</label></p>
<textarea rows=\"10\" cols=\"20\" name=\"".$this->name."\" id=\"".$this->name."\"".($this->class?" class=\"".$this->class."\"":"").">$value</textarea>
<script type=\"text/javascript\">var editor = CKEDITOR.replace('".$this->name."', {allowedContent: true, contentsCss: ['https://mpx.vn/templates/mpx/css/common.css']});</script><br>";
				break;
			case "4":	# Listbox
				if($value == '') $value = [];
				$return = "<p id=\"css".$this->name."\"><label for=\"".$this->name."\">".$this->title."</label>
<select name=\"".$this->name."[]\" id=\"".$this->name."[]\"".($this->class?" class=\"".$this->class."\"":"")." size=\"8\" multiple=\"multiple\">";
				foreach($this->getValue() as $ckey => $cvalue) {
					$return .= "<option value='$ckey'".(in_array($ckey,$value)?" selected":"").">$cvalue</option>";
				}
				$return .= "</select></p>";
				return $return;
				break;
			case "5":	# Combobox		
				$return = "<p id=\"css".$this->name."\"><label for=\"".$this->name."\">".$this->title."</label>
<select name=\"".$this->name."\" id=\"".$this->name."\"".($this->class?" class=\"".$this->class."\"":"").">";
				foreach($this->getValue() as $ckey => $cvalue) {
					$return .= "<option value='$ckey'".($value==$ckey?" selected":"").">$cvalue</option>";
				}
				$return .= "</select></p>";
				return $return;
				break;
			case "6":	# Radio
					$return = "<p id=\"css".$this->name."\"><label for=\"".$this->name."\">".$this->title."</label>";
					foreach($this->getValue() as $ckey => $cvalue) {
						$return .= "<input type=\"radio\" name=\"".$this->name."\" id=\"".$this->name."\" class=\"box\" value=\"$ckey\"".($value==$ckey?" checked":"")." /><label for=\"".$this->name."\" class=\"lbl\">$cvalue</label>";
					}
					$return .= "</p>";
					return $return;
					break;
			case "7": # Checkbox
					if ($value == '') {
						$value = $defaultValue;
					}

					if (!is_array($value)) {
						$value = [$value];
					}

					$return = "<p id=\"css".$this->name."\">
						<label for=\"".$this->name."\">".$this->title."</label>";

					foreach ($this->getValue() as $ckey => $cvalue) {
						$return .= "<input type=\"checkbox\"
							name=\"".$this->name."[]\"
							id=\"".$this->name."_".$ckey."\"
							class=\"box\"
							value=\"".$ckey."\""
							.(in_array($ckey, $value) ? " checked" : "")
							." />
							<label for=\"".$this->name."_".$ckey."\" class=\"lbl\">".$cvalue."</label>";
					}

					$return .= "</p>";

					return $return;
					break;
		}
				
	}
}	
?>
