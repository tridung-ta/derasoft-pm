<?php
include_once(ROOT_PATH . "classes/database/model.class.php");
include_once(ROOT_PATH . "classes/dao/cartiteminfo.class.php");
class CartItems extends Model {
    var $table;
    var $_db;
    var $store_id;

   function __construct($database = '') {
        global $db;
        if (is_object($database)) {
            $this->_db = $database;
        } else {
            $this->_db = $db;
        }
        $this->table = DB_PREFIX . "cart_items";
    }

    function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new CartItemInfo(
				$result[0]['cart_id'],
                $result[0]['product_id'],
                $result[0]['quantity'],
                $result[0]['price'],
                $result[0]['year'],
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
				$objects[] = new CartItemInfo(
                    $result['cart_id'],
                    $result['product_id'],
                    $result['quantity'],
                    $result['price'],
                    $result['year'],
                    $result['id']
				);
			}
			return $objects;
		}
		return '';
	}

    function getItem($cartId, $productId, $year) {
        $result = $this->select('*',
            "`cart_id` = '$cartId' AND `product_id` = '$productId' AND `year` = '$year'",
            array(), 0, 1
        );

        if ($result) {
            $r = $result[0];
            return new CartItemInfo(
                $r['cart_id'],
                $r['product_id'],
                $r['quantity'],
                $r['price'],
                $r['year'],
                $r['id']
            );
        }
        return null;
    }

    //  Thêm sản phẩm vào giỏ
    function addItem($cartId, $productId, $quantity, $price, $year = 1) {
        $cartId = (int)$cartId;
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        $year = (int)$year;

        if ($cartId <= 0 || $productId <= 0 || $quantity <= 0) return 0;

        //  check theo product + year
        $result = $this->select('*',
            "`cart_id` = '$cartId' AND `product_id` = '$productId' AND `year` = '$year'",
            array(), 0, 1
        );

        if ($result) {
            $item = $result[0];
            $newQty = $item['quantity'] + $quantity;

            if ($newQty > 100) $newQty = 100;

            return $this->update([
                'quantity' => $newQty
            ], "`id` = '".$item['id']."'");
        } else {
            return $this->add([
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'year' => $year
            ]);
        }
    }

    // 🔹 Tổng số lượng
    function getTotalQuantity($cartId) {
        $cartId = (int)$cartId;
        if ($cartId <= 0) return 0;

        $result = $this->select('SUM(quantity) as total',
            "`cart_id` = '$cartId'"
        );

        if ($result && isset($result[0]['total'])) {
            return (int)$result[0]['total'];
        }

        return 0;
    }

    // 🔹 Xóa sản phẩm
    function removeItem($cartId, $productId, $year) {
        return $this->delete(
            "`cart_id` = '$cartId' AND `product_id` = '$productId' AND `year` = '$year'"
        );
    }

    // 🔹 Update số lượng
    function updateQuantity($cartId, $productId, $quantity, $year) {

        if ($quantity <= 0) {
            return $this->removeItem($cartId, $productId, $year);
        }

        return $this->update([
            'quantity' => $quantity
        ], "`cart_id` = '$cartId' 
            AND `product_id` = '$productId' 
            AND `year` = '$year'");
    }

   function updateYear($cartId, $productId, $oldYear, $newYear) {

    // tìm đúng item cần update
    $oldItem = $this->select('*',
        "`cart_id` = '$cartId' 
        AND `product_id` = '$productId' 
        AND `year` = '$oldYear'",
        [], 0, 1
    );

    if (!$oldItem) return 0;

    $oldItem = $oldItem[0];
    $quantity = $oldItem['quantity'];

    // nếu không đổi
    if ($oldYear == $newYear) return 1;

    // check đã tồn tại variant mới chưa
    $existing = $this->select('*',
        "`cart_id` = '$cartId' 
        AND `product_id` = '$productId' 
        AND `year` = '$newYear'",
        [], 0, 1
    );

    if ($existing) {
        $newQty = $existing[0]['quantity'] + $quantity;
        if ($newQty > 100) $newQty = 100;

        // update item mới
        $this->update([
            'quantity' => $newQty
        ], "`id` = '".$existing[0]['id']."'");

        // xoá item cũ
        return $this->delete("`id` = '".$oldItem['id']."'");
    }

    // không tồn tại → update year
    return $this->update([
        'year' => $newYear
    ], "`id` = '".$oldItem['id']."'");
}

    function deleteByCartId($cartId) {
        return $this->delete("`cart_id` = '$cartId'");
    }
}