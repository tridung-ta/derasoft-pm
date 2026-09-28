<?php
/*************************************************************************
System config rate module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
**************************************************************************/
checkPermission(array(2,3));
include_once(ROOT_PATH.'classes/dao/templates.class.php');
$templates = new Templates();
$templateFile = 'systemconfig.tpl.html';

$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_config'] => '/'.ADMIN_SCRIPT.'?op=system&act=config',
				$amessages['rate_config'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=config';
$listTabs = array($amessages['general_config'] => $tabLink.'&mod=general',
				$amessages['site_down'] => $tabLink.'&mod=down',
				$amessages['rate_config'] => $tabLink.'&mod=rate',
				$amessages['sale_off_config'] => $tabLink.'&mod=saleoff',
				$amessages['order_config'] => $tabLink.'&mod=order');
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',3);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

if($_POST) { # if form is submitted
	if($request->element('doo') == 'cancel') {	# Cancel
		header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=config&mod=rate&lang=$lang&ecode=7");
		exit;
	}
	if($request->element('doo') == 'submit') {
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
		} else { # Valid data input
			# Check if the rate is less than default rate from Bido
			if($request->element('rate_usd') < DEFAULT_RATE) {
				$validate['INPUT']['rate_usd']['message'] = sprintf($amessages['rate_need_less_than_default'],DEFAULT_RATE);
				$validate['INPUT']['rate_usd']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			} else {
				# Everything is ok. Update data to DB
				if(!$validate['invalid']) {
					$old_rate = $estore->getProperty('rate_usd');
					if(!$old_rate) $old_rate = 'NULL';
					$properties = $estore->getProperties();
					if(!$properties) $properties = array('');
					$properties['rate_usd'] = Filter($request->element('rate_usd'));
					$data = array('properties' => serialize($properties));
					$stores->updateData($data,$storeId);
					$estore = $stores->getObject($storeId);
					$template->assign('item',$estore);
	
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['update_rate_ok'],number_format($old_rate), number_format($request->element('rate_usd'))),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
	
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=config&mod=rate&lang=$lang&rcode=7");
				}
			}
		}
	} else if($estore) $template->assign('item',$estore);
} else if($estore) $template->assign('item',$estore);

function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['rate_usd'] = $validate->validNumber($request->element('rate_usd'),$amessages['rate_usd_vnd']);
	if($error['INPUT']['rate_usd']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>