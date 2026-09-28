<?php
/*************************************************************************
Class Saleoffs
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/saleoffinfo.class.php");

class Saleoffs extends Model{
	var $table;
	var $_db;
	var $store_id;

	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."saleoffs";	
		$this->store_id = $store_id;
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
		$result = $this->select('*',"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result) {
			$object = new SaleoffInfo
						(	$result[0]['store_id'],
							$result[0]['name'],
							$result[0]['description'],
							$result[0]['object'],
							$result[0]['type'],
							$result[0]['amount'],
							$result[0]['created'],
							$result[0]['started'],
							$result[0]['ended'],
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
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new SaleoffInfo
								(	$result['store_id'],
									$result['name'],
									$result['description'],
									$result['object'],
									$result['type'],
									$result['amount'],
									$result['created'],
									$result['started'],
									$result['ended'],
									$result['status'],
									$result['id']
								);
			}
			return $objects;
		}
		return 0;
	}
	
	function generateCombo($objects = array(''), $current = '') {
		$return = '';
		foreach($objects as $object) {
			$return .= '<option value="'.$object->getId().'"'.($current == $object->getId()?' selected="selected"':'').'>'.$object->getName().'</option>';
		}
		return $return;
	}
}
?>
