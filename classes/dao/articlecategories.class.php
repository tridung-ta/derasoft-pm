<?php
/*************************************************************************
Class articleCategories
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 09/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/articlecategoryinfo.class.php");

class ArticleCategories extends Model {
	public $table;
	public $_db;
	public $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."article_categories";
		$this->store_id = $store_id;
	}

/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		if(!$key || !$value) return '';
		//$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		
		$sql = "SELECT c.`id`,
					c.`slug`,
    				c.`name`,
					c.`parent_id`,
					parent.`name` AS parent_name,
					c.`keyword`,
					c.`description`,
					c.`landing`,
					c.`detail`,
					c.`check_duplicate_title`,
					c.`sort_key`,
					c.`sort_direction`,
					c.`layout`,
					c.`items_per_page`,
					c.`viewed`,
					c.`position`,
					c.`properties`,
					c.`status`,
					c.`store_id`,
    				COUNT(a.`id`) AS article_count
					FROM `dc_article_categories` c
					LEFT JOIN `dc_article_categories` parent ON c.`parent_id` = parent.id
					LEFT JOIN `dc_articles` a ON a.`category_id` = c.`id`
					WHERE (c.`store_id` = '".$this->store_id."' or c.`store_id`=0) AND c.`$key` = '$value' AND ($condition)	
					GROUP BY c.`id`, c.`name`, parent.`name`";
		$result = $this->query($sql);
		
		if($result) {
			$object = new ArticleCategoryInfo
						(	$result[0]['slug'],
							$result[0]['name'],
							$result[0]['keyword'],
							$result[0]['description'],
						 	$result[0]['landing'],
							$result[0]['detail'],
							$result[0]['sort_key'],
							$result[0]['sort_direction'],
							$result[0]['layout'],
							$result[0]['items_per_page'],
							$result[0]['position'],
							$result[0]['viewed'],
						 	$result[0]['article_count'],
							$result[0]['parent_name'],
							$result[0]['properties'],
							$result[0]['status'],
						 	$result[0]['check_duplicate_title'],
							$result[0]['store_id'],
							$result[0]['parent_id'],
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;

		$sql = "SELECT c.`id`,
					c.`slug`,
    				c.`name`,
					c.`parent_id`,
					parent.`name` AS parent_name,
					c.`keyword`,
					c.`description`,
					c.`landing`,
					c.`detail`,
					c.`check_duplicate_title`,
					c.`sort_key`,
					c.`sort_direction`,
					c.`layout`,
					c.`items_per_page`,
					c.`viewed`,
					c.`position`,
					c.`properties`,
					c.`status`,
					c.`store_id`,
    				COUNT(a.`id`) AS article_count
					FROM `dc_article_categories` c
					LEFT JOIN `dc_article_categories` parent ON c.`parent_id` = parent.id
					LEFT JOIN `dc_articles` a ON a.`category_id` = c.`id`
					WHERE (c.`store_id` = '".$this->store_id."' or c.`store_id`=0) AND ($condition)		GROUP BY c.`id`, c.`name`, parent.`name`";
		
		$order_sql = '';
		if($sort) {
			$order_sql = ' ORDER BY ';
			$i = 0;
			foreach($sort as $field => $order) {
				$order_sql .= "`$field` $order".($i < count($sort) - 1?',':'');
				$i++;
			}
		}
		$sql .= $order_sql;
		if ($items_per_page != 0){
			$sql = $sql." LIMIT $start,$items_per_page";
		}
		#echo $sql;
		$results = $this->query($sql);
		// End new select
				
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new ArticleCategoryInfo
								(	$result['slug'],
									$result['name'],
									$result['keyword'],
									$result['description'],
								 	$result['landing'],
								 	$result['detail'],
								 	$result['sort_key'],
								 	$result['sort_direction'],
								 	$result['layout'],
								 	$result['items_per_page'],
									$result['position'],
									$result['viewed'],
								 	$result['article_count'],
								 	$result['parent_name'],
									$result['properties'],
									$result['status'],
								 	$result['check_duplicate_title'],
									$result['store_id'],
									$result['parent_id'],
									$result['id']
								);
			}
			return $objects;
		}
		return 0;
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

	# Change parent category
	function changeParentId($id = 0, $parent_id = 0) {
		if(!$id) return 0;
		if($this->update(array('parent_id' => $parent_id), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	
	# Change position category
	function changePosition($id = 0, $position = 0) {
		if(!$id) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		$results = $this->select('id',"`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($results) {
			include_once(ROOT_PATH."classes/dao/articles.class.php");
			$articles = new Articles($this->store_id);
			# Loop all DELETED categories
			foreach($results as $key => $result) {
				# Change status of all articles in each category to DELETED too
				$articles->update(array('status' => S_DELETED),"`store_id` = '".$this->store_id."' AND `category_id` = '".$result['id']."'");
			}	
		}
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	
		
	function getParentObject($parent_id) {
		return $this->getObject($parent_id,'parent_id');
	}

	# Return a ArticleCategory Id from provided ID
	function getIdFromSlug($slug='') {
		if(!$slug) return 0;
		$result = $this->select('id',"`store_id` = '".$this->store_id."' AND slug = '$slug'");
		if($result) return $result[0]['id'];
		return 0;
	}

	// Tìm ID category theo slug tiếng Anh được lưu trong properties['custom_en_url']
	public function getIdFromEnSlug($enSlug = '') {
		if (!$enSlug) return 0;

		// Lấy toàn bộ category của store (không LIMIT)
		$cats = $this->getObjects(1, '1=1', array(), 0);
		if (!$cats) return 0;

		foreach ($cats as $cat) {
			// ArticleCategoryInfo đã unserialize properties sẵn
			$en = $cat->getProperty('custom_en_url');
			if ($en && trim($en) === trim($enSlug)) {
				return (int)$cat->getId();
			}
		}
		return 0;
	}


	# Return a ArticleCategory Name from provided slug
	function getNameFromSlug($slug='') {
		if(!$slug) return '';
		$result = $this->select('name',"`store_id` = '".$this->store_id."' AND slug = '$slug'");
		if($result) return $result[0]['name'];
		return '';
	}

	# Return a ArticleCategory slug from provided ID
	function getSlugFromId($id='') {
		if(!$id) return '';
		$result = $this->select('slug',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['slug'];
		return '';
	}
	function getParentIdFromId($id='') {
		if(!$id) return '';
		$result = $this->select('parent_id',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['parent_id'];
		return '';
	}

	# Return a ArticleCategory name from provided ID
	function getNameFromId($id='0') {
		global $amessages;
		if(!$id) return $amessages['root'];
		$result = $this->select('name',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['name'];
		return '';
	}

/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'name', $condition = '') {
		$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
		return 0;
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
		$sql = "SELECT id, name, parent_id FROM dc_article_categories ORDER BY parent_id ASC, name ASC";
		$results = $this->query($sql);
		$allCategories = array();
		foreach($results as $key => $result) {
			$allCategories[] = $result;
		}
		return $allCategories;
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_article_categories p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'articlecategory'
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

	# New getNumITems that support multiple tables
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();
		$sql = "SELECT COUNT(c.`$pk`) FROM `dc_article_categories` c
					LEFT JOIN `dc_article_categories` parent ON c.`parent_id` = parent.id
					WHERE (c.`store_id` = '".$this->store_id."' or c.`store_id`=0) AND ($condition)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}


}
?>
