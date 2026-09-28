<?php
/*************************************************************************
Adding custom field module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 07/05/2012
**************************************************************************/
$templateFile = 'systemfield.tpl.html';
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$fields = new Fields($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_custom_field'] => '/'.ADMIN_SCRIPT.'?op=system&act=field',
				$amessages['add_new'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=field';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Allow some javascript
$template->assign('ckEditor',1);

# Field types combobox
$typeCombo = optionFieldType();

if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
		$typeCombo = optionFieldType($request->element('type'));
	} else { # Valid data input
		# check duplicate category name
		if($fields->checkDuplicate('custom_'.$request->element('name'),'name',"`module` = '".$request->element('module')."'")) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			# Get value list
			$matches = array();
			preg_match_all('/^(.+?):(.+)$/m', $request->element('value'), $matches);
			$valueList = array_combine($matches[1], $matches[2]);
	
			$data = array('store_id' => $storeId,
						  'module' => Filter(strtolower($request->element('module'))),
						  'name' => 'custom_'.Filter($request->element('name')),
						  'title' => Filter($request->element('title')),
						  'class' => Filter($request->element('class')),
						  'type' => (int)$request->element('type'),
						  'value' => serialize($valueList),
						  'position' => (int)$request->element('position'),
						  'status' => (int)$request->element('status'));
			$fields->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_custom_field'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=field&mod=list&pId=".$request->element('parent_id')."&rcode=6");
		}
	}
}

$template->assign('typeCombo',$typeCombo);

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['module'] = $validate->validString($request->element('module'),$amessages['object']);	
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['title'] = $validate->validString($request->element('title'),$amessages['title']);
	$error['INPUT']['class'] = $validate->pasteString($request->element('class'));
	$error['INPUT']['type'] = $validate->validNumber($request->element('type'),$amessages['custom_field_type']);
	$error['INPUT']['value'] = $validate->pasteString($request->element('value'));
	if($request->element('type')>3) $error['INPUT']['value'] = $validate->validString($request->element('value'),$amessages['custom_field_value']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	if($error['INPUT']['module']['error'] || $error['INPUT']['name']['error'] || $error['INPUT']['title']['error'] || $error['INPUT']['type']['error'] || $error['INPUT']['value']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>