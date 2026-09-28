<?php
/*************************************************************************
Class ProductCategory
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class ProductCategoryInfo
{
	public $id;			# Primary key
	public $parent_id;		# Parent category
	public $list_parent_id;		# List of parent category ids
	public $store_id;		# Estore id
	public $slug;			# Slug
	public $name;			# Category name
	public $keyword;		# Cagegory keyword
	public $description;	# Category description
	public $position;		# Position
	public $viewed;		# Number of views
	public $properties;	# Properties
	public $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished
	public $home;
	public $avatar_id;		# Avatar upload id
	public $avatar_url;		# Avatar url

	public $show_on_about_page;	# 0-No, 1-Yes
	public $date_created;
	public $date_updated;
	
	# Constructor
	function __construct($parent_id, $list_parent_id, $store_id, $slug, $name, $keyword, $description, $position, $viewed, $properties, $status, $home, $avatar_id, $avatar_url, $show_on_about_page, $date_created, $date_updated, $id = 0)
	{
		$this->id = $id;
		$this->parent_id = $parent_id;
		$this->list_parent_id = $list_parent_id;
		$this->store_id = $store_id;
		$this->slug = $slug;
		$this->name = $name;
		$this->keyword = $keyword;
		$this->description = $description;
		$this->position = $position;
		$this->viewed = $viewed;
		$this->properties = unserialize($properties);
		$this->status = $status;
		$this->home = $home;
		$this->avatar_id = $avatar_id;
		$this->avatar_url = $avatar_url;
		$this->show_on_about_page = $show_on_about_page;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
	}

	function getAvatarId()
	{
		return $this->avatar_id;
	}
	function setAvatarId($nValue)
	{
		$this->avatar_id = $nValue;
	}
	function getAvatarUrl()
	{
		return $this->avatar_url;
	}
	function setAvatarUrl($nValue)
	{
		$this->avatar_url = $nValue;
	}

	function getHome()
	{
		return $this->home;
	}

	function getShowOnAboutPage()
	{
		return $this->show_on_about_page;
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getParentId()
	{
		return $this->parent_id;
	}
	function setParentId($nValue)
	{
		$this->parent_id = $nValue;
	}
	function getListParentId()
	{
		return $this->list_parent_id;
	}
	function setListParentId($nValue)
	{
		$this->list_parent_id = $nValue;
	}
	function getStoreId()
	{
		return $this->store_id;
	}
	function setStoreId($nValue)
	{
		$this->store_id = $nValue;
	}
	function getSlug()
	{
		return $this->slug;
	}

	function setSlug($nValue, $lang = 'vn')
	{
		if ($lang == 'vn') $this->slug = $nValue;
		else $this->properties['custom_' . $lang . '_slug'] = stripslashes($nValue);
	}
	function getName($lang = 'vn')
	{
		if ($lang == 'vn') return $this->name;
		elseif (isset($this->properties['custom_' . $lang . '_name'])) return $this->properties['custom_' . $lang . '_name'];
	}
	function setName($nValue, $lang = 'vn')
	{
		if ($lang == 'vn') $this->name = stripslashes($nValue);
		else  $this->properties['custom_' . $lang . '_name'] = stripslashes($nValue);
	}
	function getKeyword($lang = 'vn')
	{
		if ($lang == 'vn') return $this->keyword;
		elseif (isset($this->properties['custom_' . $lang . '_keyword'])) return $this->properties['custom_' . $lang . '_keyword'];
	}
	function setKeyword($nValue, $lang = 'vn')
	{
		if ($lang == 'vn') $this->keyword = stripslashes($nValue);
		else $this->properties['custom_' . $lang . '_keyword'] = stripslashes($nValue);
	}
	function getDescription($lang = 'vn')
	{
		if ($lang == 'vn') return $this->description;
		elseif (isset($this->properties['custom_' . $lang . '_description'])) return $this->properties['custom_' . $lang . '_description'];
	}
	function setDescription($nValue, $lang = 'vn')
	{
		if ($lang == 'vn') $this->description = stripslashes($nValue);
		else $this->properties['custom_' . $lang . '_description'] = stripslashes($nValue);
	}
	function getPosition()
	{
		return $this->position;
	}
	function setPosition($nValue)
	{
		$this->position = $nValue;
	}

	function getActiveProducts()
	{
		include_once(ROOT_PATH . "classes/dao/products.class.php");
		$products = new Products($this->store_id);
		$rowsPages = $products->getNumItems('id', "`category_id` = '" . $this->id . "' AND `status` = '1'");
		return $rowsPages['rows'];
	}
	function getNumProducts()
	{
		include_once(ROOT_PATH . "classes/dao/products.class.php");
		$products = new Products($this->store_id);
		$rowsPages = $products->getNumItems('id', "`category_id` = '" . $this->id . "'");
		return $rowsPages['rows'];
	}

	function getViewed()
	{
		return $this->viewed;
	}
	function setViewed($nValue)
	{
		$this->viewed = $nValue;
	}
	function getProperty($key)
	{
		if (isset($this->properties[$key])) return '' . $this->properties[$key];
		return '';
	}
	function setProperty($key, $nValue)
	{
		$this->properties[$key] = stripslashes($nValue);
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

	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created = $nValue;
	}
	function getDateUpdated()
	{		
		return $this->date_updated;
	}
	function setDateUpdated($nValue)	{
		$this->date_updated = $nValue;
	}
	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status_product'][$this->status];
	}
	function getUrl($page = 1, $keywords = '', $sort_key = 'position', $sort_direction = 'asc')
	{
		$url = '';
		if (URL_TYPE == 1 || $page > 1) {	# Query string
			$url = '/' . SCRIPT . '?act=category&id=' . $this->id . '&pg=' . $page . '&kw=' . $keywords . '&sk=' . $sort_key . '&sd=' . $sort_direction;
			return $url;
		} elseif (URL_TYPE == 2) {	# SEO
			$url = '/' . $this->slug . ($page > 1 ? '-p' . $page : '') . '.html';
			return $url;
		} else return '';
	}

	function getChildren($page = 1, $condition = "`status` = '1'", $sort = array('position' => 'asc'), $items_per_page = 100)
	{
		include_once(ROOT_PATH . "classes/dao/productcategories.class.php");
		$productCategories = new ProductCategories($this->store_id);
		$productCategoryItems = $productCategories->getObjects($page, "`parent_id` = '" . $this->id . "' AND $condition", $sort, $items_per_page);
		return $productCategoryItems;
	}
	function getParentIdActive()
	{
		include_once(ROOT_PATH . "classes/dao/productcategories.class.php");
		$productCategories = new ProductCategories($this->store_id);
		if ($this->parent_id == 1) return $this->id;
		elseif ($this->parent_id > 1) {
			$categoryInfo = $productCategories->getObject($this->parent_id);
			return $categoryInfo->getId();
		} else return '';
	}

	function getH1($lang = 'vn')
	{
		if ($lang == 'vn') return $this->properties['custom_h1'];
		elseif (isset($this->properties['custom_' . $lang . '_h1'])) return $this->properties['custom_' . $lang . '_h1'];
		else return '';
	}
}
