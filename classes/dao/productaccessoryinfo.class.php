<?php

/*************************************************************************
Class Product
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/20250
Author: Mai Minh 
 **************************************************************************/
class ProductaccessoryInfo
{
	var $id;
	var $store_id;
	var $cat_id;
	var $slug;
	var $name;
	var $keyword;
	var $description;
	var $detail;
	var $avatar;
	var $viewed;
	var $created;
	var $updated;
	var $position;
	var $properties;
	var $status;
	var $home;
	var $trademark;
	var $carcompany;
	var $spcode;
	var $uses;
	var $specifications;
	var $size;
	var $origin;
	var $guarantee;
	var $tag;
	var $price;
	var $market_price;
	var $techno;
	var $pileloca;
	var $segment;
	var $category_name;



	# Constructor
	function __construct($id, $store_id, $cat_id, $slug, $name, $keyword, $description, $detail, $avatar, $viewed, $created, $updated, $position, $properties, $status, $home, $trademark, $carcompany, $spcode, $uses, $specifications, $size, $origin, $guarantee, $tag, $price, $market_price, $techno, $pileloca, $segment, $category_name)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->cat_id = $cat_id;
		$this->slug = $slug;
		$this->name = $name;
		$this->keyword = $keyword;
		$this->description = $description;
		$this->detail = $detail;
		$this->avatar = $avatar;
		$this->viewed = $viewed;
		$this->created = $created;
		$this->updated = $updated;
		$this->position = $position;
		$this->properties = unserialize($properties);
		$this->status = $status;
		$this->home = $home;
		$this->trademark = $trademark;
		$this->carcompany = $carcompany;
		$this->size = $size;
		$this->origin = $origin;
		$this->spcode = $spcode;
		$this->specifications = $specifications;
		$this->guarantee = $guarantee;
		$this->uses = $uses;
		$this->tag = $tag;
		$this->price = $price;
		$this->market_price = $market_price;
		$this->techno = $techno;
		$this->pileloca = $pileloca;
		$this->segment = $segment;
		$this->category_name = $category_name;
	}
	function getTechno()
	{
		return $this->techno;
	}
	function setTechno($nValue)
	{
		$this->techno = $nValue;
	}
	function getSegment()
	{
		return $this->segment;
	}
	function setSegment($nValue)
	{
		$this->segment = $nValue;
	}
	function getPileloca()
	{
		return $this->pileloca;
	}
	function setPileloca($nValue)
	{
		$this->pileloca = $nValue;
	}

	function getPrice()
	{
		return $this->price;
	}
	function setPrice($nValue)
	{
		$this->price = $nValue;
	}
	function getMarketPrice()
	{
		return $this->market_price;
	}
	function setMarketPrice($nValue)
	{
		$this->market_price = $nValue;
	}

	function getTag()
	{
		return $this->tag;
	}
	function setTag($nValue)
	{
		$this->tag = $nValue;
	}
	function getSpecifications()
	{
		return $this->specifications;
	}
	function setSpecifications($nValue)
	{
		$this->specifications = $nValue;
	}
	function getSpcode()
	{
		return $this->spcode;
	}
	function setSpcode($nValue)
	{
		$this->spcode = $nValue;
	}
	function getGuarantee()
	{
		return $this->guarantee;
	}
	function setGuarantee($nValue)
	{
		$this->guarantee = $nValue;
	}
	function getTrademark()
	{
		return $this->trademark;
	}
	function getCarcompany()
	{
		return $this->carcompany;
	}
	function getSize()
	{
		return $this->size;
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getStoreId()
	{
		return $this->store_id;
	}
	function setStoreId($nValue)
	{
		$this->store_id = $nValue;
	}
	function getCatId()
	{
		return $this->cat_id;
	}
	function setCatId($nValue)
	{
		$this->cat_id = $nValue;
	}
	function getCatSlug()
	{
		include_once(ROOT_PATH . "classes/dao/productcategories.class.php");
		$productCategories = new ProductCategories($this->store_id);
		return $productCategories->getSlugFromId($this->cat_id);
	}
	// function getCatName()
	// {
	// 	include_once(ROOT_PATH . "classes/dao/productcategories.class.php");
	// 	$productCategories = new ProductCategories($this->store_id);
	// 	return $productCategories->getNameFromId($this->cat_id);
	// }
	function getCatName() {
		return $this->category_name;
	}	
	function setCatName($nValue) {
		$this->category_name=$nValue;
	}
	function getSlug()
	{
		return $this->slug;
	}
	function setSlug($nValue)
	{
		$this->slug = stripslashes($nValue);
	}
	function getName($lang = 'vn')
	{
		if ($lang == 'vn')	return $this->name;
		elseif (isset($this->properties['custom_' . $lang . '_name'])) return $this->properties['custom_' . $lang . '_name'];
	}
	function setName($nValue, $lang = 'vn')
	{
		if ($lang == 'vn')	$this->name = stripslashes($nValue);
		else	$this->properties['custom_' . $lang . '_name'] = stripslashes($nValue);
	}
	function getKeyword($lang = 'vn')
	{
		if ($lang == 'vn')	return $this->keyword;
		elseif (isset($this->properties['custom_' . $lang . '_keyword'])) return $this->properties['custom_' . $lang . '_keyword'];
	}
	function setKeyword($lang = 'vn', $nValue)
	{
		if ($lang == 'vn')	$this->keyword = stripslashes($nValue);
		else	$this->properties['custom_' . $lang . '_keyword'] = stripslashes($nValue);
	}
	function getDescription($lang = 'vn')
	{
		if ($lang == 'vn')	return $this->description;
		elseif (isset($this->properties['custom_' . $lang . '_description'])) return $this->properties['custom_' . $lang . '_description'];
	}
	function setDescription($nValue, $lang = 'vn')
	{
		if ($lang == 'vn')	$this->description = stripslashes($nValue);
		else  $this->properties['custom_' . $lang . '_description'] = stripslashes($nValue);;
	}
	function getDetail($lang = 'vn')
	{
		if ($lang == 'vn')	return $this->detail;
		elseif (isset($this->properties['custom_' . $lang . '_detail'])) return $this->properties['custom_' . $lang . '_detail'];
	}
	function setDetail($nValue, $lang = 'vn')
	{

		if ($lang == 'vn') $this->detail = stripslashes($nValue);
		else $this->properties['custom_' . $lang . '_detail'] = stripslashes($nValue);;
	}
	function getAvatar()
	{
		$photos = $this->properties['photos'];
		if ($photos) return $photos[0];
		return '';
	}
	function setAvatar($nValue)
	{
		$this->avatar = $nValue;
	}
	function getViewed()
	{
		return $this->viewed;
	}
	function setViewed($nValue)
	{
		$this->viewed = $nValue;
	}
	function getDateCreated()
	{
		return $this->created;
	}
	function setDateCreated($nValue)
	{
		$this->created = $nValue;
	}
	function getUpdated()
	{
		return $this->updated;
	}
	function setUpdated($nValue)
	{
		$this->updated = $nValue;
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
	function getPosition()
	{
		return $this->position;
	}
	function setPosition($nValue)
	{
		$this->position = $nValue;
	}
	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getHome()
	{
		return $this->home;
	}
	function setHome($nValue)
	{
		$this->home = $nValue;
	}
	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status_product'][$this->status];
	}
	# Return 1 if File is not null
	function getNullFile($n)
	{
		for ($i = 1; $i <= $n; $i++) {
			$key = "file" . $i;
			if ($this->$key != '')
				return 1;
		}
		return '';
	}
	function getUrl($lang = 'vn')
	{
		$url = '';
		if (URL_TYPE == 1) {	# Query string
			$url = '/' . SCRIPT . '?act=product&id=' . $this->id;
			return $url;
		} elseif (URL_TYPE == 2) {	# SEO
			$url = '/' . $this->getCatSlug() . '/' . $this->slug . '-p' . $this->id . '.html';

			return $url;
		} elseif (URL_TYPE == 3) {	# SEO
			$url = '/' . $this->getCatSlug() . '/' . $this->slug . '-' . $this->id . '.html';

			return $url;
		} else return '';
	}
	function getOrderUrl($lang = 'vn')
	{
		$url = '/' . SCRIPT . '?act=order&product_id=' . $this->id . "&valid_time=2";
		return $url;
	}

	function getOrigin()
	{
		return $this->origin;
	}
	function setOrigin($nValue)
	{
		$this->origin = $nValue;
	}
	function getTireType()
	{
		return $this->tire_type;
	}

	function getUses()
	{
		return $this->uses;
	}
	function setUses($nValue)
	{
		$this->uses = $nValue;
	}
}
