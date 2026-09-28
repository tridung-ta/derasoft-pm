<?php
/*************************************************************************
Class Unit Type
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 14/06/2025
Author: Mai Minh 
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/unitinfo.class.php");

class UnitTypes extends Model {
	public $table;
	public $_db;
	public $store_id;

	public function __construct($store_id = 0,$database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
			
		} else $this->_db = $database;
		
		$this->table = DB_PREFIX."unit_types";
		$this->store_id = $store_id;
	}

/*-----------------------------------------------------------------------*
* public function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		$result = $this->select('*', "(`store_id` = '" . $this->store_id . "' or `store_id`=0) AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new UnitTypeInfo(
				$result[0]['id'],
				$result[0]['store_id'],
				$result[0]['name'],
				$result[0]['description'],
				$result[0]['status'],
				$result[0]['position'],
				$result[0]['properties'],
				$result[0]['date_created']
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "(`store_id` = '" . $this->store_id . "' or `store_id`=0) AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new UnitTypeInfo(
					$result['id'],
					$result['store_id'],
					$result['name'],
					$result['description'],
					$result['status'],
					$result['position'],
					$result['properties'],
					$result['date_created']				
				);
			}
			return $objects;
		}
		return '';
	}


/*-----------------------------------------------------------------------*
* public function: addData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	# Add record
	public function addData($object, $key = 'id') {
		$result = $this->add($object, '$key', 'NULL');
		if($result) return $result;
		return 0;
	}

/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/	
	function updateData($object, $value = '', $key = 'id') {
		$result = $this->update($object,"(`store_id` = '".$this->store_id."' or `store_id`=0)  AND `$key` = '$value'");
		if($result) return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Change position category
	function changePosition($id = 0, $position = 0) {
		if(!$id) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}		

	function generateCombo($value='',$condition='1>0') {
		global $amessages;
		$combo = '';
		$results = $this->select('id,name',"`store_id` = '".$this->store_id."' AND ".$condition."");
		if($results) {
			foreach($results as $key => $result) {
				$combo .= "<option value='".$result['id']."'".($value==$result['id']?" selected":"").">".$result['name']."</option>";	
			}
		}
		return $combo;
	}
	
	function getParentIdFromId($id='') {
		if(!$id) return '';
		$result = $this->select('aid',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['aid'];
		return '';
	}
	
	# Return name from provided ID
	function getNameFromId($id='0') {
		global $amessages;
		if(!$id) return $amessages['root'];
		$result = $this->select('name'," id = '$id'");
		if($result) return $result[0]['name'];
		return '';
	}
	
	function checkDuplicate($value = '', $key = 'name', $condition = '') {
		$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
		return 0;
	}
	
	// function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
	// 	$rows = 0;
	// 	$pages = 1;
	// 	$return = array();
	// 	$sql = "SELECT COUNT(w.`$pk`) FROM `dc_wards` w, `dc_areas` a WHERE (`area_id`= a.id) AND ($condition)";
	// 	if(SHOW_QUERY) echo $sql;
	// 	if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
	// 	if($rows) {
	// 		$pages = ceil($rows[0]/$items_per_page);
	// 		$return = array('rows'=>$rows[0],'pages'=>$pages);
	// 		return $return;			
	// 	}
	// 	return 0;
	// }
}
?>
