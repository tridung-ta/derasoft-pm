<?php
/*************************************************************************
ClassStaticpage
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 15/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/staticinfo.class.php');

class StaticPage extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."static";
		$this->store_id = $store_id;
	}

/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/

	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		#$result = $this->select('*',"`store_id` = '".$this->store_id."' AND `id` = '$key'");
		$sql = "SELECT	s.id,
    					s.store_id,
						s.menu_id,
						s.slug,
						s.title,
						s.keyword,
						s.description,
						s.detail,
						s.date_created,
						s.date_updated,
						s.status,
						s.properties,
						s.landing,
						c.id AS creator_id,
						c.username AS creator_name,
						u.id AS updater_id,
						u.username AS updater_name
				FROM dc_static s
				LEFT JOIN dc_users c ON s.creator_id = c.id
				LEFT JOIN dc_users u ON s.updater_id = u.id
				WHERE (s.`store_id` = '".$this->store_id."' or s.`store_id`=0) AND s.`$key` = '$value' AND ($condition)";
		$result = $this->query($sql);
		
		if($result) {
			$object = new StaticInfo (
										$result[0]['landing'],
										$result[0]['slug'],
										$result[0]['title'],
										$result[0]['keyword'],
										$result[0]['description'],
										$result[0]['detail'],
										$result[0]['date_created'],
										$result[0]['date_updated'],
										$result[0]['creator_name'],
										$result[0]['updater_name'],				
										$result[0]['creator_id'],
										$result[0]['updater_id'],
										$result[0]['status'],
										$result[0]['properties'],
										$result[0]['store_id'],
										$result[0]['menu_id'],
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		$sql = "SELECT	s.id,
    					s.store_id,
						s.menu_id,
						s.slug,
						s.title,
						s.keyword,
						s.description,
						s.detail,
						s.date_created,
						s.date_updated,
						s.status,
						s.properties,
						s.landing,
						c.id AS creator_id,
						c.username AS creator_name,
						u.id AS updater_id,
						u.username AS updater_name
				FROM dc_static s
				LEFT JOIN dc_users c ON s.creator_id = c.id
				LEFT JOIN dc_users u ON s.updater_id = u.id
				WHERE (s.`store_id` = '".$this->store_id."' or s.`store_id`=0) AND ($condition)";
		
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
		
		$results = $this->query($sql);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new StaticInfo (
												$result['landing'],
												$result['slug'],
												$result['title'],
												$result['keyword'],
												$result['description'],
												$result['detail'],
												$result['date_created'],
												$result['date_updated'],
												$result['creator_name'],
												$result['updater_name'],
												$result['creator_id'],
												$result['updater_id'],
												$result['status'],
												$result['properties'],
												$result['store_id'],
												$result['menu_id'],
												$result['id']
												);
			}
			return $objects;
		}
		return '';
	}
/*-----------------------------------------------------------------------*
* Function: getRecord
* Parameter: WHERE condition
* Return: 1 if id already exists, 0 if not exists
*-----------------------------------------------------------------------*/
	function getRecord($id) {
		$result = $this->select('id',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
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
	function updateData($fields,$id) {
		$result = $this->update($fields,"`store_id` = '".$this->store_id."' AND `id` =$id ");
		if($result)
			return $result;
		return "";
	}
	
	function changePosition($oId = 0, $position = 0) {
		if(!$oId) return 0;
		if($this->update(array('position' => $position), "`store_id` = '".$this->store_id."' AND `id` = '$oId'")) return 1;
		return 0;
	}
	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
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
						$uploadIds[] = $fileId;				
					}
				}
			}
			$uploads->changeStatusMultiple($uploadIds,S_DELETED);
		}

		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}
	
	#Return a Static Title from provided ID
	function getTitleFromId($id='') {
		if(!$id) return '';
		$result = $this->select('title',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['title'];
		return '';
	}
/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObjectFromSlug($slug) {
		if(!$slug) return "";
		$sql = "SELECT	s.id,
    					s.store_id,
						s.menu_id,
						s.slug,
						s.title,
						s.keyword,
						s.description,
						s.detail,
						s.date_created,
						s.date_updated,
						s.status,
						s.properties,
						s.landing,
						c.id AS creator_id,
						c.username AS creator_name,
						u.id AS updater_id,
						u.username AS updater_name
				FROM dc_static s
				LEFT JOIN dc_users c ON s.creator_id = c.id
				LEFT JOIN dc_users u ON s.updater_id = u.id
				WHERE (s.`store_id` = '".$this->store_id."' or s.`store_id`=0) AND s.`slug` = '$slug' AND ($condition)";
		$result = $this->query($sql);
		if($result) {
			$object = new StaticInfo (
										$result[0]['landing'],
										$result[0]['slug'],
										$result[0]['title'],
										$result[0]['keyword'],
										$result[0]['description'],
										$result[0]['detail'],
										$result[0]['date_created'],
										$result[0]['date_updated'],
										$result[0]['creator_name'],
										$result[0]['updater_name'],				
										$result[0]['creator_id'],
										$result[0]['updater_id'],
										$result[0]['status'],
										$result[0]['properties'],
										$result[0]['store_id'],
										$result[0]['menu_id'],
										$result[0]['id']
										);
			return $object;
		}
		return "";
	}
/*-----------------------------------------------------------------------*
* Function: CheckDuplicate
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*------------------------------------------------------------------------*/
	function checkDuplicate($value = '', $key = 'slug', $condition = '') {
		$result = $this->select("`$key`","`store_id` = '".$this->store_id."' AND `$key` = '$value'".($condition?" AND $condition":''));
		if($result) return 1;
		return 0;
	}

    function searchCustomField($kw)
    {
        $sql = "
			SELECT p.id
			FROM dc_static p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'static' 
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
	# New getNumItems that support multiple tables
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();
		
		$sql = "SELECT COUNT(s.`$pk`)
				FROM dc_static s
				WHERE (s.`store_id` = '".$this->store_id."' or s.`store_id`=0) AND ($condition)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}
	
	# Delete avatar from object
	function deleteAvatarFromObject($staticInfo,$avatarId) {
		global $userInfo;
		include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
		$uploads = new Uploads($this->store_id);
		
		if($avatarId>0) $avatarInfo = $uploads->getObject($avatarId,'u.id');
		if(isset($avatarInfo)) {
			# Change avatar status to "Deleted"
			$uploads->changeStatus($avatarId,S_DELETED);
			
			# Remove property avatarId & avatarUrl from static page
			$properties = $staticInfo->getProperties();
			$properties['avatarId'] = 0;
			$properties['avatarUrl'] = '';
			
			# Update static object to Database
			$data = array(
				'properties' => serialize($properties),
				'date_updated' => date("Y-m-d H:i:s"),
				'updater_id' => $userInfo->getId()
			);
			$this->updateData($data,$staticInfo->getId());
		}
	}
	# Delete file from object
	function deleteFileFromObject($staticInfo,$fileId) {
		global $userInfo;
		include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
		$uploads = new Uploads($this->store_id);
		
		if($fileId>0) $fileInfo = $uploads->getObject($fileId,'u.id');
		if(isset($fileInfo)) {
			# Change file status to "Deleted"
			$uploads->changeStatus($fileId,S_DELETED);
			
			# Remove property fileId from static page
			$properties = $staticInfo->getProperties();
			$properties['fileIds'] = array_diff($properties['fileIds'],[$fileId]);
			
			# Update static object to Database
			$data = array(
				'properties' => serialize($properties),
				'date_updated' => date("Y-m-d H:i:s"),
				'updater_id' => $userInfo->getId()
			);
			$this->updateData($data,$staticInfo->getId());
		}
		
	}
}
?>
