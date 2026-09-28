<?php
# DeraCMS 4.0 Project
# Company: Derasoft Co., Ltd
# Coder: Tien Le
# Reviewed by: Mai Minh (03/06/2025
############################################################
include_once(ROOT_PATH . 'classes/database/model.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptioninfo.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');

class CustomProductOptions extends Model
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

		$this->table = DB_PREFIX . "custom_product_options";
		$this->store_id = $store_id;
	}
	public function CustomProductOptions($store_id = 0, $database = '')
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
			$object = new CustomProductOptionInfo(

				$result[0]['status'],
				$result[0]['name'],
				$result[0]['product'],
				$result[0]['product_id'],
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
				$objects[] = new CustomProductOptionInfo(
					$result['status'],
					$result['name'],
					$result['product'],
					$result['product_id'],
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
	function updateData($object, $value = '', $key = 'id')
	{
		$result = $this->update($object, "(`store_id` = '" . $this->store_id . "' or `store_id`=0)  AND `$key` = '$value'");
		if ($result)
			return $result;
		return 0;
	}
	function updateDataOptionWithValues($data, $value = '', $key = 'id')
	{
		$result = $this->update($data, "(`store_id` = '" . $this->store_id . "' OR `store_id` = 0) AND `$key` = '$value'");

		if (isset($data['status']) && !empty($value)) {
			include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');
			$customProductOptionValues = new CustomProductOptionValues($this->store_id);
			$optionId = $value;

			$customProductOptionValues->changeStatusByOptionId($optionId, $data['status']);
		}

		return $result ? $result : 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '')
	{
		if (!$id) return 0;
		if ($this->update(array('status' => $status), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
		return 0;
	}

	function cleanTrash()
	{
		$result = $this->delete("`store_id` = '" . $this->store_id . "' AND `status` = " . S_DELETED);
		if ($result) return 1;
		return 0;
	}

	# Change position category
	function changePosition($id = 0, $position = 0)
	{
		if (!$id) return 0;
		if ($this->update(array('position' => $position), "`store_id` = '" . $this->store_id . "' AND `id` = '$id'")) return 1;
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
		$result = $this->select('product_id', "`store_id` = '" . $this->store_id . "' AND id = '$id'");
		if ($result) return $result[0]['product_id'];
		return '';
	}

	# Return a AdsCategory name from provided ID
	function getNameFromId($id = '0')
	{
		global $amessages;
		if (!$id) return $amessages['root'];
		$result = $this->select('name', " id = '$id'");
		if ($result) return $result[0]['name'];
		return '';
	}

	function getIdFromName($name = '')
	{
		global $amessages;
		if (!$name) return $amessages['root'];
		$result = $this->select('name', " name = '$name'");
		if ($result) return $result[0]['id'];
		return '';
	}

	// get options by product id
	public function getOptionsByProductId($productId)
	{
		$productId = intval($productId);
		$results = $this->select('name', "product_id = $productId AND status = 1");
		$names = [];
		if ($results) {
			foreach ($results as $row) {
				$names[] = $row['name'];
			}
		}
		return $names;
	}

	public function changeStatusByProductId($productId, $status = 0)
	{
		$productId = intval($productId);
		$status = intval($status);
		$results = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `product_id` = $productId");
		$optionIds = [];

		if ($results) {
			foreach ($results as $row) {
				$optionIds[] = $row['id'];
			}

			if (!empty($optionIds)) {
				$idList = implode(',', $optionIds);
				$this->update(
					['status' => $status],
					"`store_id` = '" . $this->store_id . "' AND `id` IN ($idList)"
				);
			}
		}

		return $optionIds;
	}

	public function getOptionsWithValuesByProductId($productId)
	{
		$productId = intval($productId);
		$sql = "SELECT 
					cpo.id AS option_id, 
					cpo.name AS option_name, 
					cpov.value, 
					cpov.price_modifier
				FROM dc_custom_product_options cpo
				LEFT JOIN dc_custom_product_option_values cpov
				ON cpo.id = cpov.option_id AND cpov.status = 1
				WHERE cpo.product_id = $productId AND cpo.status = 1";


		$result = $this->query($sql);
		$rows = $result ?: [];
		$options = [];

		foreach ($rows as $row) {
			$optionId = $row['option_id'];
			if (!isset($options[$optionId])) {
				$options[$optionId] = [
					'id' => $optionId,
					'name' => $row['option_name'],
					'values' => [],
				];
			}

			$options[$optionId]['values'][] = [
				'value' => $row['value'],
				'price_modifier' => $row['price_modifier'],
			];
		}

		return array_values($options);
	}

	// cập nhật PO
	// public function updateOptionsByProductId($productId, $productName, $data, $storeId)
	// {
	// 	$productId = intval($productId);
	// 	$storeId = intval($storeId);
	// 	$productName = addslashes($productName);

	// 	// Không còn option nào -> xoá sạch
	// 	if (empty($data)) {
	// 		return $this->deleteAllByProductId($productId, $storeId);
	// 	}

	// 	$usedOptionIds = [];

	// 	foreach ($data as $option) {
	// 		$optionName = addslashes(trim($option['name']));
	// 		$condition = "product_id = $productId AND store_id = $storeId AND name = '$optionName'";
	// 		$results = $this->select('id', $condition, [], 0, 1);
	// 		$optionId = $results[0]['id'] ?? null;

	// 		if ($optionId) {
	// 			$object = [
	// 				'product' => $productName,
	// 				'status' => 1
	// 			];
	// 			$this->updateData($object, $optionId, 'id');

	// 		} else {
	// 			$fieldsOption = [
	// 				'product_id' => $productId,
	// 				'product' => $productName,
	// 				'name' => $optionName,
	// 				'status' => 1,
	// 				'store_id' => $storeId,
	// 			];
	// 			$optionId = $this->addData($fieldsOption);
	// 			if (!$optionId) continue;
	// 		} # End if ($optionId) {

	// 		$usedOptionIds[] = $optionId;

	// 		$oldValues = $this->query("SELECT id, value, price_modifier FROM dc_custom_product_option_values WHERE option_id = $optionId");
	// 		$oldValueMap = [];
	// 		foreach ($oldValues as $v) {
	// 			$oldValueMap[trim($v['value'])] = $v;
	// 		}

	// 		$newValueNames = [];

	// 		foreach ($option['values'] as $value) {
	// 			$valueText = addslashes(trim($value['value']));
	// 			$priceModifier = floatval($value['price_modifier']);
	// 			$newValueNames[] = $valueText;

	// 			if (isset($oldValueMap[$valueText])) {
	// 				$old = $oldValueMap[$valueText];
	// 				if ($old['price_modifier'] != $priceModifier) {
	// 					// Cập nhật price_modifier và bật status
	// 					$this->_db->query("UPDATE dc_custom_product_option_values SET price_modifier = {$priceModifier}, status = 1 WHERE id = {$old['id']}");
	// 				} else {
	// 					// Chỉ bật status nếu đang tắt
	// 					$this->_db->query("UPDATE dc_custom_product_option_values SET status = 1 WHERE id = {$old['id']}");
	// 				} # End if ($old['price_modifier'] != $priceModifier) {
	// 			} else {
	// 				$this->_db->query("INSERT INTO dc_custom_product_option_values (option_id, value, price_modifier, status, store_id) VALUES ($optionId, '$valueText', $priceModifier, 1, $storeId)");
	// 			} # End if (isset($oldValueMap[$valueText])) {

	// 		} # End foreach ($option['values'] as $value) {

	// 		// Xóa hóa các value cũ không nằm trong danh sách mới
	// 		if (!empty($oldValues)) {
	// 			$escapedValues = array_map(function ($val) {
	// 				return "'" . addslashes($val) . "'";
	// 			}, $newValueNames);

	// 			$newValueStr = implode(',', $escapedValues);

	// 			// XÓA các value cũ không còn được gửi lên
	// 			$this->_db->query("DELETE FROM dc_custom_product_option_values WHERE option_id = $optionId AND value NOT IN ($newValueStr)");
	// 		} # End if (!empty($oldValues)) {
	// 	} # End foreach ($data as $option) {

	// 	// Vô hiệu hóa các option cũ không còn được gửi lên
	// 	if (!empty($usedOptionIds)) {
	// 		$idsStr = implode(',', $usedOptionIds);
	// 		$this->table = 'dc_custom_product_options';
	// 		$fields = ['status' => 0,];
	// 		$condition = "product_id = $productId AND store_id = $storeId AND id NOT IN ($idsStr)";
	// 		$this->update($fields, $condition);

	// 		$this->_db->query("UPDATE dc_custom_product_option_values SET status = 0 WHERE option_id IN (SELECT id FROM dc_custom_product_options WHERE product_id = $productId AND store_id = $storeId AND id NOT IN ($idsStr)
	// 			)");
	// 	} # End if (!empty($usedOptionIds)) {
	// 	return true;
	// }

	public function updateOptionsByProductId($productId, $productName, $data, $storeId)
	{
		$productId   = (int)$productId;
		$storeId     = (int)$storeId;
		$productName = addslashes($productName);

		$tableOptions = DB_PREFIX . "custom_product_options";
		$tableValues  = DB_PREFIX . "custom_product_option_values";

		// Không còn option nào -> xoá sạch
		if (empty($data)) {
			return $this->deleteAllByProductId($productId, $storeId);
		}

		$usedOptionIds = [];

		foreach ($data as $option) {
			$optionName = addslashes(trim($option['name'] ?? ''));
			if ($optionName === '') continue;

			// Tìm option theo product + store + name
			$condition = "product_id = {$productId} AND store_id = {$storeId} AND name = '{$optionName}'";
			$results   = $this->select('id', $condition, [], 0, 1);
			$optionId  = $results[0]['id'] ?? null;

			if ($optionId) {
				$this->updateData(['product' => $productName, 'status' => 1], $optionId, 'id');
			} else {
				$optionId = $this->addData([
					'product_id' => $productId,
					'product'    => $productName,
					'name'       => $optionName,
					'status'     => 1,
					'store_id'   => $storeId,
				]);
				if (!$optionId) continue;
			}

			$usedOptionIds[] = (int)$optionId;

			// Map các value cũ
			$oldValues   = $this->query("SELECT id, value, price_modifier FROM {$tableValues} WHERE option_id = {$optionId}");
			$oldValueMap = [];
			foreach ($oldValues ?: [] as $v) {
				$oldValueMap[trim($v['value'])] = $v;
			}

			// Cập nhật/Thêm value mới
			$newValueNames = [];
			foreach (($option['values'] ?? []) as $valRow) {
				$valueTextRaw = trim((string)($valRow['value'] ?? ''));
				if ($valueTextRaw === '') continue; // bỏ value rỗng

				$valueText     = addslashes($valueTextRaw);
				$priceModifier = isset($valRow['price_modifier']) && is_numeric($valRow['price_modifier'])
							? (float)$valRow['price_modifier'] : 0.0;

				$newValueNames[] = $valueText;

				if (isset($oldValueMap[$valueText])) {
					$old = $oldValueMap[$valueText];
					if ((float)$old['price_modifier'] !== $priceModifier) {
						// Cập nhật giá & bật lại
						$this->_db->query("
							UPDATE {$tableValues}
							SET price_modifier = {$priceModifier}, status = 1
							WHERE id = {$old['id']}
						");
					} else {
						// Bật lại nếu đang tắt
						$this->_db->query("
							UPDATE {$tableValues}
							SET status = 1
							WHERE id = {$old['id']}
						");
					}
				} else {
					// Thêm mới
					$this->_db->query("
						INSERT INTO {$tableValues} (option_id, value, price_modifier, status, store_id)
						VALUES ({$optionId}, '{$valueText}', {$priceModifier}, 1, {$storeId})
					");
				}
			}

			// Xoá các value cũ không còn trong danh sách mới
			if (!empty($oldValues)) {
				if (!empty($newValueNames)) {
					$newValueStr = implode(',', array_map(fn($v) => "'{$v}'", $newValueNames));
					// $newValueStr = implode(',', array_map(function ($v) { return "'" . $v . "'"; }, $newValueNames));
					$this->_db->query("
						DELETE FROM {$tableValues}
						WHERE option_id = {$optionId} AND value NOT IN ({$newValueStr})
					");
				} else {
					// Không còn value nào cho option này -> xoá sạch values
					$this->_db->query("
						DELETE FROM {$tableValues}
						WHERE option_id = {$optionId}
					");
				}
			}
		} // end foreach option

		// Vô hiệu hoá các option cũ không còn được gửi lên
		if (!empty($usedOptionIds)) {
			$idsStr      = implode(',', array_map('intval', $usedOptionIds));
			$backupTable = $this->table;

			$this->table = $tableOptions;
			$this->update(['status' => 0], "product_id = {$productId} AND store_id = {$storeId} AND id NOT IN ({$idsStr})");
			$this->table = $backupTable;

			$this->_db->query("
				UPDATE {$tableValues} AS v
				SET v.status = 0
				WHERE v.option_id IN (
					SELECT o.id FROM {$tableOptions} AS o
					WHERE o.product_id = {$productId} AND o.store_id = {$storeId} AND o.id NOT IN ({$idsStr})
				)
			");
		}

		return true;
	}

	public function deleteAllByProductId($productId, $storeId = null)
	{
		$productId = (int)$productId;
		$storeId   = is_null($storeId) ? (int)$this->store_id : (int)$storeId;

		$tableOptions = DB_PREFIX . "custom_product_options";
		$tableValues  = DB_PREFIX . "custom_product_option_values";

		// Lấy danh sách option_id thuộc sản phẩm này
		$optionRows = $this->query("
			SELECT opt.id 
			FROM {$tableOptions} AS opt
			WHERE opt.product_id = {$productId} 
			AND opt.store_id = {$storeId}
		");
		$optionIds = array_map(fn($row) => (int)$row['id'], $optionRows ?: []);
		// $optionIds = array_map(function ($row) { return (int)$row['id']; }, $optionRows ?: []);

		if (!empty($optionIds)) {
			$optionIdList = implode(',', $optionIds);

			// 1) Xoá tất cả value theo option_id
			$this->_db->query("
				DELETE val 
				FROM {$tableValues} AS val
				WHERE val.option_id IN ({$optionIdList})
			");

			// 2) Xoá options
			$this->_db->query("
				DELETE opt 
				FROM {$tableOptions} AS opt
				WHERE opt.id IN ({$optionIdList})
			");
		} else {
			// Không tìm thấy option nào → vẫn dọn rác values + options theo product_id/store_id
			$this->_db->query("
				DELETE val 
				FROM {$tableValues} AS val
				LEFT JOIN {$tableOptions} AS opt ON opt.id = val.option_id
				WHERE opt.product_id = {$productId} 
				AND opt.store_id = {$storeId}
			");

			$this->_db->query("
				DELETE opt 
				FROM {$tableOptions} AS opt
				WHERE opt.product_id = {$productId} 
				AND opt.store_id = {$storeId}
			");
		}

		return true;
	}
}
?>
