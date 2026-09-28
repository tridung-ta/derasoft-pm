<?php
/*************************************************************************
Class Users
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Author: Mai Minh
Last updated: 03/06/2025 
**************************************************************************/

include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/onlineuser.class.php");

class OnlineUsers extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."estore_online_users";
		$this->store_id = $store_id;
	}
	function getObjects($page = 1, $condition = '`id` = 0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		$results = $this->select('*',"`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new OnlineUser
								(	$result['store_id'],
									$result['sid'],
									$result['uid'],
									$result['username'],
									$result['usertype'],
									$result['ip'],
									$result['last_updated'],
									$result['last_page'],
									$result['id']
								);
			}
			return $objects;
		}
		return 0;
	}
}
?>
