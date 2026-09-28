<?php
/*************************************************************************
Staff permission module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 22/05/2012
Edit log:
- 29/09/2011 - Mai Minh: Check ID, add filter to form's fields
- 22/05/2012 - Mai Minh: Modify the tabs
**************************************************************************/
checkPermission(array(2,3));
$templateFile = 'systemstaff.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_staff'] => '/'.ADMIN_SCRIPT.'?op=system&act=staff',
				$amessages['add_new'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=staff';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_item'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
if($userInfo->isSiteFounder()) $listTabs[$amessages['tracking_title']] = $tabLink.'&mod=listTracking';
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
		# check duplicate username
		if($users->checkDuplicate($request->element('sCode').$request->element('username'))) {
			$validate['INPUT']['username']['message'] = $amessages['username_duplicated'];
			$validate['INPUT']['username']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		# check duplicate email
		if($users->checkDuplicate($request->element('email'),'email')) {
			$validate['INPUT']['email']['message'] = $amessages['email_duplicated'];
			$validate['INPUT']['email']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$uType = $request->element('user_group');
			if($uType == U_SITE_FOUNDER && !$userInfo->isSiteFounder()) $uType = U_SITE_STAFF;	# chi co founder moi co quyen them user dang Founder
			$data = array('store_id' => Filter($storeId),
						  'username' => Filter($request->element('username')),
						  'password' => md5($request->element('password')),
						  'email' => Filter($request->element('email')),
						  'fullname' => Filter($request->element('fullname')),
						  'address' => Filter($request->element('address')),
						  'tel' => Filter($request->element('telephone')),
						  'type' => Filter($uType),
						  'status' => S_ENABLED,
						  'date_created' => date("Y-m-d H:i:s"));
						 
			$users->addData($data);
			# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_user'],$request->element('username')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=staff&mod=add&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['username'] = $validate->validUsername($request->element('username'));
	$error['INPUT']['password'] = $validate->validPassword($request->element('password'));
	$error['INPUT']['confirm_password'] = $validate->validPassword($request->element('confirm_password'),$amessages['confirm_password']);
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'),$amessages['fullname']);
	$error['INPUT']['email'] = $validate->validEmail($request->element('email'));
	$error['INPUT']['address'] = $validate->pasteString($request->element('address'));
	$error['INPUT']['telephone'] = $validate->pasteString($request->element('telephone'));
	$error['INPUT']['user_group'] = $validate->pasteString($request->element('user_group'));
	
	if($error['INPUT']['username']['error'] || $error['INPUT']['password']['error'] || $error['INPUT']['confirm_password']['error'] || $error['INPUT']['fullname']['error'] || $error['INPUT']['email']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>