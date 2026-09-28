<?php

/*************************************************************************
Class question
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated:28/06/2010
Coder: Thai Nguyen
Reviewed by: Mai Minh (03/06/2025)

**************************************************************************/
include_once(ROOT_PATH . "classes/database/model.class.php");
include_once(ROOT_PATH . "classes/dao/imginfo.class.php");

class Imgs extends Model
{
	var $table;
	var $_db;

	function __construct($database = '')
	{
		if (!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX . "img";
	}

	/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		$result = $this->select('*', "`$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new ImgInfo(
				$result[0]['url_l'],
				$result[0]['url_a'],
				$result[0]['status'],
				$result[0]['name'],
				$result[0]['store_id'],
				$result[0]['date_created'],
				$result[0]['cat_id'],
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "$condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new ImgInfo(
					$result['url_l'],
					$result['url_a'],
					$result['status'],
					$result['name'],
					$result['store_id'],
					$result['date_created'],
					$result['cat_id'],
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
	function addData($fields, $key = 'id')
	{
		$result = $this->add($fields, '$key', 'NULL');
		if ($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id')
	{
		$result = $this->update($fields, "`$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`id` = '$id'")) return 1;
		return 0;
	}
	# Clean img
	function DeteImg($id)
	{
		$result = $this->delete("`id` = $id");
		if ($result) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash()
	{
		$result = $this->delete("`status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}
	# Return a Article name from provided ID
	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}
	function getUrlFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('url_l', "id = '$id'");
		if ($result) return $result[0]['url_l'];
		return '';
	}
	function getUrlAFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('url_a', "id = '$id'");
		if ($result) return $result[0]['url_a'];
		return '';
	}
	# Return a Article name from provided ID
	function getIdFromUrlL($id = '')
	{
		if (!$id) return '';
		$result = $this->select('id', "`url_l` = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
}
