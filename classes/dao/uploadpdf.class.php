<?php

include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/uploadpdfinfor.class.php');

class UploadPdf extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."upload_pdf";	
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
			$uploadPdf = new UploadPdfInfo( 
            $result[0]['id'],
            $result[0]['store_id'],
            $result[0]['name'],
            $result[0]['link_pdf'],
            $result[0]['position'],
            $result[0]['properties'],
            $result[0]['date_created'],
            $result[0]['date_updated'],
            $result[0]['status'],
            $result[0]['category_id']
            );
			return $uploadPdf;
		}
		return '';
	}
	
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND ($condition)", $sort, $start, $items_per_page);
		if($results) {
			$uploadPdfInfor = array();
			foreach($results as $key => $result) {
				$uploadPdfInfor[] = new UploadPdfInfo (	$result['id'],
											$result['store_id'],
											$result['name'],
											$result['link_pdf'], 
											$result['position'],
											$result['properties'],
											$result['date_created'],
											$result['date_updated'],
											$result['status'],
											$result['category_id']
										);
			}
			return $uploadPdfInfor;		
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
	# Change upload pdf category
	function changeCatId($id = 0, $gId = 0) {
		if(!$id) return 0;
		if($this->update(array('gid' => $gId), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change upload pdf position
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
	
	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
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

	public function countByCondition($condition = '1>0') {
		$table = DB_PREFIX . "upload_pdf";
		$sql = "
			SELECT COUNT(*) AS c
			FROM {$table}
			WHERE store_id = '" . $this->store_id . "' AND ($condition)
		";
		$rows = $this->query($sql);
		return (int)($rows[0]['c'] ?? 0);
	}

}
?>
