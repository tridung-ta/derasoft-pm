<?php
/*************************************************************************
Class Ward
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/wardinfo.class.php");

class Wards extends Model {
	public $table;
	public $_db;
	public $store_id;

	public function __construct($store_id = 0,$database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
			
		} else $this->_db = $database;
		
		$this->table = DB_PREFIX."wards";
		$this->store_id = $store_id;
	}

/*-----------------------------------------------------------------------*
* public function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		$sql = "
			SELECT 
				w.*, 
				a.name AS area_name 
			FROM `dc_wards` w
			LEFT JOIN `dc_areas` a ON w.area_id = a.id
			WHERE (w.store_id = '{$this->store_id}' OR w.store_id = 0) 
				AND w.`$key` = '$value' 
				AND ($condition)
			LIMIT 1
		";

		if (SHOW_QUERY) echo $sql . '<br>';

		$result = $this->_db->query($sql);
		if ($result) {
			$row = $this->_db->fetchArray($result);
			$this->_db->freeResult($result);

			if ($row) {
				$object = new WardInfo(
					$row['name'],
					$row['fullname'],
					$row['type'],
					$row['date_created'],
					$row['area_name'] ?? '',
					$row['area_id'],
					$row['status'],
					$row['position'],
					$row['properties'],
					$row['store_id'],
					$row['id']
				);
				return $object;
			}
		}
		return '';
	}
/*-----------------------------------------------------------------------*
* public function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	// function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
	// 	if (!$page) $page = 1;
	// 	$start = ($page - 1) * $items_per_page;

	// 	$order_sql = '';
	// 	if (!empty($sort)) {
	// 		$order_sql = ' ORDER BY ';
	// 		$order_clauses = [];
	// 		foreach ($sort as $field => $order) {
	// 			$order_clauses[] = "`w`.`$field` $order";
	// 		}
	// 		$order_sql .= implode(', ', $order_clauses);
	// 	}

	// 	$sql = "
	// 		SELECT 
	// 			w.*, 
	// 			a.name AS area_name 
	// 		FROM `dc_wards` w
	// 		LEFT JOIN `dc_areas` a ON w.area_id = a.id
	// 		WHERE (w.store_id = '{$this->store_id}' OR w.store_id = 0) AND $condition
	// 		$order_sql
	// 		LIMIT $start, $items_per_page
	// 	";

	// 	if (SHOW_QUERY) echo $sql . '<br>';

	// 	$result = $this->_db->query($sql);
	// 	if ($result) {
	// 		$objects = array();
	// 		while ($row = $this->_db->fetchArray($result)) {
	// 			$objects[] = new WardInfo(
	// 				$row['name'],
	// 				$row['type'],
	// 				$row['date_created'],
	// 				$row['area_name'] ?? '',
	// 				$row['area_id'],
	// 				$row['status'],
	// 				$row['position'],
	// 				$row['properties'],
	// 				$row['store_id'],
	// 				$row['id']
	// 			);
	// 		}
	// 		$this->_db->freeResult($result);
	// 		return $objects;
	// 	}

	// 	return '';
	// }
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;

		// Old select that supports only one table
		// $results = $this->select('*', "(`store_id` = '".$this->store_id."' or `store_id`=0) AND $condition", $sort, $start, $items_per_page);

		// New select that supports multiple tables
		$sql = "SELECT 
					w.`id`,
					w.`name`,
					w.`fullname`,
					w.`type`,
					w.`date_created`,
					w.`area_id`,
					a.`name` AS area_name,
					w.`status`,
					w.`position`,
					w.`properties`,
					w.`store_id` AS wstore_id
				FROM `dc_wards` w
				JOIN `dc_areas` a ON w.area_id = a.id
				WHERE (w.`store_id` = '".$this->store_id."' or w.`store_id`=0) 
				AND ($condition)";	

		$order_sql = '';
		if ($sort) {
			$order_sql = ' ORDER BY ';
			$i = 0;
			foreach ($sort as $field => $order) {
				$order_sql .= "`$field` $order" . ($i < count($sort) - 1 ? ',' : '');
				$i++;
			}
		}
		$sql .= $order_sql;
		if ($items_per_page != 0) {
			$sql .= " LIMIT $start,$items_per_page";
		}
		$results = $this->query($sql);

		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new WardInfo(
					$result['name'],
					$result['fullname'],
					$result['type'],
					$result['date_created'],
					$result['area_name'],
					$result['area_id'],
					$result['status'],
					$result['position'],
					$result['properties'],
					$result['wstore_id'],
					$result['id']
				);
			}
			return $objects;
		}

		return '';
	}


/*-----------------------------------------------------------------------*
* public function: updateData
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
	
	function generateComboWithAreaName($value='',$condition='1>0') {
		global $amessages;
		$combo = '';
		
		// Old select with only one table
		//$results = $this->select('id,name',"`store_id` = '".$this->store_id."' AND ".$condition."");
		
		// New select that supports multiple tables
		$sql = "SELECT w.`id`,
						w.`name` AS `ward_name`,
						a.`id` AS `area_id`,
						a.`name` AS `area_name`
				FROM `dc_wards` w
				INNER JOIN `dc_areas` a ON w.`area_id` = a.`id`
				WHERE (w.`store_id` = '".$this->store_id."' or w.`store_id`=0) AND w.`status` = 1 AND ($condition)
				ORDER BY `area_name` ASC";
		$results = $this->query($sql);
		
		if($results) {
			$area_id = 0;
			foreach($results as $key => $result) {
				if($area_id != $result['area_id']) {
					$combo .= "<option disabled>--".$result['area_name']."--</option>";
				}
				$combo .= "<option value='".$result['id']."'".($value==$result['id']?" selected":"").">".$result['ward_name']."</option>";
				$area_id = $result['area_id'];
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
	
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();
		$sql = "SELECT COUNT(w.`$pk`) FROM `dc_wards` w, `dc_areas` a WHERE (`area_id`= a.id) AND ($condition)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}
}
?>
