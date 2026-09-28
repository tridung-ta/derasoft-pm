<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$templateFile = 'systemoptionstructure.tpl.html';
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
$optionStructure = new OptionStructure($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['method_delivery'] => '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure',
	$amessages['system_custom_options'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['edit_custom_options'] => '',
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
$id = $request->element('id');
if ($id) $template->assign('id', $id);
$itemInfo = $optionStructure->getObject($id);

if (!$itemInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	# Allow some javascript
	$template->assign('ckEditor', 1);

	# Field types combobox
	$typeCombo = optionFieldType();

	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
			$typeCombo = optionFieldType($request->element('field_type'));

			$template->assign('itemInfo', $itemInfo);
		} else { # Valid data input		
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				# Get value list
				$matches = array();
				preg_match_all('/^(.+?):(.+)$/m', $request->element('value'), $matches);
				$valueList = array_combine($matches[1], $matches[2]);

				$data = array(
					'field_title' => Filter($request->element('field_title')),
					'field_class' => Filter($request->element('field_class')),
					'field_type' => Filter($request->element('field_type')),
					'value' => serialize($valueList),
					'required' => Filter($request->element('required')),
					'appearance' => Filter($request->element('appearance')),
					'position' => Filter($request->element('position')),
					'status' => Filter($request->element('status'))
				);
				$optionStructure->updateData($data, $id);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_custom_field'], $optionStructure->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=optionstructure&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load information to edit
		$template->assign('item', $itemInfo);

		# Field types combobox
		$typeCombo = optionFieldType($itemInfo->getFieldType());
	}
}

$template->assign('typeCombo', $typeCombo);

function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['field_title'] = $validate->validString($request->element('field_title'), $amessages['title']);
	$error['INPUT']['field_class'] = $validate->pasteString($request->element('field'));
	$error['INPUT']['field_type'] = $validate->validNumber($request->element('field_type'), $amessages['custom_field_type']);
	if ($request->element('field_type') > 3) $error['INPUT']['value'] = $validate->validString($request->element('value'), $amessages['custom_field_value']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if ($error['INPUT']['field_title']['error'] || $error['INPUT']['field_type']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
