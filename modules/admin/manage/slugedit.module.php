<?php
/*************************************************************************
Editing slug module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Checked by: Mai Minh (06/08/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('slug', 'edit');

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
	$amessages['edit_slug'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=slug';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['edit_slug'] => '#',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

$id = (int)$request->element('id');
if ($id) $template->assign('id', $id);
$slugInfo = $slugs->getObject($id);

if (!$slugInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	# Allow some javascript
	$template->assign('ckEditor', 1);

	# Get list of custom fields
	$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='slug'", array('position' => 'ASC'));
	if ($fieldList) $template->assign('fieldList', $fieldList);	
	
	# Get list of custom options
	$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='slug'", array('position' => 'ASC'));
	if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
	
	# Get all custom field values
	$allFieldValues = $fieldValue->getAllValuesByKeyId((int)$id);
	$template->assign('allFieldValues', $allFieldValues);
	
	# Get avatar object
	if($slugInfo->getProperty('avatarId')) {
		$avatarItem=$uploads->getObject($slugInfo->getProperty('avatarId'));
		if($avatarItem) $template->assign('avatarItem',$avatarItem);
	}
	
	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted	
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
			$slugInfo = $slugs->getObject($id);
			$template->assign('itemInfo', $slugInfo);
		} else { # Valid data input
			# Check if duplicate slug
			$textFilter = new TextFilter();
			$slug = $textFilter->urlize($request->element('slug'), false, '-');
			$i = 0;
			$dup = 1;
			while ($dup) {
				$dup = $slugs->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "`id` <> '$id' ");
				if ($dup) $i++;
			}
			$slug .= $i ? '-' . $i : '';
			
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
			$slugInfo = $slugs->getObject($id);
			if ($slugInfo) {
				$properties = $slugInfo->getProperties();

				# Custom fields
				foreach ($fieldList as $field) {
					$properties[$field->getName()] = stripslashes($request->element($field->getName()));
				}

				$data = array(
					'store_id' => $storeId,
					'slug' => $slug,
					'module' => $request->element('module'),
					'object_id' => $request->element('object_id'),
					'status' => (int)$request->element('status'),
					'properties' => serialize($properties),
					'updater_id' => (int)$userInfo->getId(),
					'date_updated' => date("Y-m-d H:i:s")
				);

				$result = $slugs->updateData($data, $id);

				# Custom Options
				if ($result) {
					foreach ($fieldOptionList as $field) {
						$fieldId = $field->getId();
						$valueType = $request->element($field->getFieldName());
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

						$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $id, $storeId);
					}
				}

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_slug'], $request->element('title')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=slug&mod=edit&lang=$lang&id=$id&rcode=7");
			}
			} # End if ($slugInfo) {
		} # End if ($validate['invalid']) {
	} else { # Load slug page information to edit
		$template->assign('item', $slugInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['slug'] = $validate->validString($request->element('slug'), $amessages['slug']);
	$error['INPUT']['module'] = $validate->validString($request->element('module'), $amessages['module']);
	$error['INPUT']['object_id'] = $validate->pasteString($request->element('object_id'), $amessages['keyword']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

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

	$error['invalid'] = 0;
	if ($error['INPUT']['slug']['error'] ||$error['INPUT']['module']['error'] || $error['INPUT']['object_id']['error']) {
		$error['invalid'] = 1;
	}

	return $error;
}
