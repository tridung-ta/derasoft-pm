<?php
/*************************************************************************
Editing language module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 28/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemlanguage.tpl.html';
include_once(ROOT_PATH.'classes/dao/languages.class.php');
include_once(ROOT_PATH.'classes/dao/currencies.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$languages = new Languages($storeId);
$currencies = new Currencies($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_language'] => '/'.ADMIN_SCRIPT.'?op=system&act=language',
				$amessages['edit_language'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=language';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_language'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$itemInfo = $languages->getObject($id);
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
			
			# Currencies combo box
			$currencyCombo = $currencies->generateCombo($request->element('currency'));
			if($currencyCombo) $template->assign('currencyCombo',$currencyCombo);
		} else { # Valid data input		
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('name' => Filter($request->element('name')),
							  'currency' => Filter($request->element('currency')),
							  'position' => Filter($request->element('position')),
							  'status' => Filter($request->element('status')));
				$languages->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_language'],$languages->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=language&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load product category information to edit
		$template->assign('item',$itemInfo);
		
		# Currencies combo box
		$currencyCombo = $currencies->generateCombo($itemInfo->getCurrency());
		if($currencyCombo) $template->assign('currencyCombo',$currencyCombo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['currency'] = $validate->validNumber($request->element('currency'),$amessages['language_currency']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['name']['error'] || $error['INPUT']['currency']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>