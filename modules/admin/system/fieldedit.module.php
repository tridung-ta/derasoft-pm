<?php
/*************************************************************************
Editing Custom Field module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 19/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemfield.tpl.html';
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$fields = new Fields($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['method_delivery'] => '/'.ADMIN_SCRIPT.'?op=system&act=field',
				$amessages['edit_custom_field'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=field';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_custom_field'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$itemInfo = $fields->getObject($id);
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
			$typeCombo = optionFieldType($request->element('type'));
			$template->assign('itemInfo',$itemInfo);
		} else { # Valid data input		
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				# Get value list
				$matches = array();
				preg_match_all('/^(.+?):(.+)$/m', $request->element('value'), $matches);
				$valueList = array_combine($matches[1], $matches[2]);
			
				$data = array('title' => Filter($request->element('title')),
							  'class' => Filter($request->element('class')),
							  'type' => (int)$request->element('type'),
							  'value' => serialize($valueList),
							  'position' => (int)$request->element('position'),
							  'status' => (int)$request->element('status'));
				$fields->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_custom_field'],$fields->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=field&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load information to edit
		$template->assign('item',$itemInfo);
		
		# Field types combobox
		$typeCombo = optionFieldType($itemInfo->getType());
	}
	$template->assign('typeCombo',$typeCombo);
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['title'] = $validate->validString($request->element('title'),$amessages['title']);
	$error['INPUT']['class'] = $validate->pasteString($request->element('class'));
	$error['INPUT']['type'] = $validate->validNumber($request->element('type'),$amessages['custom_field_type']);
	$error['INPUT']['value'] = $validate->pasteString($request->element('value'));
	if($request->element('type')>3) $error['INPUT']['value'] = $validate->validString($request->element('value'),$amessages['custom_field_value']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['title']['error'] || $error['INPUT']['type']['error'] || $error['INPUT']['value']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>