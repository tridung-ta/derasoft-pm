<?php
/*************************************************************************
Editing article module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Mai Minh
Last updated: 11/06/2025
**************************************************************************/
# Check permission
$userInfo->checkPermission('customer','edit');

$templateFile = 'managecustomer.tpl.html';
include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
include_once(ROOT_PATH.'classes/dao/customergroups.class.php');
include_once(ROOT_PATH.'classes/dao/countries.class.php');
include_once(ROOT_PATH.'classes/dao/areas.class.php');
include_once(ROOT_PATH.'classes/dao/wards.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$customers = new Customers($storeId);
$customerGroups = new CustomerGroups($storeId);
$countries = new Countries($storeId);
$areas = new Areas($storeId);
$wards = new Wards($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
if ($fieldValue) $template->assign('fieldValue', $fieldValue); 

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_customer'] => '/'.ADMIN_SCRIPT.'?op=manage&act=customer',
				$amessages['edit_customer'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=customer';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_customer'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='customer'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Result code
$result_code = $request->element('rcode'); 
if($result_code) $template->assign('result_code',$result_code);

$id = $request->element('id');
if($id) $template->assign('id',$id);
$customerInfo = $customers->getObject($id);

# Get all field values
$allFieldValues = $fieldValue->getAllValuesByKeyId($id);
$template->assign('allFieldValues', $allFieldValues);

if(!$customerInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);
	if($_POST && $request->element('doo') == 'submit') { # if form is submitted

		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='customer'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			$customerInfo = $customers->getObject($id);
			$template->assign('customerInfo',$customerInfo);
			
			# Customer group combo box
			$customerGroupsCombo = $customerGroups->generateCombo($validate['INPUT']['group_id']['value']);
			$template->assign('customerGroupsCombo',$customerGroupsCombo);

			# Countries combo box
			$countriesCombo = $countries->generateCombo($validate['INPUT']['country_id']['value']);
			$template->assign('countriesCombo',$countriesCombo);

			# Areas combo box
			$areasCombo = $areas->generateCombo($validate['INPUT']['area_id']['value'],"`country_id` = '".$validate['INPUT']['country_id']['value']."'");
			$template->assign('areasCombo',$areasCombo);

			# Wards combo box
			$wardsCombo = $wards->generateCombo($validate['INPUT']['ward_id']['value'],"`area_id` = '".$validate['INPUT']['area_id']['value']."'");
			$template->assign('wardsCombo',$wardsCombo);
		} else { 
			if($request->element('password')) {
					$new_password = md5($request->element('password'));
					$cpassword = md5($request->element('cpassword'));
					if($new_password != $cpassword) { # New password is same as confirm password
						$validate['INPUT']['cpassword']['message'] = $amessages['invalid_confirm_password'];
						$validate['INPUT']['cpassword']['error'] = 1;
						$validate['invalid'] = 1;
						$template->assign('error',$validate);
					}
				}		
				# check duplicate email
				if($customers->checkDuplicate($request->element('email'),'email',"`id` <>'$id'")) {
					$validate['INPUT']['email']['message'] = $amessages['email_duplicated'];
					$validate['INPUT']['email']['error'] = 1;
					$validate['invalid'] = 1;
					$template->assign('error',$validate);
				}
			
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$customerInfo = $customers->getObject($id);
				if($customerInfo) {
					#User update
					$properties = $customerInfo->getProperties();
					$properties = array();
					
				   $data = array('store_id' => $storeId,
							  'fullname' => $request->element('fullname'),
							  'address' => $request->element('address'),
							  'email' => Filter($request->element('email')),
							  'tel' => Filter($request->element('tel')),
							  'company' => Filter($request->element('company')),
							  'tax_code' => Filter($request->element('tax_code')),
							  'group_id' => (int)Filter($request->element('group_id')),
							  'ward_id' => (int)Filter($request->element('ward_id')),
							  'properties' => serialize($properties),
							  'updater_id' => $userInfo->getId(),
							  'date_updated' => date("Y-m-d H:i:s"),
							  'status' => (int)$request->element('status'));
					if($request->element('password')) $data['password'] = md5($request->element('password'));
					$customerUpdateId = $customers->updateData($data,$id);

					# Custom options
					if ($customerUpdateId) {
						foreach ($fieldOptionList as $field) {
							$fieldId = $field->getId();
							$valueType = $request->element($field->getFieldName());
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

							$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $id, $storeId);
						}
					}	

					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_customer'],$request->element('username')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));

					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=customer&mod=edit&lang=$lang&id=$id&rcode=7");
				}
			}
		}
	} else { # Load customer information to edit
		$customerInfo = $customers->getObject($id);
		if($customerInfo) {
			$template->assign('item',$customerInfo);
			
			# Customer group combo box
			$customerGroupsCombo = $customerGroups->generateCombo($customerInfo->getGroupId());
			$template->assign('customerGroupsCombo',$customerGroupsCombo);

			# Countries combo box
			$countriesCombo = $countries->generateCombo($customerInfo->getCountryId());
			$template->assign('countriesCombo',$countriesCombo);

			# Areas combo box
			$areasCombo = $areas->generateCombo($customerInfo->getAreaId(),"`country_id` = '".$customerInfo->getCountryId()."'");
			$template->assign('areasCombo',$areasCombo);

			# Wards combo box
			$wardsCombo = $wards->generateCombo($customerInfo->getWardId(),"`area_id` = '".$customerInfo->getAreaId()."'");
			$template->assign('wardsCombo',$wardsCombo);
		}
	}
}

# Check validate input
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['username'] = $validate->pasteString($request->element('username'));
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'),$amessages['fullname']);
	if($request->element('password')) $error['INPUT']['password'] = $validate->validPassword($request->element('password'),$amessages['password']);
	else $error['INPUT']['password'] = $validate->pasteString($request->element('password'));
	if($request->element('cpassword')) $error['INPUT']['cpassword'] = $validate->validPassword($request->element('cpassword'),$amessages['cpassword']);
	else $error['INPUT']['cpassword'] = $validate->pasteString($request->element('cpassword'));
	$error['INPUT']['address'] = $validate->validString($request->element('address'),$amessages['address']);
	$error['INPUT']['email'] = $validate->validEmail($request->element('email'),$amessages['email']);
	$error['INPUT']['tel'] = $validate->validString($request->element('tel'),$amessages['telephone']);
	$error['INPUT']['company'] = $validate->pasteString($request->element('company'),$amessages['name_company']);
	$error['INPUT']['tax_code'] = $validate->pasteString($request->element('tax_code'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
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

	if($error['INPUT']['username']['error'] || $error['INPUT']['fullname']['error'] || $error['INPUT']['password']['error'] || $error['INPUT']['address']['error'] || $error['INPUT']['tel']['error']  || $error['INPUT']['email']['error'] || 
	$error['INPUT']['tax_code']['error'] || $error['INPUT']['group_id']['error'] ||
	$error['INPUT']['country_id']['error'] || $error['INPUT']['area_id']['error'] ||
	$error['INPUT']['ward_id']['error']){
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;

	return $error;
}
?>