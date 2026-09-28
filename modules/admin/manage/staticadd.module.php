<?php
/*************************************************************************
Adding static module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Checked by: Mai Minh (15/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('static', 'add');

$templateFile = 'managestatic.tpl.html';
include_once(ROOT_PATH . 'classes/dao/static.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
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

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_static'] => '/' . ADMIN_SCRIPT . '?op=manage&act=static',
	$amessages['add_new_static'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=static';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => '',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Allow some javascript
$template->assign('ckEditor', 1);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='static'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='static'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Get menus array for generating nested combo
$arrayCategories = $menus->getObjectsForCombo();

# Category combo box
$categoryCombo = $menus->generateNestedCombo($arrayCategories, $request->element('menu_id'));
$template->assign('categoryCombo', $categoryCombo);

# Submitted form
if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	
	# Note for new file uploads:
	# Gallery root folder: ROOT_PATH.GALLERY_FOLDER."/".storeId."/".this_year
	# For example, for estore 1, all images in 2025 should be uploaded to folder:
	# /public_html/upload/1/2025
	# The system will check if album "this_year" exists. If not exists, it creates the album
	# The system also check if folder for this album is exists. If not exists, it also creates it
	# If the system cannot create folder, it returns permission error via template variable $albumCreateError
	# End note
	
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
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
		} else { # Valid data input
			# Check if duplicate slug
			$textFilter = new TextFilter();
			$slug = $textFilter->urlize($request->element('title'), false, '-');
			$i = 0;
			$dup = 1;
			
			# Add a number to the tail of slug if duplicate and loop until unique 
			while ($dup) {
				$dup = (int)$statics->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', '');
				if ($dup) $i++;
			}
			$slug .= $i ? '-' . $i : '';

			# Everything is ok. Add data to DB
			if (!$validate['invalid']) {
				$properties = array();
				
				# Avatar upload
				$fileAvatar = isset($_FILES['avatar']) ? $_FILES['avatar'] : '';
				# Only upload if uploaded file is image
				if(isImage($fileAvatar['name'])) {
					$newAvatarId = '';
					$newAvatarUrl = '';
					if($fileAvatar) {
						$newAvatarId = $uploads->uploadFile($thisYearAlbum,
															$fileAvatar['name'],
															$fileAvatar['tmp_name'],
															$fileAvatar['size'],
															'static',
															$userInfo->getId(),
															0);
						$newAvatarObject = $uploads->getObject($newAvatarId);
						if($newAvatarObject) {
							$newAvatarUrl = '/'.$newAvatarObject->getPath().'/'.$newAvatarObject->getUrlA();
						}
						
						# Properties
						$properties['avatarId'] = $newAvatarId;
						$properties['avatarUrl'] = $newAvatarUrl;
					}
				} # End if(isImage($fileAvatar['name'])) {
				
				# Files upload
				$files = isset($_FILES['files']) ? $_FILES['files'] : '';
				$newFileIds = [];
				if (is_array($files)) {
					for ($i = 0; $i < count($files['name']); $i++) {
						$newFileId = $uploads->uploadFile($thisYearAlbum,
														  $files['name'][$i],
														  $files['tmp_name'][$i],
														  $files['size'][$i],
														  'static',
														  $userInfo->getId(),
														  0);
						if($newFileId) array_push($newFileIds, $newFileId);
					} # End for ($i =0;
					
					# Properties
					$properties['fileIds'] = $newFileIds;
				}
				
				# End Files upload

				# Custom fields
				foreach ($fieldList as $field) {
					$properties[$field->getName()] = stripslashes($request->element($field->getName()));
				}

				# Add new satic page to database
				$data = array(
					'store_id' => $storeId,
					'menu_id' => (int)$request->element('menu_id') ? (int)$request->element('menu_id') : 0,
					'keyword' => $request->element('keyword'),
					'slug' => $slug,
					'title' => $request->element('title'),
					'description' => $request->element('description'),
					'detail' => $request->element('detail'),
					'status' => (int)$request->element('status'),
					'properties' => serialize($properties),
					'creator_id' => $userInfo->getId(),
					'date_created' => date("Y-m-d H:i:s")
				);
				$newId = $statics->addData($data);

				# Custom options
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
					} # End foreach ($fieldOptionList as $field) {
				} # End if ($newId) {

				#Add data search
				$newItem = $statics->getObject($newId, 'id');
				$url = '';
				if ($newItem); {
					$url = $newItem->getUrl();
				}

				$dataSearch = array(
					'store_id' => $storeId,
					'slug' => $slug,
					'title' => $request->element('title'),
					'keyword' => $request->element('keyword'),
					'sapo' => $request->element('description'),
					'detail' => $request->element('detail'),
					'status' => (int)$request->element('status'),
					'search_id' => (int)$newId,
					'type' => 'static',
					'url' => $url
				);
				$searchId = $search->addData($dataSearch);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_static'], $request->element('title')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=static&mod=list&rcode=6");
			} # End if (!$validate['invalid'])
		} # End if ($validate['invalid']) {
	} # End if(!$thisYearAlbum->getProperty('create_album_error')) {
} # End if ($_POST && $request->element('doo') == 'submit') { # if form is submitted

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['title'] = $validate->validString($request->element('title'), $amessages['title']);
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'), $amessages['keyword']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'), $amessages['description']);
	$error['INPUT']['detail'] = $validate->validString($request->element('detail'), $amessages['detail']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	# Paste value of custom fields
	global $fieldList;
	if(is_array($fieldList)) {
		foreach ($fieldList as $field) {
			$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
			if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
				$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
			} # End if ($field->getType() == 4
		} # End foreach ($fieldList as $field) {
	} # End if(is_array($fieldList)) {

	# Custom Options
	global $fieldOptionList;
	if(is_array($fieldOptionList)) {
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
			} # End if ((is_null($fieldValue)
		} # End foreach ($fieldOptionList as $field) {
	} # End if(is_array($fieldOptionList)) {
	$error['invalid'] = 0;
	if ($error['INPUT']['title']['error'] || $error['INPUT']['keyword']['error'] || $error['INPUT']['description']['error'] || $error['INPUT']['detail']['error']) {
		$error['invalid'] = 1;
	}

	return $error;
}
