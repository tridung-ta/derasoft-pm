<?php
# DeraCMS 4.0 Project
# Company: Derasoft Co., Ltd
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025

include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalueinfo.class.php');

class CustomProductOptionValues extends Model
{
	var $table;
	var $_db;
	var $store_id;


	public function __construct($store_id = 0, $database = '')
	{
		if (!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;

		$this->table = DB_PREFIX . "custom_product_option_values";
		$this->store_id = $store_id;
	}
	public function CustomProductOptionValues($store_id = 0, $database = '')
	{
		$this->__construct($store_id, $database);
	}


	/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0')
	{
		$result = $this->select('*', "(`store_id` = '" . $this->store_id . "' or `store_id`=0) AND `$key` = '$value' AND ($condition)");
		if ($result) {
			$object = new CustomProductOptionValueInfo(

				$result[0]['status'],
				$result[0]['price_modifier'],
				$result[0]['value'],
				$result[0]['option_id'],
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
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE)
	{
		if (!$page) $page = 1;
		$start = ($page - 1) * $items_per_page;
		$results = $this->select('*', "(`store_id` = '" . $this->store_id . "' or `store_id`=0) AND $condition", $sort, $start, $items_per_page);
		if ($results) {
			$objects = array();
			foreach ($results as $key => $result) {
				$objects[] = new CustomProductOptionValueInfo(
					$result['status'],
					$result['price_modifier'],
					$result['value'],
					$result['option_id'],
					$result['store_id'],
					$result['id']
				);
			}
			return $objects;
		}
		return '';
	}

	/*-----------------------------------------------------------------------*
* Function: addData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/
	function addData($object, $key = 'id')
	{
		return $this->add($object, $key, NULL);
	}
	/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if key already exists, 0 if not exists
*-----------------------------------------------------------------------*/
	// function updateData($object, $value = '', $key = 'id')
	// {
	// 	$this->update($object, "(`store_id` = '" . $this->store_id . "' or `store_id`=0)  AND `$key` = '$value'");
	// }
	function updateData($object, $value = '', $key = 'id')
	{
		$result = $this->update($object, "(`store_id` = '" . $this->store_id . "' or `store_id`=0)  AND `$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}
	# Change status
	// function changeStatus($id = 0, $status = '')
	// {
	// 	if (!$id) return 0;
	// 	if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) {
	// 		$optionValue = new OptionValue($this->store_id, $this->_db);
	// 		$optionValue->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `field_id` = '$id'");

	// 		return 1;
	// 	}
	// 	return 0;
	// }
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	function changeStatusByOptionId($optionId = 0, $status = '')
	{
		if (!$optionId) return 0;

		// Cập nhật tất cả bản ghi có option_id khớp và cùng store_id
		$where = "`store_id` = '" . $this->store_id . "' AND `option_id` = '$optionId'";
		$update = array('status' => $status);

		if ($this->update($update, $where)) return 1;

		return 0;
	}

	// change Status By Option Ids
	public function changeStatusByOptionIds(array $optionIds = [], $status = '')
	{
		if (empty($optionIds)) return 0;

		$ids = array_map('intval', $optionIds);
		$idsList = implode(',', $ids);
		$where = "`store_id` = '" . $this->store_id . "' AND `option_id` IN ($idsList)";
		$update = ['status' => $status];

		return $this->update($update, $where);
	}


	# Change position category
	function changePosition($id = 0, $position = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('position' => $position), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}
	# Clean trash
	// function cleanTrash()
	// {
	// 	// Tạo instance của OptionValue
	// 	$optionValue = new OptionValue($this->store_id, $this->_db);

	// 	// Lấy danh sách tất cả field_id sẽ bị xóa trong OptionStructure
	// 	$results = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);

	// 	if ($results) {
	// 		// Tạo mảng chứa tất cả field_id cần xóa
	// 		$fieldIds = array_map(function ($result) {
	// 			return $result['id'];
	// 		}, $results);

	// 		// Nếu có field_id hợp lệ, thực hiện DELETE một lần thay vì lặp từng field_id
	// 		if (!empty($fieldIds)) {
	// 			$fieldIdsStr = implode(',', $fieldIds);
	// 			$optionValue->delete("`store_id` = '" . $this->store_id . "' AND `field_id` IN ($fieldIdsStr)");
	// 		}
	// 	}

	// 	// Xóa dữ liệu trong OptionStructure
	// 	return $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED) ? 1 : 0;
	// }

	function cleanTrash()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}

	function checkDuplicate($value = '', $key = 'id', $condition = '')
	{
		$result = $this->select("`$key`", "`store_id` = '" . $this->store_id . "' AND `$key` = '$value'" . ($condition ? " AND $condition" : ''));
		if ($result) return 1;
		return 0;
	}

	function getParentIdFromId($id = '')
	{
		if (!$id) return '';
		$result = $this->select('option_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['option_id'];
		return '';
	}
	function getValueFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('value', " id = '$id'");
		if ($result) return $result[0]['value'];
		return '';
	}
	# Return a AdsCategory name from provided ID
	function getPriceModifierFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('price_modifier', " id = '$id'");
		if ($result) return $result[0]['price_modifier'];
		return '';
	}

	function getValueAndPriceModifierByOptionId($id = '0'): array
	{
		global $amessages;
		$id = intval($id);
		if (!$id) {
			return [
				['value' => $amessages['root'], 'price_modifier' => '']
			];
		}

		$result = $this->select('value, price_modifier', "option_id = $id");

		if (!empty($result)) {
			return $result;
		}

		return [];
	}

	function getIdFromName($name = '')
	{
		global $amessages;
		if (!$name) return $amessages['root'];
		$result = $this->select('name', " name = '$name'");
		if ($result) return $result[0]['id'];
		return '';
	}

	public function getOptionsByProductId($productId)
	{
		$productId = intval($productId);
		$results = $this->select('name', "product_id = $productId");
		$names = [];
		if ($results) {
			foreach ($results as $row) {
				$names[] = $row['name'];
			}
		}
		return $names;
	}
}
