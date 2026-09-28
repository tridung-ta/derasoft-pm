<?php

/*************************************************************************
Editing ads module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 01/05/2012
Coder: Mai Minh
Checked by: Mai Minh (07/05/2012)
 **************************************************************************/
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$userInfo->checkPermission('banner', 'edit');
$templateFile = 'manageads.tpl.html';
include_once(ROOT_PATH . 'classes/dao/adscategories.class.php');
include_once(ROOT_PATH . 'classes/dao/ads.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$imgs = new Imgs();
$template->assign('imgs', $imgs);
$adsCategories = new AdsCategories($storeId);
$fields = new Fields($storeId);
$ads = new Ads($storeId);
$adsCategories = new AdsCategories($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=ads',
	$amessages['update_banner_category'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=ads';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['edit_ads'] => '#',
	$amessages['list_ads_category'] => $tabLink . '&mod=listcategory',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='ads'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Allow some javascript
$template->assign('ckEditor', 1);
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
if ($id) $template->assign('id', $id);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='ads'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);
$adsInfo = $ads->getObject($id);

# Get all field values
$allFieldValues = $fieldValue->getAllValuesByKeyId($id);
$template->assign('allFieldValues', $allFieldValues);

# Get avatar image
$avatarImg = null;
if ($adsInfo->getAvatar()) {
    $avatarImg = $uploads->getObject($adsInfo->getAvatar());
}
$template->assign('avatarImg', $avatarImg);

# ================= DELETE AVATAR =================
if ($request->element('doo') == 'delAvatar') {
    $avatarId = $adsInfo->getAvatar(); // upload_id

    if ($avatarId) {
        $upload = $uploads->getObject($avatarId);
        if ($upload) {
            $upload->deleteFiles();
            $uploads->DeteImg($avatarId);
            }
            
            $ads->updateData([
                'avatar' => 0
                ], $id);
        $adsInfo = $ads->getObject($id);
    }
}

# Get files ids 
$fileIds = $adsInfo->getFileIds();
if ($fileIds) {
	$fileIds = explode(',', $fileIds);
	$files = [];
	foreach ($fileIds as $fileId) {
		$file = $uploads->getObject($fileId);
		if ($file) {
			$files[] = $file;
		}
	}
	$template->assign('files', $files);
}

# ================= DELETE FILES =================
if ($request->element('doo') == 'delFile') {
	$fileIds = $adsInfo->getFileIds();
	if ($fileIds) {
		$fileIds = explode(',', $fileIds);
		foreach ($fileIds as $fileId) {
			if ($fileId == $request->element('file')) {
				$upload = $uploads->getObject($fileId);
				if ($upload) {
					$upload->deleteFiles();
					$uploads->DeteImg($fileId);
				}
				// Remove the file ID from the list
				$fileIds = array_diff($fileIds, [$fileId]);
				// Update the database with the new list of file IDs
				$ads->updateData([
					'file_ids' => implode(',', $fileIds)
					], $id);
				break;
			}
		}
	}
}

if (!$adsInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);

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
			$template->assign('itemInfo', $adsInfo);
			print_r($adsInfo);

			# Category combo box
			$categoryCombo = $adsCategories->generateCombo($request->element('gid', 1));
			if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
		} else { # Valid data input
			# Category combo box
			$categoryCombo = $adsCategories->generateCombo($request->element('gid', 1));
			if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				$adsInfo = $ads->getObject($id);
				if ($adsInfo) {
					$properties = $adsInfo->getProperties();
					#User update
					$properties['user_update'] = $userInfo->getUsername();
					# Upload album
					$thisYearAlbum = getOrCreateYearUploadAlbum($storeId, $uploadAlbums);

					# Avatar upload
					$avatarUploadId = uploadAvatar(
						$thisYearAlbum,
						$uploads,
						$userInfo,
						'logo',
						'ads'
					);

					# Files upload
					$fileUploadIds = uploadFiles(
						$thisYearAlbum,
						$uploads,
						$userInfo,
						'files',
						'ads'
					);
					# Get old file ids
					$oldFileIds = $adsInfo->getFileIds();
					$oldFileIds = $oldFileIds ? explode(',', $oldFileIds) : [];
					$fileIds = array_merge($oldFileIds, $fileUploadIds ?: []);
					# End File upload

					# Custom fields
					foreach ($fieldList as $field) {
						$properties[$field->getName()] = stripslashes($request->element($field->getName()));
					}

					$data = array(
						'store_id' => $storeId,
						'avatar' => $avatarUploadId ? $avatarUploadId : $adsInfo->getAvatar(),
						'file_ids' => $fileIds ? implode(',', $fileIds) : '',
						'gid' => $request->element('gId'),
						'position' => $request->element('position'),
						'status' => $request->element('status'),
						'properties' => serialize($properties),
						'date_created' => date("Y-m-d H:i:s"),
						'date_updated' => date("Y-m-d H:i:s"),
						'content' => $request->element('altcontent'),
						'name' => $request->element('name')
					);

					$result = $ads->updateData($data, $id);

					# CO
					if ($result) {
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
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_ads'], $request->element('id')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

					# Redirect to editing page
					header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=ads&mod=edit&lang=$lang&id=$id&rcode=7");
				}
			}
		}
	} else { # Load ads category information to edit
		$template->assign('item', $adsInfo);

		# Category combo box
		$categoryCombo = $adsCategories->generateCombo($adsInfo->getGId(), 1);
		if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
	}
}
# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['gid'] = $validate->pasteString($request->element('gId'));
	$error['INPUT']['position'] = $validate->validNumber($request->element('position'), $amessages['position']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['urllogo'] = $validate->pasteString($request->element('urllogo'));
	$error['INPUT']['altcontent'] = $validate->pasteString($request->element('altcontent'));
	$error['INPUT']['url'] = $validate->pasteString($request->element('url'), $amessages['url']);
	$error['INPUT']['logo'] = $validate->pasteString($request->element('logo'), $amessages['logourl']);
	$error['INPUT']['width'] = $validate->pasteString($request->element('width'));
	$error['INPUT']['height'] = $validate->pasteString($request->element('height'));

	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

	$error['invalid'] = 0;
	if ($error['INPUT']['position']['error']) {
		$error['invalid'] = 1;
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

	return $error;
}
