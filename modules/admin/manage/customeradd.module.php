<?php
/*************************************************************************
Adding customer module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 09/09/2011
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
// error_reporting(E_ALL);
//     ini_set('display_errors', 1);
# Check permission
$userInfo->checkPermission('customer','add');

$templateFile = 'managecustomer.tpl.html';
include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
include_once(ROOT_PATH.'classes/dao/customergroups.class.php');
include_once(ROOT_PATH.'classes/dao/countries.class.php');
include_once(ROOT_PATH.'classes/dao/areas.class.php');
include_once(ROOT_PATH.'classes/dao/wards.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$customers = new Customers($storeId);
$customerGroups = new CustomerGroups($storeId);
$countries = new Countries($storeId);
$areas = new Areas($storeId);
$wards = new Wards($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_customer'] => '/'.ADMIN_SCRIPT.'?op=manage&act=customer',
				$amessages['add_new'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=customer';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);


# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Customer group combo box
$customerGroupsCombo = $customerGroups->generateCombo($request->element('group_id'));
$template->assign('customerGroupsCombo',$customerGroupsCombo);

# Countries combo box
$countriesCombo = $countries->generateCombo($request->element('country_id'));
$template->assign('countriesCombo',$countriesCombo);

# Areas combo box
if($request->element('area_id')) {
	$areasCombo = $areas->generateCombo($request->element('area_id'),"`country_id` = '".$request->element('country_id')."'");
	$template->assign('areasCombo',$areasCombo);
}

# Wards combo box
if($request->element('ward_id')) {
	$wardsCombo = $wards->generateCombo($request->element('ward_id'),"`area_id` = '".$request->element('area_id')."'");
	$template->assign('wardsCombo',$wardsCombo);
}

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='customer'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Submitted form
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate customer username
			if($customers->checkDuplicate($request->element('username'),'username')) {
				$validate['INPUT']['username']['message'] = $amessages['username_duplicated'];
				$validate['INPUT']['username']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
		# check duplicate customer email
		
			if($customers->checkDuplicate($request->element('email'),'email')) {
				$validate['INPUT']['email']['message'] = $amessages['email_duplicated'];
				$validate['INPUT']['email']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			# Password
			$pass = $request->element('password','');
			$password = md5($pass);
			
			#Properties
			$properties = array('');
			#$properties = array('about' => Filter($request->element('about')));
			# End properties
			
			$data = array('store_id' => $storeId,
						  'username' => Filter($request->element('username')),
						  'password' => $password,
						  'fullname' => $request->element('fullname'),
						  'address' => $request->element('address'),
						  'email' => Filter($request->element('email')),
						  'tel' => Filter($request->element('tel')),
						  'company' => Filter($request->element('company')),
						  'tax_code' => Filter($request->element('tax_code')),
						  'group_id' =>  (int)$request->element('group_id'),
						  'ward_id' => (int)$request->element('ward_id'),
						  'properties' => serialize($properties),
						  'creator_id' => (int)$userInfo->getId(),
						  'date_created' => date("Y-m-d H:i:s"),
						  'status' => (int)$request->element('status'));
			$newId = $customers->addData($data);
			
			# Custom Options
			if ($newId) {
				foreach ($fieldOptionList as $field) {
					// $valueType = stripslashes($request->element($field->getFieldName()));
					$rawValue = $request->element($field->getFieldName());
					$valueType = is_array($rawValue) ? $rawValue : stripslashes($rawValue);
					if ($field->getFieldType() == 4 || $field->getFieldType() == 7) {
						$selectedKeys = (array) $request->element($field->getFieldName());
						$options = $field->getValue(); 
						$selectedValues = array_map(function ($key) use ($options) {
							return isset($options[$key]) ? $options[$key] : $key;
						}, $selectedKeys);

						$valueType = implode(", ", $selectedValues);
					}
					if ($field->getFieldType() == 5 || $field->getFieldType() == 6) {
						$options = $field->getValue();
						$valueType = isset($options[$valueType]) ? $options[$valueType] : $valueType; 
					}
					$fieldData = array(
						'store_id' => $storeId,
						'field_id' => $field->getId(),
						'key_id' => $newId,
						'field_value' => html_entity_decode($valueType),
						'status' => 1,
					);
					$newFieldValue = $fieldValue->addData($fieldData);
				}
			}
					
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_customer'],$request->element('username')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=customer&mod=list&rcode=6");
		}
	}
}

# Check validate input
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['username'] = $validate->validUsername($request->element('username'),$amessages['username']);
	$error['INPUT']['password'] = $validate->validPassword($request->element('password'),$amessages['password']);
	$error['INPUT']['cpassword'] = $validate->validTestPass($request->element('password'),$request->element('cpassword'),$amessages['cpassword']);
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'),$amessages['fullname']);
	$error['INPUT']['address'] = $validate->validString($request->element('address'),$amessages['address']);
	$error['INPUT']['email'] = $validate->validEmail($request->element('email'),$amessages['email']);
	$error['INPUT']['tel'] = $validate->validString($request->element('tel'),$amessages['telephone']);
	$error['INPUT']['company'] = $validate->pasteString($request->element('company'),$amessages['name_company']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['tax_code'] = $validate->pasteString($request->element('tax_code'));
	$error['INPUT']['group_id'] = $validate->validPlusNumber($request->element('group_id'),$amessages['customer_group_name']);
	$error['INPUT']['country_id'] = $validate->validPlusNumber($request->element('country_id'),$amessages['customer_country']);
	$error['INPUT']['area_id'] = $validate->validPlusNumber($request->element('area_id'),$amessages['customer_area']);
	$error['INPUT']['ward_id'] = $validate->validPlusNumber($request->element('ward_id'),$amessages['customer_ward']);
	
	# Custom Options
	global $fieldOptionList;
	foreach ($fieldOptionList as $field) {

		$fieldName = $field->getFieldName();
		$fieldValue = $request->element($fieldName);

		if ((is_null($fieldValue) || $fieldValue === '') && $field->getRequired() == 1) {
			$error['INPUT'][$fieldName] = [
				'value' => $fieldValue,
				'error' => 1,
				'message' => $amessages["field"] . " - " . $amessages['invalid_field']
			];
			$error['invalid'] = 1;
		} else {
			$error['INPUT'][$fieldName] = [
				'value' => $fieldValue,
				'error' => 0,
				'message' => ''
			];
		}
	}

	if($error['INPUT']['username']['error'] || $error['INPUT']['fullname']['error'] || $error['INPUT']['address']['error'] || $error['INPUT']['tel']['error'] || $error['INPUT']['password']['error'] || $error['INPUT']['cpassword']['error'] || $error['INPUT']['email']['error'] ){
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;

	return $error;
}
?>
