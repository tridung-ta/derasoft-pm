<?php
/*************************************************************************
Email Template Adding module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 21/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systememail.tpl.html';
include_once(ROOT_PATH.'classes/dao/emails.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$emails = new Emails($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_email_template'] => '/'.ADMIN_SCRIPT.'?op=system&act=email',
				$amessages['add_new'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=email';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Allow some javascript
$template->assign('ckEditor',1);
	
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate category name
		if($emails->checkDuplicate($request->element('name'))) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$data = array('store_id' => $storeId,
						  'name' => Filter($request->element('name')),
						  'title' => Filter($request->element('title')),
						  'content' => serialize($request->element('mailcontent')),
						  'tokens' => Filter($request->element('tokens')),
						  'can_del' => Filter($request->element('can_del')),
						  'status' => Filter($request->element('status')));
			$emails->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_email_template'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=email&mod=list&pId=".$request->element('parent_id')."&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['title'] = $validate->validString($request->element('title'),$amessages['title']);
	$error['INPUT']['mailcontent'] = $validate->validString($request->element('mailcontent'),$amessages['email_content']);	
	$error['INPUT']['tokens'] = $validate->pasteString($request->element('tokens'));
	$error['INPUT']['can_del'] = $validate->pasteString($request->element('can_del'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	if($error['INPUT']['name']['error'] || $error['INPUT']['title']['error'] || $error['INPUT']['mailcontent']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>