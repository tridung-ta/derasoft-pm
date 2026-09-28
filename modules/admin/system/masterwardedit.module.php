<?php
/*************************************************************************
Editing Ward module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 09/09/2011
Coder: Xuyen Tran
Checked by: Mai Minh (03/06/2025)
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
				$amessages['edit_ward'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=master';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=wardlist',
				$amessages['edit_ward'] => $tabLink.'',
				$amessages['clean_trash'] => $tabLink.'&mod=wardcleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Get area ID from request
$id = $request->element('id');
if($id) $template->assign('id',$id);
$wardInfo = $wards->getObject($id);

if(!$wardInfo) {
	# Invalid area object
	$template->assign('validItem',0);
} else {
	# Valid area object
	$template->assign('validItem',1);

	# Allow some javascript
	$template->assign('ckEditor',0);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
			
        # areas combo box
        $areaCombo = $areas->generateCombo($request->element('area_id'));
        if($areaCombo) $template->assign('areaCombo',$areaCombo);
		
		} else { # Valid data input
			# areas combo box
            $areaCombo = $areas->generateCombo($request->element('area_id'));
            if($areaCombo) $template->assign('areaCombo',$areaCombo);

			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('store_id' => $storeId,
							  'name' => $request->element('name'),
							  'area_id' => (int)$request->element('area_id'),
							  'position' => (int)$request->element('position'),
							  'status' => (int)$request->element('status'));
				$result = $wards->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_ward'],$wards->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=master&mod=wardedit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load area information to edit
		$template->assign('item',$wardInfo);
		
        # Countries combo box
        $areaCombo = $areas->generateCombo($wardInfo->getAreaId());
        if($areaCombo) $template->assign('areaCombo',$areaCombo);
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