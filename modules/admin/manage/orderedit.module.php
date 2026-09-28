<?php
/*************************************************************************
Editing article module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Last updated: 09/09/2011
Coder: Tran Thi My Xuyen
**************************************************************************/
$userInfo->checkPermission('order','edit');
$templateFile = 'manageorderedit.tpl.html';
include_once(ROOT_PATH.'classes/dao/orders.class.php');
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$orders = new Orders($storeId);
$fields = new Fields($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_customer'] => '/'.ADMIN_SCRIPT.'?op=manage&act=order',
				$amessages['edit_order'] => '');
$result_code = $request->element('rcode'); 
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$orderInfo = $orders->getObject($id);
if(!$orderInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Get list of custom fields
	$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='order'",array('position' => 'ASC'));
	if($fieldList) $template->assign('fieldList',$fieldList);

	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
		$orderInfo = $orders->getObject($id);
		$template->assign('orderInfo',$orderInfo);
	} else { 
		
		# Everything is ok. Update data to DB
		if(!$validate['invalid']) {
			$orderInfo = $orders->getObject($id);
			if($orderInfo) {			
				#User update
				$properties = $orderInfo->getProperties();
				$properties['rnote'] = Filter($request->element('rnote'));
				$properties['user_update'] = $userInfo->getUsername();
				
				$properties['tech_firstName'] = Filter($request->element('tech_firstName'));
				$properties['org_firstName'] = Filter($request->element('org_firstName'));
				$properties['bill_firstName'] = Filter($request->element('bill_firstName'));

				$properties['tech_lastName'] = Filter($request->element('tech_lastName'));
				$properties['org_lastName'] = Filter($request->element('org_lastName'));
				$properties['bill_lastName'] = Filter($request->element('bill_lastName'));


				$properties['tech_city'] = Filter($request->element('tech_city'));
				$properties['org_city'] = Filter($request->element('org_city'));
				$properties['bill_city'] = Filter($request->element('bill_city'));


				$properties['tech_title'] = Filter($request->element('tech_title'));
				$properties['org_title'] = Filter($request->element('org_title'));
				$properties['bill_title'] = Filter($request->element('bill_title'));

				$properties['tech_company'] = Filter($request->element('tech_company'));
				$properties['org_company'] = Filter($request->element('org_company'));
				$properties['bill_company'] = Filter($request->element('bill_company'));

				$properties['tech_address1'] = Filter($request->element('tech_address1'));
				$properties['org_address1'] = Filter($request->element('org_address1'));
				$properties['bill_address1'] = Filter($request->element('bill_address1'));

				$properties['tech_address2'] = Filter($request->element('tech_address2'));
				$properties['org_address2'] = Filter($request->element('org_address2'));
				$properties['bill_address2'] = Filter($request->element('bill_address2'));

				$properties['tech_mst'] = Filter($request->element('tech_mst'));
				$properties['org_mst'] = Filter($request->element('org_mst'));
				$properties['bill_mst'] = Filter($request->element('bill_mst'));

				$properties['tech_postal'] = Filter($request->element('tech_postal'));
				$properties['org_postal'] = Filter($request->element('org_postal'));
				$properties['bill_postal'] = Filter($request->element('bill_postal'));

				$properties['tech_email'] = Filter($request->element('tech_email'));
				$properties['org_email'] = Filter($request->element('org_email'));
				$properties['bill_email'] = Filter($request->element('bill_email'));

				$properties['tech_fax_phone'] = Filter($request->element('tech_fax_phone'));
				$properties['org_fax_phone'] = Filter($request->element('org_fax_phone'));
				$properties['bill_fax_phone'] = Filter($request->element('bill_fax_phone'));

				$properties['tech_work_phone'] = Filter($request->element('tech_work_phone'));
				$properties['org_work_phone'] = Filter($request->element('org_work_phone'));
				$properties['bill_work_phone'] = Filter($request->element('bill_work_phone'));


				
               $statusOrder = $request->element('status');
			   $data = array('store_id' => $storeId,
			   			  'name' => Filter($request->element('tech_firstName')." ".$request->element('tech_lastName')),
						  'address' => Filter($request->element('tech_address')),
						  'email' => Filter($request->element('tech_email')),
						  'cell' => Filter($request->element('tech_fax_phone')),
						  'tel' => Filter($request->element('tech_work_phone')),						 
						  'properties' => serialize($properties),
						  'updated' => date("Y-m-d H:i:s"),
						  'status' => $statusOrder);
				$orders->updateData($data,$id);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_order'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			     if($statusOrder == 5){
			         #Call to ORDERNEW addon
                    foreach($addons->getAddonFromEvent('ORDER_PAID') as $addon) {include_once(ROOT_PATH."addons/$addon/addon.php");}
			     }
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=order&mod=edit&lang=$lang&id=$id&rcode=7");
			}
		}
	}
} else { # Load customer information to edit
	$orderInfo = $orders->getObject($id);
	if($orderInfo) {
		$template->assign('item',$orderInfo);
	}
	
}

