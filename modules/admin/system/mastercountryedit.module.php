<?php
/*************************************************************************
Editing Country module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 09/09/2011
Coder: Xuyen Tran
Checked by: Mai Minh (03/06/2025)
**************************************************************************/
$templateFile = 'systemmastercountry.tpl.html';
include_once(ROOT_PATH.'classes/dao/countries.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$countries = new Countries($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_master_data'] => '/'.ADMIN_SCRIPT.'?op=system&act=master',
				$amessages['manage_master_data_country'] => '/'.ADMIN_SCRIPT.'?op=system&act=master&mod=countrylist',
				$amessages['edit_country'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=master';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=countrylist',
				$amessages['edit_country'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=countrycleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Get country ID from request
$id = $request->element('id');
if($id) $template->assign('id',$id);
$countryInfo = $countries->getObject($id);
if(!$countryInfo) {
	# Invalid country object
	$template->assign('validItem',0);
} else {
	# Valid country object
	$template->assign('validItem',1);

	# Allow some javascript
	$template->assign('ckEditor',0);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
		} else { # Valid data input
			# check duplicate country name
			if($countries->checkDuplicate($request->element('name'),'name',"`id` <> '$id'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('store_id' => $storeId,
							  'name' => $request->element('name'),
							  'position' => (int)($request->element('position')),
							  'status' => (int)($request->element('status')));
				$countries->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_country'],$countries->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=master&mod=countryedit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load country information to edit
		$template->assign('item',$countryInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['ship_id'] = $validate->pasteString($request->element('ship_id'));
	$error['INPUT']['time'] = $validate->pasteString($request->element('time'));
	$error['INPUT']['price'] = $validate->pasteString($request->element('price'));
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['name']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>