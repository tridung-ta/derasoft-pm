<?php
/*************************************************************************
Editing staff module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 16/06/2025
**************************************************************************/
# Check permission
$userInfo->checkPermission('pro_cat','edit');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH.'classes/dao/productcategories.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php"); 
$productCategories = new ProductCategories($storeId);
$fields = new Fields($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$gallery_root = ROOT_PATH . "upload/$storeId/";
$gallery_path = $gallery_root . "category/";
if ($fieldValue) $template->assign('fieldValue', $fieldValue);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_product'] => '/'.ADMIN_SCRIPT.'?op=manage&act=product',
				$amessages['edit_product_category'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=product';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['edit_product_category'] => '#',
	$amessages['list_product_features'] => $tabLink . '&mod=listfeature',
	$amessages['add_product_features'] => $tabLink . '&mod=addfeature',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 4);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productlistcategory'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Get product categories array for generating nested combo
// $arrayCategories = $productCategories->getObjectsForCombo();

// $productCategoriesCombo = $productCategories->generateNestedCombo(
//     [],                                
//     $request->element('list_parent_id'), 
//     0,                                                     
// );
// $template->assign('productCategoriesCombo', $productCategoriesCombo);



# Get product category info object
$id = $request->element('id');
if($id) $template->assign('id',$id);
$categoryInfo = $productCategories->getObject($id);

// $id là id danh mục đang edit (bạn đã có ở trên)
$selectedList = $request->element('list_parent_id'); // nếu submit lỗi sẽ có mảng ở đây

if (!$selectedList) {
    // load lần đầu: lấy list_parent_id hiện tại từ DB
    $row = $productCategories->select(
        'list_parent_id',
        "`store_id` = '".(int)$storeId."' AND id = '".(int)$id."'"
    );

    $csv = ($row && isset($row[0]['list_parent_id'])) ? $row[0]['list_parent_id'] : '';
    $selectedList = $csv !== ''
        ? preg_split('/[,\s]+/', $csv, -1, PREG_SPLIT_NO_EMPTY)  // -> mảng id
        : [];
}

// render combo: DAO generateNestedCombo đã tự JOIN nên mảng đầu vào bỏ qua
$productCategoriesCombo = $productCategories->generateNestedCombo(
    [],                 // không dùng
    $selectedList,      // mảng id đã chọn
    0,                  // tất cả nhóm cha
    '— '                // prefix cho con
);
$template->assign('productCategoriesCombo', $productCategoriesCombo);

if(!$categoryInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);

	# Delete avatar image
	if ($request->element('doo') == 'delAvatar') {
		$avatarId = $categoryInfo->getAvatarId(); // upload_id

		if ($avatarId) {
			$upload = $uploads->getObject($avatarId);
			if ($upload) {
				$upload->deleteFiles();
				$uploads->DeteImg($avatarId);
			}

			$productCategories->updateData([
				'avatar_id' => 0,
				'avatar_url' => ''
			], $id);
			$categoryInfo = $productCategories->getObject($id);
		}
	}

	# Get avatar image
	$avatarImg = null;
	if ($categoryInfo->getAvatarId()) {
		$avatarImg = $uploads->getObject($categoryInfo->getAvatarId());
	}
	$template->assign('avatarImg', $avatarImg);

	# Allow some javascript
	$template->assign('ckEditor',1);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Get list of custom fields
		$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='productcategories'",array('position' => 'ASC'));
		if($fieldList) $template->assign('fieldList',$fieldList);

		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productlistcategory'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
	
			// # Product categories Combo
			// $productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$request->element('parent_id'));
			// if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);

		} else { # Valid data input			
			# check duplicate category name
			if($productCategories->checkDuplicate($request->element('name'),'name',"`id` <> '$id'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			
			# Check if duplicate slug
			$textFilter = new TextFilter();
			$slug = $textFilter->urlize($request->element('slug'),false,'-');
			if($productCategories->checkDuplicate($slug,'slug',"`id` <> '$id'")) {
				$validate['INPUT']['slug']['message'] = $amessages['slug_duplicated'];
				$validate['INPUT']['slug']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			
			# handle parent_id
			$raw = $request->element('parent_id');              // có thể là null hoặc ''
			$parent_id = ($raw === null || $raw === '') ? 1 : (int)$raw;
			# handle list_parent_id
			$listIds = isset($_POST['list_parent_id']) ? $_POST['list_parent_id'] : [];
			if (!is_array($listIds)) $listIds = [$listIds]; 
			$listIds = array_unique(array_map('intval', $listIds));
			$list_parent_id = implode(',', $listIds);

			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$properties = $categoryInfo->getProperties();
				# Custom fields
				foreach($fieldList as $field) {
					$properties[$field->getName()] = $request->element($field->getName());
				}
				$properties['custom_landing_page_vn'] = $request->element('landing_page_vn');

				# Check if gallery folder is exists
				if (!file_exists($gallery_root)) mkdir("$gallery_root");
				if (!file_exists($gallery_path)) mkdir("$gallery_path");

				# get or create year upload album
				$thisYearAlbum = getOrCreateYearUploadAlbum($storeId, $uploadAlbums);

				# upload avatar
				$avatarUploadId = uploadAvatar(
					$thisYearAlbum,
					$uploads,
					$userInfo,
					'avatar',
					'product_category',
				);
				$avatarUploadUrl = '';
				if (!$avatarUploadId) {
					$avatarUploadId = $categoryInfo->getAvatarId();	
				} else {
					$avatarUpload = $uploads->getObject($avatarUploadId);
					if ($avatarUpload) {
						$avatarUploadUrl = "/" . $avatarUpload->getPath() . "/" . $avatarUpload->getUrlA();
					}
				}

				$data = array('store_id' => $storeId,
							'parent_id' => $parent_id,
							'list_parent_id' => $list_parent_id,
							'slug' => $slug,
							'name' => $request->element('name'),
							'position' => (int)$request->element('position'),
							'description' => $request->element('description'),
							'status' => (int)$request->element('status'),
							'avatar_id' => $avatarUploadId,
							'avatar_url' => $avatarUploadUrl,
							'properties' => serialize($properties),
							'date_updated' => date("Y-m-d H:i:s")
				);
				$result = $productCategories->updateData($data,$id);
				
				 // custom options
				if ($result) {
					foreach ($fieldOptionList as $field) {
						$fieldId = $field->getId();
						$valueType = $request->element($field->getFieldName());
						if ($field->getFieldType() == 4 || $field->getFieldType() == 7) {
							$selectedKeys = (array) $request->element($field->getFieldName());
							$options = $field->getValue();
							$selectedValues = array_map(function ($key) use ($options) {
								return (is_array($options) && isset($options[$key])) ? $options[$key] : $key;
							}, $selectedKeys);

							$valueType = implode(", ", $selectedValues);
						}
						if ($field->getFieldType() == 5 || $field->getFieldType() == 6) {
							$options = $field->getValue();
							$valueType = (is_array($options) && isset($options[$valueType])) ? $options[$valueType] : $valueType;
						}

						$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $id, $storeId);
					}
				} 

				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_product_category'],$productCategories->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=product&mod=editcategory&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load product category information to edit
			$template->assign('item',$categoryInfo);
	
			# Product categories Combo
			// $productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$categoryInfo->getParentId());
			// if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);
			
			# Get list of custom fields
			$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='productcategories'",array('position' => 'ASC'));
			if($fieldList) $template->assign('fieldList',$fieldList);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['parent_id'] = $validate->pasteString($request->element('parent_id'));
	$error['INPUT']['slug'] = $validate->validString($request->element('slug'),$amessages['slug']);
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	if($error['INPUT']['name']['error'] || $error['INPUT']['slug']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	if ($error['INPUT']['title']['error']) {
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