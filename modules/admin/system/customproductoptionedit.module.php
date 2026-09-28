<?php

$templateFile = 'systemcustomproductoption.tpl.html';

include_once(ROOT_PATH . 'classes/dao/customproductoptions.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
$customProductOptions = new CustomProductOptions($storeId);

$topNav = array(
		$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
		$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
		'Quản lý product options' => '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption',
		$amessages['list_item'] => ''
	);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption';
$listTabs = array(
	'Danh sách options' => $tabLink . '&mod=list',
	'Cập nhật option' => '',
    // 'Danh sách values' => $tabLink . '&mod=listvalue',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	'Danh sách option mặc định' => $tabLink . '&mod=listdefault',
	'Thêm option mặc định' => $tabLink . '&mod=listdefaultadd',
	'Dọn rác option mặc định' => $tabLink . '&mod=listdefaultcleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
if ($id) $template->assign('id', $id);
$itemInfo = $customProductOptions->getObject($id);

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
				# Get value list
				$matches = array();
				preg_match_all('/^(.+?):(.+)$/m', $request->element('value'), $matches);

				$data = array(
                    'store_id' => $storeId,
					'name' => Filter($request->element('name')),
					'status' => Filter($request->element('status'))
				);
				$customProductOptions->updateDataOptionWithValues($data, $id);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_custom_field'], $customProductOptions->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=customproductoption&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load information to edit
		$template->assign('item', $itemInfo);
	}
}


# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	// $error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if ($error['INPUT']['name']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
