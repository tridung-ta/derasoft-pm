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
$template->assign('currentTab', 5);

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
		# check duplicate category name
		if ($optionObject->checkDuplicate($request->element('name'), 'name')) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error', $validate);
		}

		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			$data = array(
				'store_id' => $storeId,
				'name' => Filter(strtolower($request->element('name'))),
				'status' => Filter($request->element('status'))
			);
			$optionObject->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_custom_field'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=optionstructure&mod=listobject&pId=" . $request->element('parent_id') . "&rcode=6");
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
	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if ($error['INPUT']['name']['error'] || $error['INPUT']['status']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
