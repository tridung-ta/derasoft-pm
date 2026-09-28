<?php

/*************************************************************************
Editing product module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 09/05/2012
Checked by: Mai Minh (10/05/2012)
 **************************************************************************/
$userInfo->checkPermission('product', 'edit');
$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/productsize.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/specifications.class.php');
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
$imgs = new Imgs();
$template->assign('imgs', $imgs);
$productaccessorys = new Productaccessorys($storeId);
$productCategories = new ProductCategories($storeId);
$specifications = new Specifications($storeId);
$products = new Products($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$productsize = new ProductSize();
$productOptions = new ProductOptions(1);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
if ($fieldValue) $template->assign('fieldValue', $fieldValue);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['edit_product'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
	$amessages['list_item_lop'] => $tabLink . '&mod=list',
	$amessages['edit_item'] => $tabLink . '&mod=add',
	$amessages['list_item_pk'] => $tabLink . '&mod=listaccessory',
	$amessages['edit_item'] => '#',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_tramk'] => $tabLink . '&mod=listoption',
	$amessages['add_tramk'] => $tabLink . '&mod=addoption',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='productaccessory'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
$pc = $request->element('pc');
if ($id) $template->assign('id', $id);

$productInfo = $productaccessorys->getObject($id);
#lấy tên các hãng xe đã chọn
$idCCar = $productInfo->getCarcompany();
if ($idCCar && $idCCar != " " && $idCCar != "Gốc") {
	$listPOselected = $productOptions->getObjects(1, "`status`='1' AND `id` IN ($idCCar)", array('id' => 'ASC'), 9999);
	$arrayPOselected = [];
	if ($listPOselected) {
		foreach ($listPOselected as $value) {
			$CatId = $value->getCatid();

			$NamePO = $productOptions->getNameFromId($CatId);
			array_push($arrayPOselected, $NamePO);
		}
		$uniqueArray = implode(',', array_unique($arrayPOselected));;
		$template->assign('uniqueArray', $uniqueArray);
	}
}
#
#Thông số lỹ thuật
$specificationsList = $specifications->getObjects(1, "`status`='1'", array("name" => "ASC"), 9999);
$template->assign('specificationsList', $specificationsList);
$listproductOptions = $productOptions->getObjects(1, "`status`='1'", array('id' => 'ASC'), 9999);
if ($listproductOptions) $template->assign('listproductOptions', $listproductOptions);
// #danh sách hãng xe con
// $CproductOptions = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = '29'", array("id" => "ASC"), 9999);
// if ($CproductOptions) {
// 	$arrayproductOptions = [];
// 	foreach ($CproductOptions as $value) {
// 		$item['name'] = $value->getName();
// 		$item['id'] = $value->getId();
// 		$item['CproductOptions2'] = [];
// 		$idCproductOptions = $value->getId();
// 		$CproductOptions2 = $productOptions->getObjects(1, "`status` = '1' AND `pc_id`= '29' AND `cat_id` = '$idCproductOptions'", array("id" => "ASC"), 9999);
// 		if ($CproductOptions2) {
// 			foreach ($CproductOptions2 as $value2) {
// 				$item2['name'] = $value2->getName();
// 				$item2['id'] = $value2->getId();
// 				array_push($item['CproductOptions2'], $item2);
// 			}
// 		}
// 		array_push($arrayproductOptions, $item);
// 	}
// 	$template->assign('arrayproductOptions', $arrayproductOptions);
// }
#

if (!$productInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	if ($request->element('doo') == 'delPhoto') {
		$properties = $productInfo->getProperties();
		foreach ($properties['photos'] as $key => $value) {
			if ($value == $request->element('photo')) {
				unset($properties['photos'][$key]);
				$data = array('properties' => serialize($properties));
				$productaccessorys->updateData($data, $id);
				$productInfo = $productaccessorys->getObject($id);
				break;
			}
		}
	}

	# Allow some javascript
	$template->assign('ckEditor', 1);
	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Get list of custom fields
		$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productaccessory'", array('position' => 'ASC'));
		if ($fieldList) $template->assign('fieldList', $fieldList);

		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
			$productInfo = $productaccessorys->getObject($id);
			$template->assign('itemInfo', $productInfo);

			# Category combo box
			$categoryCombo = $productCategories->generateCombo($request->element('cat_id', 0));
			if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
		} else { # Valid data input
			# Category combo box
			$categoryCombo = $productCategories->generateCombo($request->element('cat_id', 0));
			if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

			# check duplicate category name
			if ($estore->getProperty('check_duplicate_product_name')) {
				if ($productaccessorys->checkDuplicate($request->element('name'), 'name', "`id` <> '$id' AND `cat_id` = '" . $request->element('cat_id') . "'")) {
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
				$dup = $productaccessorys->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "`id` <> '$id' AND `cat_id` = '" . $request->element('cat_id') . "'");
				if ($dup) $i++;
			}
			$slug .= $i ? '-' . $i : '';
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				$idUpdate = $request->element('id');
				$productInfo = $productaccessorys->getObject($idUpdate);
				if ($productInfo != 0) {
					$properties = $productInfo->getProperties();

					# Check if gallery folder is exists
					if (!file_exists($gallery_root)) mkdir("$gallery_root");
					if (!file_exists($gallery_path)) mkdir("$gallery_path");

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
								if (isBmp($img)) $new_img = preg_replace("/(bmp$)/", DEFAULT_PHOTO_FORMAT, $img);
								resize($gallery_path, $gallery_path, 'l_' . $img, 'l_' . $new_img, DEFAULT_LARGE_SIZE, DEFAULT_LARGE_SQUARE, DEFAULT_PHOTO_QUALITY);
								resize($gallery_path, $gallery_path, 'l_' . $img, 'a_' . $new_img, DEFAULT_AVATAR_SIZE, DEFAULT_AVATAR_SQUARE, DEFAULT_PHOTO_QUALITY);
								if (CREATE_PRODUCT_AVATAR_CORNER) imageCreateCorners($gallery_path . 'a_' . $new_img, 9);
								resize($gallery_path, $gallery_path, 'l_' . $img, 'm_' . $new_img, DEFAULT_MEDIUM_SIZE, DEFAULT_MEDIUM_SQUARE, DEFAULT_PHOTO_QUALITY);
								resize($gallery_path, $gallery_path, 'l_' . $img, 't_' . $new_img, DEFAULT_THUMBNAIL_SIZE, DEFAULT_THUMBNAIL_SQUARE, DEFAULT_PHOTO_QUALITY);
								if ($img != $new_img) unlink($gallery_path . 'l_' . $img);	# Delete file if it's not a JPEG
								if ($productInfo->getProperty('avatar')) {
									unlink($gallery_path . 'a_' . $productInfo->getProperty('avatar'));
									unlink($gallery_path . 't_' . $productInfo->getProperty('avatar'));
									unlink($gallery_path . 'm_' . $productInfo->getProperty('avatar'));
									unlink($gallery_path . 'l_' . $productInfo->getProperty('avatar'));
								}
								$properties['avatar'] = $new_img;
							}
						} #/if (preg_match
					}
					# Files upload
					# Files upload
					$files = isset($_FILES['files']) ? $_FILES['files'] : '';
					if ($files) {
						if (!isset($properties['photos'])) $properties['photos'] = array();
						for ($i = 0; $i < count($files['name']); $i++) {
							if ($files['name'][$i]) {
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
									"date_created" => date("Y-m-d H:i:s"),
									"name" => $file_name_without_extension,
								);
								$newId = $imgs->addData($data);
								$properties['photos'][] = $newId;
							}
						}
					}

					# End File upload
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
					if (empty($listcatid)) {
						$properties['text_carcompany'] = $request->element("text_carcompany");
					}
					#User update
					$properties['user_update'] = $userInfo->getId();
					# Custom fields
					foreach ($fieldList as $field) {
						$properties[$field->getName()] = stripslashes($request->element($field->getName()));
					}
					$data = array(
						'store_id' => $storeId,
						'cat_id' => (int)$request->element('cat_id'),
						'slug' => $slug,
						'name' => $request->element('name'),
						'keyword' => $request->element('keyword'),
						'position' => (int)$request->element('position'),
						'status' => (int)$request->element('status'),
						'viewed' => (int)$request->element('viewed'),
						'description' => addslashes($request->element('description')),
						'detail' => addslashes($request->element('detail')),
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
					$result = $productaccessorys->updateData($data, $idUpdate);

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

							$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $idUpdate, $storeId);
						}
					} 

					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_product'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
					# Redirect to editing page
					header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=editaccessory&lang=$lang&id=$idUpdate&rcode=7");
				}
			}
		}
	} else { # Load product information to edit
		$template->assign('item', $productInfo);
		$listCatId1 = $productInfo->getCarcompany();
		if ($listCatId1 && $listCatId1 != '') {
			$arraylistCat1 = explode(",", $listCatId1);
			if ($arraylistCat1) $template->assign('arraylistCat1', $arraylistCat1);
			//var_dump($arraylistCat1);
		}
		# Category combo box
		$categoryCombo = $productCategories->generateCombo($productInfo->getCatId(), 1);
		if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

		# Get list of custom fields
		$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productaccessory'", array('position' => 'ASC'));
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
	$error['INPUT']['cat_id'] = $validate->pasteString($request->element('cat_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'), $amessages['keyword']);
	$error['INPUT']['description'] = $validate->pasteString($request->element('detail'), $amessages['description']);
	$error['INPUT']['detail'] = $validate->pasteString($request->element('detail'), $amessages['detail']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['viewed'] = $validate->pasteString($request->element('viewed'));
	$error['INPUT']['market_price'] = $validate->pasteString($request->element('market_price'));
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
