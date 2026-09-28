<?php
/*************************************************************************
Class Uploads
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated:28/06/2010
Coder: Thai Nguyen
Reviewed by: Mai Minh (03/06/2025)

**************************************************************************/
include_once(ROOT_PATH . "classes/database/model.class.php");
include_once(ROOT_PATH . "classes/dao/uploadinfo.class.php");
class Uploads extends Model
{
	public $table;
	public $_db;
	public $store_id;

	function __construct($store_id= 0,$database = '')
	{
		if (!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX . "uploads";
		$this->store_id = $store_id;
	}

	/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'u.id', $condition = '1>0')
	{
		if (!$key || !$value) return '';
		
		#$result = $this->select('*', "(`store_id` = '".$this->store_id."' or `store_id`=0) AND `$key` = '$value' AND ($condition)");
		
		# New select that support multiple tables
		$sql = "SELECT	u.*,a.folder
				FROM dc_uploads u
				LEFT JOIN dc_upload_albums a ON u.album_id = a.id
				WHERE (u.`store_id` = '".$this->store_id."' or u.`store_id`=0) AND $key = '$value' AND ($condition)";
		
		$result = $this->query($sql);
		if ($result) {
			$object = new UploadInfo(
				$result[0]['url_o'],
				$result[0]['url_l'],
				$result[0]['url_m'],
				$result[0]['url_t'],
				$result[0]['url_a'],
				$result[0]['status'],
				$result[0]['name'],
				$result[0]['store_id'],
				$result[0]['date_created'],
				$result[0]['type'],
				$result[0]['folder'],
				$result[0]['object'],
				$result[0]['position'],
				$result[0]['album_id'],
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
		//$results = $this->select('*', "(`store_id` = '".$this->store_id."' or `store_id`=0) AND $condition", $sort, $start, $items_per_page);
	
		// New select that support multiple tables
		$sql = "SELECT	u.*,a.folder
				FROM dc_uploads u
				LEFT JOIN dc_upload_albums a ON u.album_id = a.id
				WHERE (u.`store_id` = '".$this->store_id."' or u.`store_id`=0) AND ($condition)";
		
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
		
		$results = $this->query($sql);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new UploadInfo(
					$result['url_o'],
					$result['url_l'],
					$result['url_m'],
					$result['url_t'],
					$result['url_a'],
					$result['status'],
					$result['name'],
					$result['store_id'],
					$result['date_created'],
					$result['type'],
					$result['folder'],
					$result['object'],
					$result['position'],
					$result['album_id'],
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
	function addData($fields, $key = 'id')
	{
		$result = $this->add($fields, '$key', 'NULL');
		if ($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id')
	{
		$result = $this->update($fields, "`$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`id` = '$id'")) return 1;
		return 0;
	}
	
	# Change status
	function changeStatusMultiple($ids = array(), $status = '')
	{
		// Chỉ giữ lại các ID là số
		$ids = array_values(array_filter($ids, function ($id) {
			return ctype_digit((string)$id);
		}));

		if (empty($ids)) {
			return 0;
		}

		return $this->update(
			array('status' => $status),
			"`id` IN (" . implode(',', $ids) . ")"
		);
	}
	
	# Clean img
	function DeteImg($id)
	{
		$result = $this->delete("`id` = $id");
		if ($result) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash()
	{
		$objects = $this->getObjects(1,"u.`status` = " . S_DELETED,array(),9999);
		if($objects) {
			foreach($objects as $object) {
				# Delete all files for object that will be deleted permanently
				$object->deleteFiles();
			}
		}
		$result = $this->delete("`status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}
	# Return a object from provided ID
	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}
	function getUrlOFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('url_o', "id = '$id'");
		if ($result) return $result[0]['url_o'];
		return '';
	}
	function getUrlLFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('url_l', "id = '$id'");
		if ($result) return $result[0]['url_l'];
		return '';
	}
	function getUrlMFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('url_m', "id = '$id'");
		if ($result) return $result[0]['url_m'];
		return '';
	}
	function getUrlTFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('url_t', "id = '$id'");
		if ($result) return $result[0]['url_t'];
		return '';
	}
	function getUrlAFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('url_a', "id = '$id'");
		if ($result) return $result[0]['url_a'];
		return '';
	}
	
	# Return a object from provided ID
	function getIdFromUrlO($id = '')
	{
		if (!$id) return '';
		$result = $this->select('id', "`url_o` = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
	# Return a object from provided ID
	function getIdFromUrlL($id = '')
	{
		if (!$id) return '';
		$result = $this->select('id', "`url_l` = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
	# Return a object from provided ID
	function getIdFromUrlM($id = '')
	{
		if (!$id) return '';
		$result = $this->select('id', "`url_m` = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
	# Return a object from provided ID
	function getIdFromUrlT($id = '')
	{
		if (!$id) return '';
		$result = $this->select('id', "`url_t` = '$id'");
		if ($result) return $result[0]['id'];
		return '';
	}
	
	# New getNumItems that support multiple tables
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();

		$sql = "SELECT COUNT(u.`$pk`)
				FROM `dc_uploads` AS u
				LEFT JOIN `dc_upload_albums` a ON u.`album_id`=a.`id`
				WHERE (u.`store_id` = '".$this->store_id."' or u.`store_id`=0) AND ($condition)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}
	
	# Upload file
	function uploadFile($album,$postDataName,$postDataTmpName,$postDataSize,$object='none',$creator_id=0,$updater_id=0){

    $newUploadId = '';
    $gallery_path = $album->getAbsoluteFolder();

    if (empty($postDataName)) return '';

    $textFilter = new TextFilter();

    // ===== Get extension & filename =====
    $src_file_extension = strtolower(pathinfo($postDataName, PATHINFO_EXTENSION));
    $filename_without_extension = pathinfo($postDataName, PATHINFO_FILENAME);

    // ===== Check allowed extension =====
    if (!preg_match('/^('.ALLOW_FILE_TYPES.')$/', $src_file_extension)) {
        return '';
    }

    // ===== Normalize filename =====
    $file_name_normalized = str_replace(
        ' ',
        '-',
        strtolower($textFilter->cleanVietnamese($filename_without_extension))
    );

    $tmp_filename = $postDataTmpName;
    $random = rand(10000,99999);

    // ===== Original file name =====
    $imgo = Filter($file_name_normalized . "_" . $random . "_o." . $src_file_extension);

    // ===== Destination format for resized images =====
    $dst_file_extension = $src_file_extension;

    if (!in_array($dst_file_extension, ['jpg','jpeg','png','gif','webp'])) {
        $dst_file_extension = DEFAULT_PHOTO_FORMAT;
    }

    if ($dst_file_extension === 'jpeg') $dst_file_extension = 'jpg';

    // ===== Move uploaded file =====
    if (!move_uploaded_file($tmp_filename, $gallery_path . $imgo)) {
        return '';
    }

    // ===== Initialize DB data =====
    $data = array(
        'store_id' => $this->store_id,
        'album_id' => $album->getId(),
        'status'   => 1,
        'url_o'    => '',
        'url_l'    => '',
        'url_m'    => '',
        'url_t'    => '',
        'url_a'    => '',
        'type'     => 5,
        'object'   => $object,
        'name'     => $filename_without_extension
    );

    if($creator_id>0) {
        $data['creator_id'] = $creator_id;
        $data['date_created'] = date("Y-m-d H:i:s");
    }

    if($updater_id>0) {
        $data['updater_id'] = $updater_id;
        $data['date_updated'] = date("Y-m-d H:i:s");
    }

    // =========================================================
    // ================= IMAGE FILE ============================
    // =========================================================
    if (isImage($imgo)) {

        // Extra protection: verify real image
        $info = @getimagesize($gallery_path . $imgo);
        if ($info === false) {
            unlink($gallery_path . $imgo);
            return '';
        }

        $data['type'] = 1;

        if (CREATE_LARGE_IMAGE) {
            $imgl = Filter($file_name_normalized . "_" . $random . "_l." . $dst_file_extension);
            resize($gallery_path,$gallery_path,$imgo,$imgl,DEFAULT_LARGE_SIZE,DEFAULT_LARGE_SQUARE,DEFAULT_PHOTO_QUALITY);
            $data['url_l'] = $imgl;
        }

        if (CREATE_MEDIUM_IMAGE) {
            $imgm = Filter($file_name_normalized . "_" . $random . "_m." . $dst_file_extension);
            resize($gallery_path,$gallery_path,$imgo,$imgm,DEFAULT_MEDIUM_SIZE,DEFAULT_MEDIUM_SQUARE,DEFAULT_PHOTO_QUALITY);
            $data['url_m'] = $imgm;
        }

        if (CREATE_THUMBNAIL_IMAGE) {
            $imgt = Filter($file_name_normalized . "_" . $random . "_t." . $dst_file_extension);
            resize($gallery_path,$gallery_path,$imgo,$imgt,DEFAULT_THUMBNAIL_SIZE,DEFAULT_THUMBNAIL_SQUARE,DEFAULT_PHOTO_QUALITY);
            $data['url_t'] = $imgt;
        }

        if (defined('CREATE_AVATAR_IMAGE') && CREATE_AVATAR_IMAGE) {
            $imga = Filter($file_name_normalized . "_" . $random . "_a." . $dst_file_extension);
            resize($gallery_path,$gallery_path,$imgo,$imga,DEFAULT_AVATAR_SIZE,DEFAULT_AVATAR_SQUARE,DEFAULT_PHOTO_QUALITY);
            $data['url_a'] = $imga;
        }

        if (KEEP_ORIGINAL_IMAGE_FILE) {
            $data['url_o'] = $imgo;
        } else {
            unlink($gallery_path . $imgo);
        }

    }

    // =========================================================
    // ================= VIDEO FILE ============================
    // =========================================================
    elseif (isVideo($imgo)) {
        $data['type'] = 2;
        $data['url_o'] = $imgo;
    }

    // =========================================================
    // ================= MUSIC FILE ============================
    // =========================================================
    elseif (isMusic($imgo)) {
        $data['type'] = 4;
        $data['url_o'] = $imgo;
    }

    // =========================================================
    // ================= OTHER FILE ============================
    // =========================================================
    else {
        $data['type'] = 5;
        $data['url_o'] = $imgo;
    }

    // ===== Insert to database =====
    $newUploadId = $this->addData($data);

    return $newUploadId;
}

	function cleanTrashByIds($uploadIds = array())
	{
		if (!$uploadIds || !is_array($uploadIds)) return 0;

		// ép kiểu an toàn
		$uploadIds = array_filter(array_map('intval', $uploadIds));
		if (!$uploadIds) return 0;

		$idList = implode(',', $uploadIds);

		$objects = $this->getObjects(
			1,
			"u.`id` IN ($idList)",
			array(),
			9999
		);

		if ($objects) {
			foreach ($objects as $object) {
				if (method_exists($object, 'deleteFiles')) {
					$object->deleteFiles();
				}
			}
		}

		$result = $this->delete("`id` IN ($idList)");
		if ($result) return 1;

		return 0;
	}

}
?>