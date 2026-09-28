<?php
# DeraCMS 4.0 Project
# Company: Derasoft
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025)
#***************************************************
class OptionStructureInfo
{
	var $id;			# Method code (primary key)
	var $store_id;
	var $module;		# Module in system
	var $module_id;
	var $field_name;			# Name method
	var $field_title;			# Title of custom field
	var $field_class; 			# Class CSS
	var $field_type;			# 1-textbox, 2-textarea, 3-list, 4-combo, 5-radio, 6-checkbox
	var $value;			# value for chose option
	var $required;		# 0-Disabled, 1-Active
	var $appearance;	# 0-Disabled, 1-Active
	var $position;		# Display order
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	# Constructor
	function __construct($module, $module_id, $field_name, $field_title, $field_class, $field_type, $value = '', $required = 0, $appearance = 0, $status = 0, $position = 0, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->module = $module;
		$this->module_id = $module_id;
		$this->field_name = $field_name;
		$this->field_title = $field_title;
		$this->field_class = $field_class;
		$this->field_type = $field_type;
		$this->value = $value;
		$this->required = $required;
		$this->appearance = $appearance;
		$this->status = $status;
		$this->position = $position;
	}
	public function OptionStructureInfo($module, $module_id, $field_name, $field_title, $field_class, $field_type, $value = '', $required = 0, $appearance = 0, $status = 0, $position = 0, $store_id = 0, $id = 0)
	{
		$this->__construct($module,$module_id, $field_name, $field_title, $field_class, $field_type, $value, $required, $appearance, $status, $position, $store_id, $id);
	}

	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}

	function getValue()
	{
		return unserialize($this->value);
	}

	function getBackEndValue()
	{
		$valueList = unserialize($this->value);
		$new_array = [];

		foreach ($valueList as $key => $value) {
			$new_array[] = $key . ':' . $value . "\n";
		}
		return implode('', $new_array);
	}

	function setValue($nValue)
	{
		$this->value = $nValue;
	}

	function getModule()
	{
		return $this->module;
	}
	function setModule($nValue)
	{
		$this->module = $nValue;
	}
	function getModuleId()
	{
		return $this->module_id;
	}
	function setModuleId($nValue)
	{
		$this->module_id = $nValue;
	}
	function getFieldName()
	{
		return $this->field_name;
	}
	function setFieldName($nValue)
	{
		$this->field_name = $nValue;
	}
	function getFieldTitle()
	{
		return $this->field_title;
	}
	function setFieldTitle($nValue)
	{
		$this->field_title = $nValue;
	}
	function getFieldClass()
	{
		return $this->field_class;
	}
	function setFieldClass($nValue)
	{
		$this->field_class = $nValue;
	}
	function getFieldType()
	{
		return $this->field_type;
	}
	function setFieldType($nValue)
	{
		$this->field_type = $nValue;
	}
	function getRequired()
	{
		return $this->required;
	}
	function setRequired($nValue)
	{
		$this->required = $nValue;
	}
	function getAppearance()
	{
		return $this->appearance;
	}
	function setAppearance($nValue)
	{
		$this->appearance = $nValue;
	}
	function getPosition()
	{
		return $this->position;
	}
	function setPosition($nValue)
	{
		$this->position = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getStatusText()
	{
		global $amessages;
		return $amessages['status_text'][$this->status];
	}

	function getRequiredText()
	{
		global $amessages;
		return $amessages['required_text'][$this->required];
	}
	function getAppearanceText()
	{
		global $amessages;
		return $amessages['appearance_text'][$this->appearance];
	}

	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getTypeTextBackend()
	{
		global $amessages;
		return $amessages['field_type'][$this->field_type];
	}

	function displayHTML($value, $error)
	{
		// $required = ($this->required == 1) ? " required " : "";
		$requiredName = ($this->required == 1) ? "* " : "";
		$requiredClass = (!empty($error) && $this->required == 1) ? "class='errormsg'" : "";
		switch ($this->field_type) {
			case "1":	# Textbox
				return "<p " . $requiredClass . "><label for=\"" . $this->field_name . "\" >" . $requiredName . $this->field_title . "</label>
				<input type=\"text\" value=\"$value\" name=\"" . $this->field_name . "\" id=\"" . $this->field_name . "\"" . ($this->field_class ? " class=\"" . $this->field_class . "\"" : "") . " /></p>";
				break;
			case "2":	# Textarea
				return "<p " . $requiredClass . " ><label for=\"" . $this->field_name . "\" >" . $requiredName . $this->field_title . "</label>
				<textarea rows=\"10\" cols=\"20\" name=\"" . $this->field_name . "\" id=\"" . $this->field_name . "\"" . ($this->field_class ? " class=\"" . $this->field_class . "\"" : "") . " >$value</textarea></p>";
				break;
			case "3":	# WYSIWYG
				return "<p " . $requiredClass . " ><label for=\"" . $this->field_name . "\" >" . $requiredName . $this->field_title . "</label></p>
				<textarea rows=\"10\" cols=\"20\" name=\"" . $this->field_name . "\" id=\"" . $this->field_name . "\"" . ($this->field_class ? " class=\"" . $this->field_class . "\"" : "") . " >$value</textarea>
				<script type=\"text/javascript\">var editor = CKEDITOR.replace('" . $this->field_name . "');</script>";
				break;
			case "4":    # Listbox
				$selectedValues = is_array($value) ? $value : explode(", ", $value);
				$optionMap = array_flip($this->getValue());
				$selectedKeys = array_map(function ($val) use ($optionMap) {
					return $optionMap[$val] ?? $val;
				}, $selectedValues);

				$return = "<p " . $requiredClass . " ><label for=\"" . $this->field_name . "\">" . $requiredName . $this->field_title . "</label>
    			<select name=\"" . $this->field_name . "[]\" id=\"" . $this->field_name . "[]\"" . ($this->field_class ? " class=\"" . $this->field_class . "\"" : "") . " size=\"8\" multiple=\"multiple\">";

				foreach ($this->getValue() as $ckey => $cvalue) {
					$selected = in_array($ckey, (array)$selectedKeys) ? " selected" : "";
					$return .= "<option value='$ckey'$selected >$cvalue</option>";
				}
				$return .= "</select></p>";
				return $return;
				break;
			case "5":	# Combobox		
				$options = $this->getValue();
				$keyValue = array_search($value, $options);
				$return = "<p " . $requiredClass . " ><label for=\"" . $this->field_name . "\">" . $requiredName . $this->field_title . "</label>
					<select name=\"" . $this->field_name . "\" id=\"" . $this->field_name . "\"" . ($this->field_class ? " class=\"" . $this->field_class . "\"" : "") . " >";

				foreach ($options as $ckey => $cvalue) {
					$selected = ($keyValue === false ? $value == $ckey : $keyValue == $ckey) ? " selected" : ""; 
					$return .= "<option value=\"$ckey\"$selected>$cvalue</option>";
				}
				$return .= "</select></p>";
				return $return;
				break;
			case "6":	# Radio
				$options = $this->getValue();
				$keyValue = array_search($value, $options);
				$return = "<p " . $requiredClass . " ><label for=\"" . $this->field_name . "\">" . $requiredName . $this->field_title . "</label>";
				foreach ($options as $ckey => $cvalue) {
					$checked = ($keyValue === false ? $value == $ckey : $keyValue == $ckey) ? " checked" : ""; 
					$return .= "<input type=\"radio\" name=\"" . $this->field_name . "\" class=\"box\" value=\"$ckey\"$checked  />
					<label class=\"lbl\">$cvalue</label>";
				}		
				$return .= "</p>";
				return $return;
				break;
			case "7":    # Checkbox
				$selectedValues = is_array($value) ? $value : explode(", ", $value);
				$optionMap = array_flip($this->getValue());
				$selectedKeys = array_map(function ($val) use ($optionMap) {
					return $optionMap[$val] ?? $val;
				}, $selectedValues);

				$return = "<p " . $requiredClass . " ><label for=\"" . $this->field_name . "\" >" . $requiredName . $this->field_title . "</label>";
				foreach ($this->getValue() as $ckey => $cvalue) {
					$checked = in_array($ckey, (array)$selectedKeys) ? " checked" : "";
					$return .= "<input type=\"checkbox\" name=\"" . $this->field_name . "[]\" id=\"" . $this->field_name . "\" class=\"box\" value=\"$ckey\"$checked  />
        		<label for=\"" . $this->field_name . "\" class=\"lbl\">$cvalue</label>";
				}
				$return .= "</p>";
				return $return;
				break;
		}
	}
}
