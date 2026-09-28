<?php
/*************************************************************************
Class Areas
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/	
include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/areainfo.class.php');

class Areas extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0,$database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
			
		} else $this->_db = $database;
		
		$this->table = DB_PREFIX."areas";
		$this->store_id = $store_id;
	}

/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		// Old select
		//if (!$key || !$value) return '';
		//$result = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `$key` = '$value' AND ($condition)");
		
		// New select
		$sql = "SELECT a.id AS area_id,a.name,a.fullname,a.slug,a.type,a.is_central,a.date_created,a.country_id,c.name AS country_name,a.status,a.position,a.properties,a.store_id AS astore_id FROM `dc_areas` a, `dc_countries` c WHERE (a.`store_id` = '".$this->store_id."' or a.`store_id`=0) AND (`country_id`=c.`id`) AND a.`$key` = '$value' AND ($condition)";
		$result = $this->query($sql);
		
		if($result) {
			$object = new AreaInfo(	$result[0]['name'],
						   			$result[0]['slug'],
									$result[0]['fullname'],
									$result[0]['type'],
									$result[0]['is_central'],
									$result[0]['date_created'],
								   	$result[0]['country_id'],
								    $result[0]['country_name'],
									$result[0]['status'],
									$result[0]['position'],
									$result[0]['properties'],
									$result[0]['astore_id'],
									$result[0]['area_id']
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		
		// Old select that supports only one tables
		//$results = $this->select('*', "(`store_id` = '".$this->store_id."' or `store_id`=0) AND $condition", $sort, $start, $items_per_page);
		
		// New select that support multiple tables
		$sql = "SELECT 
					a.id,
					a.name,
					a.fullname,
					a.slug,
					a.type,
					a.is_central,
					a.date_created,
					a.country_id,
					c.name AS country_name,
					a.status,
					a.position,
					a.properties,
					a.store_id AS astore_id 
				FROM `dc_areas` a
        		JOIN `dc_countries` c ON a.country_id = c.id 
				WHERE (a.`store_id` = '".$this->store_id."' or a.`store_id`=0) 
				AND ($condition)";
		
		$order_sql = '';
		if($sort) {
			$order_sql = ' ORDER BY ';
			$i = 0;
			foreach($sort as $field => $order) {
				$order_sql .= "`$field` $order".($i < count($sort) - 1?',':'');
				$i++;
			}
		}
		$sql .= $order_sql;
		if ($items_per_page != 0){
			$sql = $sql." LIMIT $start,$items_per_page";
		}
		// echo $sql;
		$results = $this->query($sql);
		// End new select
		
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new AreaInfo(  $result['name'],
										  	$result['slug'],
										  	$result['fullname'],
										  	$result['type'],
										  	$result['is_central'],
											$result['date_created'],
										    $result['country_id'],
										    $result['country_name'],
											$result['status'],
											$result['position'],
											$result['properties'],
											$result['astore_id'],
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
	function addData($object,$key = 'id') {
			 $this->add($object,'$key','NULL');
	}
/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/	
	function updateData($object, $value = '', $key = 'id') {
			 $this->update($object,"(`store_id` = '".$this->store_id."' or `store_id`=0)  AND `$key` = '$value'");
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

	function generateCombo($value='',$condition='1>0',$sort = array('name' => 'ASC')) {
		global $amessages;
		$combo = '';
		$results = $this->select('id,name',"`store_id` = '".$this->store_id."' AND ".$condition."",$sort);
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
	
	# New getNumITems that support multiple tables
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();
		$sql = "SELECT COUNT(a.`$pk`) FROM `dc_areas` a, `dc_countries` c WHERE (`country_id`=c.id) AND ($condition)";
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
