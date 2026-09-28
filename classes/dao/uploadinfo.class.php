<?php
/*************************************************************************
Class UploadInfo
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 07/11/2010
Author: Mai Minh
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
class UploadInfo
{
	public $id;				# Primary key
	public $name;			# Slug
	public $status;			# Status
	public $url_o;			# Original URL
	public $url_l;			# Large URL
	public $url_m;			# Medium URL
	public $url_t;			# Thumbnail URL
	public $url_a;			# Avatar URL
	public $store_id;		# Store ID
	public $date_created;	# Date created
	public $album_id;		# Album ID
	public $album_folder;	# Album folder
	public $type;			# File type, 1-Image, 2-Video, 3-Document, 4-Music, 5-Other
	public $position;		# Position
	public $object;			# Object, article, static, product, none,...
	
	# Constructor
	function __construct($url_o,$url_l,$url_m,$url_t,$url_a,$status,$name, $store_id,$date_created,$type,$album_folder,$object,$position=0,$album_id = 0,$id = 0)
	{
		$this->id = $id;
		$this->store_id = $store_id;
		$this->album_id = $album_id;
		$this->album_folder = $album_folder;
		$this->name = $name;
		$this->status = $status;
		$this->url_o = $url_o;
		$this->url_l = $url_l;
		$this->url_m = $url_m;
		$this->url_t = $url_t;
		$this->url_a = $url_a;
		$this->store_id = $store_id;
		$this->date_created = $date_created;
		$this->object = $object;
		$this->position = $position;
		$this->type = $type;		
	}
	function getDateCreated()
	{
		return $this->date_created;
	}
	function setDateCreated($nValue)
	{
		$this->date_created = $nValue;
	}
	function getId()
	{
		return $this->id;
	}
	function setId($nValue)
	{
		$this->id = $nValue;
	}
	function getAlbumId()
	{
		return $this->album_id;
	}
	function setAlbumId($nValue)
	{
		$this->album_id = $nValue;
	}
	function getName()
	{
		return $this->name;
	}
	function setName($nValue)
	{
		$this->name = $nValue;
	}
	function getObject()
	{
		return $this->object;
	}
	function setObject($nValue)
	{
		$this->object = $nValue;
	}
	function getStatus()
	{
		return $this->status;
	}
	function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	function getUrlO()
	{
		return $this->url_o;
	}
	function setUrlO($nValue)
	{
		$this->url_o = $nValue;
	}
	function getUrlL()
	{
		return $this->url_l;
	}
	function setUrlL($nValue)
	{
		$this->url_l = $nValue;
	}
	function getUrlM()
	{
		return $this->url_m;
	}
	function setUrlM($nValue)
	{
		$this->url_m = $nValue;
	}
	function getUrlT()
	{
		return $this->url_t;
	}
	function setUrlT($nValue)
	{
		$this->url_t = $nValue;
	}
	function getUrlA()
	{
		return $this->url_a;
	}
	function setUrlA($nValue)
	{
		$this->url_a = $nValue;
	}
	function getPosition()
	{
		return $this->position;
	}
	function setPosition($nValue)
	{
		$this->position = $nValue;
	}
	function getType()
	{
		return $this->type;
	}
	function setType($nValue)
	{
		$this->type = $nValue;
	}
	function getTypeBackend()
	{
		global $amessages;
		return $amessages['upload_file_type'][$this->type];
	}
	function getStatusTextBackend()
	{
		global $amessages;
		return $amessages['status'][$this->status];
	}
	function isImage() {
		return $this->type==1?1:0;
	}
	function isVideo() {
		return $this->type==2?1:0;
	}
	function isDocument() {
		return $this->type==3?1:0;
	}
	function isMusic() {
		return $this->type==4?1:0;
	}
	function isOther() {
		return $this->type==5?1:0;
	}
	public function getPath() {
		return GALLERY_FOLDER.'/'.$this->store_id.'/'.$this->album_folder;
	}
	function deleteFiles() {
		$path = ROOT_PATH.GALLERY_FOLDER.'/'.$this->store_id.'/'.$this->album_folder.'/';
		if(file_exists($path.$this->getUrlO())) unlink($path.$this->getUrlO());	
		if(file_exists($path.$this->getUrlL())) unlink($path.$this->getUrlL());
		if(file_exists($path.$this->getUrlM())) unlink($path.$this->getUrlM());
		if(file_exists($path.$this->getUrlT())) unlink($path.$this->getUrlT());
		if(file_exists($path.$this->getUrlA())) unlink($path.$this->getUrlA());
	}
	
}
?>