<?php

class CartItemInfo {
    var $id;
    var $cart_id;
    var $product_id;
    var $quantity;
    var $year;
    var $price;

    function __construct($cart_id, $product_id, $quantity, $price, $year, $id = 0) {
        $this->id = $id;
        $this->cart_id = $cart_id;
        $this->product_id = $product_id;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->year = $year;
    }

	function getId() { return $this->id; }
	function getCartId() { return $this->cart_id; }
	function getProductId() { return $this->product_id; }
	function getQuantity() { return $this->quantity; }
	function getYear() { return $this->year; }
	function getPrice() { return $this->price; }
    function getPriceMin1Year() {
		return ($this->price * 2.9) / 3;
	}

	function getPrice2Year() {
		return $this->price * 1.95;
	}

	function getPrice3Year() {
		return $this->price * 2.9;
	}
}
?>
