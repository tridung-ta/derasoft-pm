<?php
/*************************************************************************
Adding article category module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 07/05/2012
**************************************************************************/
$templateFile = 'systempayment.tpl.html';
include_once(ROOT_PATH.'classes/dao/paymentmethods.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$payments = new PaymentMethods($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_payment_method'] => '/'.ADMIN_SCRIPT.'?op=system&act=payment',
				$amessages['add_new'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=payment';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Allow some javascript
$template->assign('ckEditor',1);
	
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate category name
		if($payments->checkDuplicate($request->element('name'))) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		if($payments->checkDuplicate(strtolower($request->element('module')),'module')) {
				$validate['INPUT']['module']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['module']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$properties = array('note' => $request->element('note'));
			$data = array('store_id' => $storeId,
						  'module' => Filter(strtolower($request->element('module'))),
						  'name' => Filter($request->element('name')),
						  'fee' => Filter($request->element('fee')),
						  'currencies' => Filter($request->element('currencies')),
						  'position' => Filter($request->element('position')),
						  'status' => Filter($request->element('status')),
						  'properties' => serialize($properties));
			$payments->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_payment_method'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=payment&mod=list&pId=".$request->element('parent_id')."&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['module'] = $validate->validString($request->element('module'),$amessages['module']);	
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['fee'] = $validate->validNumber($request->element('fee'),$amessages['payment_fee']);
	$error['INPUT']['currencies'] = $validate->validString($request->element('currencies'),$amessages['payment_currencies']);
	$error['INPUT']['note'] = $validate->pasteString($request->element('note'));
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	if($error['INPUT']['module']['error'] || $error['INPUT']['name']['error'] || $error['INPUT']['fee']['error'] || $error['INPUT']['currencies']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>