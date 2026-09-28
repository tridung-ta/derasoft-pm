<?php
# DeraCMS 4.0 Project
# Company: Derasoft Co., Ltd
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025)

include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptiondefaultinfo.class.php');

class CustomProductOptionDefault extends Model
{
	var $table;
	var $_db;
	var $store_id;


	public function __construct($store_id = 0, $database = '')
	{
		if (!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;

		$this->table = DB_PREFIX . "custom_product_option_default";
		$this->store_id = $store_id;
	}
	public function CustomProductOptionDefault($store_id = 0, $database = '')
	{
		$this->__construct($store_id, $database);
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
			$object = new CustomProductOptionDefaultInfo(
				$result[0]['status'],
				$result[0]['value_default'],
				$result[0]['name'],
				$result[0]['store_id'],
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
		$results = $this->select('*', "(`store_id` = '" . $this->store_id . "' or `store_id`=0) AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new CustomProductOptionDefaultInfo(					
					$result['status'],
					$result['value_default'],
					$result['name'],
					$result['store_id'],
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
		$this->add($object, '$key', 'NULL');
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

	# Clean trash
	function cleanTrash()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}

	// function getParentIdFromId($id='') {
	// 	if(!$id) return '';
	// 	$result = $this->select('parent_id',"`store_id` = '".$this->store_id."' AND id = '$id'");
	// 	if($result) return $result[0]['parent_id'];
	// 	return '';
	// }

	public function getAllNamesAndValueDefault()
	{
		$results = $this->select('name, value_default', 'status = 1');
		$data = [];
		if ($results) {
			foreach ($results as $row) {
				$data[] = [
					'name' => $row['name'],
					'value_default' => json_decode($row['value_default'], true)
				];
			}
		}
		return $data;
	}

	function getNameFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('name', " id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}

	function getValueDefaultFromId($id = '0') {
		global $amessages;

		if (!$id) return [];

		$result = $this->select('value_default', "id = '$id'");
		if ($result) {
			$json = $result[0]['value_default'];
			$decoded = json_decode($json, true);
			return is_array($decoded) ? $decoded : [];
		}

		return [];
	}



	// function getModuleIdByName($moduleName) {
    //     $result = $this->select('id', "name = '$moduleName'");
    //     return $result ? $result[0]['id'] : null;
    // }

	function changePosition($id = 0, $position = 0) {
		if(!$id) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	function checkDuplicate($value = '', $key = 'name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}

	function generateCombo($selectedValue = '') {
		global $amessages;
		$combo = "";
		$results = $this->select('id, name', "(`store_id` = '" . $this->store_id . "' OR `store_id` = 0) AND status = '1'");
	
		if (!$results || !is_array($results)) {
			return "<option value=''>Không có dữ liệu</option>";
		}
	
		foreach ($results as $result) {
			$selected = ($selectedValue == $result['name']) ? " selected" : "";
			$combo .= "<option value='" . htmlspecialchars($result['name']) . "'$selected>" . htmlspecialchars($result['name']) . "</option>\n";
		}
	
		return $combo;
	}	
}
