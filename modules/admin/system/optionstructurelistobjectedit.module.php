<?php
$templateFile = 'systemoptionstructure.tpl.html';
include_once(ROOT_PATH . 'classes/dao/optionobject.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");

$optionObject = new OptionObject($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['method_delivery'] => '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure',
	$amessages['system_custom_options'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['custom_list_field_value'] => $tabLink . '&mod=listvalue',
	$amessages['custom_list_field_object'] => $tabLink . '&mod=listobject',
	$amessages['edit_object'] => '',
	$amessages['add_new_object'] => $tabLink . '&mod=listobjectadd',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	$amessages['clean_object'] => $tabLink . '&mod=listobjectcleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 5);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
if ($id) $template->assign('id', $id);
$itemInfo = $optionObject->getObject($id);

if (!$itemInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	# Allow some javascript
	$template->assign('ckEditor', 1);

	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);

			$template->assign('itemInfo', $itemInfo);
		} else { # Valid data input		
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {

				$data = array(
					'name' => Filter($request->element('name')),
					'status' => Filter($request->element('status'))
				);
				$optionObject->updateData($data, $id);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_custom_field'], $optionObject->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=optionstructurelistobject&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else {
		$template->assign('item', $itemInfo);
	}
}

$template->assign('typeCombo', $typeCombo);

function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->pasteString($request->element('name'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if ($error['INPUT']['name']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
