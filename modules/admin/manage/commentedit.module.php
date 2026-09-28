<?php
/*************************************************************************
Editing article module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Last updated: 01/05/2012
**************************************************************************/
$userInfo->checkPermission('comment','edit');
$templateFile = 'managecomment.tpl.html';
include_once(ROOT_PATH.'classes/dao/comments.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$comments = new Comments($storeId);

$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_comment'] => '/'.ADMIN_SCRIPT.'?op=manage&act=comment',
				$amessages['edit_comment'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=comment';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_comment'] => '#',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode'); 
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$commentInfo = $comments->getObject($id);
if(!$commentInfo) {
	$template->assign('validItem',0);
} else{
	$template->assign('validItem',1);
	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			$commentInfo = $comments->getObject($id);
			$template->assign('itemInfo',$commentInfo);
		} else { # Valid data input
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$commentInfo = $comments->getObject($id);
				if($commentInfo) {			
					$data = array('store_id' => $storeId,
								  'fullname' => Filter($request->element('fullname')),
								  'details' => $request->element('detail'));
					$comments->updateData($data,$id);
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_comment'],$request->element('title')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=comment&mod=edit&lang=$lang&id=$id&rcode=7");
				}
			}
		}
	} else { # Load article category information to edit
		$template->assign('item',$commentInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'),$amessages['name']);
	$error['INPUT']['detail'] = $validate->validString($request->element('detail'),$amessages['detail']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	if( $error['INPUT']['fullname']['error'] || $error['INPUT']['detail']['error'] || $error['INPUT']['status']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>