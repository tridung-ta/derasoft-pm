<?php
/*************************************************************************
Editing product options module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Linh Tien
Last updated: 14/08/2025
Checked by: 
**************************************************************************/

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
# Check permission
$userInfo->checkPermission('product', 'edit');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/productsize.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');
$customProductOptions = new CustomProductOptions($storeId);
$customProductOptionValues = new CustomProductOptionValues($storeId);
$products = new Products($storeId);
$search = new Search($storeId);
$productsize = new ProductSize();
$productOptions = new ProductOptions(1);
$gallery_root = ROOT_PATH . "upload/$storeId/";
$gallery_path = $gallery_root . "products/";
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	'Chỉnh sửa lựa chọn' => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
	$amessages['list_item_lop'] => $tabLink . '&mod=list',
	'Chỉnh sửa lựa chọn' => '#',
	$amessages['list_item_pk'] => $tabLink . '&mod=listaccessory',
	$amessages['add_pk'] => $tabLink . '&mod=addaccessory',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_tramk'] => $tabLink . '&mod=listoption',
	$amessages['add_tramk'] => $tabLink . '&mod=addoption',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$pc = $request->element('pc');
$id = $request->element('id');
if ($id) $template->assign('id', $id);

$productInfo = $products->getObject($id);
$productName = $productInfo->getName();
if($productName) $template->assign('productName', $productName);
# Get Custom Product Options
$listRawOptions = $customProductOptions->getOptionsWithValuesByProductId($id);
$listProductOptions = [];

foreach ($listRawOptions as $option) {
	$values = [];
	if (!empty($option['values'])) {
		foreach ($option['values'] as $val) {
			$values[] = [
				'value' => $val['value'],
				'price_modifier' => $val['price_modifier'],
			];
		}
	}
	$listProductOptions[] = [
		'id' => $option['id'],
		'name' => $option['name'],
		'values' => $values,
	];
}
$template->assign('listProductOptions', $listProductOptions);

if (!$productInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	# Allow some javascript
	$template->assign('ckEditor', 1);

	if ($_POST && $request->element('doo') == 'submit') {
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	
            # data input is not in valid form
			$template->assign('error', $validate);
			$productInfo = $products->getObject($id);
			$template->assign('itemInfo', $productInfo);

		} else { # Valid data input
			$textFilter = new TextFilter();
		
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				$idUpdate = $request->element('id');
				$productInfo = $products->getObject($idUpdate);
				if ($productInfo) {
					$properties = $productInfo->getProperties();

					#User update
					$properties['user_upload'] = $userInfo->getId();
					
					# Custom Product Option
					if ($productInfo) {
						$optionNames = array_values($_POST['option_names'] ?? []);
						$valueNames = array_values($_POST['value_names'] ?? []);
						$valueModifiers = array_values($_POST['value_modifiers'] ?? []);
						$productName = Filter($request->element('name'));
						$dataOptionUpdate = [];

						foreach ($optionNames as $index => $optionName) {
							if (empty(trim($optionName))) continue;
							$values = $valueNames[$index] ?? [];
							$modifiers = $valueModifiers[$index] ?? [];

							$valueList = [];
							foreach ($values as $i => $value) {
								$modifier = isset($modifiers[$i]) ? floatval($modifiers[$i]) : 0.0;

								$valueList[] = [
									'value' => $value,
									'price_modifier' => $modifier,
								];
							}

							if (empty($valueList)) { continue; }

							$dataOptionUpdate[] = [
								'name' => $optionName,
								'values' => $valueList,
							];
						}
						$customProductOptions->updateOptionsByProductId($idUpdate, $productName, $dataOptionUpdate, $storeId);
					}

					# Operation tracking
					// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_product'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
					
					# Redirect to editing page
					header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=picklist&lang=$lang&id=$idUpdate&rcode=7");
				}
			}
		}
	} else { # Load product information to edit
		$template->assign('item', $productInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['invalid'] = 0;

	// $error['INPUT']['category_id'] = $validate->pasteString($request->element('category_id'));
	// $error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	// $error['INPUT']['keyword'] = $validate->validString($request->element('keyword'), $amessages['keyword']);
	// $error['INPUT']['description'] = $validate->pasteString($request->element('detail'), $amessages['description']);
	// $error['INPUT']['detail'] = $validate->pasteString($request->element('detail'), $amessages['detail']);
	// $error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	// $error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	// $error['INPUT']['viewed'] = $validate->pasteString($request->element('viewed'));
	// $error['INPUT']['market_price'] = $validate->pasteString($request->element('market_price'));

	// global $fieldOptionList;
	// foreach ($fieldOptionList as $field) {
	// 	$fieldName = $field->getFieldName();
	// 	$fieldValue = $request->element($fieldName);

	// 	if ((is_null($fieldValue) || $fieldValue === '') && $field->getRequired() == 1) {
	// 		$error['INPUT'][$fieldName] = [
	// 			'value' => $fieldValue,
	// 			'error' => 1,
	// 			'message' => $amessages["field"] . " - " . $amessages['invalid_field']
	// 		];
	// 		$error['invalid'] = 1;
	// 	} else {
	// 		$error['INPUT'][$fieldName] = [
	// 			'value' => $fieldValue,
	// 			'error' => 0,
	// 			'message' => ''
	// 		];
	// 	}
	// }

	// if ($error['INPUT']['name']['error']) {
	// 	$error['invalid'] = 1;
	// 	$error['message'] = '';
	// }

	return $error;
}
