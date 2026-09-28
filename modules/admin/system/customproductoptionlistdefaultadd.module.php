<?php

$templateFile = 'systemcustomproductoption.tpl.html';
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/customproductoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptiondefault.class.php');
$customProductOptions = new CustomProductOptions($storeId);
$customProductOptionDefault = new CustomProductOptionDefault($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	'Quản lý product options' => '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption',
	'Thêm mới option mặc định' => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption';
$listTabs = array(
	'Danh sách options' => $tabLink . '&mod=list',
	// 'Danh sách values' => $tabLink . '&mod=listvalue',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	'Danh sách option mặc định' => $tabLink . '&mod=listdefault',
	'Thêm mới option mặc định' => $tabLink . '&mod=listdefaultadd',
	'Dọn rác option mặc định' => $tabLink . '&mod=listdefaultcleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 4);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Allow some javascript
$template->assign('ckEditor', 1);

# Field module combobox
// $objectCombo = $customProductOptionDefault->generateCombo($request->element('module'));
if ($objectCombo) $template->assign('objectCombo', $objectCombo);

# Field types combobox
$typeCombo = optionFieldType();

if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
		# Object combo box
		// $objectCombo = $customProductOptionDefault->generateCombo($request->element('module'));
		// if ($objectCombo) $template->assign('objectCombo', $objectCombo);
		// $typeCombo = optionFieldType($request->element('field_type'));
	} else { # Valid data input
		# check duplicate category name
		if ($customProductOptionDefault->checkDuplicate($request->element('name'), 'name')) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error', $validate);
		}

		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
            $rawInput = $request->element('value_default');
            $lines = explode("\n", trim($rawInput));
            $data = [];

            foreach ($lines as $line) {
                $line = trim($line, ", \t\r\n");
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $data[trim($key)] = (int) trim($value);
                }
            }

            $valueDefault = json_encode($data, JSON_UNESCAPED_UNICODE);

			$data = array(
				'store_id' => $storeId,
				'name' => Filter(strtolower($request->element('name'))),
				'value_default' => $valueDefault,
				'status' => Filter($request->element('status'))
			);
			$customProductOptionDefault->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_custom_field'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=customproductoption&mod=listdefault&pId=" . $request->element('parent_id') . "&rcode=6");
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
	$error['INPUT']['value_default'] = $validate->validString(trim($request->element('value_default')), 'Dữ liệu mẫu');
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if ($error['INPUT']['name']['error'] || $error['INPUT']['value_default']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
