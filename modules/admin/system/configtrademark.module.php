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
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
$templates = new Templates();
$fields = new Fields($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
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
$template->assign('currentTab', 2);
# Allow some javascript
$template->assign('ckEditor',1);
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);
# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='trademark'", array('position' => 'ASC'),99);
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='trademark'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Get all field values
$allFieldValues = $fieldValue->getAllValuesByKeyId(1);
$template->assign('allFieldValues', $allFieldValues);

if ($_POST) { # if form is submitted
    if ($request->element('doo') == 'cancel') {    # Cancel
        header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=config&mod=trademark&lang=$lang&ecode=7");
        exit;
    }
    if ($request->element('doo') == 'submit') {
        # Validate the data input
        $validate = validateData($request);
        if ($validate['invalid']) {    # data input is not in valid form
            $template->assign('error', $validate);
        } else { # Valid data input
            # Everything is ok. Update data to DB
            if (!$validate['invalid']) {
				$properties = $estore->getProperties();
				if (!$properties) $properties = array('');
				# Custom fields
				foreach ($fieldList as $field) {
					$properties[$field->getName()] = $request->element($field->getName());
				}
				# End change by Thai Nguyen
				$data = array(
					'properties' => serialize($properties)
				);
				$result = $stores->updateData($data, $storeId);

                // custom options
				if ($result) {
					foreach ($fieldOptionList as $field) {
						$fieldId = $field->getId();
						$valueType = $request->element($field->getFieldName());
						if ($field->getFieldType() == 4 || $field->getFieldType() == 7) {
							$selectedKeys = (array) $request->element($field->getFieldName());
							$options = $field->getValue();
							$selectedValues = array_map(function ($key) use ($options) {
								return $options[$key] ?? $key;
							}, $selectedKeys);

							$valueType = implode(", ", $selectedValues);
						}
						if ($field->getFieldType() == 5 || $field->getFieldType() == 6) {
							$options = $field->getValue();
							$valueType = $options[$valueType] ?? $valueType; 
						}

						$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $storeId, $storeId);
					}
				}

				$estore = $stores->getObject($storeId);
				$template->assign('item', $estore);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['trademark'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=config&mod=trademark&lang=$lang&rcode=7");
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

    if ($error['INPUT']['site_down']['error']) {
        $error['invalid'] = 1;
        return $error;
    }
    $error['invalid'] = 0;

    # Custom Options
	// global $fieldOptionList;
	// foreach ($fieldOptionList as $field) {

	// 	$fieldName = $field->getFieldName();
	// 	$fieldValue = $request->element($fieldName);

	// 	if ((is_null($fieldValue) || $fieldValue === '') && $field->getRequired() == 1) {
	// 		$error['INPUT'][$fieldName] = [
	// 			'value' => $fieldValue,
	// 			'error' => 1,
	// 			'message' => $amessages["field"] . " - " . $amessages['invalid_field']
	// 		];
	// 		$error['invalid'] = 1;
	// 	} else {
	// 		$error['INPUT'][$fieldName] = [
	// 			'value' => $fieldValue,
	// 			'error' => 0,
	// 			'message' => ''
	// 		];
	// 	}
	// }

    return $error;
}
