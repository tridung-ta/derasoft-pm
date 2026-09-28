<?php
/*************************************************************************
Class Product Options
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 01/06/2025
Coder: Tien Le
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptioninfo.class.php');

class ProductOptions extends Model
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

		$this->table = DB_PREFIX . "product_options";
		$this->store_id = $store_id;
	}

/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		$result = $this->select('*', "(`store_id` = '" . $this->store_id . "' or `store_id`=0) AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new ProductOptionInfo(
				$result[0]['store_id'],
				$result[0]['pc_id'],
				$result[0]['cat_id'],
				$result[0]['name'],
				$result[0]['title'],
				$result[0]['class'],
				$result[0]['type'],
				$result[0]['value'],
				$result[0]['position'],
				$result[0]['status'],
				$result[0]['slug'],
				$result[0]['home'],
				$result[0]['avatar'],
				$result[0]['detail'],
				$result[0]['sapo'],
				$result[0]['properties'],
				$result[0]['list_size'],
				$result[0]['list_camera'],
				$result[0]['list_cambien'],
				$result[0]['list_fim'],
				$result[0]['list_ppf'],
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "$condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new ProductOptionInfo(
					$result['store_id'],
					$result['pc_id'],
					$result['cat_id'],
					$result['name'],
					$result['title'],
					$result['class'],
					$result['type'],
					$result['value'],
					$result['position'],
					$result['status'],
					$result['slug'],
					$result['home'],
					$result['avatar'],
					$result['detail'],
					$result['sapo'],
					$result['properties'],
					$result['list_size'],
					$result['list_camera'],
					$result['list_cambien'],
					$result['list_fim'],
					$result['list_ppf'],
					$result['id']
				);
			}
			return $objects;
		}
		return '';
	}
	/*-----------------------------------------------------------------------*
* Function: addData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/
	function addData($object, $key = 'id')
	{
		$result = $this->add($object, '$key', 'NULL');
		if ($result) return $result;
		return 0;
	}
	/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/
	function updateData($object, $value = '', $key = 'id')
	{
		$this->update($object, "(`store_id` = '" . $this->store_id . "' or `store_id`=0)  AND `$key` = '$value'");
	}

	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change position category
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

	# Change home
	function changeHome($id = 0, $home = '')
	{
		if (!$id) return 0;
		if ($this->update(array('home' => $home), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	function getParentIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('aid', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['aid'];
		return '';
	}

	# Return a AdsCategory name from provided ID
	function getNameFromSlug($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('name', " slug = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}
	function getListCameraFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('list_camera', " id = '$id'");
		if ($result) return $result[0]['list_camera'];
		return '';
	}
	function getNameFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('name', " id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}
	function getAllNameFromStringId($id)
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('name', " id IN ($id)");
		if ($result) return implode(',', array_column($result, 'name'));
		return '';
	}
	function getSlugFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('slug', " id = '$id'");
		if ($result) return $result[0]['slug'];
		return '';
	}
	function getPcIdFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('pc_id', " id = '$id'");
		if ($result) return $result[0]['pc_id'];
		return '';
	}
	function getCatIdFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('cat_id', " id = '$id'");
		if ($result) return $result[0]['cat_id'];
		return '';
	}
	function getPcIdFromSlug($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('pc_id', " slug = '$id'");
		if ($result) return $result[0]['pc_id'];
		return '';
	}
	function getIdFromSlug($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('id', " slug = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
	function getIdFromClass($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('id', " class = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
	function getPIdFromSlug($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('pc_id', " name = '$id'");
		if ($result) return $result[0]['pc_id'];
		return '';
	}
	function getIdFromName($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('id', " slug = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
	function getIdFromName2($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('id', " name = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}

	function checkDuplicate($value = '', $key = 'name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}

	function generateCombo($value = '', $pc = '')
	{
		global $amessages;
		$combo = '';
		if ($pc == '')
			$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND status = '1'");
		else
			$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND status = '1' AND pc_id=$pc");
		if ($results) {
			foreach ($results as $key => $result) {
				$combo .= "<option value='" . $result['id'] . "'" . ($value == $result['id'] ? " selected" : "") . ">" . $result['name'] . "</option>";
			}
		}
		return $combo;
	}

	function generateComboId($value = '', $pc = '')
	{
		global $amessages;
		$combo = '';
		if ($pc == '')
			$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND status = '1'");
		else
			$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND status = '1' AND pc_id=$pc");
		if ($results) {
			foreach ($results as $key => $result) {

				return $result['id'];
			}
		}
		return 0;
	}
}
