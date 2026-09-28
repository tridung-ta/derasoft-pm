<?php
/*************************************************************************
Class Templates
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/templateinfo.class.php");

class Templates extends Model{
	var $table;
	var $_db;

	function __construct($database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."templates";	
	}	

/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	var $id;
	var $owner_id;
	var $name;
	var $folder;
	var $type;
	var $properties;
	var $status;
	function getObject($value = '0', $key = 'id') {
		if(!$key || !$value) return '';
		$result = $this->select('*',"`$key` = '$value'");
		if($result) {
			$object = new TemplateInfo
						(	$result[0]['owner_id'],
							$result[0]['name'],
							$result[0]['folder'],
							$result[0]['type'],
							$result[0]['properties'],
							$result[0]['status'],
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
	function getObjects($page = 1, $condition = '`id` = 0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		$results = $this->select('*', $condition, $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new TemplateInfo
								(	$result['owner_id'],
									$result['name'],
									$result['folder'],
									$result['type'],
									$result['properties'],
									$result['status'],
									$result['id']
								);
			}
			return $objects;
		}
		return 0;
	}
	
	function getTemplateFolderFromId($id = '0') {
		$result = $this->select('folder',"id = '$id' AND status='1'");
		if($result) return $result[0]['folder'];
		return '';
	}
	
	function generateCombo($objects = array(''), $current = '') {
		$return = '';
		foreach($objects as $object) {
			$return .= '<option value="'.$object->getId().'"'.($current == $object->getId()?' selected="selected"':'').'>'.$object->getName().'</option>';
		}
		return $return;
	}

	function getCustomerField()
	{
		$sql = "
			SELECT
				p.id,
				COALESCE(GROUP_CONCAT(cfv.field_value ORDER BY cfv.id ASC SEPARATOR ', '), 'No Data') AS value_list
			FROM dc_templates p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'config' AND cf.status = 1 AND cf.appearance = 1
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
			FROM dc_templates p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'config' AND cf.status = 1 AND cf.appearance = 1
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
			FROM dc_templates p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'config'
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
?>
