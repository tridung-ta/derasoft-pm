<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
$userInfo->checkPermission('menu','add');
$templateFile = 'managemenu.tpl.html';
include_once(ROOT_PATH.'classes/dao/menucategories.class.php');
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH.'classes/dao/menus.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$menuCategories = new MenuCategories();
$menus = new Menus($storeId);
$fields = new Fields($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_menu'] => '/'.ADMIN_SCRIPT.'?op=manage&act=menu',
				$amessages['add_new'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=menu';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => '#',
				$amessages['list_menu_category'] => $tabLink.'&mod=listcategory',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);
# Allow some javascript
$template->assign('ckEditor',1);
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Category combo box
$categoryCombo = $menuCategories->generateCombo($request->element('cat_id'));
if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

# Get list of custom fields
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='menu'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='menu'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Menu combo box
$menuCombo = $menus->generateCombo($request->element('parent_id'));
if($menuCombo) $template->assign('menuCombo',$menuCombo);

if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);	
		# Category combo box
		$categoryCombo = $menuCategories->generateCombo($request->element('cat_id'));
		if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='menu'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);	
	} else { # Valid data input	
			$textFilter = new TextFilter();
			$slugVi = $textFilter->urlize($request->element('name'), false, '-');
			$i = 0;
			$dup = 1;
			
			# Add a number to the tail of slug if duplicate and loop until unique 
			while ($dup) {
				$dup = (int)$menus->checkDuplicate($slugVi . ($i ? '-' . $i : ''), 'slug_vi', '');
				if ($dup) $i++;
			}
			$slugVi .= $i ? '-' . $i : '';
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$properties = array('');

			# Upload avatar
			$userUpload = $userInfo->getId();

			$thisYearAlbum = getOrCreateYearUploadAlbum($storeId, $uploadAlbums);
			$avatarUploadId = uploadAvatar(
				$thisYearAlbum,
				$uploads,
				$userInfo,
				'logo',     // name input
				'menu'     // object
			);
			# Custom fields
			foreach($fieldList as $field) {
				$properties[$field->getName()] = $request->element($field->getName());
			}
			$data = array('store_id' => $storeId,
						  'parent_id' => $request->element('parent_id'),
						  'mc_id' => $request->element('cat_id'),
						  'route_id' => $request->element('route_id'),
						  'route_type' => $request->element('route_type'),
						  'name' => Filter($request->element('name')),
						  'position' => $request->element('position'),
						  'status' => $request->element('status'),
						  'url' => Filter($request->element('url')),
						  'slug_vi' => $slugVi,
						  'url_en' => Filter($request->element('url_en')),
						  'url_zh' => Filter($request->element('url_zh')),
						  'avatar' => (int)$avatarUploadId,
						  'properties' => serialize($properties),
						  'date_created' => date("Y-m-d H:i:s"));
			$newId = $menus->addData($data);
			
			// custom options
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
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_menu'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=menu&mod=list&cId=".$request->element('cat_id')."&pId=".$request->element('parent_id')."&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['parent_id'] = $validate->pasteString($request->element('parent_id'));
	$error['INPUT']['cat_id'] = $validate->pasteString($request->element('cat_id'));
	$error['INPUT']['route_type'] = $validate->pasteString($request->element('route_type'));
	$error['INPUT']['route_id'] = $validate->pasteString($request->element('route_id'));
	$error['INPUT']['position'] = $validate->validNumber($request->element('position'),$amessages['position']);
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['url'] = $validate->pasteString($request->element('url'));
	$error['INPUT']['url_en'] = $validate->validUrlPath(trim($request->element('url_en')),$amessages['url_en']);
	$error['INPUT']['url_zh'] = $validate->validUrlPath(trim($request->element('url_zh')),$amessages['url_zh']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

	$error['invalid'] = 0;
	if ($error['INPUT']['name']['error'] || $error['INPUT']['position']['error'] || $error['INPUT']['url_en']['error'] || $error['INPUT']['url_zh']['error']) {
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
?>