<?php

/*************************************************************************
System config down module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
 **************************************************************************/
checkPermission(array(2, 3));
include_once(ROOT_PATH . 'classes/dao/templates.class.php');
$templates = new Templates();
$templateFile = 'systemconfig.tpl.html';

$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['system_config'] => '/' . ADMIN_SCRIPT . '?op=system&act=config',
	$amessages['site_down'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=config';
$listTabs = array(
	$amessages['general_config'] => $tabLink . '&mod=general',
	$amessages['trademark'] => $tabLink . '&mod=trademark',
	$amessages['carcompany'] => $tabLink . '&mod=carcompany',
	$amessages['size'] => $tabLink . '&mod=size',
	$amessages['site_down'] => $tabLink . '&mod=down',
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 5);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

if ($_POST) { # if form is submitted
	if ($request->element('doo') == 'cancel') {	# Cancel
		header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=config&mod=down&lang=$lang&ecode=7");
		exit;
	}
	if ($request->element('doo') == 'submit') {
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
		} else { # Valid data input
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				$properties = $estore->getProperties();
				if (!$properties) $properties = array('');
				$properties['site_down'] = $request->element('site_down');
				$properties['site_down_message'] = Filter($request->element('site_down_message'));
				$data = array('properties' => serialize($properties));
				$stores->updateData($data, $storeId);
				$estore = $stores->getObject($storeId);
				$template->assign('item', $estore);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['update_site_down_ok'], $request->element('site_down') ? 'ON' : 'OFF'), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=config&mod=down&lang=$lang&rcode=7");
			}
		}
	} else if ($estore) $template->assign('item', $estore);
} else if ($estore) $template->assign('item', $estore);

function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['site_down'] = $validate->pasteString($request->element('site_down'));
	$error['INPUT']['site_down_message'] = $validate->pasteString($request->element('site_down_message'));
	if ($request->element('site_down')) $error['INPUT']['site_down_message'] = $validate->validString($request->element('site_down_message'), $amessages['site_down_message']);
	if ($error['INPUT']['site_down']['error'] || $error['INPUT']['site_down_message']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
