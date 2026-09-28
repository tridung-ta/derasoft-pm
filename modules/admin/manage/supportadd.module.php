<?php
/*************************************************************************
Adding support module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 01/05/2012
Coder: Mai Minh
**************************************************************************/
$userInfo->checkPermission('support','add');
$templateFile = 'managesupport.tpl.html';
include_once(ROOT_PATH.'classes/dao/supports.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$supports = new Supports($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_support'] => '/'.ADMIN_SCRIPT.'?op=manage&act=support',
				$amessages['add_support'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=support';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] =>'',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			# End File upload
			$data = array('store_id' => $storeId,
						  'fullname' => $request->element('fullname'),
						  'telephone' => $request->element('telephone'),
						  'cellphone' => $request->element('cellphone'),
						  'nick_yahoo' => $request->element('nick_yahoo'),
						  'nick_skype' => $request->element('nick_skype'),
						  'position' => $request->element('position'),
						  'status' => $request->element('status'));
			$newId = $supports->addData($data);
					
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_support'],$request->element('title')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=support&mod=list&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'),$amessages['name']);
	$error['INPUT']['telephone'] = $validate->pasteString($request->element('telephone'));
	$error['INPUT']['cellphone'] = $validate->pasteString($request->element('cellphone'));
	$error['INPUT']['nick_yahoo'] = $validate->pasteString($request->element('nick_yahoo'));
	$error['INPUT']['nick_skype'] = $validate->pasteString($request->element('nick_skype'));
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['fullname']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>