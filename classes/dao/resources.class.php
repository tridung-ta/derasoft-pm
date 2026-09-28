<?php
/*************************************************************************
Class Products
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/productinfo.class.php");

class Resources extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."products";
		$this->store_id = $store_id;
	}

/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		if(!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		if($result) {
			$object = new ProductInfo
						(	$result[0]['sku'],
							$result[0]['slug'],
							$result[0]['name'],
							$result[0]['description'],
							$result[0]['avatar'],
							$result[0]['file1'],
							$result[0]['file2'],
							$result[0]['file3'],
							$result[0]['file4'],
							$result[0]['file5'],
							$result[0]['file6'],
							$result[0]['price'],
							$result[0]['sale_price'],
							$result[0]['currency'],
							$result[0]['viewed'],
							$result[0]['created'],
							$result[0]['position'],
							$result[0]['properties'],
							$result[0]['status'],
							$result[0]['cat_id'],
							$result[0]['store_id'],
							$result[0]['id']
						);
			return $object;
		}
		return 0;
	}
/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new ProductInfo
								(	$result['sku'],
									$result['slug'],
									$result['name'],
									$result['description'],
									$result['avatar'],
									$result['file1'],
									$result['file2'],
									$result['file3'],
									$result['file4'],
									$result['file5'],
									$result['file6'],
									$result['price'],
									$result['sale_price'],
									$result['currency'],
									$result['viewed'],
									$result['created'],
									$result['position'],
									$result['properties'],
									$result['status'],
									$result['cat_id'],
									$result['store_id'],
									$result['id']
								);
			}
			return $objects;
			
		}
		return 0;
	}

/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	# Add record
	function addData($fields,$key = 'id') {
		$result = $this->add($fields,'$key','NULL');
		if($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id') {
		$result = $this->update($fields,"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change product category
	function changeCatId($id = 0, $catId = 0) {
		if(!$id) return 0;
		if($this->update(array('cat_id' => $catId), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change product position
	function changePosition($id = 0, $position = 0) {
		if(!$id) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	
		
	# Return a Product Id from provided ID
	function getIdFromSlug($slug='') {
		if(!$slug) return 0;
		$result = $this->select('id',"`store_id` = '".$this->store_id."' AND slug = '$slug'");
		if($result) return $result[0]['id'];
		return 0;
	}

	# Return a Product Name from provided slug
	function getNameFromSlug($slug='') {
		if(!$slug) return '';
		$result = $this->select('name',"`store_id` = '".$this->store_id."' AND slug = '$slug'");
		if($result) return $result[0]['name'];
		return '';
	}

	# Return a Product slug from provided ID
	function getSlugFromId($id='') {
		if(!$id) return '';
		$result = $this->select('slug',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['slug'];
		return '';
	}

	# Return a Product name from provided ID
	function getNameFromId($id='') {
		if(!$id) return '';
		$result = $this->select('name',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['name'];
		return '';
	}

	# Check duplicate
	function duplicateSlug($slug, $id = 0) {
		$rows = $this->countItems('id',"`store_id` = '".$this->store_id."' AND `slug` = '$slug'".($id?" AND `id` <> '$id'":''));
		if($rows) return 1;
		return 0;		
	}

	# Return a Product name from provided ID
	function getCatIdFromId($id='') {
		if(!$id) return '';
		$result = $this->select('cat_id',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['cat_id'];
		return '';
	}
	function getProductFromPid($pId) {
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND status =1 AND `cat_id`=$pId", array('created' => 'DESC'),  $start, '');
		if($results) {
			$productInfos = array();
			foreach($results as $key => $result) {
				$productInfos[] = new ProductInfo ($result['sku'],
													$result['slug'],
													$result['name'],
													$result['description'],
													$result['avatar'],
													$result['file1'],
													$result['file2'],
													$result['file3'],
													$result['file4'],
													$result['file5'],
													$result['file6'],
													$result['price'],
													$result['sale_price'],
													$result['currency'],
													$result['viewed'],
													$result['created'],
													$result['position'],
													$result['properties'],
													$result['status'],
													$result['cat_id'],
													$result['store_id'],
													$result['id']
													);
			}
			return $productInfos;
		}
		return '';
	}
}
?>
