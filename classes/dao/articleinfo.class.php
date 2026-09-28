<?php
/*************************************************************************
Class Article
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025 
Coder: Mai Minh 
**************************************************************************/
class ArticleInfo {
	public $id;				# Primary key
	public $store_id;		# Estore id
	public $category_id;	# Category id
	public $category_name;	# Category name
	public $category_slug;	# Category slug
	public $poster_id;		# Poster user id
	public $poster_name;	# Poster user name
	public $poster_fullname;# Poster full name
	public $updater_id;		# Updater user id
	public $lang;			# Language
	public $updater_name;	# Updater user name
	public $updater_fullname;# Updater full name
	public $slug;			# Slug
	public $slug_en;			# Slug English
	public $slug_zh;			# Slug Chinese
	public $title;			# Title
	public $keyword;		# Keyword
	public $description;	# Description
	public $detail;			# Detail
	public $viewed;			# Number of views
	public $star;			# Star
	public $article_group_ids;	# Article group_ids
	public $date_created;	# Date created
	public $date_updated;	# Date created
	public $position;		# Position
	public $properties;		# Properties
	public $status;			# 0-Disabled, 1-Active, 2-Deleted, 3-Unpublished
	public $home;			# Display in home page
	public $publish_at;		# Publish date
	# Constructor
	function __construct($slug, $slug_en, $slug_zh, $title, $keyword, $description, $detail, $viewed, $star, $article_group_ids, $date_created, $date_updated, $position, $properties, $status, $home, $publish_at, $updater_fullname, $updater_name, $updater_id, $lang, $poster_fullname, $poster_name, $poster_id, $category_slug, $category_name, $category_id = 0, $store_id = 0, $id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->category_id = $category_id;
		$this->category_name = $category_name;
		$this->category_slug = $category_slug;
		$this->poster_id = $poster_id;
		$this->poster_name = $poster_name;
		$this->poster_fullname = $poster_fullname;
		$this->updater_id = $updater_id;
		$this->lang = $lang;
		$this->updater_name = $updater_name;
		$this->updater_fullname = $updater_fullname;
		$this->slug = $slug;
		$this->slug_en = $slug_en;
		$this->slug_zh = $slug_zh;
		$this->title = $title;
		$this->keyword = $keyword;
		$this->description = $description;
		$this->detail = $detail;
		$this->viewed = $viewed;
		$this->star = $star;
		$this->article_group_ids = $article_group_ids;
		$this->date_created = $date_created;
		$this->date_updated = $date_updated;
		$this->position = $position;
		$this->properties = unserialize($properties);
		$this->status = $status;
		$this->home = $home;
		$this->publish_at = $publish_at;
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
	function getCategoryName() {
		return $this->category_name;
	}
	function getCategorySlug() {
		return $this->category_slug;
	}
	function getPosterId() {
		return $this->poster_id;
	}
	function setPosterId($nValue) {
		$this->poster_id=$nValue;
	}
	function getPosterName() {
		return $this->poster_name;
	}
	function getPosterFullName() {
		return $this->poster_fullname;
	}
	function getUpdaterId() {
		return $this->updater_id;
	}
	function setUpdaterId($nValue) {
		$this->updater_id=$nValue;
	}

	function getLang() {
		return $this->lang;
	}

	function setLang($nValue) {
		$this->lang=$nValue;
	}
	function getUpdaterName() {
		return $this->updater_name;
	}
	function getUpdaterFullName() {
		return $this->updater_fullname;
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
	function getViewed() {
		return $this->viewed;
	}	
	function setViewed($nValue) {
		$this->viewed=$nValue;
	}

	function getStar() {
		return $this->star;
	}	
	function setStar($nValue) {
		$this->star=$nValue;
	}

	function getArticleGroupIds() {
		return $this->article_group_ids;
	}	
	function setArticleGroupIds($nValue) {
		$this->article_group_ids=$nValue;
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

	function getPublishAt()
	{
		return $this->publish_at;
	}
	function setPublishAt($nValue)
	{
		$this->publish_at=$nValue;
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
	function getStatusTextBackend() {
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function getHomeTextBackend() {
		global $amessages;
		return $amessages['status_home'][$this->home];
	}
	function getAvatar() {
		if($this->getProperty('avatarId')) {
			include_once(ROOT_PATH . "classes/dao/uploads.class.php");
			$uploads = new Uploads($this->store_id);
			$avatar = $uploads->getObject($this->getProperty('avatarId'));
			if($avatar) return $avatar;
		}
		return '';		
	}
	function getFiles() {
		if($this->getProperty('fileIds')) {
			include_once(ROOT_PATH . "classes/dao/uploads.class.php");
			$uploads = new Uploads($this->store_id);
			$files = $uploads->getObjects(1,'id IN ('.implode(',',$this->getProperty('fileIds').')',[],999));
			if($files) return $files;
		}
		return '';	
	}

	public function getAvatarImage($uploads) {
		if (!$uploads) return null;

		$avatarId = $this->getProperty('avatarId');
		if (!$avatarId) return null;

		$file = $uploads->getObject($avatarId);
		return $file ?: null;
	}

	public function getCommentCount() {
		include_once(ROOT_PATH . 'classes/dao/comments.class.php');
		$comments = new Comments($this->store_id);
		return $comments->countComments($this->id);
	}

	public function getDisplayDate() {
		$publishAt = strtotime($this->getPublishAt() ?: '');
		$updatedAt = strtotime($this->getDateUpdated() ?: '');
		$createdAt = strtotime($this->getDateCreated() ?: '');
		$latestDate = max($publishAt ?: 0, $updatedAt ?: 0);
		$timestamp = $latestDate ?: $createdAt;
		return $timestamp ? date("d/m/Y", $timestamp) : '';
	}

	/* =========================
	* CHECK LANGUAGE
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
		$langs = array_map(
			'trim',
			explode(',', strtolower($this->lang))
		);
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

		if (!$this->hasLang($lang)) {
			return $vnValue;
		}
		$key = 'custom_' . $lang . '_' . $field;

		if ( isset($this->properties[$key]) && trim($this->properties[$key]) !== '') {
			return $this->properties[$key];
		}

		return $vnValue;
	}

	/* =========================
	* TITLE
	* ========================= */

	public function getTitle($lang = 'vn')
	{
		return $this->getLangData('title', $lang);
	}

	public function setTitle($nValue, $lang = 'vn')
	{
		$lang = strtolower(trim($lang));
		if ($lang == 'vn') {
			$this->title = stripslashes($nValue);
		} else {
			$this->properties['custom_' . $lang . '_title'] = stripslashes($nValue);
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
	* URL
	* ========================= */
	public function getUrl($lang = 'vn')
	{
		$lang = strtolower(trim($lang));
		// VN
		if ($lang == 'vn') {
			return '/' . $this->getSlug();
		}
		// Không có ngôn ngữ -> fallback VN
		if (!$this->hasLang($lang)) {
			return '/' . $this->getSlug();
		}
		switch ($lang) {
			case 'en':
				if (!empty($this->getSlugEn())) {
					return '/en/' . $this->getSlugEn();
				}
				break;
			case 'zh':
				if (!empty($this->getSlugZh())) {
					return '/zh/' . $this->getSlugZh();
				}
				break;
		}
		// Fallback cuối cùng
		return '/' . $this->getSlug();
	}
}	
?>
