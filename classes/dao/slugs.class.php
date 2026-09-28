<?php
/*************************************************************************
Class Slugs
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Checked by: Mai Minh (06/08/2025)
**************************************************************************/
include_once(ROOT_PATH.'classes/database/model.class.php');
include_once(ROOT_PATH.'classes/dao/sluginfo.class.php');

class Slugs extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."slugs";
		$this->store_id = $store_id;
	}

/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/

	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		$sql = "SELECT	s.id,
    					s.store_id,
						s.slug,
						s.module,
						s.object_id,
						s.date_created,
						s.date_updated,
						s.status,
						s.properties,
						c.id AS creator_id,
						c.username AS creator_name,
						u.id AS updater_id,
						u.username AS updater_name
				FROM dc_slugs s
				LEFT JOIN dc_users c ON s.creator_id = c.id
				LEFT JOIN dc_users u ON s.updater_id = u.id
				WHERE (s.`store_id` = '".$this->store_id."' or s.`store_id`=0) AND s.`$key` = '$value' AND ($condition)";
		$result = $this->query($sql);
		
		if($result) {
			$object = new SlugInfo (
										$result[0]['slug'],
										$result[0]['module'],
										$result[0]['object_id'],
										$result[0]['date_created'],
										$result[0]['date_updated'],
										$result[0]['creator_name'],
										$result[0]['updater_name'],
										$result[0]['creator_id'],
										$result[0]['updater_id'],
										$result[0]['status'],
										$result[0]['properties'],
										$result[0]['store_id'],
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
						s.slug,
						s.module,
						s.object_id,
						s.date_created,
						s.date_updated,
						s.status,
						s.properties,
						c.id AS creator_id,
						c.username AS creator_name,
						u.id AS updater_id,
						u.username AS updater_name
				FROM dc_slugs s
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
				$objects[] = new SlugInfo (
												$result['slug'],
												$result['module'],
												$result['object_id'],
												$result['date_created'],
												$result['date_updated'],
												$result['creator_name'],
												$result['updater_name'],
												$result['creator_id'],
												$result['updater_id'],
												$result['status'],
												$result['properties'],
												$result['store_id'],
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
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}

	#Return a slug text from provided ID
	function getSlugFromId($id='') {
		if(!$id) return '';
		$result = $this->select('slug',"`store_id` = '".$this->store_id."' AND id = '$id'");
		if($result) return $result[0]['slug'];
		return '';
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
	
	function getCustomField()
    {
        $sql = "
			SELECT
				p.id,
                COALESCE(GROUP_CONCAT(cfv.field_value ORDER BY cfv.id ASC SEPARATOR ', '), 'No Data') AS value_list
			FROM dc_slugs p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'slugs' AND cf.status = 1 AND cf.appearance = 1
			LEFT JOIN dc_custom_options_value cfv ON p.id = cfv.key_id AND cf.id = cfv.field_id
			GROUP BY p.id
			ORDER BY p.id DESC
		";

        $result = $this->_db->query($sql);

        if (!$result) {
            die("SQL error: " . $this->_db->error);
        }

        if (!$result) {
            return [];
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    function getCustomFieldEdit($id)
    {
        $sql = "
			SELECT
				GROUP_CONCAT(DISTINCT cfv.id ORDER BY cfv.id ASC SEPARATOR ', ') AS id_list,
				GROUP_CONCAT(cfv.field_value ORDER BY cfv.id ASC SEPARATOR ', ') AS value_list
			FROM dc_slugs p
			LEFT JOIN dc_custom_options_structure cf ON cf.module = 'slugs' AND cf.status = 1 AND cf.appearance = 1
			LEFT JOIN dc_custom_options_value cfv ON p.id = cfv.key_id AND cf.id = cfv.field_id
			WHERE p.id = " . $id . "
			GROUP BY p.id
			ORDER BY p.id DESC
		";

        $result = $this->_db->query($sql);

        if (!$result) {
            return [];
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    function searchCustomField($kw)
    {
        $sql = "
			SELECT p.id
			FROM dc_slugs p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'slugs' 
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
				FROM dc_slugs s
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
}
?>
