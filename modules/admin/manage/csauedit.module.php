<?php

/*************************************************************************
Editing csau module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Last updated: 15/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (07/05/2012)
 **************************************************************************/
$userInfo->checkPermission('csau', 'edit');
$templateFile = 'managecsau.tpl.html';
include_once(ROOT_PATH . 'classes/dao/csaus.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
$csaus = new Csaus($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
if ($fieldValue) $template->assign('fieldValue', $fieldValue);
$gallery_root = ROOT_PATH . "upload/$storeId/";
$gallery_path = $gallery_root . "resources/";
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_csau'] => '/' . ADMIN_SCRIPT . '?op=manage&act=csau',
	"Cập nhật thông tin" => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=csau';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	"Cập nhật thông tin" => '#',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='csau'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
if ($id) $template->assign('id', $id);
$csauInfo = $csaus->getObject($id);
if (!$csauInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	# Allow some javascript
	$template->assign('ckEditor', 1);
	if ($request->element('doo') == 'delAvatar') {
		$csauInfo = $csaus->getObject($id);
		if ($csauInfo->getAvatar()) {
			unlink($gallery_path . 'a_' . $csauInfo->getAvatar());
			unlink($gallery_path . 'l_' . $csauInfo->getAvatar());
			unlink($gallery_path . 't_' . $csauInfo->getAvatar());
			unlink($gallery_path . 'm_' . $csauInfo->getAvatar());

			$data = array('avatar' => '');
			$csaus->updateData($data, $id);
			$productInfo = $csaus->getObject($id);
		}
	}

	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Get list of custom fields
		$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='csau'", array('position' => 'ASC'));
		if ($fieldList) $template->assign('fieldList', $fieldList);
		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='csau'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
			$csauInfo = $csaus->getObject($id);
			$template->assign('itemInfo', $csauInfo);
		} else { # Valid data input
			# Check if duplicate slug
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				$csauInfo = $csaus->getObject($id);

				if ($csauInfo) {
					#File Avatar
					$fileAvatr = isset($_FILES['avatar']) ? $_FILES['avatar'] : '';
					if ($fileAvatr) {
						$img = addslashes(Filter(rand() . "_" . $fileAvatr['name']));
						$tmp_img = $fileAvatr['tmp_name'];
						$size = $fileAvatr['size'];
						$type = strtolower(substr($img, -3));
						if (preg_match("/" . ALLOW_FILE_TYPES . "/", strtolower($img))) {
							# Upload
							if (isImage($img)) {
								$new_img = $img;
								move_uploaded_file($tmp_img, $gallery_path . 'l_' . $img);
								if (isBmp($img)) $new_img = preg_replace("/(bmp$)/", "jpg", $img);
								resize($gallery_path, $gallery_path, 'l_' . $img, 'l_' . $new_img, DEFAULT_LARGE_SIZE, DEFAULT_LARGE_SQUARE, DEFAULT_PHOTO_QUALITY);
								resize($gallery_path, $gallery_path, 'l_' . $img, 'a_' . $new_img, DEFAULT_AVATAR_SIZE, DEFAULT_AVATAR_SQUARE, DEFAULT_PHOTO_QUALITY);
								if (CREATE_PRODUCT_AVATAR_CORNER) imageCreateCorners($gallery_path . 'a_' . $new_img, 9);
								resize($gallery_path, $gallery_path, 'l_' . $img, 'm_' . $new_img, DEFAULT_MEDIUM_SIZE, DEFAULT_MEDIUM_SQUARE, DEFAULT_PHOTO_QUALITY);
								resize($gallery_path, $gallery_path, 'l_' . $img, 't_' . $new_img, DEFAULT_THUMBNAIL_SIZE, DEFAULT_THUMBNAIL_SQUARE, DEFAULT_PHOTO_QUALITY);
								if ($img != $new_img) unlink($gallery_path . 'l_' . $img);	# Delete file if it's not a JPEG
								$avatar = $new_img;
							}
						} #/if (preg_match
					}
					if (empty($avatar)) {
						$avatar = $csauInfo->getAvatar();
					}
					# End File upload
					# Custom fields
					foreach ($fieldList as $field) {
						$properties[$field->getName()] = stripslashes($request->element($field->getName()));
					}
					$data = array(
						'store_id' => $storeId,
						'fullname' => Filter($request->element('title')),
						'details' => addslashes($request->element('detail')),
						'status' => $request->element('status'),
						'cat_id' => $request->element('cat_id'),
						'avatar' => $avatar,
						'created' => date("Y-m-d H:i:s")
					);
					$result = $csaus->updateData($data, $id);

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

							$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $id, $storeId);
						}
					}

					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_article'], $request->element('title')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
					# Redirect to editing page
					header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=csau&mod=edit&lang=$lang&id=$id&rcode=7");
				}
			}
		}
	} else { # Load product category information to edit
		$template->assign('item', $csauInfo);
		# Get list of custom fields
		$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='csau'", array('position' => 'ASC'));
		if ($fieldList) $template->assign('fieldList', $fieldList);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['slug'] = $validate->validString($request->element('slug'), $amessages['slug']);
	$error['INPUT']['title'] = $validate->validString($request->element('title'), $amessages['title']);
	$error['INPUT']['detail'] = $validate->validString($request->element('detail'), $amessages['detail']);


	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

	$error['invalid'] = 0;
	if ($error['INPUT']['title']['error'] || $error['INPUT']['detail']['error']) {
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
