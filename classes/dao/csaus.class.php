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
include_once(ROOT_PATH . 'classes/dao/csauinfo.class.php');

class Csaus extends Model
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
		$this->table = DB_PREFIX . "csau";
		$this->store_id = $store_id;
	}
	public function Csaus($store_id = 0, $database = '')
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
			$object = new CsauInfo(
				$result[0]['fullname'],
				$result[0]['avatar'],
				$result[0]['details'],
				$result[0]['created'],
				$result[0]['store_id'],
				$result[0]['status'],
				$result[0]['cat_id'],
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
				$objects[] = new CsauInfo(
					$result['fullname'],
					$result['avatar'],
					$result['details'],
					$result['created'],
					$result['store_id'],
					$result['status'],
					$result['cat_id'],
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
	#Return a Static Title from provided ID
	function getTitleFromId($id='') {
		if(!$id) return '';
		$result = $this->select('fullname',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['fullname'];
		return '';
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

	function getCustomerField()
	{
		$sql = "
			SELECT
				p.id,
				COALESCE(GROUP_CONCAT(cfv.field_value ORDER BY cfv.id ASC SEPARATOR ', '), 'No Data') AS value_list
			FROM dc_csau p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'csau' AND cf.status = 1 AND cf.appearance = 1
			LEFT JOIN dc_custom_options_value cfv ON p.id = cfv.key_id AND cf.id = cfv.field_id
			GROUP BY p.id
			ORDER BY p.id DESC
		";

		$result = $this->_db->query($sql);

		if (!$result) {
			die("Lỗi SQL: " . $this->_db->error);
		}

		if (!$result) {
			return [];
		}

		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		return $data;
	}

	function getCustomerFieldEdit($id)
	{
		$sql = "
			SELECT
				GROUP_CONCAT(DISTINCT cfv.id ORDER BY cfv.id ASC SEPARATOR ', ') AS id_list,
				GROUP_CONCAT(cfv.field_value ORDER BY cfv.id ASC SEPARATOR ', ') AS value_list
			FROM dc_csau p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'csau' AND cf.status = 1 AND cf.appearance = 1
			LEFT JOIN dc_custom_options_value cfv ON p.id = cfv.key_id AND cf.id = cfv.field_id
			WHERE p.id = " . $id . "
			GROUP BY p.id
			ORDER BY p.id DESC
		";

		$result = $this->_db->query($sql);

		if (!$result) {
			return [];
		}

		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		return $data;
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_csau p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'csau' 
				AND cf.status = 1 
				AND cf.appearance = 1
			JOIN dc_custom_options_value cfv 
				ON p.id = cfv.key_id 
				AND cf.id = cfv.field_id 
				AND cfv.field_value LIKE '%" . $kw . "%'
			GROUP BY p.id
			ORDER BY p.id DESC
		";

		$result = $this->_db->query($sql);

		if (!$result) {
			return false;
		}

		if ($result->num_rows == 0) {
			return false;
		}

		$ids = [];
		while ($row = $result->fetch_assoc()) {
			$ids[] = $row['id'];
		}

		return "(" . implode(",", $ids) . ")";
	}
}
