<?php
/*************************************************************************
System config Saleoff module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
**************************************************************************/
checkPermission(array(2,3));
include_once(ROOT_PATH.'classes/dao/saleoffs.class.php');
$saleoffs = new Saleoffs($storeId);
$templateFile = 'systemconfig.tpl.html';

$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_config'] => '/'.ADMIN_SCRIPT.'?op=system&act=config',
				$amessages['sale_off_config'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=config';
$listTabs = array($amessages['general_config'] => $tabLink.'&mod=general',
				$amessages['site_down'] => $tabLink.'&mod=down',
				$amessages['rate_config'] => $tabLink.'&mod=rate',
				$amessages['sale_off_config'] => $tabLink.'&mod=saleoff',
				$amessages['order_config'] => $tabLink.'&mod=order');
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',4);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

if($_POST) { # if form is submitted
	if($request->element('doo') == 'cancel') {	# Cancel
		header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=config&mod=saleoff&lang=$lang&ecode=7");
		exit;
	}
	if($request->element('doo') == 'submit') {
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
		} else { # Valid data input
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				if($request->element('sale_off_policy') == '2' && $request->element('sale_off_program_id') <= 0) {
					$validate['INPUT']['sale_off_program_id']['message'] = $amessages['need_select_sale_off_program'];
					$validate['INPUT']['sale_off_program_id']['error'] = 1;
					$validate['invalid'] = 1;
					$template->assign('error',$validate);
					$estore = $stores->getObject($storeId);
					$template->assign('estore',$estore);
				} else {
					$properties = $estore->getProperties();
					if(!$properties) $properties = array('');
					$properties['sale_off_policy'] = $request->element('sale_off_policy');
					$properties['sale_off_program_id'] = $request->element('sale_off_program_id');
					$data = array('properties' => serialize($properties));
					$stores->updateData($data,$storeId);
					$estore = $stores->getObject($storeId);
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['update_sale_off_ok'],$amessages['sale_off_status'][$request->element('sale_off_policy')].($request->element('sale_off_policy') == '2'?' #'.$request->element('sale_off_program_id'):'')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
						
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=config&mod=saleoff&lang=$lang&rcode=7");
				}
			}
		}
	} else if($estore) $template->assign('item',$estore);
} else if($estore) $template->assign('item',$estore);

$saleoffPrograms = $saleoffs->getObjects(1,"`status` = 1 AND `store_id` = '$storeId'",'',1000);
if($saleoffPrograms) $template->assign('saleoffPrograms',$saleoffs->generateCombo($saleoffPrograms,$estore->getProperty('sale_off_program_id')));

function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['sale_off_policy'] = $validate->pasteString($request->element('sale_off_policy'));
	$error['INPUT']['sale_off_program_id'] = $validate->pasteString($request->element('sale_off_program_id'));
	$error['invalid'] = 0;
	return $error;
}
?>