<?php
// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);
    // }

$userInfo->checkPermission('product', 'addfeature');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/customproductoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptiondefault.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/productfeatures.class.php");

$productFeatures = new ProductFeatures($storeId);
$customProductOptions = new CustomProductOptions($storeId);
$customProductOptionValues = new CustomProductOptionValues($storeId);
$customProductOptionDefault = new CustomProductOptionDefault($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['add_new_product'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_product_features'] => $tabLink . '&mod=listfeature',
	$amessages['add_product_features'] => $tabLink . '&mod=addfeature',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 6);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productfeatures'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productfeatures'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Allow some javascript
$template->assign('ckEditor', 1);

# Default product options
$valueDefault = $customProductOptionDefault->getAllNamesAndValueDefault();
if ($valueDefault) $template->assign('valueDefault', $valueDefault);

# Submitted form
if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
		
		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productfeatures'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
	} else { # Valid data input
		# check duplicate product name
        if ($productFeatures->checkDuplicate($request->element('name'), 'name' )) {
            $validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
            $validate['INPUT']['name']['error'] = 1;
            $validate['invalid'] = 1;
            $template->assign('error', $validate);
        }
		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('name'), false, '-');
		$i = 0;
		$dup = 1;
		while ($dup) {
			$dup = $productFeatures->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug');
			if ($dup) $i++;
		}
		$slug .= $i ? '-' . $i : '';
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
				'avatar',     // name input
				'productfeature'     // object
			);

			$fileIds = uploadFiles(
				$thisYearAlbum,
				$uploads,
				$userInfo,
				'files',
				'productfeature'
			);						

			$properties = array(
				// 'avatar' => $avatar,
				// 'photos' => $uphotos,
				// 'videos' => $uvideos,
				// 'files' => $ufiles,
				'user_upload' => $userUpload,
			);

			# Custom fields
			foreach ($fieldList as $field) {
				$properties[$field->getName()] = stripslashes($request->element($field->getName()));
			}
			$data = array(
				'store_id' => $storeId,
                'name' => $request->element('name'),
                'slug' => $slug,
                'status' => (int)$request->element('status'),
                'avatar' => $avatarUploadId,
                'description' => $request->element('description'),
                'pid' => (int)$request->element('pid') ? (int)$request->element('pid') : NULL,
                'date_created' => date("Y-m-d H:i:s"),
				'properties' => serialize($properties)
			);
			$newId = $productFeatures->addData($data);	

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

			#Add data search
			$newItem = $productFeatures->getObject($newId, 'id');
			$url = '';
			if ($newItem) {
				$url = $newItem->getSlug();
			}

			$dataSearch = array(
				"search_id" => $newId,
				"slug" => $slug,
				"title" => Filter($request->element('name')),
				"type" => "productfeature",
				"status" => 1,
				"sapo" => addslashes($request->element('description')) ? addslashes($request->element('description')) : '',
				"detail" => addslashes($request->element('detail')) ? addslashes($request->element('detail')) : '',
				"store_id" => 1,
				"keyword" => Filter($request->element('keyword')) ? Filter($request->element('keyword')) : '',
				"tag" => $request->element('tag') ? $request->element('tag') : '',
				"url" => $slug,
			);

			$searchId = $search->addData($dataSearch);
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_productfeature'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listfeature&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['invalid'] = 0;

	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['description'] = $validate->pasteString($request->element('description'), $amessages['description']);

	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

	if ($error['INPUT']['name']['error'] || $error['INPUT']['status']['error'] ) {
		$error['invalid'] = 1;
		$error['message'] = '';
	}

	# Custom Options
	global $fieldOptionList;
	$fieldOptionList = is_iterable($fieldOptionList) ? $fieldOptionList : [];
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
