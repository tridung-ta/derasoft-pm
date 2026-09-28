<?php
$userInfo->checkPermission('menu','edit');
$templateFile = 'managemenu.tpl.html';
include_once(ROOT_PATH.'classes/dao/menucategories.class.php');
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH.'classes/dao/menus.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$productOptions = new ProductOptions(1);
$menuCategories = new menuCategories();
$fields = new Fields($storeId);
$menus = new Menus($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_menu'] => '/'.ADMIN_SCRIPT.'?op=manage&act=menu',
				$amessages['edit_item'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=menu';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_menu'] => '#',
				$amessages['list_menu_category'] => $tabLink.'&mod=listcategory',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='menu'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Allow some javascript
$template->assign('ckEditor',1);
# Get list of custom fields
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='menu'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);
#
$listproductOptions = $productOptions->getObjects(1, "`status`='1'", array('id' => 'ASC'), 9999);
if ($listproductOptions) $template->assign('listproductOptions', $listproductOptions);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$menuInfo = $menus->getObject($id);
$listCatId1 = $menuInfo->getProperty('company_id');

# Get all field values
$allFieldValues = $fieldValue->getAllValuesByKeyId($id);
$template->assign('allFieldValues', $allFieldValues);

# Get avatar image
$avatarImg = null;
if ($menuInfo->getAvatar()) {
    $avatarImg = $uploads->getObject($menuInfo->getAvatar());
}
$template->assign('avatarImg', $avatarImg);

# ================= DELETE AVATAR =================
if ($request->element('doo') == 'delAvatar') {
    $avatarId = $menuInfo->getAvatar(); // upload_id

    if ($avatarId) {
        $upload = $uploads->getObject($avatarId);
        if ($upload) {
            $upload->deleteFiles();
            $uploads->DeteImg($avatarId);
            }
            
            $menus->updateData([
                'avatar' => 0
                ], $id);
        $menuInfo = $menus->getObject($id);
    }
}

if ($listCatId1 && $listCatId1 != '') {
	$arraylistCat1 = explode(",", $listCatId1);
	if ($arraylistCat1) $template->assign('arraylistCat1', $arraylistCat1);
	//var_dump($arraylistCat1);
}
if(!$menuInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);
	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		
		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='menu'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
		
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			# Category combo box
			$categoryCombo = $menuCategories->generateCombo($request->element('cat_id'));
			if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);
			# Menus combo box
			$menuCombo = $menus->generateCombo($request->element('parent_id',0));
			if($menuCombo) $template->assign('menuCombo',$menuCombo);
		} else { # Valid data input
			$textFilter = new TextFilter();
			$slugVi = $textFilter->urlize($request->element('name'), false, '-');
			$i = 0;
			$dup = 1;
			while ($dup) {
				$dup = $menus->checkDuplicate($slugVi . ($i ? '-' . $i : ''), 'slug_vi', "`id` <> '$id' ");
				if ($dup) $i++;
			}
			$slugVi .= $i ? '-' . $i : '';
			# Category combo box
			$categoryCombo = $menuCategories->generateCombo($request->element('cat_id'));
			if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);
			# Menu combo box
			$menuCombo = $menus->generateCombo($request->element('parent_id',0));
			if($menuCombo) $template->assign('menuCombo',$menuCombo);
					
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$menuInfo = $menus->getObject($id);
				if($menuInfo) {			
					
					$properties = $menuInfo->getProperties();
					# Upload album
					$thisYearAlbum = getOrCreateYearUploadAlbum($storeId, $uploadAlbums);

					# Avatar upload
					$avatarUploadId = uploadAvatar(
						$thisYearAlbum,
						$uploads,
						$userInfo,
						'logo',
						'menu'
					);
					# Custom fields
					foreach($fieldList as $field) {
						$properties[$field->getName()] = $request->element($field->getName());
					}
					$list_cat_id = array();
					foreach ($request->element("list_carcompany") as $route_id) {
						if ($route_id > 0) {
							array_push($list_cat_id, $route_id);
						}
					}
					if ($list_cat_id) {
						$listcatid = implode(",", $list_cat_id);
					} else {
						$listcatid = '';
					}
					$properties['company_id'] = $listcatid;
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
								  'avatar' => $avatarUploadId ? (int)$avatarUploadId : (int)$menuInfo->getAvatar(),
								  'properties' => serialize($properties),
								  'date_updated' => date("Y-m-d H:i:s"));
					$result = $menus->updateData($data,$id);
					
					// custom options
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
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_menu'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=menu&mod=edit&lang=$lang&id=$id&rcode=7");
				}
			}
		}
	} else { # Load product category information to edit
		$template->assign('item',$menuInfo);
		# Category combo box
		$categoryCombo = $menuCategories->generateCombo($menuInfo->getCId());
		if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);
		
		# Menu combo box
		$menuCombo = $menus->generateCombo($menuInfo->getParentId());
		if($menuCombo) $template->assign('menuCombo',$menuCombo);
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
	$error['INPUT']['route_id'] = $validate->pasteString($request->element('route_id'));
	$error['INPUT']['route_type'] = $validate->pasteString($request->element('route_type'));
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