<?php
/*************************************************************************
Ward add module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 06/06/2025
**************************************************************************/
$templateFile = 'systemmasterward.tpl.html';
include_once(ROOT_PATH."classes/data/textfilter.class.php");
include_once(ROOT_PATH.'classes/dao/wards.class.php');
include_once(ROOT_PATH.'classes/dao/areas.class.php');
$wards = new Wards($storeId);
$areas = new Areas($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_master_data'] => '/'.ADMIN_SCRIPT.'?op=system&act=master',
				$amessages['manage_master_data_country'] => '/'.ADMIN_SCRIPT.'?op=system&act=master&mod=wardlist',
				$amessages['add_country'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=master';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=wardlist',
				$amessages['add_new'] => $tabLink.'&mod=wardadd',
				$amessages['clean_trash'] => $tabLink.'&mod=wardcleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Countries combo box
$areaCombo = $areas->generateCombo($request->element('area_id'));
if($areaCombo) $template->assign('areaCombo',$areaCombo);

# Allow some javascript
$template->assign('ckEditor',0);
	
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate country name
		// if($wards->checkDuplicate($request->element('name'))) {
		// 	$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
		// 	$validate['INPUT']['name']['error'] = 1;
		// 	$validate['invalid'] = 1;
		// 	$template->assign('error',$validate);
		// }
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$data = array(
						  'store_id' => $storeId,
						  'name' => $request->element('name'),
						  'area_id' => (int)$request->element('area_id'),
						  'date_created' => date("Y-m-d H:i:s"),
						  'position' => (int)$request->element('position'),
						  'status' => (int)$request->element('status'));
			$newId = $wards->addData($data);
			
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_country'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=master&mod=wardlist&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['area_id'] = $validate->pasteString($request->element('area_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['name']['error'] || $error['INPUT']['area_id']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>