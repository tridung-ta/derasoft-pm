<?php

include_once(ROOT_PATH . "classes/database/model.class.php");
include_once(ROOT_PATH . "classes/dao/cartinfo.class.php");
include_once(ROOT_PATH . "classes/dao/cartitems.class.php");

class Carts extends Model {
    var $table;
    var $_db;
    var $store_id;

    function __construct($store_id = 0, $database = '') {
        global $db;
        $this->_db = $database ? $database : $db;
        $this->table = DB_PREFIX . "cart";
        $this->store_id = $store_id;
    }

    function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new CartInfo(
				$result[0]['store_id'],
				$result[0]['customer_id'],
				$result[0]['session_id'],
				$result[0]['date_created'],
				$result[0]['date_updated'],
				$result[0]['id']
			);
			return $object;
		}
		return '';
	}
	function getObjects($page = 1, $condition = '`pid` = 0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new CartInfo(
					$result['store_id'],
					$result['customer_id'],
					$result['session_id'],
					$result['date_created'],
					$result['date_updated'],
					$result['id']
				);
			}
			return $objects;
		}
		return '';
	}
	# Add record
	function addData($fields, $key = 'id')
	{
		$result = $this->add($fields, '$key', 'NULL');
		if ($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id')
	{
		$result = $this->update($fields, "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}

	function getByCustomer($customerId) {
		if (!$customerId) return '';

		$result = $this->select('*',"`store_id` = '".$this->store_id."' AND `customer_id` = '$customerId'",array(),0,1);

		if ($result) {
			return $result[0];
		}
		return '';
	}
	function getBySession($sessionId) {
		if (!$sessionId) return '';

		$result = $this->select('*',"`store_id` = '".$this->store_id."' AND `session_id` = '$sessionId'",array(),0, 1);
		if ($result) {
			return $result[0];
		}
		return '';
	}

	function create($customerId = 0, $sessionId = null) {
		if ($customerId) {
			$sessionId = null;
		}
		$fields = [
			'store_id' => $this->store_id,
			'customer_id' => $customerId,
			'session_id' => $sessionId,
			'date_created' => date('Y-m-d H:i:s'),
			'date_updated' => date('Y-m-d H:i:s')
		];

		return $this->addData($fields);
	}

	function getCurrentCartId() {
		$customerId = $_SESSION['store_customerId'] ?? 0;
		$sessionId = session_id();

		if ($customerId) {
			$cart = $this->getByCustomer($customerId);
		} else {
			$cart = $this->getBySession($sessionId);
		}

		if ($cart) {
			return $cart['id'];
		}

		return 0; 
	}

	function getOrCreateCartId() {
		$cartId = $this->getCurrentCartId();

		if ($cartId) {
			$this->touch($cartId); // update time khi có cart
			return $cartId;
		}

		$customerId = $_SESSION['store_customerId'] ?? 0;
		$sessionId = session_id();

		 if ($customerId) {
			$sessionId = null;
		}

		return $this->create($customerId, $sessionId);
	}

	function touch($cartId) {
		if (!$cartId) return 0;

		$fields = [
			'date_updated' => date('Y-m-d H:i:s')
		];

		return $this->updateData($fields, $cartId);
	}

	function mergeCart($customerId) {
		if (!$customerId) return 0;

		$sessionId = session_id();

		// cart guest
		$guestCart = $this->getBySession($sessionId);

		// cart user
		$userCart = $this->getByCustomer($customerId);

		if (!$guestCart) return 0;

		//  CASE 1: user chưa có cart 
		if (!$userCart) {
			return $this->updateData([
				'customer_id' => $customerId,
				'session_id' => null
			], $guestCart['id']);
		}
		//  CASE 2: đã có cả 2 → merge 
		$cartItems = new CartItems();

		$guestItems = $cartItems->getObjects(1, "`cart_id` = '".$guestCart['id']."'");

		if ($guestItems) {
			foreach ($guestItems as $item) {
				$cartItems->addItem(
					$userCart['id'],
					$item->getProductId(),
					$item->getQuantity(),
					$item->getPrice(),
					$item->getYear()
				);
			}
		}

		// xoá item guest
		$cartItems->deleteByCartId($guestCart['id']);

		// xoá cart guest
		$this->delete("`id` = '".$guestCart['id']."'");

		// update lại time cart user (chuẩn ecommerce)
		$this->touch($userCart['id']);

		return 1;
	}

}
