<?php
# DeraCMS 4.0 Project
# Company: Derasoft
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025)

include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalueinfo.class.php');

class OptionValue extends Model
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

		$this->table = DB_PREFIX . "custom_options_value";
		$this->store_id = $store_id;
	}
	public function OptionValue($store_id = 0, $database = '')
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
			$object = new OptionValueInfo(
				$result[0]['status'],
				$result[0]['field_id'],
				$result[0]['key_id'],
				$result[0]['field_value'],
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

		$query = "
			SELECT v.*, s.field_name, s.field_title, s.field_type
			FROM " . DB_PREFIX . "custom_options_value v
			LEFT JOIN " . DB_PREFIX . "custom_options_structure s
			ON v.field_id = s.id
			WHERE (v.store_id = '" . $this->store_id . "' OR v.store_id = 0) AND $condition
		";

		if (!empty($sort)) {
			$orderClauses = [];
			foreach ($sort as $key => $value) {
				$orderClauses[] = "$key $value";
			}
			$query .= " ORDER BY " . implode(", ", $orderClauses);
		}

		$query .= " LIMIT $start, $items_per_page";

		$results = $this->query($query);

		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new OptionValueInfo(
					isset($result['field_name']) ? $result['field_name'] : '',
					$result['status'],
					$result['field_id'],
					$result['key_id'],
					$result['field_value'],
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
		$result = $this->add($object, $key, 'NULL');
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
		$result = $this->update($object, "(`store_id` = '" . $this->store_id . "' or `store_id`=0)  AND `$key` = '$value'");
		if ($result) return $result;
		return 0;
	}

	/*-----------------------------------------------------------------------*
	* Function: update or insert Data
	* Parameter: Info object
	* Return: 
			1 nếu thêm bản ghi mới.
			2 nếu cập nhật giá trị (1 dòng bị xóa và 1 dòng được chèn lại do ON DUPLICATE KEY UPDATE).
			0 nếu không có thay đổi nào.
	*-----------------------------------------------------------------------*/

	public function updateOrInsertFieldValue(string $fieldValue, int $fieldId, int $keyId, int $storeId) {
		if ($storeId <= 0 || $fieldId <= 0 || $keyId <= 0) {
			return 0;
		}
	
		try {
			$sql = "INSERT INTO {$this->table} (store_id, field_id, key_id, field_value, status)
					VALUES (?, ?, ?, ?, 1)
					ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)";

			$stmt = $this->_db->connection->prepare($sql);
			if (!$stmt) {
				throw new Exception("Lỗi prepare: " . $this->_db->connection->error);
			}
	
			// Bind parameters
			$stmt->bind_param("iiis", $storeId, $fieldId, $keyId, $fieldValue);
			$stmt->execute();
	
			$affectedRows = $stmt->affected_rows;
			$stmt->close();
	
			return $affectedRows;
		} catch (Exception $e) {
			error_log("Lỗi SQL: " . $e->getMessage());
			return 0;
		}
	}
	public function deleteData()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}
	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `key_id` = '$id'")) return 1;
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

	function getParentIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('aid', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['aid'];
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

	// old
	function getValueFromIdField($key_id = '', $field_id = '')
	{
	    if (!$key_id) return '';
	    $result = $this->select('field_value', "`store_id` = '" . $this->store_id . "' AND field_id = '$field_id' AND key_id = $key_id");
	    if ($result) return $result[0]['field_value'];
	    return '';
	}

	// new for edit
	function getAllValuesByKeyId($key_id) {
    $results = $this->select('field_id, field_value', "`store_id` = '" . $this->store_id . "' AND key_id = $key_id");
    $values = array();
    if ($results) {
        foreach ($results as $row) {
            $values[$row['field_id']] = $row['field_value'];
        }
    }
    return $values;
	}

	function checkDuplicate($value = '', $key = 'name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}

	function generateCombo($value = '')
	{
		global $amessages;
		$combo = '';
		$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND status = '1'");
		if ($results) {
			foreach ($results as $key => $result) {
				$combo .= "<option value='" . $result['id'] . "'" . ($value == $result['id'] ? " selected" : "") . ">" . $result['name'] . "</option>";
			}
		}
		return $combo;
	}

	// Get field_name by id from dc_custom_options_structure
	function searchOptionField($kw)
	{
		$kw_no_underscore = str_replace('_', '', $kw);

		$conditions = [
			"cos.field_name LIKE '%$kw%'",
			"REPLACE(cos.field_name, '_', '') LIKE '%$kw_no_underscore%'",
			"REPLACE(cos.field_name, '_', ' ') LIKE '%$kw%'"
		];

		$keywords = explode(" ", $kw);
		foreach ($keywords as $word) {
			$word = trim($word);
			if (!empty($word)) {
				$conditions[] = "REPLACE(cos.field_name, '_', ' ') LIKE '%$word%'";
			}
		}

		$conditionString = implode(" OR ", $conditions);
		
		$sql = "
			SELECT cov.id 
			FROM dc_custom_options_value cov
			LEFT JOIN dc_custom_options_structure cos ON cov.field_id = cos.id
			WHERE $conditionString
			ORDER BY cov.id DESC

		";

		$result = $this->_db->query($sql);

		if (!$result || $result->num_rows == 0) {
			return false;
		}

		$ids = [];
		while ($row = $result->fetch_assoc()) {
			$ids[] = $row['id'];
		}

		return "(" . implode(",", $ids) . ")";
	}
}
