<?php
/*************************************************************************
Class OrderItems
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/orderitem.class.php");

class OrderItems extends Model {
	var $table;
	var $_db;
	var $order_id;
	
	function __construct($order_id, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."order_items";
		$this->order_id = $order_id;
	}

/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		if(!$key || !$value) return '';
		$result = $this->select('*', "`order_id` = '".$this->order_id."' AND `$key` = '$value' AND ($condition)");
		if($result) {
			$object = new OrderItem
						(	$result[0]['product_id'],
							$result[0]['name'],
							$result[0]['quantity'],
							$result[0]['price'],
							$result[0]['properties'],
							$result[0]['order_id'],
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		$results = $this->select('*', "`order_id` = '".$this->order_id."' AND $condition", $sort, $start, $items_per_page);
		if($results) { 
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new OrderItem
						(	$result['product_id'],
							$result['name'],
							$result['quantity'],
							$result['price'],
							$result['properties'],
							$result['order_id'],
							$result['id']
						);
			}
			return $objects;
		}
		return 0;
	}

	# Add record
	function addData($fields,$key = 'id') {
		$result = $this->add($fields,'$key','NULL');
		if($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id') {
		$result = $this->update($fields,"`order_id` = '".$this->sess_id."' AND `$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}

	# Delete record
	function deleteData($value = '', $key = 'id') {
		$result = $this->delete("`$key` = '$value'");
		if($result) return 1;
		return 0;
	}	
		
	# Check if a product already exists in cart
	function isProductExists($product_id = 0) {
		$rows = $this->countItems('id',"`order_id` = '".$this->sess_id."'".($product_id?" AND `product_id` = '$product_id'":''));
		return $rows;
	}
	
	function getNumItemsInOrder() {
		return $this->countItems('id', "`order_id` = '".$this->sess_id."'");
	}
	
	function getSum() {
		$result = $this->select('SUM(price*quantity)', "`order_id` = '".$this->order_id."'");
		if($result) return $result[0][0];
		return 0;
	}
	
}
?>
