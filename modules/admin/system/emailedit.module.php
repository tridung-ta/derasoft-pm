<?php
/*************************************************************************
Editing email template module
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
				$amessages['edit_email_template'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=email';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_email_template'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$itemInfo = $emails->getObject($id);
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
			if($emails->checkDuplicate($request->element('name'),'name',"`id` <> '$id'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('title' => Filter($request->element('title')),
							  'content' => serialize($request->element('mailcontent')),
							  'tokens' => Filter($request->element('tokens')),
							  'can_del' => Filter($request->element('can_del')),
							  'status' => Filter($request->element('status')));
				$emails->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_email_template'],$emails->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=email&mod=edit&lang=$lang&id=$id&rcode=7");
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
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['title'] = $validate->validString($request->element('title'),$amessages['title']);
	$error['INPUT']['mailcontent'] = $validate->validString($request->element('mailcontent'),$amessages['email_content']);
	$error['INPUT']['tokens'] = $validate->pasteString($request->element('tokens'));
	$error['INPUT']['can_del'] = $validate->pasteString($request->element('can_del'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['title']['error'] || $error['INPUT']['mailcontent']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>