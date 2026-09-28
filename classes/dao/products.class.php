<?php
include_once(ROOT_PATH . "classes/database/model.class.php");
include_once(ROOT_PATH . "classes/dao/productinfo.class.php");

class Products extends Model
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
		$this->table = DB_PREFIX . "products";
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
		if (empty($key) || $value === null) return '';

		$result = $this->query("
			SELECT p.*, c.name AS category_name 
			FROM " . DB_PREFIX . "products p 
			LEFT JOIN " . DB_PREFIX . "product_categories c 
			ON p.category_id = c.id 
			WHERE p.store_id = '" . $this->store_id . "' 
			AND `p`.`$key` = '$value' 
			AND ($condition) 
			LIMIT 1
		");
		if ($result) {
			$object = new ProductInfo(
					$result[0]['professional'],
					$result[0]['footer'],
					$result[0]['price'],
					$result[0]['file_ids'],
					$result[0]['gallery_img_ids'],
					$result[0]['category_name'],
					$result[0]['expiration_date'],
					$result[0]['home'],
					$result[0]['set_about_page'],
					$result[0]['status'],
					$result[0]['properties'],
					$result[0]['position'],
					$result[0]['date_updated'],
					$result[0]['date_created'],
					$result[0]['viewed'],
					$result[0]['star'],
					$result[0]['avatar'],
					$result[0]['detail'],
					$result[0]['description'],
					$result[0]['overview'],
					$result[0]['keyword'],
					$result[0]['name'],
					$result[0]['slug'],
					$result[0]['slug_en'],
					$result[0]['slug_zh'],
					$result[0]['lang'],
					$result[0]['category_id'],
					$result[0]['store_id'],
					$result[0]['id']

				);
			return $object;
		}
		return 0;
	}

	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;

		$query = "
			SELECT p.*, c.name AS category_name
			FROM " . DB_PREFIX . "products p
			LEFT JOIN " . DB_PREFIX . "product_categories c 
			ON p.category_id = c.id
			WHERE p.store_id = '" . $this->store_id . "' AND $condition
		";

		if (!empty($sort)) {
			$orderClauses = [];
			foreach ($sort as $key => $value) {
				$orderClauses[] = "$key $value";
			}
			$query .= " ORDER BY " . implode(", ", $orderClauses);
		}

		$query .= " LIMIT $start, $items_per_page";

		$results = $this->query($query);

		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new ProductInfo(
					$result['professional'],
					$result['footer'],
					$result['price'],
					$result['file_ids'],
					$result['gallery_img_ids'],
					$result['category_name'],
					$result['expiration_date'],
					$result['home'],
					$result['set_about_page'],
					$result['status'],
					$result['properties'],
					$result['position'],
					$result['date_updated'],
					$result['date_created'],
					$result['viewed'],
					$result['star'],
					$result['avatar'],
					$result['detail'],
					$result['description'],
					$result['overview'],
					$result['keyword'],
					$result['name'],
					$result['slug'],
					$result['slug_en'],
					$result['slug_zh'],
					$result['lang'],
					$result['category_id'],
					$result['store_id'],
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
	function changeHome($id = 0, $home = '')
	{
		if (!$id) return 0;
		if ($this->update(array('home' => $home), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	function changeAbout($id = 0, $set_about_page = '') {
		if(!$id) return 0;
		if($this->update(array('set_about_page' => $set_about_page), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	function changeFooter($id = 0, $footer = null) {
		if(!$id) return 0;
		if($this->update(array('footer' => $footer), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	function changeProfessional($id = 0, $professional = null) {
		if(!$id) return 0;
		if($this->update(array('professional' => $professional), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Change product category
	function changeCategoryId($id = 0, $category_id = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('category_id' => $category_id), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
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
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$properties = unserialize($result['properties']);
				if ($properties['avatar']) {
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/l_" . $properties['avatar']);
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/m_" . $properties['avatar']);
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/t_" . $properties['avatar']);
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/a_" . $properties['avatar']);
				}
				foreach ($properties['photos'] as $pkey => $pvalue) {
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/l_" . $pvalue);
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/m_" . $pvalue);
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/t_" . $pvalue);
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/a_" . $pvalue);
				}
				foreach ($properties['videos'] as $pkey => $pvalue) {
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/" . $pvalue);
				}
				foreach ($properties['files'] as $pkey => $pvalue) {
					unlink(ROOT_PATH . "upload/" . $this->store_id . "/products/" . $pvalue);
				}				
			}
		}
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}
	function increaseViewed($pId)
	{
		$sql = "UPDATE `" . $this->table . "` SET viewed = viewed + 1 WHERE store_id = '" . $this->store_id . "' AND id = '" . addslashes($pId) . "'";
		if (SHOW_QUERY) echo $sql;
		if ($this->_db->query($sql)) return 1;
		return 0;
	}
	# Return a Product Id from provided ID
	function getIdFromSlug($slug = '')
	{
		if (!$slug) return 0;
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND slug = '$slug'");
		if ($result) return $result[0]['id'];
		return 0;
	}
	# Return a Product Id from provided ID
	function getIdFromName($name = '')
	{
		if (!$name) return 0;
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND name = '$name'");
		if ($result) return $result[0]['id'];
		return 0;
	}

	# Return a Product Name from provided slug
	function getNameFromSlug($slug = '')
	{
		if (!$slug) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND slug = '$slug'");
		if ($result) return $result[0]['name'];
		return '';
	}

	# Return a Product slug from provided ID
	function getSlugFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('slug', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['slug'];
		return '';
	}

	# Return a Product name from provided ID
	function getNameFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('name', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}

	function checkDuplicate($value = '', $key = 'name', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}

	# Return a Product name from provided ID
	function getCatIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('category_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['category_id'];
		return '';
	}
	function getProductFromPid($pId)
	{
		$results = $this->select('*', "`store_id` = '" . $this->store_id . "' AND status =1 AND `category_id`=$pId", array('date_created' => 'DESC'),  $start, '');
		if ($results) {
			$productInfos = array();
			foreach ($results as $key => $result) {
				$productInfos[] = new ProductInfo(
					$result['professional'],
					$result['footer'],
					$result['price'],
					$result['file_ids'],
					$result['gallery_img_ids'],
					$result['category_name'],
					$result['expiration_date'],
					$result['home'],
					$result['set_about_page'],
					$result['status'],
					$result['properties'],
					$result['position'],
					$result['date_updated'],
					$result['date_created'],
					$result['viewed'],
					$result['star'],
					$result['avatar'],
					$result['detail'],
					$result['description'],
					$result['overview'],
					$result['keyword'],
					$result['name'],
					$result['slug'],
					$result['slug_en'],
					$result['slug_zh'],
					$result['lang'],
					$result['category_id'],
					$result['store_id'],
					$result['id']
				);
			}
			return $productInfos;
		}
		return '';
	}

	function searchCustomField($kw)
	{
		$sql = "
			SELECT p.id
			FROM dc_products p
			LEFT JOIN dc_custom_options_structure cf 
				ON cf.module = 'product' 
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

	# handle for pagination
	public function countByCondition($condition = '1>0') {
		$sql = "
			SELECT COUNT(*) AS c
			FROM " . DB_PREFIX . "products p
			LEFT JOIN " . DB_PREFIX . "product_categories c
			ON p.category_id = c.id
			WHERE p.store_id = '" . $this->store_id . "' AND $condition
		";
		$rows = $this->query($sql);          
		return (int)($rows[0]['c'] ?? 0);
	}

	public function distinctWeights($condition = '1>0') {
		$sql = "
			SELECT DISTINCT TRIM(p.`weight`) AS w
			FROM " . DB_PREFIX . "products p
			LEFT JOIN " . DB_PREFIX . "product_categories c
			ON p.category_id = c.id
			WHERE p.store_id = '" . $this->store_id . "'
			AND $condition AND TRIM(p.`weight`) <> ''
			ORDER BY w ASC
		";
		$rows = $this->query($sql);
		return $rows ? array_column($rows, 'w') : [];
	}

	# get upload ids from these fields
	protected $uploadFields = [
		'Avatar',     // 1 upload id <==> getAvatar()
		'FileIds',    // csv upload ids
		'GalleryImgIds', // csv upload ids
	];

	public function getTrashUploadIds()
	{
		$uploadIds = [];

		$trashProducts = $this->getObjects(1,"p.`status` = " . S_DELETED,[],100000);

		if (!$trashProducts) return [];

		foreach ($trashProducts as $product) {
			foreach ($this->uploadFields as $field) {

				$getter = 'get' . ucfirst($field);
				if (!method_exists($product, $getter)) continue;

				$value = $product->$getter();
				if (!$value) continue;

				if (is_numeric($value)) {
					$uploadIds[] = (int)$value;
				} elseif (is_string($value)) {
					$ids = array_map('intval', explode(',', $value));
					$uploadIds = array_merge($uploadIds, $ids);
				}
			}
		}

		return array_values(
			array_unique(
				array_filter($uploadIds)
			)
		);
	}

}
