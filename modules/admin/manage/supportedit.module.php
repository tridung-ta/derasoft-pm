<?php
/*************************************************************************
Editing support module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Last updated: 01/05/2012
Coder: Mai Minh
Checked by: Mai Minh (02/05/2012)
**************************************************************************/
$userInfo->checkPermission('support','edit');
$templateFile = 'managesupport.tpl.html';
include_once(ROOT_PATH.'classes/dao/supports.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$supports = new Supports($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_support'] => '/'.ADMIN_SCRIPT.'?op=manage&act=support',
				$amessages['edit_support'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=support';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_support'] => '#',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode'); 
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$supportInfo = $supports->getObject($id);
if(!$supportInfo) {
	$template->assign('validItem',0);
} else{
	$template->assign('validItem',1);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			$supportInfo = $supports->getObject($id);
			$template->assign('itemInfo',$supportInfo);
		} else { # Valid data input	
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$supportInfo = $supports->getObject($id);
				if($supportInfo) {								
					$data = array('store_id' => $storeId,
								  'fullname' => Filter($request->element('fullname')),
								  'telephone' => Filter($request->element('telephone')),
								  'cellphone' => Filter($request->element('cellphone')),
								  'nick_yahoo' => Filter($request->element('nick_yahoo')),
								  'nick_skype' => Filter($request->element('nick_skype')),
								  'status' => Filter($request->element('status')),
								  'position' => Filter($request->element('position')));
					$supports->updateData($data,$id);
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_support'],$request->element('fullname')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=support&mod=edit&lang=$lang&id=$id&rcode=7");
				}
			}
		}
	} else { # Load support category information to edit
		$template->assign('item',$supportInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'),$amessages['fullname']);
	$error['INPUT']['telephone'] = $validate->pasteString($request->element('telephone'));
	$error['INPUT']['cellphone'] = $validate->pasteString($request->element('cellphone'));
	$error['INPUT']['nick_yahoo'] = $validate->pasteString($request->element('nick_yahoo'));
	$error['INPUT']['nick_skype'] = $validate->pasteString($request->element('nick_skype'));
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	if($error['INPUT']['fullname']['error'] || $error['INPUT']['status']['error'] || $error['INPUT']['position']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>