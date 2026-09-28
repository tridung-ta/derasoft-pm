<?php
/*************************************************************************
Editing addon module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 28/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemaddon.tpl.html';
include_once(ROOT_PATH.'classes/dao/addons.class.php');
include_once(ROOT_PATH.'classes/dao/events.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$addons = new Addons($storeId);
$events = new Events($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_addon'] => '/'.ADMIN_SCRIPT.'?op=system&act=addon',
				$amessages['edit_addon'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=addon';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_addon'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$itemInfo = $addons->getObject($id);
if(!$itemInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			$template->assign('itemInfo',$itemInfo);
			
			# Events combo box
			$eventCombo = $events->generateCombo($request->element('event'));
			if($eventCombo) $template->assign('eventCombo',$eventCombo);
		} else { # Valid data input		
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('event' => Filter($request->element('event')),
							  'description' => Filter($request->element('description')),
							  'position' => Filter($request->element('position')),
							  'status' => Filter($request->element('status')));
				$addons->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_addon'],$addons->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=addon&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load information to edit
		$template->assign('item',$itemInfo);
		
		# Events combo box
		$eventCombo = $events->generateCombo($itemInfo->getEvent());
		if($eventCombo) $template->assign('eventCombo',$eventCombo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['event'] = $validate->validNumber($request->element('event'),$amessages['event']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'),$amessages['addon_description']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['event']['error'] || $error['INPUT']['description']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>