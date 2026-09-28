<?php

include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/uploadpdfcategoryinfor.class.php');

class UploadPdfCategories extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."upload_pdf_categories";	
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
			$uploadPdf = new UploadPdfCategoryInfor( 
            $result[0]['id'],
            $result[0]['store_id'],
            $result[0]['name'],
            $result[0]['category_id'],
            $result[0]['position'],
            $result[0]['properties'],
            $result[0]['date_created'],
            $result[0]['date_updated'],
            $result[0]['status']
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
				$uploadPdfInfor[] = new UploadPdfCategoryInfor (	$result['id'],
											$result['store_id'],
											$result['name'],
											$result['category_id'], 
											$result['position'],
											$result['properties'],
											$result['date_created'],
											$result['date_updated'],
											$result['status']
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

	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}

	function generateCombo($value='', $noroot = 0) {
		global $amessages;
		$combo = '';
		if(!$noroot) $combo = '<option value="0"'.($value=='0'?" selected":"").'>'.$amessages['root'].'</option>';
		$results = $this->select('id,name',"`store_id` = '".$this->store_id."' AND parent_id = '0'");
		if($results) {
			foreach($results as $key => $result) {
				$combo .= "<option value='".$result['id']."'".($value==$result['id']?" selected":"").">&nbsp;&nbsp;&nbsp;l--".$result['name']."</option>";	
				$s1results = $this->select('id,name',"`store_id` = '".$this->store_id."' AND parent_id = '".$result['id']."'");
				if($s1results) {
					foreach($s1results as $key1 => $result1) {
						$combo .= "<option value='".$result1['id']."'".($value==$result1['id']?" selected":"").">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;l--".$result1['name']."</option>";
					}
				}			
			}
		}
		return $combo;
	}

	function generateNestedCombo(array $categories, $selectedId = null, $parentId = 0, $prefix = '') {
 		$html = '';
		foreach ($categories as $category) {
			if ((int)$category['parent_id'] === (int)$parentId) {
				$isSelected = ((string)$category['id'] === (string)$selectedId) ? ' selected' : '';
				$html .= '<option value="' . $category['id'] . '"' . $isSelected . '>' 
					   . htmlspecialchars($prefix . $category['name']) 
					   . '</option>';

				# Gọi đệ quy để xử lý chuyên mục con cho lùi vào
				$html .= $this->generateNestedCombo($categories, $selectedId, $category['id'], $prefix . '— ');
			}
		}
		
		return $html;
	}
	
	function getObjectsForCombo() {
		$sql = "SELECT id, name, parent_id FROM dc_upload_pdf_categories ORDER BY parent_id ASC, name ASC";
		$results = $this->query($sql);
		$allCategories = array();
		foreach($results as $key => $result) {
			$allCategories[] = $result;
		}
		return $allCategories;
	}
}
?>
