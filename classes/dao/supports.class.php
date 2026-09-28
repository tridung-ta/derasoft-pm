<?php
/*************************************************************************
Class Ads
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Name: Mai Minh
Last updated: 03/06/2025
**************************************************************************/
include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/supportinfo.class.php');

class Supports extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."support";
		$this->store_id = $store_id;
	}
/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObject($key = '0') {
		$result = $this->select('*',"`id` = '$key'");
		if($result) {
			$support = new SupportInfo($result[0]['pa_id'],
										$result[0]['store_id'],
										$result[0]['fullname'],
										$result[0]['telephone'],
										$result[0]['cellphone'],
										$result[0]['nick_yahoo'],
										$result[0]['nick_skype'],
										$result[0]['status'],
										$result[0]['position'],
										$key
										);
			return $support;
		}
		return '';
	}
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', $condition, $sort, $start, $items_per_page);
		if($results) {
			$supportInfos = array();
			foreach($results as $key => $result) {
				$supportInfos[] = new SupportInfo ($result['pa_id'],
													$result['store_id'],
													$result['fullname'],
													$result['telephone'],
													$result['cellphone'],
													$result['nick_yahoo'],
													$result['nick_skype'],
													$result['status'],
													$result['position'],
													$result['id']
													);
			}
			return $supportInfos;		
		}
		return '';
	}
	
	function getSupport($key = '0') {		
		$results = $this->select('*',"`paid` = '$key'");
		if($results) {
			$supportInfos = array();
			foreach($results as $key => $result) {
				$supportInfos[] = new SupportInfo ($result['pa_id'],
													$result['store_id'],
													$result['fullname'],
													$result['telephone'],
													$result['cellphone'],
													$result['nick_yahoo'],
													$result['nick_skype'],
													$result['status'],
													$result['position'],
													$result['id']
													);
			}
			return $supportInfos;		
		}
		return '';
	}
	
/*-----------------------------------------------------------------------*
* Function: getRecord
* Parameter: WHERE condition
* Return: 1 if id already exists, 0 if not exists
*-----------------------------------------------------------------------*/
	function getRecord($id) {
		$result = $this->select('id',"`id` = '$id'");
		if($result) return 1;
		return '';
	} 
/*-----------------------------------------------------------------------*
* Function: addData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/	
	function addData($object) {
		$result = $this->add($object,'id','NULL');
		if($result)
			return $result;
		return "";
	}
/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/	
	function updateData($object,$cId) {
		$result = $this->update($object,"id =$cId ");
		if($result)
			return $result;
		return "";
	}
/*-----------------------------------------------------------------------*
* Function: up, down, changeposition
* Parameter: $oId, $position
* Return: 1 if update position successful, 0 if not successful
*-----------------------------------------------------------------------*/	
function up($oId = 0, $position = 0) {
		if(!$oId) return 0;
		$result = $this->select("`id`,`position`", "`id` <> '$oId' AND `position` <= '$position'", array('`position`' => 'DESC'),0,1);
		if($result) {
			$nId = $result[0][0];
			$nPosition = $result[0][1];
			if($nPosition >= $position) $nPosition = $position - 1;
			$this->update(array('position' => $nPosition), "`id` = '$oId'");
			$this->update(array('position' => $position), "`id` = '$nId'");				
			return 1;
		}
		return 0;
	}

	function down($oId = 0, $position = 0) {
		if(!$oId) return 0;
		$result = $this->select("`id`,`position`", "`id` <> '$oId' AND `position` >= '$position'", array('`position`' => 'ASC'), 0, 1);
		if($result) {
			$nId = $result[0][0];
			$nPosition = $result[0][1];
			$this->update(array('position' => $nPosition), "`id` = '$oId'");
			$this->update(array('position' => $position), "`id` = '$nId'");				
			return 1;
		}
		return 0;
	}
	function changePosition($oId = 0, $position = 0) {
		if(!$oId) return 0;
		if($this->update(array('position' => $position), "`id` = '$oId'")) return 1;
		return 0;
	}
	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Return a Product name from provided ID
	function getNameFromId($id='') {
		if(!$id) return '';
		$result = $this->select('fullname',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['fullname'];
		return '';
	}
		# Clean trash
	function cleanTrash() {
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}
}
?>
