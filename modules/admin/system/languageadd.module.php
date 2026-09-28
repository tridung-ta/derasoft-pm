<?php
/*************************************************************************
Language Adding module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 29/05/2012
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
				$amessages['add_new'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=language';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Currencies combo box
$currencyCombo = $currencies->generateCombo($request->element('currency'));
if($currencyCombo) $template->assign('currencyCombo',$currencyCombo);

if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate prefix
		if($languages->checkDuplicate($request->element('prefix'),'prefix')) {
			$validate['INPUT']['prefix']['message'] = $amessages['language_prefix_duplicated'];
			$validate['INPUT']['prefix']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		
		# Check file language
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$data = array('store_id' => $storeId,
						  'prefix' => Filter(strtolower($request->element('prefix'))),
						  'name' => Filter($request->element('name')),					  
						  'currency' => Filter($request->element('currency')),
						  'position' => Filter($request->element('position')),
						  'status' => Filter($request->element('status')));
			$languages->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_language'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=language&mod=list&pId=".$request->element('parent_id')."&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['prefix'] = $validate->validString($request->element('prefix'),$amessages['language_prefix']);
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['currency'] = $validate->validNumber($request->element('currency'));	
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	if($error['INPUT']['prefix']['error'] || $error['INPUT']['name']['error'] || $error['INPUT']['currency']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>