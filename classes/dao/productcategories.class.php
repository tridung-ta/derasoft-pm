<?php
/*************************************************************************
Class ProductCategories
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 03/06/2025
Author: Mai Minh 
**************************************************************************/
include_once(ROOT_PATH . "classes/database/model.class.php");
include_once(ROOT_PATH . "classes/dao/productcategoryinfo.class.php");

class ProductCategories extends Model
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
		$this->table = DB_PREFIX . "product_categories";
		$this->store_id = $store_id;
	}

	/* Common methods
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
			$object = new ProductCategoryInfo(
				$result[0]['parent_id'],
				$result[0]['list_parent_id'],
				$result[0]['store_id'],
				$result[0]['slug'],
				$result[0]['name'],
				$result[0]['keyword'],
				$result[0]['description'],
				$result[0]['position'],
				$result[0]['viewed'],
				$result[0]['properties'],
				$result[0]['status'],
				$result[0]['home'],
				$result[0]['avatar_id'],
				$result[0]['avatar_url'],
				$result[0]['show_on_about_page'],
				$result[0]['date_created'],
				$result[0]['date_updated'],
				$result[0]['id']
			);

			return $object;
		}
		return 0;
	}
	/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new ProductCategoryInfo(
					$result['parent_id'],
					$result['list_parent_id'],
					$result['store_id'],
					$result['slug'],
					$result['name'],
					$result['keyword'],
					$result['description'],
					$result['position'],
					$result['viewed'],
					$result['properties'],
					$result['status'],
					$result['home'],
					$result['avatar_id'],
					$result['avatar_url'],
					$result['show_on_about_page'],
					$result['date_created'],
					$result['date_updated'],
					$result['id']
				);
			}
			return $objects;
		}
		return 0;
	}
	
	// function getObjectsForCombo() {
	// 	$sql = "SELECT id, name, parent_id FROM dc_product_categories ORDER BY parent_id ASC, name ASC";
	// 	$results = $this->query($sql);
	// 	$allCategories = array();
	// 	foreach($results as $key => $result) {
	// 		$allCategories[] = $result;
	// 	}
	// 	return $allCategories;
	// }

	function getObjectsForCombo() {
		$sql = "
			SELECT 
				id,
				name,
				parent_id,
				COALESCE(list_parent_id, '') AS list_parent_id,
				COALESCE(position, 0)       AS position,
				COALESCE(status, 1)         AS status
			FROM ".DB_PREFIX."product_categories
			WHERE store_id = ".(int)$this->store_id."
			ORDER BY parent_id ASC, position ASC, id ASC
		";

		$rows = [];
		$res  = $this->_db->query($sql);
		if ($res instanceof mysqli_result) {
			while ($r = $res->fetch_assoc()) {
				$rows[] = $r; // chỉ key dạng assoc: id,name,parent_id,list_parent_id,position,status
			}
			$res->free();
		} else if (is_array($res)) {
			// nếu Model::query() đã trả về mảng, vẫn đảm bảo chỉ giữ key assoc cần thiết
			foreach ($res as $r) {
				$rows[] = [
					'id'             => isset($r['id']) ? $r['id'] : (isset($r[0]) ? $r[0] : null),
					'name'           => isset($r['name']) ? $r['name'] : (isset($r[1]) ? $r[1] : null),
					'parent_id'      => isset($r['parent_id']) ? $r['parent_id'] : (isset($r[2]) ? $r[2] : 0),
					'list_parent_id' => isset($r['list_parent_id']) ? $r['list_parent_id'] : (isset($r[3]) ? $r[3] : ''),
					'position'       => isset($r['position']) ? $r['position'] : (isset($r[4]) ? $r[4] : 0),
					'status'         => isset($r['status']) ? $r['status'] : (isset($r[5]) ? $r[5] : 1),
				];
			}
		}
		return $rows;
	}

	
	// function generateNestedCombo(array $categories, $selectedId = null, $parentId = 0, $prefix = '') {
 	// 	$html = '';
	// 	foreach ($categories as $category) {
	// 		if ((int)$category['parent_id'] === (int)$parentId) {
	// 			$isSelected = ((string)$category['id'] === (string)$selectedId) ? ' selected' : '';
	// 			$html .= '<option value="' . $category['id'] . '"' . $isSelected . '>' 
	// 				   . htmlspecialchars($prefix . $category['name']) 
	// 				   . '</option>';

	// 			# Gọi đệ quy để xử lý chuyên mục con cho lùi vào
	// 			$html .= $this->generateNestedCombo($categories, $selectedId, $category['id'], $prefix . '— ');
	// 		}
	// 	}
	// 	return $html;
	// }

	function generateNestedCombo(array $categories = [], $selectedId = null, $parentId = 0, $prefix = '|— ')
	{
		// 1) Lấy dữ liệu Cha–Con
		$sql = "
			SELECT 
				p.id AS group_id, 
				p.name AS group_name, 
				p.position AS group_pos,
				c.id AS option_id, 
				c.name AS option_name, 
				c.position AS option_pos
			FROM ".DB_PREFIX."product_categories p
			JOIN ".DB_PREFIX."product_categories c
				ON FIND_IN_SET(p.id, REPLACE(COALESCE(c.list_parent_id,''), ' ', '')) > 0
			WHERE p.status = 1 
				AND c.status = 1
				AND p.store_id = ".(int)$this->store_id."
				AND c.store_id = ".(int)$this->store_id."
				".($parentId > 0 ? " AND p.id = ".(int)$parentId : "")."
			ORDER BY p.position ASC, c.position ASC
		";

		$rows = [];
		$res  = $this->_db->query($sql);

		if ($res instanceof mysqli_result) {
			while ($r = $res->fetch_assoc()) {
				$rows[] = $r;
			}
			$res->free();
		} elseif (is_array($res)) {
			$rows = $res;
		}

		// 2) Chuẩn hóa selected
		$selectedSet = is_array($selectedId)
			? array_map('strval', $selectedId)
			: ((isset($selectedId) && $selectedId !== '') ? [(string)$selectedId] : []);

		// 3) Gom nhóm trước khi render (FIX LỖI LẶP)
		$grouped = [];
		foreach ($rows as $r) {
			$gid = $r['group_id'];
			$cid = $r['option_id'];
			if (!isset($grouped[$gid])) {
				$grouped[$gid] = [
					'id' => $gid,
					'name' => $r['group_name'],
					'position' => $r['group_pos'],
					'children' => []
				];
			}
			if (!isset($grouped[$cid])) {
				$grouped[$cid] = [
					'id' => $cid,
					'name' => $r['option_name'],
					'position' => $r['option_pos'],
					'children' => []
				];
			}
			$grouped[$gid]['children'][$cid] = &$grouped[$cid];
		}

		$allChildren = [];
		foreach ($grouped as $node) {
			foreach ($node['children'] as $child) {
				$allChildren[$child['id']] = true;
			}
		}

		$tree = [];
		foreach ($grouped as $id => $node) {
			if (!isset($allChildren[$id])) {
				$tree[$id] = $node;
			}
		}

		$this->sortTree($tree);

		// 3.1) Gom thêm nhóm danh mục cha không có danh mục con nào (nếu có)
		$listCategoryParents = $this->getObjects(1, "parent_id = 0 AND status = 1", array('position' => 'ASC'), 999);
		if ($listCategoryParents) {
			foreach ($listCategoryParents as $cat) {
				$catId = $cat->id;
				if (!isset($tree[$catId])) {
					$tree[$catId] = [
						'id' => $catId,
						'name' => $cat->getName(),
						'position' => $cat->getPosition(),
						'children' => []
					];
				}
			}
		}

		// 4) Render HTML
		$html = $this->renderTreeOptions($tree, $selectedSet);

		return $html;
	}

function sortTree(&$nodes)
{
    uasort($nodes, function ($a, $b) {
        return $a['position'] <=> $b['position'];
    });

    foreach ($nodes as &$node) {
        if (!empty($node['children'])) {
            $this->sortTree($node['children']);
        }
        $node['children'] = array_values($node['children']);
    }
}

function renderTreeOptions(array $nodes, array $selectedSet = [], string $prefix = '') {
    $html = '';
    foreach ($nodes as $node) {
        $selected = in_array(
            (string)$node['id'],
            $selectedSet,
            true
        ) ? ' selected' : '';
        $html .= '<option value="'.$node['id'].'"'.$selected.'>'
            . htmlspecialchars($prefix.$node['name'])
            . '</option>' . "\n";
        if (!empty($node['children'])) {
            $html .= $this->renderTreeOptions(
                $node['children'],
                $selectedSet,
                $prefix . '|— '
            );
        }
    }
    return $html;
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

	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change home
	function changeHome($id = 0, $home = '') {
		if(!$id) return 0;
		if($this->update(array('home' => $home), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	function changeAbout($id = 0, $show_on_about_page = '') {
		if(!$id) return 0;
		if($this->update(array('show_on_about_page' => $show_on_about_page), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Change parent category
	function changePId($id = 0, $pId = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('parent_id' => $pId), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Change position category
	function changePosition($id = 0, $position = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('position' => $position), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash()
	{
		$results = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($results) {
			include_once(ROOT_PATH . "classes/dao/products.class.php");
			$products = new Products($this->store_id);
			# Loop all DELETED categories
			foreach ($results as $key => $result) {
				# Change status of all products in each category to DELETED too
				$products->update(array('status' => S_DELETED), "`store_id` = '" . $this->store_id . "' AND `category_id` = '" . $result['id'] . "'");
			}
		}
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}

	function getParentObject($parent_id)
	{
		return $this->getObject($parent_id, 'parent_id');
	}

	# Return a ProductCategory Id from provided ID
	function getIdFromSlug($slug = '')
	{
		if (!$slug) return 0;
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND slug = '$slug'");
		if ($result) return $result[0]['id'];
		return 0;
	}

	# Return a ProductCategory Name from provided slug
	function getNameFromSlug($slug = '')
	{
		if (!$slug) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND slug = '$slug'");
		if ($result) return $result[0]['name'];
		return '';
	}
	function getSapoFromSlug($slug = '')
	{
		if (!$slug) return '';
		$result = $this->select('sapo', "`store_id` = '" . $this->store_id . "' AND slug = '$slug'");
		if ($result) return $result[0]['sapo'];
		return '';
	}
	# Return a ProductCategory slug from provided ID
	function getSlugFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('slug', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['slug'];
		return '';
	}
	function getParentIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('parent_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['parent_id'];
		return '';
	}

	# Return a ProductCategory name from provided ID
	function getNameFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}

	function getIdFromName($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND name = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}

	# Return ProductCategory from provided parent_id
	function getAllSubCategory($pId)
	{
		$results = $this->select("id", "status =1 AND `parent_id` = '$pId'", array('position' => 'ASC'));
		if ($results) {
			$categoryInfos = array();
			foreach ($results as $key => $result) {
				$a = $result['id'];
				$categoryInfos[] = $result['id'];
				$results1 = $this->select("id", "status =1 AND `parent_id` = '$a'", array('position' => 'ASC'));
				foreach ($results1 as $key => $result_1) {
					$b = $result_1['id'];
					$categoryInfos[] = $result_1['id'];
					$results2 = $this->select("id", "status =1 AND `parent_id` = '$b'", array('position' => 'ASC'));
					foreach ($results2 as $key => $result_2) {
						$c = $result_2['id'];
						$categoryInfos[] = $result_2['id'];
						$results3 = $this->select("id", "status =1 AND `parent_id` = '$c'", array('position' => 'ASC'));
						foreach ($results3 as $key => $result_3) {
							$d = $result_3['id'];
							$categoryInfos[] = $result_3['id'];
						}
					}
				}
			}
			if ($pId) {
				return implode(",", $categoryInfos) . ",$pId";
			} else {
				return implode(",", $categoryInfos);
			}
		}
		return ($pId);
	}

	/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}

	function generateCombo($value = '', $noroot = 0)
	{
		global $amessages;
		$combo = '';
		if (!$noroot) $combo = '<option value="0"' . ($value == '0' ? " selected" : "") . '>' . $amessages['root'] . '</option>';
		$results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND parent_id = '0'");
		if ($results) {
			foreach ($results as $key => $result) {
				$combo .= "<option value='" . $result['id'] . "'" . ($value == $result['id'] ? " selected" : "") . ">&nbsp;&nbsp;&nbsp;l--" . $result['name'] . "</option>";
				$s1results = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND parent_id = '" . $result['id'] . "'");
				if ($s1results) {
					foreach ($s1results as $key1 => $result1) {
						$combo .= "<option value='" . $result1['id'] . "'" . ($value == $result1['id'] ? " selected" : "") . ">&nbsp;&nbsp;&nbsp;l&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;l--" . $result1['name'] . "</option>";
						$s1resultss = $this->select('id,name', "`store_id` = '" . $this->store_id . "' AND parent_id = '" . $result1['id'] . "'");
						if ($s1resultss) {
							foreach ($s1resultss as $key2 => $result2) {
								$combo .= "<option value='" . $result2['id'] . "'" . ($value == $result2['id'] ? " selected" : "") . ">&nbsp;&nbsp;&nbsp;l&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;l--" . $result2['name'] . "</option>";
							}
						}
					}
				}
			}
		}
		return $combo;
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_product_categories p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'productlistcategory' 
				AND cf.status = 1 
				AND cf.appearance = 1
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

	// Combo chỉ các danh mục cấp 1 (parent_id = 0)
	function generateRootCombo($selectedId = null, $autoSelectFirst = true) {
		$combo = '';

		$results = $this->select(
			'id,name',
			"`store_id` = '" . $this->store_id . "' AND status = 1",
			array('position' => 'ASC', 'name' => 'ASC')
		);
		if (!$results || !count($results)) return '';

		// Chuẩn hóa selectedId
		$selectedId = ($selectedId === null || $selectedId === '' || $selectedId === 0) ? null : (string)(int)$selectedId;

		// Chỉ auto-chọn phần tử đầu khi được phép
		if ($selectedId === null && $autoSelectFirst) {
			$selectedId = (string)$results[0]['id'];
		}

		foreach ($results as $row) {
			$isSelected = ($selectedId !== null && (string)$row['id'] === (string)$selectedId) ? ' selected' : '';
			$combo .= '<option value="' . (int)$row['id'] . '"' . $isSelected . '>'
					. htmlspecialchars($row['name']) . '</option>';
		}
		return $combo;
	}



}
