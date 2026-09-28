<?php

include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/menuinfo.class.php');

class Menus extends Model
{
	var $table;
	var $_db;
	var $store_id;
	function __construct($store_id = 0, $database = '')
	{
		if (!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX . "menus";
		$this->store_id = $store_id;
	}
	/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		$result = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new MenuInfo(
				$result[0]['name'],
				$result[0]['url'],
				$result[0]['slug_vi'],
				$result[0]['url_en'],
				$result[0]['url_zh'],
				$result[0]['avatar'],
				$result[0]['status'],
				$result[0]['position'],
				$result[0]['properties'],
				$result[0]['mc_id'],
				$result[0]['route_id'],
				$result[0]['route_type'],
				$result[0]['store_id'],
				$result[0]['parent_id'],
				$result[0]['date_created'],
				$result[0]['date_updated'],
				$result[0]['home'],
				$result[0]['id']
			);
			return $object;
		}
		return '';
	}

	/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObjects($page = 1, $condition = '`pid` = 0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new MenuInfo(
					$result['name'],
					$result['url'],
					$result['slug_vi'],
					$result['url_en'],
					$result['url_zh'],
					$result['avatar'],
					$result['status'],
					$result['position'],
					$result['properties'],
					$result['mc_id'],
					$result['route_id'],
					$result['route_type'],
					$result['store_id'],
					$result['parent_id'],
					$result['date_created'],
					$result['date_updated'],
					$result['home'],
					$result['id']
				);
			}
			return $objects;
		}
		return '';
	}

	/*-----------------------------------------------------------------------*
* Function: getMenuFromPid
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getMenuFromPid($store_id, $mc_id = 1, $parent_id = 0)
	{
		$results = $this->select('*', "status =1 AND store_id=$store_id AND mc_id=$mc_id AND parent_id = '$parent_id'", array('position' => 'ASC'), '');
		if ($results) {
			$menuInfos = array();
			foreach ($results as $key => $result) {
				$menuInfos[] = new MenuInfo(
					$result['name'],
					$result['url'],
					$result['slug_vi'],
					$result['url_en'],
					$result['url_zh'],
					$result['avatar'],
					$result['status'],
					$result['position'],
					$result['properties'],
					$result['mc_id'],
					$result['route_id'],
					$result['route_type'],
					$result['store_id'],
					$result['parent_id'],
					$result['date_created'],
					$result['date_updated'],
					$result['home'],
					$result['id']
				);
			}
			return $menuInfos;
		}
		return '';
	}


	/*-----------------------------------------------------------------------*
	* Function: updateData
	* Parameter: Info object
	* Return: 1 if success, 0 if fail
	*-----------------------------------------------------------------------*/

	# Add record
	function addData($fields, $key = 'id')
	{
		$result = $this->add($fields, '$key', 'NULL');
		if ($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id')
	{
		$result = $this->update($fields, "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}
	# Change home
	function changeHome($id = 0, $home = '')
	{
		if (!$id) return 0;
		if ($this->update(array('home' => $home), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change product category
	function changeCId($id = 0, $cId = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('mc_id' => $cId), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change product position
	function changePosition($id = 0, $position = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('position' => $position), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}

	# Return a Product name from provided ID
	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}
	# Return a Id Product from slug
	function getIdFromSlug($slug = '')
	{
		if (!$slug) return '';
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `slug` = '$slug'");
		if ($result) return $result[0]['id'];
		return '';
	}
	# Return a Product name from provided ID
	function getParentIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('parent_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['parent_id'];
		return '';
	}
	function getParentIdFromUrl($id = '')
	{
		if (!$id) return '';
		$result = $this->select('parent_id', "`store_id` = '" . $this->store_id . "' AND url = '$id'");
		if ($result) return $result[0]['parent_id'];
		return '';
	}
	# Return a Product name from provided ID
	function getCIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('cat_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['cat_id'];
		return '';
	}
	function generateCombo($value = '', $noroot = 0)
	{
		global $amessages;
		$combo = '';
		if (!$noroot) $combo = '<option value="0"' . ($value == '0' ? " selected" : "") . '>' . $amessages['root'] . '</option>';
		
		$combo .= $this->_generateComboLevel(0, $value, 0);
		
		return $combo;
	}

	private function _generateComboLevel($parent_id, $value, $depth, $maxDepth = 10)
	{
		if ($depth >= $maxDepth) return '';
		
		$combo = '';
		$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND parent_id = '" . $parent_id . "'");
		if ($results) {
			$prefix = str_repeat("&nbsp;&nbsp;&nbsp;l&nbsp;", $depth) . "&nbsp;&nbsp;l--";
			foreach ($results as $result) {
				$combo .= "<option value='" . $result['id'] . "'" . ($value == $result['id'] ? " selected" : "") . ">" . $prefix . $result['name'] . "</option>";
				$combo .= $this->_generateComboLevel($result['id'], $value, $depth + 1, $maxDepth);
			}
		}
		return $combo;
	}

	function CompanyListMenu($value = '')
	{
		include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
		$productOptions = new ProductOptions(1);
		$combo = '';

		// Sửa lỗi cú pháp SQL (thêm khoảng trắng giữa `id` và `IN`)
		$results = $productOptions->getObjects(1, "status = '1' AND id IN ($value)", array("id" => "ASC"), 999);

		if ($results) {
			// Dùng vòng lặp để tạo danh sách các mục menu
			foreach ($results as $key => $result) {
				$combo .= "<li class='menu__dropdown-2__item'><a href='binh-ac-quy-xe-" . $result->slug . "' class='menu__dropdown-2__link'>" . $result->name . "</a></li>";
			}
		}

		return $combo;
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_menus p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'menu' 
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

function getObjectsForCombo() {
	$sql = "SELECT id, name, parent_id FROM dc_menus ORDER BY parent_id ASC, name ASC";
	$results = $this->query($sql);
	$allCategories = array();
	foreach($results as $key => $result) {
		$allCategories[] = $result;
	}
	return $allCategories;
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

function checkDuplicate($value = '', $key = 'title', $condition = '') {
	$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
	if($result) return 1;
	return 0;
}
}
