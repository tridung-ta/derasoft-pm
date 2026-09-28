<?php
/*************************************************************************
System order config module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 22/05/2012
Coder: Mai Minh
**************************************************************************/
checkPermission(array(2,3));
$templateFile = 'systemconfig.tpl.html';

$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_config'] => '/'.ADMIN_SCRIPT.'?op=system&act=config',
				$amessages['system_config_general'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=config';
$listTabs = array($amessages['general_config'] => $tabLink.'&mod=general',
				$amessages['site_down'] => $tabLink.'&mod=down',
				$amessages['rate_config'] => $tabLink.'&mod=rate',
				$amessages['sale_off_config'] => $tabLink.'&mod=saleoff',
				$amessages['order_config'] => $tabLink.'&mod=order');
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',5);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

if($_POST) { # if form is submitted
	if($request->element('doo') == 'cancel') {	# Cancel
		header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=config&mod=order&lang=$lang&ecode=7");
		exit;
	}
	if($request->element('doo') == 'submit') {
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			$template->assign('estore',$estore);
		} else { # Valid data input
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$properties = $estore->getProperties();
				if(!$properties) $properties = array('');
				$properties['order_on'] = $request->element('order_on');
				$properties['order_require_login'] = $request->element('order_require_login');
				$properties['order_status'] = $request->element('order_status');
				$properties['order_hours'] = $request->element('order_hours');
				$properties['order_vat'] = $request->element('order_vat');
				$properties['order_change_email'] = $request->element('order_change_email');
				$properties['order_allow_cancel'] = $request->element('order_allow_cancel');

				$data = array('properties' => serialize($properties));
				$stores->updateData($data,$storeId);
				$estore = $stores->getObject($storeId);
				$template->assign('item',$estore);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['update_order_setting_ok'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=config&mod=order&lang=$lang&rcode=7");
			}
		}
	} else if($estore) $template->assign('item',$estore);
} else if($estore) $template->assign('item',$estore);

function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['order_on'] = $validate->pasteString($request->element('order_on'));
	$error['INPUT']['order_require_login'] = $validate->pasteString($request->element('order_require_login'));
	$error['INPUT']['order_status'] = $validate->pasteString($request->element('order_status'));
	$error['INPUT']['order_hours'] = $validate->validPlusNumber($request->element('order_hours'), $amessages['order_hours']);
	$error['INPUT']['order_vat'] = $validate->validPlusNumber($request->element('order_vat'), $amessages['order_vat']);
	$error['INPUT']['order_change_email'] = $validate->pasteString($request->element('order_change_email'));
	$error['INPUT']['order_allow_cancel'] = $validate->pasteString($request->element('order_allow_cancel'));

	if($error['INPUT']['order_hours']['error'] || $error['INPUT']['order_vat']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>