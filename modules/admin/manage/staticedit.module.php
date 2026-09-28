<?php
/*************************************************************************
Editing static module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Checked by: Mai Minh (16/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('static', 'edit');

$templateFile = 'managestatic.tpl.html';
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
include_once(ROOT_PATH . "includes/admin/functions.inc.php");
include_once(ROOT_PATH . "classes/dao/menus.class.php");

$uploads = new Uploads($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$statics = new StaticPage($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$menus = new Menus($storeId);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_static'] => '/' . ADMIN_SCRIPT . '?op=manage&act=static',
	$amessages['edit_static'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=static';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['edit_static'] => '#',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get menus array for generating nested combo
$arrayCategories = $menus->getObjectsForCombo();


$id = (int)$request->element('id');
if ($id) $template->assign('id', $id);
$staticInfo = $statics->getObject($id);

# Category combo box
$categoryCombo = $menus->generateNestedCombo($arrayCategories,$staticInfo->getMenuId());
$template->assign('categoryCombo', $categoryCombo);

if (!$staticInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	# Allow some javascript
	$template->assign('ckEditor', 1);

	# Get list of custom fields
	$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='static'", array('position' => 'ASC'));
	if ($fieldList) $template->assign('fieldList', $fieldList);	
	
	# Get list of custom options
	$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='static'", array('position' => 'ASC'));
	if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
	
	# Get all custom field values
	$allFieldValues = $fieldValue->getAllValuesByKeyId((int)$id);
	$template->assign('allFieldValues', $allFieldValues);
	
	# Get avatar object
	if($staticInfo->getProperty('avatarId')) {
		$avatarItem=$uploads->getObject($staticInfo->getProperty('avatarId'));
		if($avatarItem) $template->assign('avatarItem',$avatarItem);
	}
		
	# Get file upload objects
	if($staticInfo->getProperty('fileIds')) {
		$uploadIds = implode(',',$staticInfo->getProperty('fileIds'));
		$fileItems = $uploads->getObjects(1,"u.id IN ($uploadIds)");
		if($fileItems) $template->assign('fileItems',$fileItems);
	}	
	
	# Delete avatar upload
	if ($request->element('doo') == 'delAvatar') {
		$avatarId = (int)$request->element('avatarId');
		if($avatarId>0) $avatarInfo = $uploads->getObject($avatarId);
		if(isset($avatarInfo)) {
			$statics->deleteAvatarFromObject($staticInfo,$avatarId);
			
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_static_delete_avatar'], $staticInfo->getTitle()), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			
			# Redirect to editing page
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=static&mod=edit&lang=$lang&id=$id&rcode=7");
		} # End if(isset($avatarInfo)) {
	} # End if ($request->element('doo') == 'delAvatar') {
	
	# Delete file upload
	if ($request->element('doo') == 'delFile') {
		$fileId = (int)$request->element('fileId');
		if($fileId>0) $fileInfo = $uploads->getObject($fileId);
		if(isset($fileInfo)) {
			$statics->deleteFileFromObject($staticInfo,$fileId);
			
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_static_delete_file'], $staticInfo->getTitle()), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			
			# Redirect to editing page
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=static&mod=edit&lang=$lang&id=$id&rcode=7");
		} # End if(isset($avatarInfo)) {
	} # End if ($request->element('doo') == 'delAvatar') {
	
	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted	
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
			$staticInfo = $statics->getObject($id);
			$template->assign('itemInfo', $staticInfo);
		} else { # Valid data input
			# Check if duplicate slug
			$textFilter = new TextFilter();
			$slug = $textFilter->urlize($request->element('slug'), false, '-');
			$i = 0;
			$dup = 1;
			while ($dup) {
				$dup = $statics->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "`id` <> '$id' ");
				if ($dup) $i++;
			}
			$slug .= $i ? '-' . $i : '';
			
			# Check if the image upload album for this year exists. If not exists, create it
			$thisYearAlbum = $uploadAlbums->getObject(date("Y"),"name");
			if(!$thisYearAlbum) { # Album for this year not exists, create it
				$newAlbumData = array('store_id' => $storeId,
									 'name' => date("Y"),
									 'status' => 1,
									 'folder' => date("Y"),
									 'date_created' => date("Y-m-d H:i:s"),
									 'properties' => serialize(array()));
				$thisYearAlbumId = $uploadAlbums->addData($newAlbumData);

				# Get album object that just created
				$thisYearAlbum = $uploadAlbums->getObject($thisYearAlbumId);
			} else { # Album for this year exists
				# Return error if cannot create the image upload folder
				if($thisYearAlbum->getProperty('create_album_error')) {
					$template->assign('albumCreateError',$amessages['cannot_create_folder']." ".$thisYearAlbum->getAbsoluteFolder());
				}
			}
			
			# Check if album and folder exists now. If not, stop here and ask the staff to set permission for the folder
			if(!$thisYearAlbum->getProperty('create_album_error')) {
			# Everything is ok. Update data to DB
				if (!$validate['invalid']) {
				$staticInfo = $statics->getObject($id);
				if ($staticInfo) {
					$properties = $staticInfo->getProperties();
					
					# Avatar upload
					$fileAvatar = isset($_FILES['avatar']) ? $_FILES['avatar'] : '';
					# Only upload if uploaded file is image
					if(isImage($fileAvatar['name'])) {
						$newAvatarId = '';
						$newAvatarUrl = '';
						if($fileAvatar) {
							$newAvatarId = (int)$uploads->uploadFile($thisYearAlbum,
																$fileAvatar['name'],
																$fileAvatar['tmp_name'],
																$fileAvatar['size'],
																'static',
																$userInfo->getId(),
																0);
							# Change old avatar to DELETED if exists
							$oldAvatarId = (int)$properties['avatarId'];
							if($oldAvatarId>0) $uploads->changeStatus($oldAvatarId,S_DELETED);
							
							$newAvatarObject = $uploads->getObject($newAvatarId);
							if($newAvatarObject) {
								$newAvatarUrl = '/'.$newAvatarObject->getPath().'/'.$newAvatarObject->getUrlA();
							}
						
							# Properties
							$properties['avatarId'] = $newAvatarId;
							$properties['avatarUrl'] = $newAvatarUrl;
						} # End if($fileAvatar) {
					} # End if(isImage($fileAvatar['name'])) {
					
					# Files upload
					$files = isset($_FILES['files']) ? $_FILES['files'] : '';
					$newFileIds = $properties['fileIds'];
					if (is_array($files)) {
						for ($i = 0; $i < count($files['name']); $i++) {
							$newFileId = (int)$uploads->uploadFile($thisYearAlbum,
															  $files['name'][$i],
															  $files['tmp_name'][$i],
															  $files['size'][$i],
															  'static',
															  $userInfo->getId(),
															  0);
							if($newFileId>0) array_push($newFileIds, $newFileId);
						} # End for ($i =0;
						$properties['fileIds'] = $newFileIds;
					} # if (is_array($files)) {

					# Custom fields
					foreach ($fieldList as $field) {
						$properties[$field->getName()] = stripslashes($request->element($field->getName()));
					}

					$data = array(
						'store_id' => $storeId,
						'menu_id' => (int)$request->element('menu_id') ? (int)$request->element('menu_id') : 0,	
						'slug' => $slug,
						'keyword' => $request->element('keyword'),
						'title' => $request->element('title'),
						'description' => $request->element('description'),
						'detail' => $request->element('detail'),
						'status' => (int)$request->element('status'),
						'properties' => serialize($properties),
						'updater_id' => (int)$userInfo->getId(),
						'date_updated' => date("Y-m-d H:i:s")
					);

					$result = $statics->updateData($data, $id);

					# Custom Options
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
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_static'], $request->element('title')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

					# Redirect to editing page
					header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=static&mod=edit&lang=$lang&id=$id&rcode=7");
				}
				} # End if ($staticInfo) {
			} # End if(!$thisYearAlbum->getProperty('create_album_error')) {
		} # End if ($validate['invalid']) {
	} else { # Load static page information to edit
		$template->assign('item', $staticInfo);
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
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'), $amessages['keyword']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'), $amessages['description']);
	$error['INPUT']['detail'] = $validate->validString($request->element('detail'), $amessages['detail']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

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

	$error['invalid'] = 0;
	if ($error['INPUT']['slug']['error'] ||$error['INPUT']['title']['error'] || $error['INPUT']['keyword']['error'] || $error['INPUT']['description']['error'] || $error['INPUT']['detail']['error']) {
		$error['invalid'] = 1;
	}

	return $error;
}
