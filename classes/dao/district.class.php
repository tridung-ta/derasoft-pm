<?php
/*************************************************************************
Class Ward
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 03/06/2025
Author: Mai Minh
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/districtinfo.class.php");

class District extends Model {
	public $table;
	public $_db;

	public function __construct($database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."quanhuyen";
	}
	public function District($database = '') {
		$this->__construct($database);
	}
/* Common methods
/*-----------------------------------------------------------------------*
* public function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	public function getObject($value = '0', $key = 'id', $condition = '1>0') {
		if(!$key || !$value) return '';
		$result = $this->select('*', "`$key` = '$value' AND ($condition)");
		if($result) {
			$object = new DisctrictInfo
						(	$result[0]['name_quanhuyen'],
							$result[0]['type'],
							$result[0]['tpid'],
							$result[0]['id']
						);
			return $object;
		}
		return 0;
	}
/*-----------------------------------------------------------------------*
* public function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	public function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		$results = $this->select('*', "$condition", $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new DisctrictInfo
								(	$result['name_quanhuyen'],
									$result['type'],
									$result['tpid'],
									$result['id']
								);
			}
			return $objects;
		}
		return 0;
	}

/*-----------------------------------------------------------------------*
* public function: updateData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	# Add record
	public function addData($fields,$key = 'id') {
		$result = $this->add($fields,'$key','NULL');
		if($result) return $result;
		return 0;
	}

	# Update record
	public function updateData($fields, $value = '', $key = 'id') {
		$result = $this->update($fields,"`$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}

	# Change status
	public function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	public function cleanTrash() {
		$result = $this->delete("`status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	
		
	# Return a ProductCategory Id from provided ID
	public function getIdFromSlug($slug='') {
		if(!$slug) return 0;
		$result = $this->select('id',"`slug` = '$slug'");
		if($result) return $result[0]['id'];
		return 0;
	}

	# Return a ProductCategory Name from provided slug
	public function getNameFromSlug($slug='') {
		if(!$slug) return '';
		$result = $this->select('name_quanhuyen',"`slug` = '$slug'");
		if($result) return $result[0]['name_quanhuyen'];
		return '';
	}

	# Return a ProductCategory slug from provided ID
	public function getSlugFromId($id='') {
		if(!$id) return '';
		$result = $this->select('slug',"``id` = '$id'");
		if($result) return $result[0]['slug'];
		return '';
	}

	# Return a ProductCategory name from provided ID
	public function getNameFromId($id='') {
		if(!$id) return '';
		$result = $this->select('name_quanhuyen',"`id` = '$id'");
		if($result) return $result[0]['name_quanhuyen'];
		return '';
	}

	# Check duplicate
	public function duplicateSlug($slug, $id = 0) {
		$rows = $this->countItems('id',"`slug` = '$slug'".($id?" AND `id` <> '$id'":''));
		if($rows) return 1;
		return 0;		
	}
	public function createComboBox($value = 0, $pkey = 'id', $field = 'name', $sort = array('position' => 'ASC')) {
		$options = '';
		$results = $this->select("`$pkey`, `$field`", "1>0", $sort, 0, 500);
		if($results) {
			foreach($results as $key => $result)
				$options .= '<option value="'.$result[$pkey].'"'.($result[$pkey]==$value?' selected="selected"':'').'>'.$result[$field].'</option>';
		}
		return $options;		
	}
}
?>
