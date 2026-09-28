<?php
/*************************************************************************
Editing currency module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 28/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemcurrency.tpl.html';
include_once(ROOT_PATH.'classes/dao/currencies.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$currencies = new Currencies($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_currency'] => '/'.ADMIN_SCRIPT.'?op=system&act=currency',
				$amessages['edit_currency'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=currency';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_currency'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$itemInfo = $currencies->getObject($id);
if(!$itemInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);

	# Allow some javascript
	$template->assign('ckEditor',1);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			$template->assign('itemInfo',$itemInfo);
		} else { # Valid data input		
			# check duplicate category name
			if($currencies->checkDuplicate($request->element('name'),'name',"`id` <> '$id'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('name' => Filter($request->element('name')),
							  'display' => Filter($request->element('display')),
							  'rate' => Filter(str_replace(",","",$request->element('rate'))),
							  'decimal' => Filter($request->element('decimal')),
							  'position' => Filter($request->element('position')),
							  'status' => Filter($request->element('status')));
				if($currencies->checkPrimaryFromId($id)) $data['rate'] = 1;
				$currencies->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_currency'],$currencies->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=currency&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load product category information to edit
		$template->assign('item',$itemInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->validString($request->element('name'));
	$error['INPUT']['display'] = $validate->validString($request->element('display'),$amessages['currency_display']);
	$error['INPUT']['rate'] = $validate->validPrice($request->element('rate'),$amessages['currency_rate']);
	$error['INPUT']['decimal'] = $validate->validNumber($request->element('decimal'),$amessages['decimal']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['name']['error'] || $error['INPUT']['display']['error'] || $error['INPUT']['rate']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>