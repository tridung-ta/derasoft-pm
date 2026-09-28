<?php

/*************************************************************************
Class RecruitmentInfo
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Nguyen Anh Ngoc                                    
Last updated: 07/10/2009
Checked by: Mai Minh (03/06/2025)
 **************************************************************************/
include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/recruitmentinfo.class.php');

class Recruitments extends Model
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
		$this->table = DB_PREFIX . "recruitment";
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
			$object = new RecruitmentInfo(
				$result[0]['file'],
				$result[0]['mail'],
				$result[0]['parent_id'],
				$result[0]['age'],
				$result[0]['gender'],
				$result[0]['job_location'],
				$result[0]['location'],
				$result[0]['store_id'],
				$result[0]['name'],
				$result[0]['slug'],
				$result[0]['detail'],
				$result[0]['status'],
				$result[0]['properties'],
				$result[0]['date_created'],
				$result[0]['income'],
				$result[0]['degree'],
				$result[0]['experience'],
				$result[0]['rank'],
				$result[0]['number_recruits'],
				$result[0]['date_exp'],
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
				$objects[] = new RecruitmentInfo(
					$result['file'],
					$result['mail'],
					$result['parent_id'],
					$result['age'],
					$result['gender'],
					$result['job_location'],
					$result['location'],
					$result['store_id'],
					$result['name'],
					$result['slug'],
					$result['detail'],
					$result['status'],
					$result['properties'],
					$result['date_created'],
					$result['income'],
					$result['degree'],
					$result['experience'],
					$result['rank'],
					$result['number_recruits'],
					$result['date_exp'],
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
		$result = $this->add($fields, $key, 'NULL');
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

	function changeDetail($id = 0, $detail = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('detail' => $detail), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	// function cleanTrash()
	// {
	// 	$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
	// 	if ($result) return 1;
	// 	return 0;
	// }

	// function cleanTrash($condition = '') 
	// {
	// 	$where = "`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED;
	// 	if ($condition) $where .= " AND ($condition)"; 
	// 	$result = $this->delete($where);
	// 	return $result ? 1 : 0;
	// }

	function cleanTrash($condition = '')
	{
		$where = "`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED;
		if ($condition) $where .= " AND ($condition)";

		// Chỉ children mới có file: nếu điều kiện đụng children thì unlink trước khi xoá
		$rows = $this->select('id,file,parent_id', $where);
		if ($rows) {
			foreach ($rows as $r) {
				if ((int)$r['parent_id'] > 0 && !empty($r['file'])) {
					$f = rtrim(ROOT_PATH, '/').'/upload/fileCV/'.basename((string)$r['file']);
					if (is_file($f)) @unlink($f);
				}
			}
		}

		return $this->delete($where) ? 1 : 0;
	}

	# Return a Product name from provided ID
	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}

	function checkDuplicate($value = '', $key = 'name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}
	
	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_recruitment p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'recruitment' 
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

	public function countByCondition($condition = '1>0') {
		$table = DB_PREFIX . "recruitment";
		$sql = "
			SELECT COUNT(*) AS c
			FROM {$table}
			WHERE store_id = '" . $this->store_id . "' AND ($condition)
		";
		$rows = $this->query($sql);
		return (int)($rows[0]['c'] ?? 0);
	}

	function checkDuplicateEmail($email, $parentId = null, $extraCondition = '')
	{
		$email = strtolower(trim($email)); // chuẩn hoá để so trùng
		$cond  = "`mail`='" . addslashes($email) . "'";

		// per-job: chỉ check trong phạm vi 1 tin TD
		if ($parentId !== null) {
			$cond .= " AND `parent_id`=" . (int)$parentId;
		}

		// tuỳ chọn: truyền thêm điều kiện ngoài (nếu cần)
		if ($extraCondition !== '') {
			$cond .= " AND (" . $extraCondition . ")";
		}

		// countByCondition đã tự thêm store_id = $this->store_id
		return (bool)$this->countByCondition($cond);
	}
}
