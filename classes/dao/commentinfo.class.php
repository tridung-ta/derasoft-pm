<?php
/*************************************************************************
Class Staticinfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Tran Thi Kim Que                                  
Last updated: 15/10/2009
Reviewed by: Mai Minh (03/06/2025)                                 
**************************************************************************/
class CommentInfo {
	public $id;
	public $fullname;
	public $email;
	public $tel;
	public $address;
	public $details;
	public $product_name;
	public $created;
	public $store_id;
	public $star;
	public $status;
	public $pid;
	public $mail_status;
	public $mail_job_id;
	public $mail_sent_at;
	public $unsubscribe_token;
	public $sent_job_ids;
	function __construct ($sent_job_ids=null, $unsubscribe_token=null, $mail_sent_at=null, $mail_job_id=0, $mail_status='pending', $fullname='', $email='',$tel = '', $address = '', $details='',$product_name ='', $created ='',$store_id = 0, $star = 0, $status = 0, $pid= 0, $id = 0)
	{
		$this->id = $id;
		$this->fullname = $fullname;
		$this->email = $email;
		$this->tel = $tel;
		$this->address = $address;
		$this->details = $details;
		$this->product_name = $product_name;
		$this->created = $created;
		$this->store_id = $store_id;
		$this->star = $star;
		$this->status = $status;
		$this->pid = $pid;
		$this->mail_status = $mail_status;
		$this->mail_job_id = $mail_job_id;
		$this->mail_sent_at = $mail_sent_at;
		$this->unsubscribe_token = $unsubscribe_token;
		$this->sent_job_ids = $sent_job_ids;
	}
	public function CommentInfo ($sent_job_ids=null, $unsubscribe_token=null, $mail_sent_at=null,$mail_job_id=0,$mail_status='pending',$fullname='', $email='',$tel = '', $address = '', $details='', $product_name ='', $created ='', $store_id = 0, $star = 0, $status = 0, $pid=0, $id = 0)
	{
		$this->__construct($sent_job_ids,$unsubscribe_token,$mail_sent_at,$mail_job_id,$mail_status,$fullname, $email,$tel,$address, $details,$product_name, $created ,$store_id, $star, $status ,$pid, $id);
	}
	public function getSentJobIds() {
		return $this->sent_job_ids;
	}	
	public function setSentJobIds($nValue) {
		$this->sent_job_ids=$nValue;
	}
	public function getMailStatus() {
		return $this->mail_status;
	}	
	public function setMailStatus($nValue) {
		$this->mail_status=$nValue;
	}
	public function getProductName() {
		return $this->product_name;
	}	
	public function getId() {
		return $this->id;
	}	
	public function setId($nValue) {
		$this->id=$nValue;
	}
	public function getStars() {
		return $this->star;
	}	
	public function setStar($nValue) {
		$this->star=$nValue;
	}
	public function getPId() {
		return $this->pid;
	}	
	public function setPId($nValue) {
		$this->pid=$nValue;
	}
	public function getFullname() {
		return $this->fullname;
	}	
	public function setFullname($nValue) {
		$this->fullname=stripslashes($nValue);
	}
	public function getEmail() {
		return $this->email;
	}	
	public function setEmail($nValue) {
		$this->email=$nValue;
	}
	public function getTel() {
		return $this->tel;
	}	
	public function setTel($nValue) {
		$this->tel=$nValue;
	}
	public function getAddress() {
		return $this->address;
	}	
	public function setAddress($nValue) {
		$this->address=$nValue;
	}
	public function getDetails() {
		return $this->details;
	}	
	public function setDetails($nValue) {
		$this->details=$nValue;
	}
	public function getDateCreated() {
		return $this->created;
	}	
	public function setDateCreated($nValue) {
		$this->created=$nValue;
	}
	public function getStoreId() {
		return $this->store_id;
	}
	public function setStoreId($nValue) {
		$this->store_id=$nValue;
	}
	public function getStatus() {
		return $this->status;
	}	
	public function setStatus($nValue) {
		$this->status=$nValue;
	}
	public function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	public function getProName() {
		include_once(ROOT_PATH."classes/dao/products.class.php");
		$products = new Products($this->store_id);
		return $products->getNameFromId($this->pid);
	}

	public function getCategoryName() {
		include_once(ROOT_PATH."classes/dao/productcategories.class.php");
		include_once(ROOT_PATH."classes/dao/products.class.php");
		$products = new Products($this->store_id);
		$productInfo = $products->getObject($this->pid);
		$productCategories = new ProductCategories($this->store_id);
		return $productCategories->getNameFromId($productInfo->category_id);
	}

	public function getUrl($page = 1, $keywords = '', $sort_key = 'created', $sort_direction = 'desc') {
		include_once(ROOT_PATH."classes/dao/products.class.php");
		$products = new Products($this->store_id);
		$productItem = $products->getObject($this->pid);
		if($productItem) $url = $productItem->getUrl();
		else $url= '#';
		return $url;	
	}
	
}	

?>
