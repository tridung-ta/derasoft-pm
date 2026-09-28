<?php
$templateFile = 'systemcustomproductoption.tpl.html';
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/customproductoptiondefault.class.php');
$customProductOptionDefault = new CustomProductOptionDefault($storeId);

$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	'Quản lý product options' => '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption',
	$amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption';
$listTabs = array(
	'Danh sách options' => $tabLink . '&mod=list',
	// 'Danh sách values' => $tabLink . '&mod=listvalue',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	'Danh sách option mặc định' => $tabLink . '&mod=listdefault',
	'Cập nhật option mặc định' => $tabLink . '#',
	'Thêm option mặc định' => $tabLink . '&mod=listdefaultadd',
	'Dọn rác option mặc định' => $tabLink . '&mod=listdefaultcleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 4);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
if ($id) $template->assign('id', $id);
$itemInfo = $customProductOptionDefault->getObject($id);

# get value default to edit
$valueDefault = $customProductOptionDefault->getValueDefaultFromId($id);
$formattedDefault = '';
foreach ($valueDefault as $color => $price) {
    $formattedDefault .= "{$color}: {$price}\n";
}
$template->assign('valueDefaultText', $formattedDefault);

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

                $nameDefault = Filter($request->element('name'));
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
					'name' => $nameDefault,
					'value_default' => $valueDefault,
					'status' => Filter($request->element('status'))
				);
				$customProductOptionDefault->updateData($data, $id);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_custom_field'], $customProductOptionDefault->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=customproductoptionlistdefault&mod=edit&lang=$lang&id=$id&rcode=7");
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
