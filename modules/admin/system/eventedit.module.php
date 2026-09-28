<?php
/*************************************************************************
Editing event template module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 29/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemevent.tpl.html';
include_once(ROOT_PATH.'classes/dao/events.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$events = new Events($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_event'] => '/'.ADMIN_SCRIPT.'?op=system&act=event',
				$amessages['edit_event'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=event';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_event'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$itemInfo = $events->getObject($id);
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
			if($events->checkDuplicate($request->element('name'),'name',"`id` <> '$id'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('name' => Filter($request->element('name')),
							  'can_del' => Filter($request->element('can_del')),
							  'status' => Filter($request->element('status')));
				$events->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_event'],$events->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=event&mod=edit&lang=$lang&id=$id&rcode=7");
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
	$error['INPUT']['can_del'] = $validate->pasteString($request->element('can_del'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['name']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>