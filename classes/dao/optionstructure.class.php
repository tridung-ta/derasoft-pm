<?php
# DeraCMS 4.0 Project
# Company: Derasoft
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025)
#*******************************************************************
include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructureinfo.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
class OptionStructure extends Model
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

		$this->table = DB_PREFIX . "custom_options_structure";
		$this->store_id = $store_id;
	}
	public function OptionStructure($store_id = 0, $database = '')
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
			$object = new OptionStructureInfo(
				$result[0]['module'],
				$result[0]['module_id'],
				$result[0]['field_name'],
				$result[0]['field_title'],
				$result[0]['field_class'],
				$result[0]['field_type'],
				$result[0]['value'],
				$result[0]['required'],
				$result[0]['appearance'],
				$result[0]['status'],
				$result[0]['position'],
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
				$objects[] = new OptionStructureInfo(
					$result['module'],
					$result['module_id'],
					$result['field_name'],
					$result['field_title'],
					$result['field_class'],
					$result['field_type'],
					$result['value'],
					$result['required'],
					$result['appearance'],
					$result['status'],
					$result['position'],
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
		$this->add($object, $key, NULL);
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
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) {
			$optionValue = new OptionValue($this->store_id, $this->_db);
			$optionValue->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `field_id` = '$id'");

			return 1;
		}
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
		$optionValue = new OptionValue($this->store_id, $this->_db);
                $results = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `status` = " . (int)S_DELETED);

                if ($results) {
                        $fieldIds = array_map(function ($result) {
                                return $result['id'];
                        }, $results);
                        if (!empty($fieldIds)) {
                                $fieldIdsStr = implode(',', $fieldIds);
                                $optionValue->delete("`store_id` = '" . $this->store_id . "' AND `field_id` IN ($fieldIdsStr)");
                        }
                }
                return $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . (int)S_DELETED) ? 1 : 0;
	}

	function getParentIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('module_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['module_id'];
		return '';
	}

	# Return a AdsCategory name from provided ID
	function getNameFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('field_name', " id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}
	function getFieldNameFromId($id = '0')
    {
        global $amessages;
        if (!$id) return $amessages['root'];
        $result = $this->select('field_name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
        if ($result) return $result[0]['field_name'];
        return '';
    }
	
	function getValueFromId($id)
	{
	    if (!$id) return '';
	    $result = $this->select('value', "`store_id` = '" . $this->store_id . "' AND id = '$id' ");
	    if ($result) return $result[0]['value'];
	    return '';
	}


	function getModuleFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('module', " id = '$id'");
		if ($result) return $result[0]['module'];
		return '';
	}

	function checkDuplicate($value = '', $key = 'field_name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}

	function generateCombo($value = '')
	{
		global $amessages;
		$combo = '';
		$results = $this->select('id,field_name', "`store_id` = '" . $this->store_id . "' AND status = '1'");
		if ($results) {
			foreach ($results as $key => $result) {
				$combo .= "<option value='" . $result['id'] . "'" . ($value == $result['id'] ? " selected" : "") . ">" . $result['field_name'] . "</option>";
			}
		}
		return $combo;
	}

	function getIdFromName($name = '')
	{
		global $amessages;
		if (!$name) return $amessages['root'];
		$result = $this->select('field_name', " field_name = '$name'");
		if ($result) return $result[0]['id'];
		return '';
	}

	function getNameFromModule($module)
	{
		global $amessages;
		if (!$module) return $amessages['root'];

		$result = $this->select('field_title', "status = 1 AND module = '$module' AND appearance = 1 ORDER BY position ASC");

		if (is_array($result)) {
			$names = [];
			foreach ($result as $row) {
				$names[] = $row['field_title'];
			}
			return $names;
		}

		return [];
	}

	function getCustomValueField($moduleDB, $module)
	{
		$sql = "
			SELECT p.id AS module_id, cos.id AS field_id, cos.field_title, cov.field_value 
			FROM dc_{$moduleDB} p 
			LEFT JOIN dc_custom_options_structure cos ON cos.module = '$module' 
			AND cos.status = 1 AND cos.appearance = 1 
			LEFT JOIN dc_custom_options_value cov ON p.id = cov.key_id 
			AND cos.id = cov.field_id 
			ORDER BY p.id DESC, cos.id ASC
    	";

		$result = $this->_db->query($sql);

		if (!$result) {
			return [];
		}

		$customValueField = [];

		while ($row = $result->fetch_assoc()) {
			$moduleId = $row['module_id'];
			$fieldId = $row['field_id'];
			$fieldValue = $row['field_value'];

			if (!isset($customValueField[$moduleId])) {
				$customValueField[$moduleId] = [];
			}

			$customValueField[$moduleId][$fieldId] = $fieldValue;
		}

		return $customValueField;
	}

	function getCustomFieldsMapping($module)
	{
		$result = $this->select('id, field_title', "status = 1 AND module = '$module' AND appearance = 1");

		if (!$result || !is_array($result)) {
			return [];
		}

		$fieldMap = [];

		foreach ($result as $row) {
			$fieldMap[$row['field_title']] = $row['id'];
		}

		return $fieldMap;
	}

	# Get a field value 
	public function getCustomOption($field_name) {
		$sql = "
				SELECT cov.field_value 
				FROM dc_custom_options_structure cos 
				LEFT JOIN dc_custom_options_value cov ON cos.id = cov.field_id
				WHERE cos.field_name = '$field_name' 
				AND cov.field_value IS NOT NULL
				LIMIT 1
			";
	
		$result = $this->_db->query($sql);
	
		if (!$result) {
			return null;
		}
	
		$row = $result->fetch_assoc();
	
		return $row['field_value'] ?? null;
	}

	public function getCustomOptionByModule($field_name, $module, $key_id = 1) {
		$sql = "
				SELECT cov.field_value 
				FROM dc_custom_options_structure cos 
				LEFT JOIN dc_custom_options_value cov ON cos.id = cov.field_id
				WHERE cos.field_name = '$field_name'
				AND cos.module = '$module'
				AND cov.key_id = $key_id
				AND cov.field_value IS NOT NULL
				LIMIT 1
			";
	
		$result = $this->_db->query($sql);
	
		if (!$result) {
			return null;
		}
	
		$row = $result->fetch_assoc();
	
		return $row['field_value'] ?? null;
	}
}
