<?php

/*************************************************************************
Class CarBatterys
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Xuyen Tran                                    
Last updated: 22/09/2011
Checked by: Mai Minh (03/06/2025)
 **************************************************************************/
include_once(ROOT_PATH . "classes/database/model.class.php");
include_once(ROOT_PATH . "classes/dao/carbatteryinfo.class.php");

class CarBatterys extends Model
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
		$this->table = DB_PREFIX . "car_battery";
		$this->store_id = $store_id;
	}

	/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		$result = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new CarBatteryInfo(
				$result[0]['status'],
				$result[0]['store_id'],
				$result[0]['battery_id'],
				$result[0]['car_id'],
				$result[0]['car_type'],
				$result[0]['battery_note'],
				$result[0]['properties'],
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
	$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND $condition", $sort, $start, $items_per_page);
	if ($results) {
		$objects = array();
		foreach ($results as $key => $result) {
			$objects[] = new CarBatteryInfo(
				$result['status'],
				$result['store_id'],
				$result['battery_id'],
				$result['car_id'],
				$result['car_type'],
				$result['battery_note'],
				$result['properties'],
				$result['id']
				);
		}
		return $objects;
	}
	return 0;
}

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

	# Clean trash
	function cleanTrash()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}
	function checkDuplicate($value = '', $key = 'battery_id', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}
}
