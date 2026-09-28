<?php
/*************************************************************************
Class Customers
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/customerinfo.class.php");

class Customers extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."customers";
		$this->store_id = $store_id;
	}

/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		$sql = "SELECT c.id AS customer_id,
								c.verify_expired_at,
								c.verify_token,
								c.username,
								c.password,
								c.fullname,
								c.address,
								c.email,
								c.tel,
								c.company,
								c.tax_code,
								c.date_updated,
								c.creator_id,
								creator.username AS creator_name,
								updater.username AS updater_name,
								c.date_created,
								c.updater_id, 
								c.last_login,
								c.status AS customer_status,
								c.ward_id,
								w.name AS ward_name,
								a.id AS area_id,
								a.name AS area_name,
								co.id as country_id,
								co.name AS country_name,
								c.group_id,
								g.name AS group_name,
								c.status,
								c.properties,
								c.store_id AS cstore_id
				FROM `dc_customers` AS c
				LEFT JOIN `dc_wards` w ON c.`ward_id`=w.`id`
				LEFT JOIN `dc_areas` a ON w.`area_id`=a.`id`
				LEFT JOIN `dc_countries` co ON a.`country_id`=co.`id`
				LEFT JOIN `dc_customer_groups` g ON c.group_id = g.id
				LEFT JOIN `dc_users` creator ON c.creator_id = creator.id
				LEFT JOIN `dc_users` updater ON c.updater_id = updater.id
				WHERE (c.`store_id` = '".$this->store_id."' or c.`store_id`=0) 
				AND c.`$key` = '$value' AND ($condition)";
		$result = $this->query($sql);
		#echo $sql;
		
		if($result) {
			$object = new CustomerInfo
						(	
							$result[0]['verify_expired_at'],
							$result[0]['verify_token'],
							$result[0]['username'],
							$result[0]['password'],
							$result[0]['fullname'],
							$result[0]['address'],
							$result[0]['email'],
							$result[0]['tel'],
							$result[0]['company'],
						 	$result[0]['tax_code'],
							$result[0]['properties'],
						 	$result[0]['updater_name'],
							$result[0]['creator_name'],
							$result[0]['date_updated'],
						 	$result[0]['date_created'],
							$result[0]['last_login'],
							$result[0]['status'],
						 	$result[0]['group_name'],
						 	$result[0]['country_name'],
						 	$result[0]['area_name'],
						    $result[0]['ward_name'],
							$result[0]['group_id'],
						    $result[0]['country_id'],
						 	$result[0]['area_id'],
						 	$result[0]['ward_id'],
							$result[0]['cstore_id'],
							$result[0]['customer_id']
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		
		// New select that support multiple tables
		$sql = "SELECT c.id AS customer_id,
								c.verify_expired_at,
								c.verify_token,
								c.username,
								c.password,
								c.fullname,
								c.address,
								c.email,
								c.tel,
								c.company,
								c.tax_code,
								c.date_updated,
								c.creator_id,
								creator.username AS creator_name,
								updater.username AS updater_name,
								c.date_created,
								c.updater_id,
								c.date_created,
								c.last_login,
								c.status AS customer_status,
								c.ward_id,
								w.name AS ward_name,
								a.id AS area_id,
								a.name AS area_name,
								co.id as country_id,
								co.name AS country_name,
								c.group_id,
								g.name AS group_name,
								c.status,
								c.properties,
								c.store_id AS cstore_id
				FROM `dc_customers` AS c
				LEFT JOIN `dc_wards` w ON c.`ward_id`=w.`id`
				LEFT JOIN `dc_areas` a ON w.`area_id`=a.`id`
				LEFT JOIN `dc_countries` co ON a.`country_id`=co.`id`
				LEFT JOIN `dc_customer_groups` g ON c.`group_id` = g.`id`
				LEFT JOIN `dc_users` creator ON c.`creator_id` = creator.`id`
				LEFT JOIN `dc_users` updater ON c.`updater_id` = updater.`id`
				WHERE (c.`store_id` = '".$this->store_id."' or c.`store_id`=0) AND ($condition)";
		
		$order_sql = '';
		if($sort) {
			$order_sql = ' ORDER BY ';
			$i = 0;
			foreach($sort as $field => $order) {
				$order_sql .= "$field $order".($i < count($sort) - 1?',':'');
				$i++;
			}
		}
		$sql .= $order_sql;
		if ($items_per_page != 0){
			$sql = $sql." LIMIT $start,$items_per_page";
		}
		// echo $sql;die;
		$results = $this->query($sql);
		// End new select
		
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new CustomerInfo
								(	$result['verify_expired_at'],
									$result['verify_token'],
									$result['username'],
									$result['password'],
									$result['fullname'],
									$result['address'],
									$result['email'],
									$result['tel'],
									$result['company'],
								 	$result['tax_code'],
									$result['properties'],
								 	$result['updater_name'],
								 	$result['creator_name'],
									$result['date_updated'],
								 	$result['date_created'],
									$result['last_login'],
									$result['customer_status'],
								 	$result['group_name'],
								 	$result['country_name'],
								 	$result['area_name'],
									$result['ward_name'],
								 	$result['group_id'],
								 	$result['country_id'],
								 	$result['area_id'],
									$result['ward_id'],
									$result['cstore_id'],
									$result['customer_id']
								);
			}
			return $objects;
			
		}
		return 0;
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
	# Change customer ward
	function changeWardId($id = 0, $wardId = 0) {
		if(!$id) return 0;
		if($this->update(array('ward_id' => $wardId), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change customer position
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
		
	# Return a Customer name from provided ID
	function getFullNameFromId($id='') {
		if(!$id) return '';
		$result = $this->select('fullname',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['fullname'];
		return '';
	}
	# Return a Customer username from provided ID
	function getUserNameFromId($id='') {
		if(!$id) return '';
		$result = $this->select('username',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['username'];
		return '';
	}
    
    # Return a Customer username from provided ID
	function getIdFromEmail($email='abc') {
		if(!$email) return '';
		$result = $this->select('id',"`store_id` = '".$this->store_id."' AND email = '$email'");
		if($result) return $result[0]['id'];
		return '';
	}		
/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'username', $condition = '') {
		$result = $this->select("`$key`,`id`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return $result[0]['id'];;
		return 0;
	}
	function authenticateUser($username,$password) {
		if(!$username || !$password) return 0;
		$username = str_replace(" ",'',$username);
		$username = str_replace("\\",'',$username);
		$username = str_replace("\"",'',$username);
		$username = str_replace("'",'',$username);	
		$password = md5($password);
		$result = $this->select('`id`,`status`',"`store_id` = '".$this->store_id."' AND `username` = '$username' AND `password` = '$password'");# AND `status` = 1");
		if($result) { # User o trang thai kich hoat, cho phep dang nhap
			if($result[0]['status'] == 1) {
				$last_login = array('last_login'=>date("Y-m-d H:i:s"));
				$this->update($last_login,"`id`='".$result[0]['id']."'");
				return $result[0]['id'];
			} else return '-1';
		}
		return 0;
	}
	
	# New getNumItems that support multiple tables
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();

		$sql = "SELECT COUNT(c.`$pk`)
				FROM `dc_customers` AS c
				LEFT JOIN `dc_wards` w ON c.`ward_id`=w.`id`
				LEFT JOIN `dc_areas` a ON w.`area_id`=a.`id`
				LEFT JOIN `dc_countries` co ON a.`country_id`=co.`id`
				LEFT JOIN `dc_customer_groups` g ON c.group_id = g.id
				WHERE (c.`store_id` = '".$this->store_id."' or c.`store_id`=0) AND ($condition)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_customers p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'customer' 
				AND cf.status = 1 
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
