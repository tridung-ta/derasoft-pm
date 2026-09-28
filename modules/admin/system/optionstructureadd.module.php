<?php

$templateFile = 'systemoptionstructure.tpl.html';
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionobject.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
$optionStructure = new OptionStructure($storeId);
$optionObject = new OptionObject($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['system_custom_options'] => '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure',
	$amessages['add_new'] => ''
);
$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['custom_list_field_value'] => $tabLink . '&mod=listvalue',
	$amessages['custom_list_field_object'] => $tabLink . '&mod=listobject',
	$amessages['add_new_object'] => $tabLink . '&mod=listobjectadd',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	$amessages['clean_object'] => $tabLink . '&mod=listobjectcleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Allow some javascript
$template->assign('ckEditor', 1);

# Field module combobox
$objectCombo = $optionObject->generateCombo($request->element('module'));
if ($objectCombo) $template->assign('objectCombo', $objectCombo);

# Field types combobox
$typeCombo = optionFieldType();

if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
		# Object combo box
		$objectCombo = $optionObject->generateCombo($request->element('module'));
		if ($objectCombo) $template->assign('objectCombo', $objectCombo);
		$typeCombo = optionFieldType($request->element('field_type'));
	} else { # Valid data input
		# check duplicate category field_name
		if ($optionStructure->checkDuplicate('custom_' . $request->element('field_name'), 'field_name', "`module` = '" . $request->element('module') . "'")) {
			$validate['INPUT']['field_name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['field_name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error', $validate);
		}

		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			# Get value list
			$matches = array();
			preg_match_all('/^(.+?):(.+)$/m', $request->element('value'), $matches);
			$valueList = array_combine($matches[1], $matches[2]);

			$moduleName = Filter(strtolower($request->element('module')));
			$moduleId = $optionObject->getModuleIdByName($moduleName);

			$data = array(
				'store_id' => $storeId,
				'module' => $moduleName,
				'module_id' => $moduleId,
				'field_name' => 'co_' . Filter($request->element('field_name')),
				'field_title' => Filter($request->element('field_title')),
				'field_class' => Filter($request->element('field_class')),
				'field_type' => Filter($request->element('field_type')),
				'value' => serialize($valueList),
				'required' => Filter($request->element('required')),
				'appearance' => Filter($request->element('appearance')),
				'position' => is_numeric($request->element('position')) ? (int) $request->element('position') : 0,
				'status' => Filter($request->element('status'))
			);
			$optionStructure->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_custom_field'], $request->element('field_name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=optionstructure&mod=list&pId=" . $request->element('parent_id') . "&rcode=6");
		}
	}
}

$template->assign('typeCombo', $typeCombo);

# function validation
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['module'] = $validate->validString($request->element('module'), $amessages['object']);
	$error['INPUT']['field_name'] = $validate->validString($request->element('field_name'), $amessages['name']);
	$error['INPUT']['field_title'] = $validate->validString($request->element('field_title'), $amessages['title']);
	$error['INPUT']['field_class'] = $validate->pasteString($request->element('field'));
	$error['INPUT']['field_type'] = $validate->validNumber($request->element('field_type'), $amessages['custom_field_type']);
	$error['INPUT']['value'] = $validate->pasteString($request->element('value'));
	if ($request->element('field_type') > 3) $error['INPUT']['value'] = $validate->validString($request->element('value'), $amessages['custom_field_value']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if ($error['INPUT']['module']['error'] || $error['INPUT']['field_name']['error'] || $error['INPUT']['field_title']['error'] || $error['INPUT']['field_type']['error'] || $error['INPUT']['value']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
