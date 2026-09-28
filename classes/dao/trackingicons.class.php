<?php
/*************************************************************************
Class Trackingss
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Author: Mai Minh
Last updated: 03/06/2025 
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/trackingiconinfo.class.php");

class TrackingIcons extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."trackings_icon";
		$this->store_id = $store_id;
	}	

/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id') {
		if(!$key || !$value) return '';
		$result = $this->select('*',"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result) {
			$object = new TrackingIconInfo
						(	$result[0]['store_id'],
						 	$result[0]['username'],
							$result[0]['action'],
							$result[0]['date_created'],
							$result[0]['ip'],
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
		$results = $this->select('*',"`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new TrackingIconInfo
								(	$result['store_id'],
									$result['username'],
									$result['action'],
									$result['date_created'],
									$result['ip'],
									$result['id']
								);
			}
			return $objects;
		}
		return 0;
	}

/*-----------------------------------------------------------------------*
* Function: addData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	function addData($fields,$key = 'id') {
		$result = $this->add($fields,'$key','NULL');
		if($result)
			return $result;
		return 0;
	}

/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	function updateData($fields, $value = '', $key = 'id') {
		$result = $this->update($fields,"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}

	function clean($condition = '`id` = 0') {
		$result = $this->delete($condition);
		if($result) return 1;
		return 0;
	}
}
?>
