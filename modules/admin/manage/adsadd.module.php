<?php

/*************************************************************************
Adding Ads module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 21/09/2011
Coder: Tran Thi My Xuyen
Checkeb by: Mai Minh (07/05/2012)
 **************************************************************************/

// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);
//     }

$userInfo->checkPermission('banner', 'add');
$templateFile = 'manageads.tpl.html';
include_once(ROOT_PATH . 'classes/dao/adscategories.class.php');
include_once(ROOT_PATH . 'classes/dao/ads.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/dao/imgs.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$adsCategories = new AdsCategories($storeId);
$imgs = new Imgs();
$fields = new Fields($storeId);
$ads = new Ads($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_banner'] => '/' . ADMIN_SCRIPT . '?op=manage&act=ads',
	$amessages['add_new'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=ads';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => '#',
	$amessages['list_ads_category'] => $tabLink . '&mod=listcategory',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Allow some javascript
$template->assign('ckEditor', 1);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
# Category combo box
$categoryCombo = $adsCategories->generateCombo($request->element('gId'), 1);
if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='ads'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='ads'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='ads'", array('position' => 'ASC'));
	if ($fieldList) $template->assign('fieldList', $fieldList);

	# Get list of custom options
	$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='ads'", array('position' => 'ASC'));
	if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
		# Category combo box
		$categoryCombo = $adsCategories->generateCombo($request->element('gId'), 1);
		if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
	} else { # Valid data input
		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			$properties = array('');
				# User upload
			$userUpload = $userInfo->getId();

			$thisYearAlbum = getOrCreateYearUploadAlbum($storeId, $uploadAlbums);

			$avatarUploadId = uploadAvatar(
				$thisYearAlbum,
				$uploads,
				$userInfo,
				'logo',     // name input
				'ads'     // object
			);

			$fileIds = uploadFiles(
				$thisYearAlbum,
				$uploads,
				$userInfo,
				'files',
				'ads'
			);						


			$properties = array(
				'logo_type' => $request->element('typeurl'),
				'url_logo' => Filter($request->element('urllogo')),
				'url_logo_type' => $request->element('typeurl'),
				'url' => Filter($request->element('url')),
				'user_upload' => $userUpload,
			);

			# Custom fields
			foreach ($fieldList as $field) {
				$properties[$field->getName()] = stripslashes($request->element($field->getName()));
			}

			$data = array(
				'store_id' => $storeId,
				'gid' => $request->element('gId'),
				'avatar' => $avatarUploadId,
				'file_ids' => $fileIds ? implode(',', $fileIds) : '',
				'position' => $request->element('position'),
				'status' => $request->element('status'),
				// 'url' => $imgl,
				'properties' => serialize($properties),
				'date_created' => date("Y-m-d H:i:s"),
				'content' => $request->element('altcontent'),
				'name' => $request->element('name')
			);
			$newId = $ads->addData($data);

			# Custom Options
			if ($newId) {
				foreach ($fieldOptionList as $field) {
					$valueType = stripslashes($request->element($field->getFieldName()));
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
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_ads'], $newId), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=ads&mod=list&gId=" . $request->element('gId') . "&rcode=6");
		}
	}
}

# Custom Options
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['invalid'] = 0;

	$error['INPUT']['gid'] = $validate->pasteString($request->element('gid'));
	$error['INPUT']['position'] = $validate->validNumber($request->element('position'), $amessages['position']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['urllogo'] = $validate->pasteString($request->element('urllogo'));
	$error['INPUT']['altcontent'] = $validate->pasteString($request->element('altcontent'));
	$error['INPUT']['url'] = $validate->pasteString($request->element('url'), $amessages['url']);
	$error['INPUT']['logo'] = $validate->pasteString($request->element('logo'), $amessages['logourl']);
	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

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

	if ($error['INPUT']['position']['error']) {
		$error['invalid'] = 1;
	}

	return $error;
}
