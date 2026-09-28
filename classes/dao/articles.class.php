<?php
/*************************************************************************
Class Articles
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/articleinfo.class.php");

class Articles extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."articles";
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
		// Old seelct that supports only one table
		//$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		
		// New select that support multiple tables
		$sql = "SELECT	a.id AS article_id,
    					a.store_id,
						a.slug,
						a.slug_en,
						a.slug_zh,
						a.title,
						a.keyword,
						a.description,
						a.detail,
						a.lang,
						a.viewed,
						a.star,
						a.article_group_ids,
						a.date_created,
						a.date_updated,
						a.properties,
						a.position,
						a.status,
						a.home,
						a.publish_at,
    					c.id AS category_id,
    					c.name AS category_name,
						c.slug AS category_slug,
    					c.parent_id,
    					parent.name AS parent_name,
						p.id AS poster_id,
						p.username AS poster_username,
						p.fullname AS poster_fullname,
						u.id AS updater_id,
						u.username AS updater_username,
						u.fullname AS updater_fullname
				FROM dc_articles a
				INNER JOIN dc_article_categories c ON a.category_id = c.id
				LEFT JOIN dc_users p ON a.poster_id = p.id
				LEFT JOIN dc_users u ON a.updater_id = u.id
				LEFT JOIN dc_article_categories parent ON c.parent_id = parent.id 
				WHERE (a.`store_id` = '".$this->store_id."' or a.`store_id`=0) AND a.`$key` = '$value' AND ($condition)";
		// End new select

		$result = $this->query($sql);
		if($result) {
			$object = new ArticleInfo
						(	$result[0]['slug'],
							$result[0]['slug_en'],
							$result[0]['slug_zh'],
							$result[0]['title'],
							$result[0]['keyword'],
							$result[0]['description'],
							$result[0]['detail'],
							$result[0]['viewed'],
							$result[0]['star'],
							$result[0]['article_group_ids'],
							$result[0]['date_created'],
							$result[0]['date_updated'],
							$result[0]['position'],
							$result[0]['properties'],
							$result[0]['status'],
							$result[0]['home'],
							$result[0]['publish_at'],
							$result[0]['updater_fullname'],
							$result[0]['updater_username'],
							$result[0]['updater_id'],
							$result[0]['lang'],
							$result[0]['poster_fullname'],
							$result[0]['poster_username'],
							$result[0]['poster_id'],
							$result[0]['category_slug'],
							$result[0]['category_name'],
							$result[0]['category_id'],
							$result[0]['store_id'],
							$result[0]['article_id']
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

		// New select that support multiple tables
		$sql = "SELECT	a.id AS article_id,
    					a.store_id,
						a.slug,
						a.slug_en,
						a.slug_zh,
						a.title,
						a.keyword,
						a.description,
						a.detail,
						a.lang,
						a.viewed,
						a.star,
						a.article_group_ids,
						a.date_created,
						a.date_updated,
						a.properties,
						a.position,
						a.status,
						a.home,
						a.publish_at,
    					c.id AS category_id,
    					c.name AS category_name,
						c.slug AS category_slug,
    					c.parent_id,
    					parent.name AS parent_name,
						p.id AS poster_id,
						p.username AS poster_username,
						p.fullname AS poster_fullname,
						u.id AS updater_id,
						u.username AS updater_username,
						u.fullname AS updater_fullname
				FROM dc_articles a
				INNER JOIN dc_article_categories c ON a.category_id = c.id
				LEFT JOIN dc_users p ON a.poster_id = p.id
				LEFT JOIN dc_users u ON a.updater_id = u.id
				LEFT JOIN dc_article_categories parent ON c.parent_id = parent.id 
				WHERE (a.`store_id` = '".$this->store_id."' or a.`store_id`=0) AND ($condition)";
		
		$order_sql = '';
		if($sort) {
			$order_sql = ' ORDER BY ';
			$i = 0;
			foreach($sort as $field => $order) {
				$order_sql .= "$field $order".($i < count($sort) - 1?',':'');
				$i++;
			}
		}
		$sql .= $order_sql;
		if ($items_per_page != 0){
			$sql = $sql." LIMIT $start,$items_per_page";
		}
		// End new select
		#echo $sql;
		$results = $this->query($sql);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new ArticleInfo
								(	$result['slug'],
									$result['slug_en'],
									$result['slug_zh'],
									$result['title'],
									$result['keyword'],
									$result['description'],
									$result['detail'],
									$result['viewed'],
									$result['star'],
									$result['article_group_ids'],
									$result['date_created'],
									$result['date_updated'],
									$result['position'],
									$result['properties'],
									$result['status'],
									$result['home'],
									$result['publish_at'],
								 	$result['updater_fullname'],
								 	$result['updater_username'],
								 	$result['updater_id'],
									$result['lang'],
								 	$result['poster_fullname'],
								 	$result['poster_username'],
								 	$result['poster_id'],
									$result['category_slug'],
								 	$result['category_name'],
								 	$result['category_id'],
									$result['store_id'],
									$result['article_id']
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
		$result = $this->add($fields,$key,'NULL');
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
	# Change home
	function changeHome($id = 0, $home = '') {
		if(!$id) return 0;
		if($this->update(array('home' => $home), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change article category
	function changeCategoryId($id = 0, $category_id = 0) {
		if(!$id) return 0;
		if($this->update(array('category_id' => $category_id), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change article position
	function changePosition($id = 0, $position = 0) {
		if(!$id) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
		$uploads = new Uploads($this->store_id);
		
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($results) {
			# Change status of all file upload of static pages to DELETED 
			$uploadIds = array();
			foreach($results as $key => $result) {
				$properties = unserialize($result['properties']);
				
				if($properties['avatarId']) { $uploadIds[] = $properties['avatarId'];									
				}
				if($properties['fileIds']) {
					foreach($properties['fileIds'] as $fileId) {
						if (ctype_digit((string)$fileId)) {
							$uploadIds[] = $fileId;
						}			
					}
				}
			}
			$uploads->changeStatusMultiple($uploadIds,S_DELETED);
		}

		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	
		
	# Return article Id from provided slug
	function getIdFromSlug($slug='') {
		if(!$slug) return 0;
		$result = $this->select('id',"`store_id` = '".$this->store_id."' AND slug = '$slug'");
		if($result) return $result[0]['id'];
		return 0;
	}

	# Return article title from provided slug
	function getTitleFromSlug($slug='') {
		if(!$slug) return '';
		$result = $this->select('title',"`store_id` = '".$this->store_id."' AND slug = '$slug'");
		if($result) return $result[0]['title'];
		return '';
	}

	# Return article slug from provided ID
	function getSlugFromId($id='') {
		if(!$id) return '';
		$result = $this->select('slug',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['slug'];
		return '';
	}

	# Return article title from provided ID
	function getTitleFromId($id='') {
		if(!$id) return '';
		$result = $this->select('title',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['title'];
		return '';
	}

	function increaseViewed($pId)
	{
		$articleId = (int)$pId;
		$sql = "UPDATE `" . $this->table . "` SET viewed = viewed + 1 WHERE store_id = '" . (int)$this->store_id . "' AND id = '" . $articleId . "'";
		if (SHOW_QUERY) echo $sql;
		if ($this->_db->query($sql)) {
			$table = DB_PREFIX . 'article_view_daily';
			$check = $this->_db->query("SHOW TABLES LIKE '" . addslashes($table) . "'");
			$hasDailyTable = $check && $this->_db->numRows($check) > 0;
			if ($check) $this->_db->freeResult($check);
			if ($hasDailyTable) {
				$this->_db->query(
					"INSERT INTO `" . $table . "` (store_id, article_id, view_date, views) " .
					"VALUES (" . (int)$this->store_id . ", " . $articleId . ", CURDATE(), 1) " .
					"ON DUPLICATE KEY UPDATE views = views + 1, updated_at = NOW()"
				);
			}
			return 1;
		}
		return 0;
	}

/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'title', $condition = '') {
		$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
		return 0;
	}
	
	// # New getNumItems that support multiple tables
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();
		
		$sql = "SELECT COUNT(a.`$pk`)
				FROM dc_articles a
				INNER JOIN dc_article_categories c ON a.category_id = c.id
				LEFT JOIN dc_users p ON a.poster_id = p.id
				LEFT JOIN dc_users u ON a.updater_id = u.id
				LEFT JOIN dc_article_categories parent ON c.parent_id = parent.id 
				WHERE (a.`store_id` = '".$this->store_id."' or a.`store_id`=0) AND ($condition)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}
	
	# Return article cateogry name from provided category ID
	function getCategoryIdFromId($id='') {
		if(!$id) return '';
		$result = $this->select('category_id',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['category_id'];
		return '';
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
	
	# Delete avatar from object
	function deleteAvatarFromObject($articleInfo,$avatarId) {
		global $userInfo;
		include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
		$uploads = new Uploads($this->store_id);
		
		if($avatarId>0) $avatarInfo = $uploads->getObject($avatarId,'u.id');
		if(isset($avatarInfo)) {
			# Change avatar status to "Deleted"
			$uploads->changeStatus($avatarId,S_DELETED);
			
			# Remove property avatarId from static page
			$properties = $articleInfo->getProperties();
			$properties['avatarId'] = 0;
			$properties['avatarUrl'] = '';
			
			# Update article object to Database
			$data = array(
				'properties' => serialize($properties),
				'date_updated' => date("Y-m-d H:i:s"),
				'updater_id' => $userInfo->getId()
			);
			$this->updateData($data,$articleInfo->getId());
		}
	}
	# Delete file from object
	function deleteFileFromObject($articleInfo,$fileId) {
		global $userInfo;
		include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
		$uploads = new Uploads($this->store_id);
		
		if($fileId>0) $fileInfo = $uploads->getObject($fileId,'u.id');
		if(isset($fileInfo)) {
			# Change file status to "Deleted"
			$uploads->changeStatus($fileId,S_DELETED);
			
			# Remove property fileId from static page
			$properties = $articleInfo->getProperties();
			$properties['fileIds'] = array_diff($properties['fileIds'],[$fileId]);
			
			# Update article object to Database
			$data = array(
				'properties' => serialize($properties),
				'date_updated' => date("Y-m-d H:i:s"),
				'updater_id' => $userInfo->getId()
			);
			$this->updateData($data,$articleInfo->getId());
		}
		
	}
}
?>
