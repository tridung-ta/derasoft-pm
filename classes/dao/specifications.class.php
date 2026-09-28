<?php

/*************************************************************************
Class SpecificationsInfo
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Nguyen Anh Ngoc                                    
Last updated: 07/10/2009
Checked by: Mai Minh (03/06/2025)
 **************************************************************************/
include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/specificationsinfo.class.php');

class Specifications extends Model
{
	var $table;
	var $_db;
	var $store_id;
	function __construct($store_id = 0, $database = '')
	{
		if (!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX . "specifications";
		$this->store_id = $store_id;
	}
	/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new SpecificationsInfo(
				$result[0]['parent_id'],
				$result[0]['store_id'],
				$result[0]['mc_id'],
				$result[0]['name'],
				$result[0]['url'],
				$result[0]['position'],
				$result[0]['status'],
				$result[0]['properties'],
				$result[0]['date_created'],
				$result[0]['cat_id'],
				$result[0]['id']
			);
			return $object;
		}
		return '';
	}

	/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObjects($page = 1, $condition = '`pid` = 0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new SpecificationsInfo(
					$result['parent_id'],
					$result['store_id'],
					$result['mc_id'],
					$result['name'],
					$result['url'],
					$result['position'],
					$result['status'],
					$result['properties'],
					$result['date_created'],
					$result['cat_id'],
					$result['id']
				);
			}
			return $objects;
		}
		return '';
	}



	/*-----------------------------------------------------------------------*
	* Function: updateData
	* Parameter: Info object
	* Return: 1 if success, 0 if fail
	*-----------------------------------------------------------------------*/

	# Add record
	function addData($fields, $key = 'id')
	{
		$result = $this->add($fields, '$key', 'NULL');
		if ($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id')
	{
		$result = $this->update($fields, "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change product category
	function changeCId($id = 0, $cId = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('mc_id' => $cId), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change product position
	function changePosition($id = 0, $position = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('position' => $position), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}

	# Return a Product name from provided ID
	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}

	function getPropertiesFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('properties', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) {
			// Use unserialize to convert the serialized string to an array
			$properties = unserialize($result[0]['properties']);
			// Return the unserialized properties
			return $properties;
		}

		return '';
	}
	# Return a Id Product from slug
	function getIdFromSlug($slug = '')
	{
		if (!$slug) return '';
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `slug` = '$slug'");
		if ($result) return $result[0]['id'];
		return '';
	}
	function getIdFromName($slug = '')
	{
		if (!$slug) return '';
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `name` = '$slug'");
		if ($result) return $result[0]['id'];
		return '';
	}
	# Return a Product name from provided ID
	function getParentIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('parent_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['parent_id'];
		return '';
	}
	function getMcIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('mc_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['mc_id'];
		return '';
	}

	# Return a Product name from provided ID
	function getCIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('cat_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['cat_id'];
		return '';
	}
	function generateCombo($value = '', $noroot = 0)
	{
		global $amessages;
		$combo = '';
		if (!$noroot) $combo = '<option value="0"' . ($value == '0' ? " selected" : "") . '>' . $amessages['root'] . '</option>';
		$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND parent_id = '0'");
		if ($results) {
			foreach ($results as $key => $result) {
				$combo .= "<option value='" . $result['id'] . "'" . ($value == $result['id'] ? " selected" : "") . ">&nbsp;&nbsp;&nbsp;l--" . $result['name'] . "</option>";
				$s1results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND parent_id = '" . $result['id'] . "'");
				if ($s1results) {
					foreach ($s1results as $key1 => $result1) {
						$combo .= "<option value='" . $result1['id'] . "'" . ($value == $result1['id'] ? " selected" : "") . ">&nbsp;&nbsp;&nbsp;l&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;l--" . $result1['name'] . "</option>";
					}
				}
			}
		}
		return $combo;
	}
	function checkDuplicate($value = '', $key = 'name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}
}
