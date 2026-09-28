<?php
/*************************************************************************
Class User Info
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Author: Mai Minh
Last updated: 03/06/2025 
**************************************************************************/
class UserInfo {
	var $id;
	var $store_id;
	var $username;
	var $password;
	var $type;	# 1-Store staff, 2-Store admin, 3-Store boss, 7-Site staff, 8-Site admin, 9-Site boss
	var $fullname;
	var $email;
	var $address;
	var $tel;
	var $avatar;
	var $cell;
	var $date_created;
	var $status;
	var $properties;
	var $last_login;
	var $code;
	var $qrcode;
	var $active_2fa;
	
	function __construct($code,$qrcode,$active_2fa,$store_id, $username, $password, $type, $fullname, $email, $address, $tel, $avatar, $cell,  $date_created, $last_login, $status, $properties = '', $id = 0 )
	{
		$this->id = $id;
		$this->code = $code;
		$this->qrcode = $qrcode;
		$this->active_2fa = $active_2fa;
		$this->store_id = $store_id;
		$this->username = trim($username);
		$this->password = $password;
		$this->type = $type;
		$this->fullname = $fullname;
		$this->email = $email;	
		$this->address= $address;
		$this->tel = $tel;
		$this->avatar = $avatar;
		$this->cell = $cell;		
		$this->date_created = $date_created;
		$this->last_login = $last_login;
		$this->status = $status;
		$this->properties = !empty($properties) ? unserialize($properties) : [];
	}
	function getActive2fa()
	{
		return $this->active_2fa;
	}
	function getQrcode()
	{
		return $this->qrcode;
	}
	function getCode()
	{
		return $this->code;
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue) {
		$this->id = $nValue;
	}
	function getStoreid()
	{
		return $this->store_id;
	}
	function setStoreid($nValue) {
		$this->store_id = $nValue;
	}
	function getUserName()
	{
		return $this->username;
	}
	function setUserName($nValue) {
		$this->username = $nValue;
	}
	function getPassword()
	{
		return $this->password;
	}
	function setPassword($nValue) {
		$this->password = $nValue;
	}
	function getType()
	{
		return $this->type;
	}
	function setType($nValue) 
	{
		$this->type = $nValue;
	}
	function getFullName()
	{
	  return $this->fullname;
	}
	function setFullName($nValue) {
		$this->fullname = $Value;
	}
	function getEmail()
	{
		return $this->email;
	}
	function setEmail($nValue)
	{
		$this->email = $nValue;
	}
	function getAddress() 
	{
		return $this->address;
	}
	function setAddress($nValue) {
		$this->address = $nValue;
	}
	function getTel() 
	{
		return $this->tel;
	}
	function setTel($nValue) 
	{
		$this->tel = $nValue;
	}
	function getAvatar() 
	{
		return $this->avatar;
	}
	function setAvatar($nValue)
	{
		$this->avatar = $nValue;
	}
	function getCell() 
	{
		return $this->cell;
	}
	function setCell($nValue) 
	{
		$this->cell = $nValue;
	}
	
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue) 
	{
		$this->date_created = $nValue;
	}
	function getLastLogin()
	{
		return $this->last_login;
	}
	function setLastLogin($nValue) 
	{
		$this->last_login = $nValue;
	}
	function getStatus()
	{
		return( $this->status );
	}
	function setStatus($nValue) 
	{
		$this->status = $nValue;
	}
	function getProperties()
	{
		return $this->properties;
	}
	function setProperties($nValue)
	{
		$this->properties=$nValue;
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
	function getStatusText() {
		global $messages;
		return $messages['status_user'][$this->status];
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status_user'][$this->status];
	}
	function getTypeTextBackend() {
		global $amessages;
		return $amessages['type_user'][$this->type];
	}
	function isDeleted() {
		return ($this->status == S_DELETED?1:0);
	}
	function isEnabled() {
		return ($this->status == S_ENABLED?1:0);
	}
	function isDisabled() {
		return ($this->status == S_DISABLED?1:0);
	}
	function isSiteStaff() {
		return ($this->type == U_SITE_STAFF?1:0);
	}
	function isSiteAdmin() {
		return ($this->type == U_SITE_ADMIN?1:0);
	}
	function isSiteFounder() {
		return ($this->type == U_SITE_FOUNDER?1:0);
	}
	function isBidoStaff() {
		return ($this->type == U_BIDO_STAFF?1:0);
	}
	function isBidoAdmin() {
		return ($this->type == U_BIDO_ADMIN?1:0);
	}
	function isBidoFounder() {
		return ($this->type == U_BIDO_FOUNDER?1:0);
	}
	function getPermissions() {
		return $this->getProperty('permissions');
	}
	function getPermission($act='',$mod='') {
		if($act == '' || $mod == '') return 0;
		$permissions = $this->getPermissions();
		if(isset($permissions[$act][$mod])) return $permissions[$act][$mod];
		return 0;
	}
	function checkPermission($act='',$mod='',$allow_admin = 1) {
		if($this->isSiteFounder()) return 1;
		if($allow_admin && $this->isSiteAdmin()) return 1;
		$permissions = $this->getPermissions();
		if(isset($permissions[$act][$mod]) && $permissions[$act][$mod] == 1) return 1;
		header("location: /admin.php?op=accessdenied");
		exit;
	}

	public function getAvatarImage($uploads = null) {
		$avatarId = $this->getAvatar();

		if (!$uploads || !method_exists($uploads, 'getObject') || !$avatarId) {
			return null;
		}

		return $uploads->getObject($avatarId);
	}
}
?>
