<?php
/*************************************************************************
Editing product module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 03/06/2025
Checked by: Mai Minh
**************************************************************************/
# Check permission

if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

$userInfo->checkPermission('product', 'edit');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/customproductoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php"); 
include_once(ROOT_PATH . "classes/dao/productfeatures.class.php");

$productFeatures = new ProductFeatures($storeId);
$customProductOptions = new CustomProductOptions($storeId);
$customProductOptionValues = new CustomProductOptionValues($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$productCategories = new ProductCategories($storeId);
$products = new Products($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$gallery_root = ROOT_PATH . "upload/$storeId/";
$gallery_path = $gallery_root . "products/";
$gallery_guide_path = $gallery_root . "guide/";
$gallery_content_path = $gallery_root . "products/contents/";

$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['edit_product'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['edit_item'] => '#',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_product_features'] => $tabLink . '&mod=listfeature',
	$amessages['add_product_features'] => $tabLink . '&mod=addfeature',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$pc = $request->element('pc');
$id = $request->element('id');
if ($id) $template->assign('id', $id);

$productInfo = $products->getObject($id);

# Get Custom Product Options
$listRawOptions = $customProductOptions->getOptionsWithValuesByProductId($id);
$listProductOptions = [];

foreach ($listRawOptions as $option) {
	$values = [];
	if (!empty($option['values'])) {
		foreach ($option['values'] as $val) {
			$values[] = [
				'value' => $val['value'],
				'price_modifier' => $val['price_modifier'],
			];
		}
	}
	$listProductOptions[] = [
		'id' => $option['id'],
		'name' => $option['name'],
		'values' => $values,
	];
}
$template->assign('listProductOptions', $listProductOptions);

# Get list of product features
$productFeatureList = $productFeatures->getObjects(1, "`status`='1'", array('id' => 'DESC'),999);
if ($productFeatureList) $template->assign('productFeatureList', $productFeatureList);

# Get product categories array for generating nested combo
$arrayCategories = $productCategories->getObjectsForCombo();

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Get all field values custom options
$allFieldValues = $fieldValue->getAllValuesByKeyId($id);
$template->assign('allFieldValues', $allFieldValues);

if (!$productInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);

	# Delete avatar image
	if ($request->element('doo') == 'delAvatar') {
		$avatarId = $productInfo->getAvatar(); // upload_id

		if ($avatarId) {
			$upload = $uploads->getObject($avatarId);
			if ($upload) {
				$upload->deleteFiles();
				$uploads->DeteImg($avatarId);
			}

			$products->updateData([
				'avatar' => 0
			], $id);
			$productInfo = $products->getObject($id);
		}
	}

	$language = $productInfo->getLang();
	$template->assign('language', explode(',', ($language)));

	# Delete attached file
	if ($request->element('doo') == 'delFile') {
		$fileId = (int)$request->element('file');

		if ($fileId) {
			$upload = $uploads->getObject($fileId);
			if ($upload) {
				$upload->deleteFiles();
				$uploads->DeteImg($fileId);
			}

			$fileIds = $productInfo->getFileIds();
			$ids = array_filter(array_map('intval', explode(',', $fileIds)));
			$ids = array_diff($ids, [$fileId]);

			$products->updateData([
				'file_ids' => implode(',', $ids)
			], $id);
			$productInfo = $products->getObject($id);
		}
	}

	# Delete gallery image
	if ($request->element('doo') == 'delGalleryFile') {
		$fileId = (int)$request->element('file');

		if ($fileId) {
			$upload = $uploads->getObject($fileId);
			if ($upload) {
				$upload->deleteFiles();
				$uploads->DeteImg($fileId);
			}

			$galleryImgIds = $productInfo->getGalleryImgIds();
			$ids = array_filter(array_map('intval', explode(',', $galleryImgIds)));
			$ids = array_diff($ids, [$fileId]);

			$products->updateData([
				'gallery_img_ids' => implode(',', $ids)
			], $id);
			$productInfo = $products->getObject($id);
		}
	}

	# Get avatar image
	$avatarImg = null;
	if ($productInfo->getAvatar()) {
		$avatarImg = $uploads->getObject($productInfo->getAvatar());
	}
	$template->assign('avatarImg', $avatarImg);

	# Get attached images
	$fileIds = $productInfo->getFileIds();
	$fileImgs = [];

	if ($fileIds) {
		$ids = array_filter(array_map('intval', explode(',', $fileIds)));

		foreach ($ids as $fileId) {
			$file = $uploads->getObject($fileId);
			if ($file) {
				$fileImgs[] = $file;
			}
		}
	}
	$template->assign('fileImgs', $fileImgs);

	# Get gallery images
	$galleryImgIds = $productInfo->getGalleryImgIds();
	$galleryImgs = [];

	if ($galleryImgIds) {
		$ids = array_filter(array_map('intval', explode(',', $galleryImgIds)));

		foreach ($ids as $galleryImgId) {
			$galleryImg = $uploads->getObject($galleryImgId);
			if ($galleryImg) {
				$galleryImgs[] = $galleryImg;
			}
		}
	}
	$template->assign('galleryImgs', $galleryImgs);

	# Allow some javascript
	$template->assign('ckEditor', 1);

	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted

		# Get list of custom fields
		$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
		if ($fieldList) $template->assign('fieldList', $fieldList);

		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form

			$template->assign('error', $validate);
			$productInfo = $products->getObject($id);
			$template->assign('itemInfo', $productInfo);

		# Product categories Combo
		$productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$request->element('category_id'));
		if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);
		} else { # Valid data input
			# Product categories Combo
			$productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$request->element('category_id'));
			if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);

			# check duplicate category name
			if ($estore->getProperty('check_duplicate_product_name')) {
				if ($products->checkDuplicate($request->element('name'), 'name', "`id` <> '$id' AND `category_id` = '" . $request->element('cat_id') . "'")) {
					$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
					$validate['INPUT']['name']['error'] = 1;
					$validate['invalid'] = 1;
					$template->assign('error', $validate);
				}
			}

			# Check if duplicate slug
			$textFilter = new TextFilter();
			$slug = $textFilter->urlize($request->element('slug'), false, '-');
			$i = 0;
			$dup = 1;
			while ($dup) {
				$dup = $products->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "`id` <> '$id' AND `category_id` = '" . $request->element('cat_id') . "'");
				if ($dup) $i++;
			}
			$slug .= $i ? '-' . $i : '';
			
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				$idUpdate = $request->element('id');
				$productInfo = $products->getObject($idUpdate);

				$currentAvatar = $request->element('current_avatar') ?: $productInfo->getAvatar();
				$newAvatar = $currentAvatar;

				if ($productInfo != 0) {
					$properties = $productInfo->getProperties();

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
						'product'
					);

					if (!$avatarUploadId) {
						$avatarUploadId = $productInfo->getAvatar();
					}

					# File upload
					$newFileIds = uploadFiles(
						$thisYearAlbum,
						$uploads,
						$userInfo,
						'files',
						'product'
					);

					# Get old file ids
					$oldFileIds = $productInfo->getFileIds();
					$oldFileIds = $oldFileIds ? explode(',', $oldFileIds) : [];
					$fileIds = array_merge($oldFileIds, $newFileIds ?: []);

					# Gallery images upload
					$newGalleryImgIds = uploadFiles(
						$thisYearAlbum,
						$uploads,
						$userInfo,
						'gallery_imgs',
						'product'
					);

					# Get old gallery image ids
					$oldGalleryImgIds = $productInfo->getGalleryImgIds();
					$oldGalleryImgIds = $oldGalleryImgIds ? explode(',', $oldGalleryImgIds) : [];
					$galleryImgIds = array_merge($oldGalleryImgIds, $newGalleryImgIds ?: []);

					#User update
					$properties['user_upload'] = $userInfo->getId();

					# Custom fields
					foreach ($fieldList as $field) {
						$value = $request->element($field->getName());
						if ($field->getType() == 4 || $field->getType() == 7) {
							# Listbox và Checkbox → giá trị là array, không dùng stripslashes
							$properties[$field->getName()] = $value;
						} else {
							$properties[$field->getName()] = stripslashes((string)$value);
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
						'date_updated' => date("Y-m-d H:i:s"),
						'viewed' => (int)$request->element('viewed') ? (int)$request->element('viewed') : 0,
						'position' => (int)$request->element('position'),
						'lang' => implode(',', (array)$request->element('language')),
						'properties' => serialize($properties),
						'avatar' => $avatarUploadId,
						'file_ids' => $fileIds ? implode(',', $fileIds) : '',
						'gallery_img_ids' => $galleryImgIds ? implode(',', $galleryImgIds) : '',
						'status' => (int)$request->element('status'),
						'price' => (float)$request->element('price'),
					);
					$productUpdateId = $products->updateData($data, $idUpdate);
					
					# PO
					if ($productUpdateId) {
						$optionNames = array_values($_POST['option_names'] ?? []);
						$valueNames = array_values($_POST['value_names'] ?? []);
						$valueModifiers = array_values($_POST['value_modifiers'] ?? []);
						$productName = Filter($request->element('name'));
						$dataOptionUpdate = [];

						foreach ($optionNames as $index => $optionName) {
							if (empty(trim($optionName))) continue;
							$values = $valueNames[$index] ?? [];
							$modifiers = $valueModifiers[$index] ?? [];

							$valueList = [];
							foreach ($values as $i => $value) {
								$modifier = isset($modifiers[$i]) ? floatval($modifiers[$i]) : 0.0;

								$valueList[] = [
									'value' => $value,
									'price_modifier' => $modifier,
								];
							}

							if (empty($valueList)) {
								continue;
							}

							$dataOptionUpdate[] = [
								'name' => $optionName,
								'values' => $valueList,
							];
						}
						$customProductOptions->updateOptionsByProductId($idUpdate, $productName, $dataOptionUpdate, $storeId);
					}

					# CO
					if ($productUpdateId) {
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

							$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $idUpdate, $storeId);
						}
					}
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_product'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
					
					# Redirect to editing page
					header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=edit&lang=$lang&id=$idUpdate&rcode=7");
				}
			}
		}
	} else { # Load product information to edit
		$template->assign('item', $productInfo);
		# Product categories Combo
		$productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$productInfo->getCategoryId());
		if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);

		# Get list of custom fields
		$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
		if ($fieldList) $template->assign('fieldList', $fieldList);

		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='product'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
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
	$error['INPUT']['slug'] = $validate->validString($request->element('slug'), $amessages['slug']);
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
	$error['INPUT']['language'] = array('value' => $request->element('language'), 'error' => 0, 'message' => '');
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
		if ($field->getType() == 4 || $field->getType() == 7) {
			# Listbox và Checkbox → giá trị là array, không cần stripslashes
			$error['INPUT'][$field->getName()] = [
				'value'   => $request->element($field->getName()),
				'error'   => 0,
				'message' => ''
			];
		} else {
			# Các field thường → xử lý bình thường
			$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		}
	}

	if ($error['INPUT']['name']['error'] || $error['INPUT']['keyword']['error'] || $error['INPUT']['slug_en']['error'] || $error['INPUT']['slug_zh']['error'] || $error['INPUT']['description']['error'] || $error['INPUT']['slug']['error'] || $error['INPUT']['detail']['error'] || $error['INPUT']['overview']['error']) {
		$error['invalid'] = 1;
		$error['message'] = '';
	}

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
