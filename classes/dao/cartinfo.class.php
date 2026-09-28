<?php 
class CartInfo {
    var $id;
    var $store_id;
    var $customer_id;
    var $session_id;
    var $date_created;
    var $date_updated;

    function __construct($store_id, $customer_id, $session_id, $date_created, $date_updated, $id = 0) {
        $this->id = $id;
        $this->store_id = $store_id;
        $this->customer_id = $customer_id;
        $this->session_id = $session_id;
        $this->date_created = $date_created;
        $this->date_updated = $date_updated;
    }

    function getId() { return $this->id; }
    function setId($nValue) { $this->id = $nValue; }
    function getCustomerId() { return $this->customer_id; }
    function setCustomerId($nValue) { $this->customer_id = $nValue; }
    function getSessionId() { return $this->session_id; }
    function setSessionId($nValue) { $this->session_id = $nValue; }
    function getDateCreated() { return $this->date_created; }
    function setDateCreated($nValue) { $this->date_created = $nValue; }
    function getDateUpdated() { return $this->date_updated; }
    function setDateUpdated($nValue) { $this->date_updated = $nValue; }
}