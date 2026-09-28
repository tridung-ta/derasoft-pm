<?php
/*************************************************************************
Adding product module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 10/05/2012
Checked by: Mai Minh (16/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('product', 'add');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/specifications.class.php');
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
// include_once(ROOT_PATH . "classes/dao/imgs.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
// $imgs = new Imgs();
$productaccessorys = new Productaccessorys($storeId);

$productOptions = new ProductOptions($storeId);
$productCategories = new ProductCategories($storeId);
$specifications = new Specifications($storeId);
$products = new Products($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);

# Allow some javascript
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";

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
	$amessages['list_item_lop'] => $tabLink . '&mod=list',
	$amessages['add_lop'] => $tabLink . '&mod=add',
	$amessages['list_item_pk'] => $tabLink . '&mod=listaccessory',
	$amessages['add_pk'] => $tabLink . '&mod=addaccessory',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_tramk'] => $tabLink . '&mod=listoption',
	$amessages['add_tramk'] => $tabLink . '&mod=addoption',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 4);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get product categories array for generating nested combo
$arrayCategories = $productCategories->getObjectsForCombo();

# Product categories Combo
# New generate combo from array that needs only one query
# It reduces number of queries, especially when we need to generate many combos
$productCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$request->element('cat_id'));
if($productCategoriesCombo) $template->assign('productCategoriesCombo',$productCategoriesCombo);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productaccessory'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productaccessory'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Allow some javascript
$template->assign('ckEditor', 1);
#danh sách thuong hiệu
$listproductOptions = $productOptions->getObjects(1, "`status`='1'", array('id' => 'ASC'), 9999);
if ($listproductOptions) $template->assign('listproductOptions', $listproductOptions);
#Thông số lỹ thuật
$specificationsList = $specifications->getObjects(1, "`status`='1'", array("name" => "ASC"), 9999);
$template->assign('specificationsList', $specificationsList);

#danh sách hãng xe con
// $CproductOptions = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = '29'", array("id" => "ASC"), 9999);
// $arrayproductOptions = [];
// foreach ($CproductOptions as $value) {
// 	$item['name'] = $value->getName();
// 	$item['id'] = $value->getId();
// 	$item['CproductOptions2'] = [];
// 	$id = $value->getId();
// 	$CproductOptions2 = $productOptions->getObjects(1, "`status` = '1' AND `pc_id`= '29' AND `cat_id` = '$id'", array("id" => "ASC"), 9999);
// 	if ($CproductOptions2) {
// 		foreach ($CproductOptions2 as $value2) {
// 			$item2['name'] = $value2->getName();
// 			$item2['id'] = $value2->getId();
// 			array_push($item['CproductOptions2'], $item2);
// 		}
// 	}
// 	array_push($arrayproductOptions, $item);
// }
// $template->assign('arrayproductOptions', $arrayproductOptions);

#

if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
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
	$listidcar = $request->element("listidcar");
	if (empty($listcatid)) {
		$properties['text_carcompany'] = $request->element("text_carcompany");
	}
	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
	} else { # Valid data input
		# check duplicate product name
		if ($estore->getProperty('check_duplicate_product_name')) {
			if ($productaccessorys->checkDuplicate($request->element('name'), 'name', "cat_id = '" . $request->element('cat_id') . "'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error', $validate);
			}
		}
		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('name'), false, '-');
		$i = 0;
		$dup = 1;
		while ($dup) {
			$dup = $productaccessorys->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "cat_id = '" . $request->element('cat_id') . "'");
			if ($dup) $i++;
		}
		$slug .= $i ? '-' . $i : '';
		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			$properties = array('');
			# Check if gallery folder is exists
			if (!file_exists($gallery_root)) mkdir("$gallery_root");
			if (!file_exists($gallery_path)) mkdir("$gallery_path");
			# User upload
			$userUpload = $userInfo->getId();
			// #File Avatar
			// $fileAvatr = isset($_FILES['avatar']) ? $_FILES['avatar'] : '';
			// if ($fileAvatr) {
			// 	$img = addslashes(Filter(rand() . "_" . $fileAvatr['name']));
			// 	$tmp_img = $fileAvatr['tmp_name'];
			// 	$size = $fileAvatr['size'];
			// 	$type = strtolower(substr($img, -3));
			// 	if (preg_match("/" . ALLOW_FILE_TYPES . "/", strtolower($img))) {
			// 		# Upload
			// 		if (isImage($img)) {
			// 			$new_img = $img;
			// 			move_uploaded_file($tmp_img, $gallery_path . 'l_' . $img);
			// 			if (isBmp($img)) $new_img = preg_replace("/(bmp$)/", "jpg", $img);
			// 			resize($gallery_path, $gallery_path, 'l_' . $img, 'l_' . $new_img, DEFAULT_LARGE_SIZE, DEFAULT_LARGE_SQUARE, DEFAULT_PHOTO_QUALITY);
			// 			resize($gallery_path, $gallery_path, 'l_' . $img, 'a_' . $new_img, DEFAULT_AVATAR_SIZE, DEFAULT_AVATAR_SQUARE, DEFAULT_PHOTO_QUALITY);
			// 			if (CREATE_PRODUCT_AVATAR_CORNER) imageCreateCorners($gallery_path . 'a_' . $new_img, 9);
			// 			resize($gallery_path, $gallery_path, 'l_' . $img, 'm_' . $new_img, DEFAULT_MEDIUM_SIZE, DEFAULT_MEDIUM_SQUARE, DEFAULT_PHOTO_QUALITY);
			// 			resize($gallery_path, $gallery_path, 'l_' . $img, 't_' . $new_img, DEFAULT_THUMBNAIL_SIZE, DEFAULT_THUMBNAIL_SQUARE, DEFAULT_PHOTO_QUALITY);
			// 			if ($img != $new_img) unlink($gallery_path . 'l_' . $img);	# Delete file if it's not a JPEG
			// 			$avatar = $new_img;
			// 		}
			// 	} #/if (preg_match
			// }
			# Files upload
			$files = isset($_FILES['files']) ? $_FILES['files'] : '';
			if ($files) {
				$dataIdImg = [];
				for ($i = 0; $i < count($files['name']); $i++) {
					if($files['name'][$i] != '') {
						$textFilter = new TextFilter();
						$last_dot_position = strrpos($files['name'][$i], '.');
						// Cắt chuỗi từ đầu đến vị trí của dấu chấm cuối cùng (không bao gồm dấu chấm)
						if ($last_dot_position !== false) {
							$filename_without_extension = substr($files['name'][$i], 0, $last_dot_position);
						}
						// Lấy 3 ký tự cuối của tên tệp
						$type = strtolower(substr($files['name'][$i], -3));
						// Loại bỏ dấu tiếng Việt và chuyển đổi khoảng trắng thành dấu "-"
						$file_name_normalized = str_replace(' ', '-', strtolower($textFilter->convert_vi_to_en($filename_without_extension)));
						$imgl = addslashes(Filter($file_name_normalized . "_l_" . rand() . "." . $type));
						$imga = addslashes(Filter($file_name_normalized . "_a_" . rand() . "." . $type));
						$tmp_img = $files['tmp_name'][$i];
						$size = $files['size'][$i];
						# Upload
						$new_imgl = $imgl;
						$new_imga = $imga;
						move_uploaded_file($tmp_img, $gallery_path . $imgl);
						if (isBmp($imgl)) $new_imgl = preg_replace("/(bmp$)/", "jpg", $imgl);
						resize($gallery_path, $gallery_path, $imgl, $new_imgl, DEFAULT_LARGE_SIZE, DEFAULT_LARGE_SQUARE, DEFAULT_PHOTO_QUALITY);
						resize($gallery_path, $gallery_path, $imgl, $new_imga, DEFAULT_AVATAR_SIZE, DEFAULT_AVATAR_SQUARE, DEFAULT_PHOTO_QUALITY);
						if ($imgl != $new_imgl) unlink($gallery_path . $imgl);    # Delete file if it's not a JPEG
						if ($imga != $new_imga) unlink($gallery_path . $imga);    # Delete file if it's not a JPEG
						#xóa đuôi .
						$file_parts = explode('.', $files['name'][$i]);
						// Lấy tất cả các phần trừ phần mở rộng
						$file_name_without_extension = implode('.', array_slice($file_parts, 0, -1));
						$data = array(
							"url_l" => $imgl,
							"url_a" => $imga,
							"status" => 1,
							"store_id" => 1,
							"name" => $file_name_without_extension,
						);
						$newIdImg = $imgs->addData($data);
						if ($newIdImg) {
							array_push($dataIdImg, $newIdImg);
						}
					} # Enf if($files['name'][$i] != '') {
				} # End for ($i = 0;
			}
			$properties = array(
				// 'avatar' => $avatar,
				'photos' => $dataIdImg,
				'videos' => $uvideos,
				'files' => $ufiles,
				'user_upload' => $userUpload,
				'listidcar' => $listidcar,
			);

			# Custom fields
			foreach ($fieldList as $field) {
				$properties[$field->getName()] = stripslashes($request->element($field->getName()));
			}

			$data = array(
				'store_id' => $storeId,
				'cat_id' => (int)$request->element('cat_id'),
				'slug' => $slug,
				'name' => Filter($request->element('name')),
				'keyword' => Filter($request->element('keyword')),
				'position' => (int)$request->element('position'),
				'status' => (int)$request->element('status'),
				'viewed' => (int)$request->element('viewed'),
				'description' => $request->element('description'),
				'detail' => $request->element('detail'),
				'properties' => serialize($properties),
				'trademark' => (int)$request->element('trademark'),
				'carcompany' => $listcatid,
				'origin' => (int)$request->element('origin'),
				'uses' => $request->element('usess'),
				'guarantee' => $request->element('warranty_policy'),
				'spcode' => $request->element('spcode'),
				'specifications' => $request->element('specifications'),
				'size' => $request->element('sizepk'),
				'tag' => $request->element('tag'),
				'price' => (int)$request->element('price'),
				'market_price' => (int)$request->element('market_price'),
				'techno' => $request->element('techno'),
				'pileloca' => $request->element('pileloca'),
				'segment' => $request->element('segment'),
				'created' => date("Y-m-d H:i:s")
			);

			$newId = $productaccessorys->addData($data);

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
						'field_value' => $valueType,
						'status' => 1,
					);
					$newFieldValue = $fieldValue->addData($fieldData);
				}
			}

			$dataSearch = array(
				"search_id" => $newId,
				"slug" => $slug,
				"title" => Filter($request->element('name')),
				"type" => "productaccessorys",
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
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listaccessory&pId=" . $request->element('cat_id') . "&rcode=6");
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
	$error['INPUT']['cat_id'] = $validate->pasteString($request->element('cat_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'), $amessages['keyword']);
	$error['INPUT']['description'] = $validate->pasteString($request->element('detail'), $amessages['description']);
	$error['INPUT']['detail'] = $validate->pasteString($request->element('detail'), $amessages['detail']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['viewed'] = $validate->pasteString($request->element('viewed'));
	$error['INPUT']['list_carcompany'] = $validate->pasteString($request->element('list_carcompany'));

	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

	if ($error['INPUT']['name']['error']) {
		$error['invalid'] = 1;
		$error['message'] = '';
		return $error;
	}
	$error['invalid'] = 0;

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
