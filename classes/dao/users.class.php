<?php
/*************************************************************************
Class Users
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/userinfo.class.php");

class Users extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."users";
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
			$object = new UserInfo
						(	
							$result[0]['code'],
							$result[0]['qrcode'],
							$result[0]['active_2fa'],
							$result[0]['store_id'],
						 	$result[0]['username'],
							$result[0]['password'],
							$result[0]['type'],
							$result[0]['fullname'],
							$result[0]['email'],
							$result[0]['address'],
							$result[0]['tel'],
							$result[0]['avatar'],
							$result[0]['cell'],
							$result[0]['date_created'],
							$result[0]['last_login'],
							$result[0]['status'],
							$result[0]['properties'],
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
				$objects[] = new UserInfo
								(	
									$result['code'],
									$result['qrcode'],
									$result['active_2fa'],
									$result['store_id'],
									$result['username'],
									$result['password'],
									$result['type'],
									$result['fullname'],
									$result['email'],
									$result['address'],
									$result['tel'],
									$result['avatar'],
									$result['cell'],
									$result['date_created'],
									$result['last_login'],
									$result['status'],
									$result['properties'],
									$result['id']
								);
			}
			return $objects;
		}
		return 0;
	}
/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'username', $condition = '') {
		$value = str_replace(" ",'',$value);
		$value = str_replace("\\",'',$value);
		$value = str_replace("\"",'',$value);
		$value = str_replace("'",'',$value);
		$result = $this->select("`$key`",($this->store_id?"`store_id` = '".$this->store_id."' AND ":'')."`$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
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
	function getFullNameFromId($id='') {
		if(!$id) return '';
		$result = $this->select('fullname',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['fullname'];
		return '';
	}
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	function changeType($id = 0, $type = '') {
		if(!$id) return 0;
		if($this->update(array('type' => $type), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	function cleanTrash() {
		$result = $this->delete('status = '.S_DELETED);
		if($result) return 1;
		return 0;
	}

/* Special methods	
/*-----------------------------------------------------------------------*
* Function: change password
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	
	function changePassword($id = 0, $password = '') {
		if(!$id) return 0;
		if($this->update(array('`password`' => $password), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	
	function getUserId($condition = '1=1') {
		$result = $this->select('`id`',"`store_id` = '".$this->store_id."' AND $condition");
		if($result) return $result[0]['id'];
		return 0;
	}
	
	function getUserType($condition = '1=1') {
		$result = $this->select('`type`',"`store_id` = '".$this->store_id."' AND $condition");
		if($result) return $result[0]['type'];
		return 0;
	}
	
	function getUsername($condition = '1=1') {
		$result = $this->select('`username`',"`store_id` = '".$this->store_id."' AND $condition");
		if($result) return $result[0]['username'];
		return '';
	}
	
	function generateCombo($value='',$condition='1>0') {
		global $amessages;
		$combo = '';
		$results = $this->select('id,username',"`store_id` = '".$this->store_id."' AND ".$condition."");
		if($results) {
			foreach($results as $key => $result) {
				$combo .= "<option value='".$result['id']."'".($value==$result['id']?" selected":"").">".$result['username']."</option>";	
			}
		}
		return $combo;
	}
	
	function generateComboFromArray(array $users, $selectedId = null, $prefix = '') {
 		$html = '';
		foreach ($users as $user) {
			$isSelected = ((string)$user['id'] === (string)$selectedId) ? ' selected' : '';
			$html .= '<option value="' . $user['id'] . '"' . $isSelected . '>' 
				   . htmlspecialchars($prefix . $user['username']) 
				   . '</option>';
		}
		
		return $html;
	}
	
	function getObjectsForCombo() {
		$sql = "SELECT id, username FROM dc_users ORDER BY username ASC";
		$results = $this->query($sql);
		$allUsers = array();
		foreach($results as $key => $result) {
			$allUsers[] = $result;
		}
		return $allUsers;
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
	
	function authenticateUserAdmin($username,$password) {
		if(!$username || !$password) return 0;
		$username = str_replace(" ",'',$username);
		$username = str_replace("\\",'',$username);
		$username = str_replace("\"",'',$username);
		$username = str_replace("'",'',$username);
		$password = md5($password);
		$result = $this->select('`id`',"`store_id` = '".$this->store_id."' AND `username` = '$username' AND `password` = '$password' AND type >= '".U_MERCHANT."'");
		if($result) {
			$last_login = array('last_login'=>date("Y-m-d H:i:s"));
			$this->update($last_login,"`id`='".$result[0]['id']."'");
			return $result[0]['id'];
		}
		return 0;
	}
	
	function authenticateUserAdminCP($username,$password) {
		if(!$username || !$password) return 0;
		$username = str_replace(" ",'',$username);
		$username = str_replace("\\",'',$username);
		$username = str_replace("\"",'',$username);
		$username = str_replace("'",'',$username);
		$password = md5($password);
		$result = $this->select('`id`',"`store_id` = '".$this->store_id."' AND `username` = '$username' AND `password` = '$password' AND type >= '".U_BIDO_SALE."'");
		if($result) {
			$last_login = array('last_login'=>date("Y-m-d H:i:s"));
			$this->update($last_login,"`id`='".$result[0]['id']."'");
			return $result[0]['id'];
		}
		return 0;
	}
}
?>
