<?php
/*************************************************************************
Class Ads
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Tran Thi My Xuyen
Updated by: Mai Mihn (03/06/2025)
**************************************************************************/
include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/adsinfo.class.php');

class Ads extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."ads";	
		$this->store_id = $store_id;
	}


/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		if(!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		if($result) {
			$ads = new AdsInfo( 
				$result[0]['name'],
				$result[0]['avatar'],
				$result[0]['file_ids'],
				$result[0]['logo_url'],
								$result[0]['url'],
								$result[0]['status'],
								$result[0]['position'],
								$result[0]['viewed'],
								$result[0]['date_created'],
								$result[0]['date_updated'],
								$result[0]['properties'],
								$result[0]['content'],
								$result[0]['gid'],
								$result[0]['store_id'],
								$value
								);
			return $ads;
		}
		return '';
	}
	
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND ($condition)", $sort, $start, $items_per_page);
		if($results) {
			$adsInfos = array();
			foreach($results as $key => $result) {
				$adsInfos[] = new AdsInfo (	
											$result['name'],
											$result['avatar'],
											$result['file_ids'],
											$result['logo_url'],
											$result['url'],
											$result['status'],
											$result['position'], 
											$result['viewed'],
											$result['date_created'],
											$result['date_updated'],
											$result['properties'],
											$result['content'],
											$result['gid'],
											$result['store_id'],
											$result['id']
										);
			}
			return $adsInfos;		
		}
		return '';
	}
/*-----------------------------------------------------------------------*
* Function: getAdsFromGId
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	
	function getAdsFromGId($store_id, $gId) {
		$results = $this->select('*', "`store_id` = '$store_id' AND status = 1 and gid = $gId", array('position'=>'ASC'));
		if($results) {
			$adsInfos = array();
			foreach($results as $key => $result) {
				$adsInfos[] = new AdsInfo ( 
											$result['name'],
											$result['avatar'],
											$result['file_ids'],
											$result['logo_url'],
											$result['url'],
											$result['status'],
											$result['position'], 
											$result['viewed'],
											$result['date_created'],
											$result['date_updated'],
											$result['properties'],
											$result['content'],
											$result['gid'],
											$result['store_id'],
											$result['id']
										);
			}
			return $adsInfos;		
		}
		return '';
	}
	

/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	# Add record
	function addData($fields,$key = 'id') {
		$result = $this->add($fields,'$key','NULL');
		if($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id') {
		$result = $this->update($fields,"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change ads category
	function changeCatId($id = 0, $gId = 0) {
		if(!$id) return 0;
		if($this->update(array('gid' => $gId), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change ads position
	function changePosition($id = 0, $position = 0) {
		if(!$id) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($results) {
			foreach($results as $key => $result) {
				$properties = unserialize($result['properties']);
				if($properties['logo']) {
					unlink(ROOT_PATH."gallery/".$this->store_id."/resources/".$properties['logo']);
				}
			}
		}
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	
		
/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'id', $condition = '') {
		$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
		return 0;
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_ads p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'ads' 
				AND cf.status = 1 
			JOIN dc_custom_options_value cfv 
				ON p.id = cfv.key_id 
				AND cf.id = cfv.field_id 
				AND cfv.field_value LIKE '%" . $kw . "%'
			GROUP BY p.id
			ORDER BY p.id DESC
		";

		$result = $this->_db->query($sql);

		if (!$result) {
			return false;
		}

		if ($result->num_rows == 0) {
			return false;
		}

		$ids = [];
		while ($row = $result->fetch_assoc()) {
			$ids[] = $row['id'];
		}

		return "(" . implode(",", $ids) . ")";
	}
}
?>
