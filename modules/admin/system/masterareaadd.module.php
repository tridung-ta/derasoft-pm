<?php
/*************************************************************************
Area add module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 03/06/2025
**************************************************************************/
$templateFile = 'systemmasterarea.tpl.html';
include_once(ROOT_PATH.'classes/dao/areas.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
include_once(ROOT_PATH.'classes/dao/countries.class.php');
$areas = new Areas($storeId);
$countries = new Countries($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_master_data'] => '/'.ADMIN_SCRIPT.'?op=system&act=master',
				$amessages['manage_master_data_area'] => '/'.ADMIN_SCRIPT.'?op=system&act=master&mod=arealist',
				$amessages['add_area'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=master';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=arealist',
				$amessages['add_new'] => $tabLink.'&mod=areaadd',
				$amessages['clean_trash'] => $tabLink.'&mod=areacleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Countries combo box
$countriesCombo = $countries->generateCombo($request->element('country_id'));
if($countriesCombo) $template->assign('countriesCombo',$countriesCombo);

# Allow some javascript
$template->assign('ckEditor',0);
	
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	echo $request->element('country_id');
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate area name
		if($areas->checkDuplicate($request->element('name'))) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$data = array(
						  'store_id' => $storeId,
						  'name' => $request->element('name'),
						  'country_id' => (int)$request->element('country_id'),
						  'date_created' => date("Y-m-d H:i:s"),
						  'position' => (int)$request->element('position'),
						  'status' => (int)$request->element('status'));
			$areas->addData($data);
			
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_area'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=master&mod=arealist&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['country_id'] = $validate->pasteString($request->element('country_id'));
	$error['INPUT']['area_id'] = $validate->pasteString($request->element('area_id'));
	$error['INPUT']['ward_id'] = $validate->pasteString($request->element('ward_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['name']['error'] || $error['INPUT']['country_id']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>