<?php
// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);
    // }

$userInfo->checkPermission('product', 'add');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
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
$productCategories = new ProductCategories($storeId);
$products = new Products($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);

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
	// $amessages['list_product_features'] => $tabLink . '&mod=listfeature',
	// $amessages['add_product_features'] => $tabLink . '&mod=addfeature',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get product categories array for generating nested combo
$arrayCategories = $productCategories->getObjectsForCombo();
// var_dump($arrayCategories);die;

# Product categories Combo
$productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$request->element('category_id'));
// var_dump($productCategoriesCombo);die;
if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);

# Brand
$productRootCategories = $productCategories->generateRootCombo($request->element('brand_id') ?: null);
if($productRootCategories) $template->assign('productRootCategories',$productRootCategories);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
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
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
	} else { # Valid data input
		# check duplicate product name
		// if ($estore->getProperty('check_duplicate_product_name')) {
			if ($products->checkDuplicate($request->element('name'), 'name', "category_id = '" . $request->element('category_id') . "'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error', $validate);
			}
		// }
		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('name'), false, '-');
		$i = 0;
		$dup = 1;
		while ($dup) {
			$dup = $products->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "category_id = '" . $request->element('category_id') . "'");
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
				'product'     // object
			);

			$fileIds = uploadFiles(
				$thisYearAlbum,
				$uploads,
				$userInfo,
				'files',
				'product'
			);	
			
			$galleryImgIds = uploadFiles(
				$thisYearAlbum,
				$uploads,
				$userInfo,
				'gallery_imgs',
				'product'
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
				$value = $request->element($field->getName());
				
				if ($field->getType() == 4 || $field->getType() == 7) { // Listbox và Checkbox
					$properties[$field->getName()] = $value; // Giữ nguyên mảng
				} else {
					$properties[$field->getName()] = stripslashes($value); // Xử lý chuỗi bình thường
				}
			}
			$data = array(
				'store_id' => $storeId,
				'category_id' => (int)$request->element('category_id'),
				'slug' => $slug,
				'slug_en' => $request->element('slug_en'),
				'slug_zh' => $request->element('slug_zh'),
				'name' => $request->element('name'),
				'keyword' => $request->element('keyword'),
				'overview' => $request->element('overview'),
				'description' => $request->element('description'),
				'detail' => $request->element('detail'),
				'avatar' => $avatarUploadId,
				'file_ids' => implode(',', $fileIds),
				'gallery_img_ids' => implode(',', $galleryImgIds),
				'date_created' => date("Y-m-d H:i:s"),
				'viewed' => (int)$request->element('viewed') ? (int)$request->element('viewed') : 0,
				'position' => (int)$request->element('position') ? (int)$request->element('position') : 0,
				'lang' => implode(',', (array)$request->element('language')),
				'properties' => serialize($properties),
				'status' => (int)$request->element('status'),
				'price' => (float)$request->element('price'),
			);
			$newId = $products->addData($data);
			
			# Start PO
			if ($newId) {
				$optionNames = ($_POST['option_names'] ?? []);
				$valueNames = $_POST['value_names'] ?? [];
				$valueModifiers = $_POST['value_modifiers'] ?? [];

				foreach ($optionNames as $index => $optionName) {
					if (empty(trim($optionName))) continue;

					$productOptionData = [
						'store_id' => $storeId,
						'product_id' => $newId,
						'name' => $optionName,
						'product' => Filter($request->element('name')),
						'status' => 1
					];

					$customProductOptionId = $customProductOptions->addData($productOptionData);

					$values = $valueNames[$index] ?? [];
					$modifiers = $valueModifiers[$index] ?? [];
					foreach ($values as $j => $value) {
						if (empty($value)) continue;

						$modifier = isset($modifiers[$j]) ? (float)$modifiers[$j] : 0.0;

						$productOptionValueData = [
							'store_id' => $storeId,
							'option_id' => $customProductOptionId,
							'value' => $value,
							'price_modifier' => $modifier,
							'status' => 1
						];

						$customProductOptionValues->addData($productOptionValueData);
					}
				}
			}	

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
			$newItem = $products->getObject($newId, 'id');
			$url = '';
			if ($newItem) {
				$url = $newItem->getSlug();
			}

			$dataSearch = array(
				"search_id" => (int)$newItem,
				"slug" => $slug,
				"title" => Filter($request->element('name')),
				"type" => "product",
				"status" => 1,
				"sapo" => addslashes($request->element('description')),
				"detail" => addslashes($request->element('detail')),
				"store_id" => 1,
				"keyword" => Filter($request->element('keyword')),
				"tag" => $request->element('tag'),
				"url" => $slug,
			);

			$searchId = $search->addData($dataSearch);
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_product'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=list&filter_categories=" . $request->element('category_id') . "&rcode=6");
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

	$error['INPUT']['category_id'] = $validate->pasteString($request->element('category_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'), $amessages['keyword']);
	// Ngôn ngữ được chọn
	$chooseLang = $request->element('language');

	if (!is_array($chooseLang)) {
		$chooseLang = [];
	}

	// Mặc định slug_en không có lỗi
	$error['INPUT']['slug_en'] = [
		'value' => $request->element('slug_en'),
		'error' => 0,
		'message' => ''
	];

	// Mặc định slug_zh không có lỗi
	$error['INPUT']['slug_zh'] = [
		'value' => $request->element('slug_zh'),
		'error' => 0,
		'message' => ''
	];

	// Tiếng Anh
	if (in_array('en', $chooseLang)) {
		$error['INPUT']['slug_en'] = $validate->validSlug(
			htmlspecialchars($request->element('slug_en')),
			$amessages['slug_en']
		);
	}

	// Tiếng Hoa
	if (in_array('zh', $chooseLang)) {
		$error['INPUT']['slug_zh'] = $validate->validSlug(
			htmlspecialchars($request->element('slug_zh')),
			$amessages['slug_zh']
		);
	}
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['overview'] = $validate->pasteString($request->element('overview'), $amessages['overview']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'), $amessages['description']);
	$error['INPUT']['detail'] = $validate->validString($request->element('detail'), $amessages['detail']);
	$error['INPUT']['expiration_date'] = $validate->pasteString($request->element('expiration_date'));
	$error['INPUT']['price'] = $validate->pasteString($request->element('price'));



	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$isArrayField = ($field->getType() == 4 || $field->getType() == 7); // Listbox và Checkbox

		if ($isArrayField) {
			// Không cần pasteString, lấy thẳng giá trị mảng
			$error['INPUT'][$field->getName()] = [];
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		} else {
			// Các trường thông thường → xử lý chuỗi bình thường
			$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		}
	}

	if ($error['INPUT']['name']['error'] || $error['INPUT']['keyword']['error'] || $error['INPUT']['slug_en']['error'] || $error['INPUT']['slug_zh']['error'] || $error['INPUT']['description']['error'] || $error['INPUT']['detail']['error'] || $error['INPUT']['overview']['error'] ) {
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
