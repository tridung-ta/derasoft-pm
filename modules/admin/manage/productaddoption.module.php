<?php

/*************************************************************************
Adding product module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 10/05/2012
Checked by: Mai Minh (10/05/2012)
 **************************************************************************/
$userInfo->checkPermission('product', 'add');
$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/productsize.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
$productaccessorys = new Productaccessorys($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$productsize = new ProductSize();
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$productOptions = new ProductOptions($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['add_new_product'] => ''
);
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
$template->assign('currentTab', 8);
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$gallery_root = ROOT_PATH . "upload/$storeId/";
$gallery_path = $gallery_root . "resources/";
# Category combo box
$categoryCombo = $productCategories->generateCombo($request->element('cat_id'), 1);
if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
# productpotion combo box
$productOptionsCombo = $productOptions->generateCombo(1, 29);
if ($productOptionsCombo) $template->assign('productOptionsCombo', $productOptionsCombo);

# Get list of fields
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='productoptions'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);
#
$ListSize = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = '30'", array("class" => "ASC"), 999);
if ($ListSize) $template->assign('ListSize', $ListSize);
#
$ListCamera = $productaccessorys->getObjects(1, "p.`status` = '1' AND `cat_id` = '61'", array("id" => "ASC"), 999);
if ($ListCamera) $template->assign('ListCamera', $ListCamera);
#
$ListCamBien = $productaccessorys->getObjects(1, "p.`status` = '1' AND `cat_id` = '60'", array("id" => "ASC"), 999);
if ($ListCamBien) $template->assign('ListCamBien', $ListCamBien);
#
$ListFim = $productaccessorys->getObjects(1, "p.`status` = '1' AND `cat_id` = '88'", array("id" => "ASC"), 999);
if ($ListFim) $template->assign('ListFim', $ListFim);
#
$ListPpf = $productaccessorys->getObjects(1, "p.`status` = '1' AND `cat_id` = '95'", array("id" => "ASC"), 999);
if ($ListPpf) $template->assign('ListPpf', $ListPpf);

# Allow some javascript
$template->assign('ckEditor', 1);

# Field types combobox
$typeCombo = optionFieldType1();

if ($_POST && $request->element('doo') == 'submit') { # if form is submitted

	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
		# Category combo box
		$categoryCombo = $productCategories->generateCombo($request->element('cat_id'));
		if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

		# Type combo
		$template->assign('error', $validate);
		$typeCombo = optionFieldType($request->element('type'));
	} else { # Valid data input
		$properties = array('');
		# check duplicate product option name
		if ($productOptions->checkDuplicate($request->element('name'), 'name', "`pc_id` = '" . $request->element('cat_id') . "'")) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error', $validate);
		}

		#File Avatar
		$fileAvatar = isset($_FILES['avatar']) ? $_FILES['avatar'] : '';
		if ($fileAvatar['name'] != '') {
			$img = addslashes(Filter(rand() . "_" . $fileAvatar['name']));
			$tmp_img = $fileAvatar['tmp_name'];
			$size = $fileAvatar['size'];
			$type = strtolower(substr($img, -3));
			if (preg_match("/" . ALLOW_FILE_TYPES . "/", strtolower($img))) {
				# Upload
				if (isImage($img)) {
					$new_img = $img;
					move_uploaded_file($tmp_img, $gallery_path . 'l_' . $img);
					if (isBmp($img)) $new_img = preg_replace("/(bmp$)/", "jpg", $img);
					resize($gallery_path, $gallery_path, 'l_' . $img, 'l_' . $new_img, DEFAULT_LARGE_SIZE, DEFAULT_LARGE_SQUARE, DEFAULT_PHOTO_QUALITY);
					resize($gallery_path, $gallery_path, 'l_' . $img, 'a_' . $new_img, DEFAULT_AVATAR_SIZE, DEFAULT_AVATAR_SQUARE, DEFAULT_PHOTO_QUALITY);
					if (CREATE_PRODUCT_AVATAR_CORNER) imageCreateCorners($gallery_path . 'a_' . $new_img, 9);
					resize($gallery_path, $gallery_path, 'l_' . $img, 'm_' . $new_img, DEFAULT_MEDIUM_SIZE, DEFAULT_MEDIUM_SQUARE, DEFAULT_PHOTO_QUALITY);
					resize($gallery_path, $gallery_path, 'l_' . $img, 't_' . $new_img, DEFAULT_THUMBNAIL_SIZE, DEFAULT_THUMBNAIL_SQUARE, DEFAULT_PHOTO_QUALITY);
					if ($img != $new_img) unlink($gallery_path . 'l_' . $img);	# Delete file if it's not a JPEG
					$avatar = $new_img;
				}
			} #/if (preg_match
		}
		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('name'), false, '-');
		$i = 0;
		$dup = 1;
		$slug .= $i ? '-' . $i : '';


		foreach ($fieldList as $field) {
			$properties[$field->getName()] = stripslashes($request->element($field->getName()));
		}
		#Dòng xe này dùng những size vỏ nào
		$listSize = implode(",", is_array($request->element("list_size"))?$request->element("list_size"):[]);
		$listcamera = implode(",", is_array($request->element("list_camera"))?$request->element("list_camera"):[]);
		$listcambien = implode(",", is_array($request->element("list_cambien"))?$request->element("list_cambien"):[]);
		$listfim = implode(",", is_array($request->element("list_fim"))?$request->element("list_fim"):[]);
		$listppf = implode(",", is_array($request->element("list_ppf"))?$request->element("list_ppf"):[]);

		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			$data = array(
				'store_id' => $storeId,
				'slug' => $slug,
				'pc_id' => (int)$request->element('cat_id'),
				'name' => $request->element('name'),
				'class' => (int)$request->element('vanh'),
				'position' => (int)$request->element('position'),
				'cat_id' => (int)$request->element('car_company'),
				'status' => (int)$request->element('status'),
				'sapo' => $request->element('sapo'),
				'avatar' => $avatar,
				'properties' => serialize($properties),
				'list_size' => $listSize,
				'list_camera' => $listcamera,
				'list_cambien' => $listcambien,
				'list_fim' => $listfim,
				'list_ppf' => $listppf,
				'detail' => addslashes($request->element('detail')),
			);
			$idop = $productOptions->addData($data);
			
			$dataSearch = array(
				"search_id" => $idop,
				"slug" => $slug,
				"title" => $request->element('name'),
				"type" => "productOptions",
				"status" => 1,
				"sapo" => $request->element('sapo'),
				"detail" => $request->element('detail'),
				"store_id" => 1,
				"keyword" => $request->element('name'),
				"url" => $slug,
			);
			$searchId = $search->addData($dataSearch);
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_product_option'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listoption&pId=" . $request->element('cat_id') . "&rcode=6");
		}
	}
}

$template->assign('typeCombo', $typeCombo);

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['cat_id'] = $validate->pasteString($request->element('cat_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);

	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	if ($error['INPUT']['name']['error']) {
		$error['invalid'] = 1;
		$error['message'] = '';
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
