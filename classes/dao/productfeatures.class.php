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
include_once(ROOT_PATH.'classes/dao/productfeatureinfo.class.php');
include_once(ROOT_PATH.'classes/dao/uploads.class.php');

class ProductFeatures extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."product_features";	
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
			$productfeature = new ProductFeatureInfo( 
								$result[0]['name'],
								$result[0]['slug'],
								$result[0]['status'],
								$result[0]['avatar'],
								$result[0]['description'],
								$result[0]['pid'],
								$result[0]['properties'],
								$result[0]['date_created'],
								$result[0]['store_id'],
								$result[0]['id'],
							);
			return $productfeature;
		}
		return '';
	}
	
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND ($condition)", $sort, $start, $items_per_page);
		if($results) {
			$productfeatureinfo = array();
			foreach($results as $key => $result) {
				$productfeatureinfo[] = new ProductFeatureInfo (	$result['name'],
											$result['slug'],
											$result['status'],
											$result['avatar'], 
											$result['description'],
											$result['pid'],
											$result['properties'],
											$result['date_created'],
											$result['store_id'],
											$result['id']
										);
			}
			return $productfeatureinfo;		
		}
		return '';
	}
	function getProductFeaturesFromPId($store_id, $gId) {
		$results = $this->select('*', "`store_id` = '$store_id' AND status = 1 and pid = $gId", array('position'=>'ASC'));
		if($results) {
			$productfeatureinfo = array();
			foreach($results as $key => $result) {
				$productfeatureinfo[] = new ProductFeatureInfo ( $result['name'],
											$result['slug'],
											$result['status'],
											$result['avatar'], 
											$result['description'],
											$result['pid'],
											$result['date_created'],
											$result['store_id'],
											$result['id']
										);
			}
			return $productfeatureinfo;		
		}
		return '';
	}
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
	# Change product feature category
	function changePId($id = 0, $pId = 0) {
		if(!$id) return 0;
		if($this->update(array('pid' => $pId), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Clean trash
	function cleanTrash()
	{
		$uploads = new Uploads($this->store_id);
		$album_folder = date('Y');
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($results) {
			foreach ($results as $result) {
				$files = array(
					$uploads->getUrlLFromId($result->getAvatar()),
					$uploads->getUrlMFromId($result->getAvatar()),
					$uploads->getUrlTFromId($result->getAvatar()),
					$uploads->getUrlAFromId($result->getAvatar())
				);
				foreach ($files as $file) {
					if ($file) {
						$fullPath = ROOT_PATH . GALLERY_FOLDER . '/' . $this->store_id . '/' . $album_folder . '/' . $file;

						if (file_exists($fullPath)) {
							unlink($fullPath);
						}
					}
				}
			}
		}
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		return $result ? 1 : 0;
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_articles p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'article' 
				AND cf.status = 1 
			JOIN dc_custom_options_value cfv 
				ON p.id = cfv.key_id 
				AND cf.id = cfv.field_id 
				AND cfv.field_value LIKE '%" . controlBackSlashMySQL($kw) . "%'
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

	function getAllFeaturesMap() {
		$features = $this->getObjects(1, "status = 1", array(), 9999);
		$map = array();
		if ($features) {
			foreach ($features as $item) {
				$map[$item->getId()] = $item->getName();
			}
		}
		return $map;
	}

	
}
?>
