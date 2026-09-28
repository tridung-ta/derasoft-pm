<?php

/*************************************************************************
ClassStaticpage
----------------------------------------------------------------
DeraCMS Project
Company: Derasoft Co., Ltd                                  
Name: Tran Thi Kim Que                                  
Last updated: 15/10/2009    
 **************************************************************************/

include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/filecvinfo.class.php');

class FileCv extends Model
{
	public $table;
	public $_db;
	private $store_id;

	public function __construct($store_id = 0, $database = '')
	{
		if (!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX . "filecv";
		$this->store_id = $store_id;
	}
	public function FileCv($store_id = 0, $database = '')
	{
		$this->__construct($store_id, $database);
	}

	/*-----------------------------------------------------------------------*
* public function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	public function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new FileCvInfo(
				$result[0]['fullname'],
				$result[0]['email'],
				$result[0]['tel'],
				$result[0]['url'],
				$result[0]['created'],
				$result[0]['store_id'],
				$result[0]['status'],
				$result[0]['pid'],
				$result[0]['id'],

			);
			return $object;
		}
		return '';
	}
	/*-----------------------------------------------------------------------*
* public function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	public function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new FileCvInfo(
					$result['fullname'],
					$result['email'],
					$result['tel'],
					$result['url'],
					$result['created'],
					$result['store_id'],
					$result['status'],
					$result['pid'],
					$result['id']
				);
			}
			return $objects;
		}
		return '';
	}

	/*-----------------------------------------------------------------------*
* public function: addData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/

	# Add record
	public function addData($fields, $key = 'id')
	{
		$result = $this->add($fields, '$key', 'NULL');
		if ($result) return $result;
		return 0;
	}

	# Update record
	public function updateData($fields, $value = '', $key = 'id')
	{
		$result = $this->update($fields, "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}

	# Change status
	public function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	public function cleanTrash()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}
}
