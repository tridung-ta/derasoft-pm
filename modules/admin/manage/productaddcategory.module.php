<?php
/*************************************************************************
Adding product category module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Checked by: Mai Minh (116/06/2025)
**************************************************************************/
# Check permission

// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
//         ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);
//     }
$userInfo->checkPermission('pro_cat', 'add');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
$fields = new Fields($storeId);
$productCategories = new ProductCategories($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['add_product_category'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	// $amessages['list_product_features'] => $tabLink . '&mod=listfeature',
	// $amessages['add_product_features'] => $tabLink . '&mod=addfeature',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 4);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get product categories array for generating nested combo
// $arrayCategories = $productCategories->getObjectsForCombo();

$productCategoriesCombo = $productCategories->generateNestedCombo(
    [],                                
    $request->element('list_parent_id'), 
    0,                                                     
);
$template->assign('productCategoriesCombo', $productCategoriesCombo);

# Product categories Combo
# New generate combo from array that needs only one query
# It reduces number of queries, especially when we need to generate many combos
// $productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$request->element('parent_id'));
// if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productcategories'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productlistcategory'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Allow some javascript
$template->assign('ckEditor', 1);

if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Get list of custom options
	$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productlistcategory'", array('position' => 'ASC'));
	if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
	
	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
	} else { # Valid data input
		# check duplicate category name
		if ($productCategories->checkDuplicate($request->element('name'))) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error', $validate);
		}

		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('name'), false, '-');
		$i = 0;
		$dup = 1;
		while ($dup) {
			$dup = $productCategories->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug');
			if ($dup) $i++;
		}
		$slug .= $i ? '-' . $i : '';
		# Custom fields
		foreach ($fieldList as $field) {
			$properties[$field->getName()] = $request->element($field->getName());
		}
		$properties['landing_page'] = $request->element('landing_page');

		# handle parent_id
		$raw = $request->element('parent_id');              // có thể là null hoặc ''
		$parent_id = ($raw === null || $raw === '') ? 1 : (int)$raw;

		$listIds = isset($_POST['list_parent_id']) ? $_POST['list_parent_id'] : [];
		if (!is_array($listIds)) $listIds = [$listIds]; // phòng khi wrapper trả về string
		$listIds = array_unique(array_map('intval', $listIds));
		$list_parent_id = implode(',', $listIds);

		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			$properties = array('');
			$data = array(
				'store_id' => $storeId,
				'parent_id' => $parent_id,
				'list_parent_id' => $list_parent_id,
				'slug' => $slug,
				'name' => $request->element('name'),
				'keyword' => $request->element('keyword'),
				'description' => $request->element('description'),
				'position' => (int)$request->element('position'),
				'status' => (int)$request->element('status'),
				'properties' => serialize($properties),
				'date_created' => date("Y-m-d H:i:s")
			);
			// var_dump($data);die;
			$newId = $productCategories->addData($data);

			// custom options
			if ($newId) {
				foreach ($fieldOptionList as $field) {
					$valueType = stripslashes($request->element($field->getFieldName()));
					if ($field->getFieldType() == 4 || $field->getFieldType() == 7) {
						$selectedKeys = (array) $request->element($field->getFieldName());
						$options = $field->getValue(); 
						$selectedValues = array_map(function ($key) use ($options) {
							return (is_array($options) && isset($options[$key])) ? $options[$key] : $key;
						}, $selectedKeys);

						$valueType = implode(", ", $selectedValues);
					}
					if ($field->getFieldType() == 5 || $field->getFieldType() == 6) {
						$options = $field->getValue();
						$valueType = (is_array($options) && isset($options[$valueType])) ? $options[$valueType] : $valueType;
					}
					$fieldData = array(
						'store_id' => $storeId,
						'field_id' => (int)$field->getId(),
						'key_id' => (int)$newId,
						'field_value' => $valueType,
						'status' => 1,
					);
					$newFieldValue = $fieldValue->addData($fieldData);
				}
			}
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_product_category'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listcategory&pId=" . $request->element('parent_id') . "&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['parent_id'] = $validate->pasteString($request->element('parent_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'), $amessages['keyword']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'), $amessages['description']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	if ($error['INPUT']['name']['error'] || $error['INPUT']['keyword']['error'] || $error['INPUT']['description']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;

	global $fieldOptionList;
	foreach ($fieldOptionList as $field) {

		$fieldName = $field->getFieldName();
		$fieldValue = $request->element($fieldName);

		if ((is_null($fieldValue) || $fieldValue === '') && $field->getRequired() == 1) {
			$error['INPUT'][$fieldName] = [
				'value' => $fieldValue,
				'error' => 1,
				'message' => $amessages["field"] . " - " . $amessages['invalid_field']
			];
			$error['invalid'] = 1;
		} else {
			$error['INPUT'][$fieldName] = [
				'value' => $fieldValue,
				'error' => 0,
				'message' => ''
			];
		}
	}

	return $error;
}
