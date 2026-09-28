<?php
/*************************************************************************
Class Product
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
class ProductInfo {
	public $id;			# Primary key
	public $store_id;		# Estore id
	public $category_id;		# Category id
	public $slug;			# Slug
	public $slug_en;			# Slug English
	public $slug_zh;			# Slug Chinese
	public $lang;
	public $name;			# Product name
	public $keyword;		# Product keyword
	public $description;	# Description
	public $detail;		# Detail
	public $overview;		# Overview
	public $avatar;		# avatar
	public $viewed;		# Number of views
	public $star;			# Star rating
	public $date_created;	# Date created
	public $date_updated;	# Date updated
	public $position;
	public $properties;	# Properties
	public $status;		# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished
	public $home;
	public $set_about_page; # 0-No, 1-Yes
	public $expiration_date; # HSD
	public $category_name;
	public $avatarImg = null;
	public $price;
	public $file_ids;
	public $gallery_img_ids;
	public $footer;
	public $professional;


	# Constructor
	function __construct(
		$professional,
		$footer,
		$price,
		$file_ids,
		$gallery_img_ids,
		$category_name,
		$expiration_date, 
		$home,
		$set_about_page, 
		$status, 
		$properties, 
		$position, 
		$date_updated, 
		$date_created, 
		$viewed, 
		$star,
		$avatar, 
		$detail, 
		$description, 
		$overview,
		$keyword, 
		$name, 
		$slug, 
		$slug_en, 
		$slug_zh,
		$lang,
		$category_id, 
		$store_id, 
		$id
		)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->category_id = $category_id;
		$this->slug = $slug;
		$this->slug_en = $slug_en;
		$this->slug_zh = $slug_zh;
		$this->lang = $lang;
		$this->name = $name;
		$this->keyword = $keyword;
		$this->description = $description;
		$this->detail = $detail;
		$this->overview = $overview;
		$this->avatar = $avatar;
		$this->viewed = $viewed;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->position = $position;
		$this->properties = unserialize($properties);
		$this->status = $status;
		$this->home = $home;
		$this->star = $star;
		$this->set_about_page = $set_about_page;
		$this->expiration_date = $expiration_date;
		$this->category_name = $category_name;
		$this->file_ids = $file_ids;
		$this->gallery_img_ids = $gallery_img_ids;
		$this->price = $price;
		$this->footer = $footer;
		$this->professional = $professional;
	}
	function getProfessional() {
		return $this->professional;
	}
	function setProfessional($nValue) {
		$this->professional = $nValue;
	}

	function getStar() {
		return $this->star;
	}	
	function setStar($nValue) {
		$this->star=$nValue;
	}

	function getFooter() {
		return $this->footer;
	}
	function setFooter($nValue) {
		$this->footer = $nValue;
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
	function getCategoryId() {
		return $this->category_id;
	}
	function setCategoryId($nValue) {
		$this->category_id=$nValue;
	}
	function getCategorySlug() {
		include_once(ROOT_PATH."classes/dao/productcategories.class.php");
		$productCategories = new ProductCategories($this->store_id);
		return $productCategories->getSlugFromId($this->category_id);
	}
	function getCategoryName() {
		include_once(ROOT_PATH."classes/dao/productcategories.class.php");
		$productCategories = new ProductCategories($this->store_id);
		return $productCategories->getNameFromId($this->category_id);
	}

	function getSlug() {
		return $this->slug;		
	}
	function setSlug($nValue) {
		$this->slug=stripslashes($nValue);
	}
	function getSlugEn() {
		return $this->slug_en;		
	}
	function setSlugEn($nValue) {
		$this->slug_en=stripslashes($nValue);
	}
	function getSlugZh() {
		return $this->slug_zh;		
	}
	function setSlugZh($nValue) {
		$this->slug_zh=stripslashes($nValue);
	}
	function getLang() {
		return $this->lang;		
	}
	function setLang($nValue) {
		$this->lang=stripslashes($nValue);
	}
	function getAvatar() {
		return $this->avatar;
	}
	function setAvatar($nValue) {
		$this->avatar=$nValue;
	}
	function getPhotos() {
		$photos = $this->properties['photos'];
		if($photos) return $photos[0];
		return '';
	}
	function getViewed() {
		return $this->viewed;
	}	
	function setViewed($nValue) {
		$this->viewed=$nValue;
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
	function getProperty($key)
	{
		if(isset($this->properties[$key])) return $this->properties[$key];
		return '';
	}
	function setProperty($key,$nValue)
	{
		$this->properties[$key]=$nValue;
	}
	function getPosition() {
		return $this->position;
	}
	function setPosition($nValue) {
		$this->position = $nValue;
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
	function getHome() {
		return $this->home;
	}
	function setHome($nValue) {
		$this->home = $nValue;
	}
	function getSetAboutPage() {
		return $this->set_about_page;
	}
	function setSetAboutPage($nValue) {
		$this->set_about_page = $nValue;
	}
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status_product'][$this->status];
	}
	# Return 1 if File is not null
	function getNullFile($n) {
		for($i=1;$i<=$n;$i++){
		$key = "file".$i;
		if($this->$key!='')
			return 1;
		}
		return '';
	}
	function getExpirationDate() {
		return $this->expiration_date;
	}
	function setExpirationDate($nValue) {
		$this->expiration_date = $nValue;
	}
	function getFileIds() {
		return $this->file_ids;
	}
	function setFileIds($nValue) {
		$this->file_ids = $nValue;
	}
	function getGalleryImgIds() {
		return $this->gallery_img_ids;
	}
	function setGalleryImgIds($nValue) {
		$this->gallery_img_ids = $nValue;
	}
	function getPrice() {
		return $this->price;
	}
	function setPrice($nValue) {
		$this->price = $nValue;
	}

	public function getFeatureNamesByIds($ids) {
		if (!$ids) return array();

		$ids = array_filter(array_map('intval', explode(',', $ids)));
		if (!$ids) return array();

		static $featureMap = null;

		if ($featureMap === null) {
			include_once(ROOT_PATH.'classes/dao/productfeatures.class.php');
			$productFeatures = new ProductFeatures($this->store_id);
			$featureMap = $productFeatures->getAllFeaturesMap();
		}

		$names = array();
		foreach ($ids as $id) {
			if (isset($featureMap[$id])) {
				$names[] = $featureMap[$id];
			}
		}

		return $names;
	}

	public function getAvatarImage($uploads) {
		if (!$uploads) return null;

		$avatarId = $this->getAvatar();
		if (!$avatarId) return null;

		$file = $uploads->getObject($avatarId);
		return $file ? $file : null;
	}

	public function getFileImages($uploads) {
		$fileIds = $this->getFileIds();
		if (!$fileIds) return array();

		$ids = explode(',', $fileIds);
		$images = array();

		foreach ($ids as $id) {
			$img = $uploads->getObject(trim($id));
			if ($img) $images[] = $img;
		}

		return $images;
	}

	public function getGalleryImages($uploads) {
		$galleryIds = $this->getGalleryImgIds();
		if (!$galleryIds) return array();

		$ids = explode(',', $galleryIds);
		$images = array();

		foreach ($ids as $id) {
			$img = $uploads->getObject(trim($id));
			if ($img) $images[] = $img;
		}

		return $images;
	}

	public function getCommentCount() {
		include_once(ROOT_PATH . 'classes/dao/comments.class.php');
		$comments = new Comments($this->store_id);
		return $comments->countComments($this->id,);
	}

	/* =========================
	* LANGUAGE
	* ========================= */

	public function hasLang($lang = 'vn')
	{
		$lang = strtolower(trim($lang));
		if ($lang == 'vn') {
			return true;
		}

		if (empty($this->lang)) {
			return false;
		}

		$langs = array_map('trim', explode(',', strtolower($this->lang)));
		return in_array($lang, $langs, true);
	}

	/* =========================
	* GET LANGUAGE DATA
	* ========================= */

	private function getLangData($field, $lang = 'vn')
	{
		$lang = strtolower(trim($lang));
		$vnValue = isset($this->{$field}) ? $this->{$field} : '';

		if ($lang == 'vn') {
			return $vnValue;
		}

		// Không có ngôn ngữ này -> fallback VN
		if (!$this->hasLang($lang)) {
			return $vnValue;
		}

		// Key dữ liệu đa ngôn ngữ
		$key = 'custom_' . $lang . '_' . $field;

		// Có dữ liệu -> lấy dữ liệu ngôn ngữ
		if (isset($this->properties[$key]) && trim($this->properties[$key]) !== '') {
			return $this->properties[$key];
		}

		// Dữ liệu rỗng -> fallback VN
		return $vnValue;
	}

	/* =========================
	* NAME
	* ========================= */

	public function getName($lang = 'vn')
	{
		return $this->getLangData('name', $lang);
	}


	public function setName($nValue, $lang = 'vn')
	{
		$lang = strtolower(trim($lang));

		if ($lang == 'vn') {
			$this->name = stripslashes($nValue);
		} else {
			$this->properties['custom_' . $lang . '_name'] = stripslashes($nValue);
		}
	}


	/* =========================
	* KEYWORD
	* ========================= */

	public function getKeyword($lang = 'vn')
	{
		return $this->getLangData('keyword', $lang);
	}


	public function setKeyword($nValue, $lang = 'vn')
	{
		$lang = strtolower(trim($lang));

		if ($lang == 'vn') {
			$this->keyword = stripslashes($nValue);
		} else {
			$this->properties['custom_' . $lang . '_keyword'] = stripslashes($nValue);
		}
	}


	/* =========================
	* DESCRIPTION
	* ========================= */

	public function getDescription($lang = 'vn')
	{
		return $this->getLangData('description', $lang);
	}


	public function setDescription($nValue, $lang = 'vn')
	{
		$lang = strtolower(trim($lang));

		if ($lang == 'vn') {
			$this->description = stripslashes($nValue);
		} else {
			$this->properties['custom_' . $lang . '_description'] = stripslashes($nValue);
		}
	}


	/* =========================
	* DETAIL
	* ========================= */

	public function getDetail($lang = 'vn')
	{
		return $this->getLangData('detail', $lang);
	}


	public function setDetail($nValue, $lang = 'vn')
	{
		$lang = strtolower(trim($lang));

		if ($lang == 'vn') {
			$this->detail = stripslashes($nValue);
		} else {
			$this->properties['custom_' . $lang . '_detail'] = stripslashes($nValue);
		}
	}


	/* =========================
	* OVERVIEW
	* ========================= */

	public function getOverview($lang = 'vn')
	{
		return $this->getLangData('overview', $lang);
	}


	public function setOverview($nValue, $lang = 'vn')
	{
		$lang = strtolower(trim($lang));

		if ($lang == 'vn') {
			$this->overview = stripslashes($nValue);
		} else {
			$this->properties['custom_' . $lang . '_overview'] = stripslashes($nValue);
		}
	}


	/* =========================
	* URL
	* ========================= */

	public function getUrl($lang = 'vn')
	{
		$lang = strtolower(trim($lang));

		// Tiếng Việt
		if ($lang == 'vn') {
			return '/' . $this->getSlug();
		}

		// Không có ngôn ngữ -> fallback VN
		if (!$this->hasLang($lang)) {
			return '/' . $this->getSlug();
		}

		switch ($lang) {

			case 'en':

				// Có EN nhưng slug EN rỗng
				if (!empty($this->getSlugEn())) {
					return '/en/' . $this->getSlugEn();
				}

				break;

			case 'zh':

				// Có ZH nhưng slug ZH rỗng
				if (!empty($this->getSlugZh())) {
					return '/zh/' . $this->getSlugZh();
				}

				break;
		}

		// Fallback cuối cùng về VN
		return '/' . $this->getSlug();
	}
}	
?>