# Get list of custom fields
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='order'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['tech_firstName'] = $validate->validString($request->element('tech_firstName'));
	$error['INPUT']['org_firstName'] = $validate->validString($request->element('org_firstName'));
	$error['INPUT']['bill_firstName'] = $validate->validString($request->element('bill_firstName'));

	$error['INPUT']['tech_title'] = $validate->validString($request->element('tech_title'));
	$error['INPUT']['org_title'] = $validate->validString($request->element('org_title'));
	$error['INPUT']['bill_title'] = $validate->validString($request->element('bill_title'));

	$error['INPUT']['tech_lastName'] = $validate->validString($request->element('tech_lastName'));
	$error['INPUT']['org_lastName'] = $validate->validString($request->element('org_lastName'));
	$error['INPUT']['bill_lastName'] = $validate->validString($request->element('bill_lastName'));

	$error['INPUT']['tech_company'] = $validate->validString($request->element('tech_company'));
	$error['INPUT']['org_company'] = $validate->validString($request->element('org_company'));
	$error['INPUT']['bill_company'] = $validate->validString($request->element('bill_company'));

	$error['INPUT']['tech_address1'] = $validate->validString($request->element('tech_address1'));
	$error['INPUT']['org_address1'] = $validate->validString($request->element('org_address1'));
	$error['INPUT']['bill_address1'] = $validate->validString($request->element('bill_address1'));

	$error['INPUT']['tech_city'] = $validate->validString($request->element('tech_city'));
	$error['INPUT']['org_city'] = $validate->validString($request->element('org_city'));
	$error['INPUT']['bill_city'] = $validate->validString($request->element('bill_city'));

	$error['INPUT']['tech_mst'] = $validate->validString($request->element('tech_mst'));
	$error['INPUT']['org_mst'] = $validate->validString($request->element('org_mst'));
	$error['INPUT']['bill_mst'] = $validate->validString($request->element('bill_mst'));

	$error['INPUT']['tech_postal'] = $validate->validString($request->element('tech_postal'));
	$error['INPUT']['org_postal'] = $validate->validString($request->element('org_postal'));
	$error['INPUT']['bill_postal'] = $validate->validString($request->element('bill_postal'));

	$error['INPUT']['tech_email'] = $validate->validEmail($request->element('tech_email'));
	$error['INPUT']['org_email'] = $validate->validEmail($request->element('org_email'));
	$error['INPUT']['bill_email'] = $validate->validEmail($request->element('bill_email'));

	$error['INPUT']['tech_work_phone'] = $validate->validString($request->element('tech_work_phone'));
	$error['INPUT']['org_work_phone'] = $validate->validString($request->element('org_work_phone'));
	$error['INPUT']['bill_work_phone'] = $validate->validString($request->element('bill_work_phone'));
	
	if($error['INPUT']['tech_firstName']['error'] ||
		$error['INPUT']['org_firstName']['error'] || 
		$error['INPUT']['bill_firstName']['error'] || 
		$error['INPUT']['tech_title']['error'] || 
		$error['INPUT']['org_title']['error'] || 
		$error['INPUT']['bill_title']['error'] || 
		$error['INPUT']['tech_lastName']['error'] || 
		$error['INPUT']['org_lastName']['error'] || 
		$error['INPUT']['bill_lastName']['error'] || 
		$error['INPUT']['tech_company']['error'] || 
		$error['INPUT']['org_company']['error'] || 
		$error['INPUT']['bill_company']['error'] || 
		$error['INPUT']['tech_address1']['error'] || 
		$error['INPUT']['org_address1']['error'] || 
		$error['INPUT']['bill_address1']['error'] || 
		$error['INPUT']['tech_city']['error'] || 
		$error['INPUT']['org_city']['error'] || 
		$error['INPUT']['bill_city']['error'] || 
		$error['INPUT']['tech_mst']['error'] || 
		$error['INPUT']['org_mst']['error'] || 
		$error['INPUT']['bill_mst']['error'] || 
		$error['INPUT']['tech_email']['error'] || 
		$error['INPUT']['org_email']['error'] || 
		$error['INPUT']['bill_email']['error'] || 
		$error['INPUT']['tech_work_phone']['error'] || 
		$error['INPUT']['org_work_phone']['error'] || 
		$error['INPUT']['bill_work_phone']['error'] 
	){
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>