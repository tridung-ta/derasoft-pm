<?php
/*************************************************************************
Class CustomerInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 09/09/2011
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
class CustomerInfo {
	var $id;			# Primary key
	var $store_id;		# Estore id
	var $ward_id;		# Area id
	var $ward_name;		# Ward name
	var $area_id;		# Ward id
	var $creator_name;	# Creator name
	var $updater_name;	# Updater name
	var $area_name;		# Province name
	var $country_id;	# Country id
	var $country_name;	# Country name
	var $group_id;		# Customer group id
	var $group_name;	# Customer group name
	var $username;		# Username
	var $password;		# Password
	var $fullname;		# Fullname
	var $address;		# Address
	var $email;			# Email
	var $tel;			# Tel
	var $company;		# Company
	var $tax_code;		# Tax code
	var $properties;	# Properties(about, cel)
	var $date_created;	# Date created
	var $date_updated;	# Date updated
	var $last_login;	# Date Last login
	var $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished
	var $verify_token;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished
	var $verify_expired_at;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished

	# Constructor
	function __construct($verify_expired_at, $verify_token, $username, $password, $fullname,$address,$email, $tel, $company, $tax_code, $properties, $updater_name, $creator_name, $date_updated, $date_created, $last_login, $status, $group_name, $country_name, $area_name,$ward_name, $group_id, $country_id, $area_id, $ward_id=0, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->ward_id = $ward_id;
		$this->group_id = $group_id;
		$this->ward_name = $ward_name;
		$this->area_id = $area_id;
		$this->area_name = $area_name;
		$this->country_id = $country_id;
		$this->country_name = $country_name;
		$this->group_name = $group_name;
		$this->creator_name = $creator_name;
		$this->updater_name = $updater_name;
		$this->username = trim($username);
		$this->password = $password;
		$this->fullname = $fullname;
		$this->address = $address;
		$this->email = $email;
		$this->tel = $tel;
		$this->company = $company;
		$this->tax_code = $tax_code;
		$this->properties = unserialize($properties);
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->last_login = $last_login;
		$this->status = $status;
		$this->verify_token = $verify_token;
		$this->verify_expired_at = $verify_expired_at;
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
	function getWardId() {
		return $this->ward_id;
	}
	function setWardId($nValue) {
		$this->ward_id=$nValue;
	}
	function getWardName() {
		return $this->ward_name;
	}
	function getAreaId() {
		return $this->area_id;
	}
	function getAreaName() {
		return $this->area_name;
	}
	function getCountryId() {
		return $this->country_id;
	}
	function getCountryName() {
		return $this->country_name;
	}
	function getGroupId() {
		return $this->group_id;
	}
	function setGroupId($nValue) {
		$this->group_id=$nValue;
	}
	function getGroupName() {
		return $this->group_name;
	}
	function getCreatorId() {
		return $this->creator_id;
	}
	function setCreatorId($nValue) {
		$this->creator_id=$nValue;
	}
	function getCreatorName() {
		return $this->creator_name;
	}
	function getUpdaterId() {
		return $this->updater_id;
	}
	function setUpdaterId($nValue) {
		$this->updater_id=$nValue;
	}
	function getUpdaterName() {
		return $this->updater_name;
	}
	function getUsername() {
		return $this->username;		
	}
	function setUsername($nValue) {
		$this->username=$nValue;
	}
	function getPassword() {
		return $this->password;		
	}
	function setPassword($nValue) {
		$this->password=$nValue;
	}
	function getFullName() {
		return $this->fullname;		
	}
	function setFullName($nValue) {
		$this->fullname=$nValue;
	}
	function getAddress() {
		return $this->address;		
	}
	function setAddress($nValue) {
		$this->address=$nValue;
	}
	function getEmail() {
		return $this->email;		
	}
	function setEmail($nValue) {
		$this->email=$nValue;
	}
	function getTel() {
		return $this->tel;		
	}
	function setTel($nValue) {
		$this->tel=$nValue;
	}
	function getCompany() {
		return $this->company;		
	}
	function setCompany($nValue) {
		$this->company=$nValue;
	}
	function getTaxCode() {
		return $this->tax_code;		
	}
	function setTaxCode($nValue) {
		$this->tax_code=$nValue;
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created=$nValue;
	}
	function getDateUpdated()
	{
		return $this->date_updated;
	}
	function setDateUpdated($nValue)
	{
		$this->date_updated=$nValue;
	}
	function getLastLogin()
	{
		return $this->last_login;
	}
	function setLastLogin($nValue)
	{
		$this->last_login=$nValue;
	}
	function getProperty($key)
	{
		if(isset($this->properties[$key])) return $this->properties[$key];
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
		$this->status = $nValue;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getVerifyToken() {
		return $this->verify_token;
	}
	function setVerifyToken($nValue) {
		$this->verify_token = $nValue;
	}
	function getVerifyExpiredAt() {
		return $this->verify_expired_at;
	}
	function setVerifyExpiredAt($nValue) {
		$this->verify_expired_at = $nValue;
	}
	
}	
?>
