<?php
/*************************************************************************
Adding comment module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 01/05/2012
Coder: Mai Minh
**************************************************************************/
$userInfo->checkPermission('comment','add');
$templateFile = 'managecomment.tpl.html';
include_once(ROOT_PATH.'classes/dao/comments.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$comments = new Comments($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_comment'] => '/'.ADMIN_SCRIPT.'?op=manage&act=comment',
				$amessages['add_comment'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=comment';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] =>'',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$pid = $request->element('pid');
if($pid) $template->assign('pid',$pid);

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
						  'details' => $request->element('detail'),
						  'created' => date("Y-m-d H:i:s"),
						  'status' => $request->element('status'),
						  'pid' => $request->element('pid'));
			$newId = $comments->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_comment'],$newId),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=comment&mod=list&rcode=6");
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
	$error['INPUT']['detail'] = $validate->validString($request->element('detail'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['fullname']['error'] || $error['INPUT']['detail']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>