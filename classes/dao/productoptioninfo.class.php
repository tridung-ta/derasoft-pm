<?php
/*************************************************************************
Class Product option info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 01/06/2025
Coder: Tien LE
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
class ProductOptionInfo
{
	var $id;			# Primary key
	var $store_id;
	var $pc_id;
	var $cat_id;		# Product category id
	var $name;			# Name method
	var $title;			# Title of custom ProductOption
	var $class;			# CSS class name
	var $type;			# 1-textbox, 2-textarea, 3-list, 4-combo, 5-radio, 6-checkbox
	var $value;			# List value
	var $position;		# Display order
	var $status;		# 0-Disabled, 1-Active, 2-Deleted
	var $slug;		# 0-Disabled, 1-Active, 2-Deleted
	var $home;		# 0-Disabled, 1-Active, 2-Deleted
	var $avatar;
	var $detail;
	var $sapo;
	var $properties;	# Properties
	var $list_size;
	var $list_camera;
	var $list_cambien;
	var $list_fim;
	var $list_ppf;
	
	# Constructor
	function __construct($store_id, $pc_id, $cat_id, $name, $title, $class, $type, $value, $position, $status, $slug, $home, $avatar, $detail, $sapo, $properties, $list_size, $list_camera, $list_cambien, $list_fim, $list_ppf, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->pc_id = $pc_id;
		$this->cat_id = $cat_id;
		$this->name = $name;
		$this->title = $title;
		$this->class = $class;
		$this->type = $type;
		$this->value = $value;
		$this->status = $status;
		$this->slug = $slug;
		$this->home = $home;
		$this->avatar = $avatar;
		$this->detail = $detail;
		$this->sapo = $sapo;
		$this->position = $position;
		$this->properties = unserialize($properties);
		$this->list_size = $list_size;
		$this->list_camera = $list_camera;
		$this->list_cambien = $list_cambien;
		$this->list_fim = $list_fim;
		$this->list_ppf = $list_ppf;
	}
	function getListPpf()
	{
		return $this->list_ppf;
	}
	function setListPpf($nValue)
	{
		$this->list_ppf = $nValue;
	}
	function getListFim()
	{
		return $this->list_fim;
	}
	function setListFim($nValue)
	{
		$this->list_fim = $nValue;
	}

	function getListCamBien()
	{
		return $this->list_cambien;
	}
	function setListCamBien($nValue)
	{
		$this->list_cambien = $nValue;
	}
	function getListCamera()
	{
		return $this->list_camera;
	}
	function setListCamera($nValue)
	{
		$this->list_camera = $nValue;
	}
	function getListSize()
	{
		return $this->list_size;
	}
	function setListSize($nValue)
	{
		$this->list_size = $nValue;
	}
	function getSapo()
	{
		return $this->sapo;
	}
	function setSapo($nValue)
	{
		$this->sapo = $nValue;
	}
	function getAvatar()
	{
		return $this->avatar;
	}
	function setAvatar($nValue)
	{
		$this->avatar = $nValue;
	}
	function getDetails()
	{
		return $this->detail;
	}
	function setDetails($nValue)
	{
		$this->detail = $nValue;
	}
	function getHome()
	{
		return $this->home;
	}
	function setHome($nValue)
	{
		$this->home = $nValue;
	}
	function getCatId()
	{
		return $this->cat_id;
	}
	function setCatId($nValue)
	{
		$this->cat_id = $nValue;
	}
	function getSlug()
	{
		return $this->slug;
	}
	function setSlug($nValue)
	{
		$this->slug = $nValue;
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}

	function getPCId()
	{
		return $this->pc_id;
	}
	function setPCId($nValue)
	{
		$this->pc_id = $nValue;
	}
	function getPCName()
	{
		include_once(ROOT_PATH . "classes/dao/productcategories.class.php");
		$productCategories = new ProductCategories($this->store_id);
		return $productCategories->getNameFromId($this->pc_id);
	}
	function getName()
	{
		return $this->name;
	}
	function setName($nValue)
	{
		$this->name = $nValue;
	}
	function getTitle()
	{
		return $this->title;
	}
	function setTitle($nValue)
	{
		$this->title = $nValue;
	}
	function getClass()
	{
		return $this->class;
	}
	function setClass($nValue)
	{
		$this->class = $nValue;
	}

	function getType()
	{
		return $this->type;
	}
	function setType($nValue)
	{
		$this->type = $nValue;
	}
	function getPlainValue()
	{
		return $this->value;
	}
	function getValue()
	{
		// return unserialize($this->value);
		return $this->value;
	}
	function getBackEndValue()
	{
		$valueList = unserialize($this->value);
		$new_array = array_map(create_function('$key, $value', 'return $key.":".$value."\n";'), array_keys($valueList), array_values($valueList));
		return implode($new_array);
	}
	function setValue($nValue)
	{
		$this->value = $nValue;
	}

	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getPosition()
	{
		return $this->position;
	}
	function setPosition($nValue)
	{
		$this->position = $nValue;
	}
	function getStatusText()
	{
		global $amessages;
		return $amessages['status_text'][$this->status];
	}
	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getTypeTextBackend()
	{
		global $amessages;
		return $amessages['field_type'][$this->type];
	}
	function getProperty($key)
	{
		if (isset($this->properties[$key])) return $this->properties[$key];
		return '';
	}
	function setProperty($key, $nValue)
	{
		$this->properties[$key] = $nValue;
	}

	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties = $nValue;
	}
}
