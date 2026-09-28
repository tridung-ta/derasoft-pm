<?php
class ArticleGroupInfo {
	public $id;				# Primary key
	public $store_id;		# Estore id
    public $name;
	public $name_en;
	public $name_zh;
    public $slug;
    public $status;
	public $properties;
    public $date_created;
    public $date_updated;

	# Constructor
	function __construct($name, $name_en, $name_zh, $slug, $status, $properties, $date_created, $date_updated, $store_id, $id = 0)
	{
        $this->name = $name;
		$this->name_en = $name_en;
		$this->name_zh = $name_zh;
        $this->slug = $slug;
        $this->status = $status;
		$this->properties = unserialize($properties);
        $this->date_created = $date_created;
        $this->date_updated = $date_updated;
        $this->store_id = $store_id;
        $this->id = $id;
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
	function getName() {
		return $this->name;		
	}
	function setName($nValue) {
		$this->name=$nValue;
	}
	function getNameEn() {
		return $this->name_en;		
	}
	function setNameEn($nValue) {
		$this->name_en=$nValue;
	}
	function getNameZh() {
		return $this->name_zh;		
	}
	function setNameZh($nValue) {
			if($nValue != '') {
			$this->name_zh=$nValue;
		}
	}
	function getSlug() {
		return $this->slug;		
	}
	function setSlug($nValue) {
		$this->slug=stripslashes($nValue);
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
	function getNameByLang($lang = 'vn')
	{
		if($lang == 'en' && trim((string)$this->name_en) !== '') return $this->name_en;
		if($lang == 'zh' && trim((string)$this->name_zh) !== '') return $this->name_zh;
		return $this->name;
	}
    
}	
?>
