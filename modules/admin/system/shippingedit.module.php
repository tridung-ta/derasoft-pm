<?php
/*************************************************************************
Editing Shipping Method module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 08/05/2012
Coder: Xuyen Tran
**************************************************************************/
$templateFile = 'systemshipping.tpl.html';
include_once(ROOT_PATH.'classes/dao/shippingmethods.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$methods = new ShippingMethods($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['method_delivery'] => '/'.ADMIN_SCRIPT.'?op=system&act=shipping',
				$amessages['edit_shipping_method'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=shipping';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_shipping_method'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$itemInfo = $methods->getObject($id);
if(!$itemInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);

	# Allow some javascript
	$template->assign('ckEditor',1);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
		} else { # Valid data input		
			# check duplicate category name
			if($methods->checkDuplicate($request->element('name'),'name',"`id` <> '$id'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$properties = array('');
				$properties['note'] = $request->element('note');
				$data = array('name' => Filter($request->element('name')),
							  'price' => Filter(str_replace(',','',$request->element('price'))),
							  'position' => Filter($request->element('position')),
							  'status' => Filter($request->element('status')),
							  'properties' => serialize($properties));
				$methods->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_shipping_method'],$methods->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=shipping&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load product category information to edit
		$template->assign('item',$itemInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['note'] = $validate->pasteString($request->element('note'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['price'] = $validate->validPrice($request->element('price'),$amessages['unit_shipping_price']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	if($error['INPUT']['name']['error'] || $error['INPUT']['price']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>