<?php
/*************************************************************************
Class OrderInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 03/06/2025
Author: Mai Minh )
**************************************************************************/
class OrderInfo {
	var $id;			# Order ID
	var $store_id;		# Store ID
	var $customer_id;	# Customer ID
	var $pic_id;		# Person in charge (PIC) ID
	var $pic_name;		# PIC name
	var $type;		
	var $code;
	var $name;
	var $email;	
	var $address;
	var $province;
	var $district;
	var $ward;
	var $tel;
	var $rname;
	var $remail;
	var $raddress;
	var $rprovince;
	var $rdistrict;
	var $rward;
	var $rtel;
	var $date_created;
	var $date_updated;
	var $payment_method;
	var $payment_status;
	var $delivery_vendor;
	var $delivery_status;
	var $properties;
	var $status;
	var $total;
	var $id_prize;
	var $note;
	var $id_aff;
	var $rose;
	var $referralcode;


	# Constructor
	function __construct($note,$id_aff,$rose,$referralcode,$id_prize, $total, $status, $properties, $delivery_status, $delivery_vendor, $payment_status, $payment_method, $date_updated, $date_created, $rtel, $rward, $rdistrict, $rprovince, $raddress, $remail, $rname, $tel, $ward, $district, $province, $address, $email, $name, $code, $type, $pic_name, $pic_id, $customer_id = 0, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->customer_id = $customer_id;
		$this->pic_id = $pic_id;
		$this->type = $type;
		$this->code = $code;
		$this->name = $name;
		$this->pic_name = $pic_name;
		$this->email = $email;
		$this->address = $address;
		$this->province = $province;
		$this->district = $district;
		$this->ward = $ward;
		$this->tel = $tel;
		$this->rname = $rname;
		$this->remail = $remail;
		$this->raddress = $raddress;
		$this->rprovince = $rprovince;
		$this->rdistrict = $rdistrict;
		$this->rward = $rward;
		$this->rtel = $rtel;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->payment_method = $payment_method;
		$this->payment_status = $payment_status;
		$this->delivery_vendor = $delivery_vendor;
		$this->delivery_status = $delivery_status;
		$this->properties = unserialize($properties);
		$this->status = $status;
		$this->total = $total;
		$this->id_prize = $id_prize;
		$this->note = $note;
		$this->id_aff = $id_aff;
		$this->rose = $rose;
		$this->referralcode = $referralcode;
	}
	function getReferralcode() {
		return $this->referralcode;
	}
	function getRose() {
		return $this->rose;
	}
	function getIdAff() {
		return $this->id_aff;
	}
	function getIdPrize() {
		return $this->id_prize;
	}
	function getWard() {
		return $this->ward;
	}
	function setWard($nValue) {
		$this->ward=$nValue;
	}
	function getRWard() {
		return $this->rward;
	}
	function setRWard($nValue) {
		$this->rward=$nValue;
	}
	function getDistrict() {
		return $this->district;
	}
	function setDistrict($nValue) {
		$this->rdistrict=$nValue;
	}
	function getRDistrict() {
		return $this->rdistrict;
	}
	function setRDistrict($nValue) {
		$this->rdistrict=$nValue;
	}
	function getType() {
		return $this->type;
	}
	function getCustomerId() {
		return $this->customer_id;
	}
	function getTotal() {
		return $this->total;
	}
	function setTotal($nValue) {
		$this->total=$nValue;
	}
	function getId() {
		return $this->id;
	}	
	function setId($nValue) {
		$this->id=$nValue;
	}
	function getStoreId() {
		return $this->store_id;
	}
	function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	function setCustomerId($nValue) {
		$this->customer_id=$nValue;
	}
	function getPicId() {
		return $this->pic_id;
	}
	function setPicId($nValue) {
		$this->pic_id=$nValue;
	}
	function getPicName() {
		return $this->pic_name;
	}
	function getCode() {
		return $this->code;
	}	
	function setCode($nValue) {
		$this->code=$nValue;
	}
	function getName() {
		return $this->name;
	}
	function setName($nValue) {
		$this->name=stripslashes($nValue);
	}
	 function getRName() {
		return $this->rname;
	}
	function setRName($nValue) {
		$this->rname=stripslashes($nValue);
	}
	function getEmail() {
		return $this->email;
	}	
	function setEmail($nValue) {
		$this->email=$nValue;
	}
	function getREmail() {
		return $this->remail;
	}
	function setREmail($nValue) {
		$this->remail=$nValue;
	}
	function getAddress() {
		return $this->address;
	}	
	function setAddress($nValue) {
		$this->address=stripslashes($nValue);
	}
	function getRAddress() {
		return $this->raddress;
	}
    function setRAddress($nValue) {
		$this->raddress=stripslashes($nValue);
	}
	function getTel() {
		return $this->tel;
	}	
	function setTel($nValue) {
		$this->tel=$nValue;
	}
	function getRTel() {
		return $this->rtel;
	}
	function setRTel($nValue) {
		$this->rtel=$nValue;
	}
	function getProvince() {
		return $this->province;
	}	
	function setProvince($nValue) {
		$this->province=$nValue;
	}
	function getRProvince() {
		return $this->rprovince;
	}
	function setRProvince($nValue) {
		$this->rprovince=$nValue;
	}
	function getNote() {
		return $this->note;
	}	
	function setNote($nValue) {
		$this->note=$nValue;
	}
	function getDateCreated() {
		return $this->date_created;
	}	
	function setDateCreated($nValue) {
		$this->date_created=$nValue;
	}
	function getDateUpdated() {
		return $this->date_updated;
	}
	function setDateUpdated($nValue) {
		$this->date_updated=$nValue;
	}
	function getProperty($key)
	{
		if(isset($this->properties[$key])) return ''.$this->properties[$key];
		return '';
	}
	function setProperty($key,$nValue)
	{
		$this->properties[$key]=$nValue;
	}
	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties=$nValue;
	}
	function getStatus() {
		return $this->status;
	}	
	function setStatus($nValue) {
		$this->status=$nValue;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['order_status'][$this->status];
	}
	function getPaymentMethod() {
		return $this->payment_method;
	}
	function getPaymentMethodText() {
		global $amessages;
		return $amessages['order_payment_method'][$this->payment_method];
        }	
	function setPaymentMethod($nValue) {
		$this->payment_method=$nValue;
	}
	function getPaymentStatus() {
		return $this->payment_status;
	}
	function setPaymentStatus($nValue) {
		$this->payment_status=$nValue;
	}
	function getPaymentStatusText() {
		global $amessages;
		return $amessages['order_payment_status'][$this->payment_status];
	}
	function setDeliveryVendor($nValue) {
		$this->delivery_vendor=$nValue;
	}
	function getDeliveryVendor() {
		return $this->delivery_vendor;
	}
	function getDeliveryVendorText() {
		global $amessages;
		return $amessages['order_delivery_vendor'][$this->delivery_vendor];
	}
	function getDeliveryStatus() {
		return $this->delivery_status;
	}
	function setDeliveryStatus($nValue) {
		$this->delivery_status=$nValue;
	}
	function getDeliveryStatusText() {
		global $amessages;
		return $amessages['order_delivery_status'][$this->delivery_status];
	}
	function getTypeBackendIdFromType() {
		$type=$this->type;
		if ($type==0) {
			$Nametype="User thường";
		}elseif ($type==1) {
			$Nametype='<span class="statusCompleted">User Affiliate</span>';
		}elseif ($type==2) {
			$Nametype='<span style="color:#006600">Thành viên của AFF</span>';

		}
		return $Nametype;
	}
}	
?>
