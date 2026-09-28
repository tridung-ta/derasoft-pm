<?php
/*************************************************************************
Class MenuCategories
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Xuyen Tran                                    
Last updated: 22/09/2011
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/menucategoryinfo.class.php");

class MenuCategories extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0,$database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."menu_categories";	
		$this->store_id = $store_id;
		
	}

/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		if($result) {
			$object = new MenuCategoryInfo ($result[0]['name'],
										$result[0]['status'],
										$result[0]['properties'],
										$result[0]['store_id'],
										$result[0]['id']);
			return $object;
		}
		return '';
	}

/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new MenuCategoryInfo($result['name'],
												$result['status'],
												$result['properties'],
												$result['store_id'],
												$result['id']);
			}
			return $objects;
		}
		return '';
	}

	# Add record
	function addData($fields,$key = 'id') {
		$result = $this->add($fields,'$key','NULL');
		if($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id') {
		$result = $this->update($fields,"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	
		
	# Return a ProductCategory name from provided ID
	function getNameFromId($id='0') {
		global $amessages;
		if(!$id) return $amessages['root'];
		$result = $this->select('name',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['name'];
		return '';
	}

/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'name', $condition = '') {
		$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
		return 0;
	}
	
	function generateCombo($value='') {
		global $amessages;
		$combo = '';
		$results = $this->select('id,name',"1>0");
		if($results) {
			foreach($results as $key => $result) {
				$combo .= "<option value='".$result['id']."'".($value==$result['id']?" selected":"").">&nbsp;&nbsp;&nbsp;l--".$result['name']."</option>";	
			}
		}
		return $combo;
	}

	function getCustomerField()
	{
		$sql = "
			SELECT
				p.id,
				COALESCE(GROUP_CONCAT(cfv.field_value ORDER BY cfv.id ASC SEPARATOR ', '), 'No Data') AS value_list
			FROM dc_menu_categories p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'menucategory' AND cf.status = 1 AND cf.appearance = 1
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
			FROM dc_menu_categories p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'menucategory' AND cf.status = 1 AND cf.appearance = 1
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
			FROM dc_menu_categories p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'menucategory'
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
