<?php

include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/contactinfo.class.php');

class Contacts extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."contacts";	
		$this->store_id = $store_id;
	}


/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		if(!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		if($result) {
			$contact = new contactInfo( 
            $result[0]['id'],
            $result[0]['store_id'],
            $result[0]['type_of_service'],
            $result[0]['status'],
            $result[0]['position'],
            $result[0]['name'], 
            $result[0]['email'],
            $result[0]['description'],
            $result[0]['phone_number'],
            $result[0]['company_name'],
            $result[0]['expected_budget'],
            $result[0]['date_created'],
            $result[0]['date_updated'],
            $result[0]['properties']
            );
			return $contact;
		}
		return '';
	}
	
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND ($condition)", $sort, $start, $items_per_page);
		if($results) {
			$contactInfo = array();
			foreach($results as $key => $result) {
				$contactInfo[] = new contactInfo (
                                        $result['id'],
                                        $result['store_id'],
                                        $result['type_of_service'],
                                        $result['status'],
                                        $result['position'],
                                        $result['name'], 
                                        $result['email'],
                                        $result['description'],
                                        $result['phone_number'],
                                        $result['company_name'],
                                        $result['expected_budget'],
                                        $result['date_created'],
                                        $result['date_updated'],
                                        $result['properties']
										);
			}
			return $contactInfo;		
		}
		return '';
	}
/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
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
	# Change upload pdf category
	function changeCatId($id = 0, $gId = 0) {
		if(!$id) return 0;
		if($this->update(array('gid' => $gId), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change upload pdf position
	function changePosition($id = 0, $position = 0) {
		if(!$id) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($results) {
			foreach($results as $key => $result) {
				$properties = unserialize($result['properties']);
				if($properties['logo']) {
					unlink(ROOT_PATH."gallery/".$this->store_id."/resources/".$properties['logo']);
				}
			}
		}
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	
		
/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'id', $condition = '') {
		$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
		return 0;
	}

	public function countByCondition($condition = '1>0') {
		$table = $this->table; 
		$sql = "
			SELECT COUNT(*) AS c
			FROM {$table}
			WHERE store_id = '" . $this->store_id . "' AND ($condition)
		";
		$rows = $this->query($sql);
		return (int)($rows[0]['c'] ?? 0);
	}

}
?>
