<?php

/*************************************************************************
Editing Custom Field module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 19/05/2012
Coder: Mai Minh
 **************************************************************************/
$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/productsize.class.php');
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
$productaccessorys = new Productaccessorys($storeId);
$imgs = new Imgs();
$template->assign('imgs', $imgs);
$products = new Products($storeId);
$search = new Search($storeId);
$fields = new Fields($storeId);
$productsize = new ProductSize();
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
    $amessages['edit_tramk'] => "#",
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 8);

$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
if ($id) $template->assign('id', $id);
$itemInfo = $productOptions->getObject($id);
$categoryCombo = $productCategories->generateCombo($itemInfo->getType(), 1);
if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
# productpotion combo box
$productOptionsCombo = $productOptions->generateCombo($itemInfo->getCatId(), 29);
if ($productOptionsCombo) $template->assign('productOptionsCombo', $productOptionsCombo);
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


# Get list of fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productoptions'", array('position' => 'ASC'),99);
if ($fieldList) $template->assign('fieldList', $fieldList);

if (!$itemInfo) {
    $template->assign('validItem', 0);
} else {
    $template->assign('validItem', 1);

    # Allow some javascript
    $template->assign('ckEditor', 1);
    $productInfo = $productOptions->getObject($id);
    if ($request->element('doo') == 'delAvatar') {
        if ($productInfo->getAvatar()) {
            unlink($gallery_path . 'a_' . $productInfo->getAvatar());
            unlink($gallery_path . 'l_' . $productInfo->getAvatar());
            unlink($gallery_path . 't_' . $productInfo->getAvatar());
            unlink($gallery_path . 'm_' . $productInfo->getAvatar());
            $data = array('avatar' => "");
            $productOptions->updateData($data, $id);
            $productInfo = $productOptions->getObject($id);
        }
    }
    #Size
    $listSize = $productInfo->getListSize();
    if ($listSize && $listSize != '') {
        $arraylistSize = explode(",", $listSize);
        if ($arraylistSize) $template->assign('arraylistSize', $arraylistSize);
        //var_dump($arraylistCat1);
    }
    #Camera
    $listCamera = $productInfo->getListCamera();
    if ($listCamera && $listCamera != '') {
        $arraylistCamera = explode(",", $listCamera);
        if ($arraylistCamera) $template->assign('arraylistCamera', $arraylistCamera);
        //var_dump($arraylistCat1);
    }
    #Cảm biến
    $ListCamBien = $productInfo->getListCamBien();
    if ($ListCamBien && $ListCamBien != '') {
        $arrayListCamBien = explode(",", $ListCamBien);
        if ($arrayListCamBien) $template->assign('arrayListCamBien', $arrayListCamBien);
        //var_dump($arraylistCat1);
    }
    #Fim
    $ListFim = $productInfo->getListFim();
    if ($ListFim && $ListFim != '') {
        $arrayListFim = explode(",", $ListFim);
        if ($arrayListFim) $template->assign('arrayListFim', $arrayListFim);
        //var_dump($arraylistCat1);
    }
    #Ppf
    $ListPpf = $productInfo->getListPpf();
    if ($ListPpf && $ListPpf != '') {
        $arrayListPpf = explode(",", $ListPpf);
        if ($arrayListPpf) $template->assign('arrayListPpf', $arrayListPpf);
        //var_dump($arraylistCat1);
    }

    if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
        # Get list of custom fields
        $fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productoptions'", array('position' => 'ASC'),99);
        if ($fieldList) $template->assign('fieldList', $fieldList);

        $productInfo = $productOptions->getObject($id);
        $properties = $productInfo->getProperties();
        # Validate the data input
        $validate = validateData($request);
        if ($validate['invalid']) {    # data input is not in valid form
            $template->assign('error', $validate);
            $typeCombo = optionFieldType1($request->element('type'));
            echo $request->element('type');
            $template->assign('itemInfo', $itemInfo);
        } else { # Valid data input		
            # check duplicate product option name
            if ($productOptions->checkDuplicate($request->element('name'), 'name', "`id` <> '$id' AND `pc_id` = '" . $request->element('cat_id') . "'")) {
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
                $dup = $products->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "`id` <> '$id' AND `category_id` = '" . $request->element('cat_id') . "'");
                if ($dup) $i++;
            }
            $slug .= $i ? '-' . $i : '';
            $cat_id = 0;
            if ($request->element('cat_id') == 29) $cat_id = $request->element('car_company');
            $avatar = $productInfo->getAvatar();

            #File Avatar
            $fileAvatr = isset($_FILES['avatar']) ? $_FILES['avatar'] : '';
            if ($fileAvatr) {
                if ($fileAvatr['name'] != '') {
                    $textFilter = new TextFilter();
                    $last_dot_position = strrpos($fileAvatr['name'], '.');
                    // Cắt chuỗi từ đầu đến vị trí của dấu chấm cuối cùng (không bao gồm dấu chấm)
                    if ($last_dot_position !== false) {
                        $filename_without_extension = substr($fileAvatr['name'], 0, $last_dot_position);
                    }
                    // Lấy 3 ký tự cuối của tên tệp
                    $type = strtolower(substr($fileAvatr['name'], -3));
                    // Loại bỏ dấu tiếng Việt và chuyển đổi khoảng trắng thành dấu "-"
                    $file_name_normalized = str_replace(' ', '-', strtolower($textFilter->convert_vi_to_en($filename_without_extension)));
                    $imgl = addslashes(Filter($file_name_normalized . "_l_" . rand() . "." . $type));
                    $imga = addslashes(Filter($file_name_normalized . "_a_" . rand() . "." . $type));
                    $tmp_img = $fileAvatr['tmp_name'];
                    $size = $fileAvatr['size'];
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
                    $file_parts = explode('.', $fileAvatr['name']);
                    // Lấy tất cả các phần trừ phần mở rộng
                    $file_name_without_extension = implode('.', array_slice($file_parts, 0, -1));
                    #luu data img.
                    $data = array(
                        "url_l" => $imgl,
                        "url_a" => $imga,
                        "status" => 1,
                        "store_id" => 1,
                        "date_created" => date("Y-m-d H:i:s"),
                        "name" => $file_name_without_extension,
                    );
                    $avatar = $imgs->addData($data);
                }
            }
          
           	# Dòng xe này dùng những size vỏ nào
			$listSize = implode(",", is_array($request->element("list_size"))?$request->element("list_size"):[]);
			$listcamera = implode(",", is_array($request->element("list_camera"))?$request->element("list_camera"):[]);
			$listcambien = implode(",", is_array($request->element("list_cambien"))?$request->element("list_cambien"):[]);
			$listfim = implode(",", is_array($request->element("list_fim"))?$request->element("list_fim"):[]);
			$listppf = implode(",", is_array($request->element("list_ppf"))?$request->element("list_ppf"):[]);


            # Custom fields
            foreach ($fieldList as $field) {
                $properties[$field->getName()] = stripslashes($request->element($field->getName()));
            }
            # Everything is ok. Update data to DB
            if (!$validate['invalid']) {
                $value = '';
                $data = array(
                    'store_id' => $storeId,
                    'slug' => $request->element('slug'),
                    'avatar' => $avatar,
                    'pc_id' => (int)$request->element('cat_id'),
                    'name' => $request->element('name'),
                    'class' => (int)$request->element('vanh'),
                    'position' => (int)$request->element('position'),
                    'detail' => $request->element('detail'),
                    'sapo' => $request->element('sapo'),
                    'type' => (int)$request->element('type'),
                    'cat_id' => (int)$cat_id,
                    'list_size' => $listSize,
                    'list_camera' => $listcamera,
                    'list_cambien' => $listcambien,
                    'list_fim' => $listfim,
                    'list_ppf' => $listppf,
                    'properties' => serialize($properties),
                    'status' => (int)$request->element('status')
                );

                $newItem = $productOptions->updateData($data, $id);
                # Operation tracking
                $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_product_option'], $productOptions->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
                # Redirect to editing page
                header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=editoption&lang=$lang&id=$id&rcode=7");
            }
        }
    } else { # Load information to edit
        $template->assign('item', $itemInfo);
        # Field types combobox
        $typeCombo = optionFieldType1($itemInfo->getType());
    }
    $template->assign('typeCombo', $typeCombo);
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
    global $amessages;
    include_once(ROOT_PATH . 'classes/data/validate.class.php');
    $error = array();
    $validate = new Validate();
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
