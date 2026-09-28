<?php
/*************************************************************************
Adding slug module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Checked by: Mai Minh (06/08/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('slug', 'add');

$templateFile = 'manageslug.tpl.html';
include_once(ROOT_PATH . 'classes/dao/slugs.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$slugs = new Slugs($storeId);
$fields = new Fields($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_slug'] => '/' . ADMIN_SCRIPT . '?op=manage&act=slug',
	$amessages['add_new_slug'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=slug';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => '',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Allow some javascript
$template->assign('ckEditor', 1);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='slug'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='slug'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Submitted form
if ($_POST && $request->element('doo') == 'submit') { # if form is submitte	
	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
	} else { # Valid data input
		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('slug'), false, '-');
		$i = 0;
		$dup = 1;

		# Add a number to the tail of slug if duplicate and loop until unique 
		while ($dup) {
			$dup = (int)$slugs->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', '');
			if ($dup) $i++;
		}
		$slug .= $i ? '-' . $i : '';

		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			$properties = array('');

			# Custom fields
			foreach ($fieldList as $field) {
				$properties[$field->getName()] = stripslashes($request->element($field->getName()));
			}

			# Add new slug to database
			$data = array(
				'store_id' => $storeId,
				'slug' => $slug,
				'module' => $request->element('module'),
				'object_id' => $request->element('object_id'),
				'status' => (int)$request->element('status'),
				'properties' => serialize($properties),
				'creator_id' => $userInfo->getId(),
				'date_created' => date("Y-m-d H:i:s")
			);
			$newId = $slugs->addData($data);

			# Custom options
			if ($newId) {
				foreach ($fieldOptionList as $field) {
					$valueType = stripslashes($request->element($field->getFieldName()));
					if ($field->getFieldType() == 4 || $field->getFieldType() == 7) {
						$selectedKeys = (array) $request->element($field->getFieldName());
						$options = $field->getValue(); 
						$selectedValues = array_map(function ($key) use ($options) {
							return isset($options[$key]) ? $options[$key] : $key;
						}, $selectedKeys);

						$valueType = implode(", ", $selectedValues);
					}
					if ($field->getFieldType() == 5 || $field->getFieldType() == 6) {
						$options = $field->getValue();
						$valueType = isset($options[$valueType]) ? $options[$valueType] : $valueType; 
					}
					$fieldData = array(
						'store_id' => $storeId,
						'field_id' => $field->getId(),
						'key_id' => $newId,
						'field_value' => html_entity_decode($valueType),
						'status' => 1,
					);
					$newFieldValue = $fieldValue->addData($fieldData);
				} # End foreach ($fieldOptionList as $field) {
			} # End if ($newId) {

			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_slug'], $request->element('slug')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=slug&mod=list&rcode=6");
		} # End if (!$validate['invalid'])
	} # End if ($validate['invalid']) {
} # End if ($_POST && $request->element('doo') == 'submit') { # if form is submitted

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['slug'] = $validate->validString($request->element('slug'), $amessages['slug']);
	$error['INPUT']['module'] = $validate->validString($request->element('module'), $amessages['frontend_module']);
	$error['INPUT']['object_id'] = $validate->pasteString($request->element('object_id'), $amessages['object_id']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	# Paste value of custom fields
	global $fieldList;
	if(is_array($fieldList)) {
		foreach ($fieldList as $field) {
			$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
			if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
				$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
			} # End if ($field->getType() == 4
		} # End foreach ($fieldList as $field) {
	} # End if(is_array($fieldList)) {

	# Custom Options
	global $fieldOptionList;
	if(is_array($fieldOptionList)) {
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
			} # End if ((is_null($fieldValue)
		} # End foreach ($fieldOptionList as $field) {
	} # End if(is_array($fieldOptionList)) {
	$error['invalid'] = 0;
	if ($error['INPUT']['slug']['error'] || $error['INPUT']['module']['error']) {
		$error['invalid'] = 1;
	}

	return $error;
}
