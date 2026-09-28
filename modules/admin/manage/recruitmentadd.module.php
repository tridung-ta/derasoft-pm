<?php

/*************************************************************************
Product listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 10/05/2012
Checked by: Mai Minh (10/05/2012)
 **************************************************************************/

// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
//         ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);
//     }

$userInfo->checkPermission('recruitment', 'view');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
$templateFile = 'managerecruitment.tpl.html';
include_once(ROOT_PATH . 'classes/dao/recruitment.class.php');
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/areas.class.php');
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH . 'classes/dao/comments.class.php'); 
$fields = new Fields($storeId);
$areas = new Areas($storeId);
$imgs = new Imgs();
$recruitments = new Recruitments(1);
$fields = new Fields($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$textFilter = new TextFilter();
$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
    'Tuyển dụng' => '/' . ADMIN_SCRIPT . '?op=manage&act=recruitment',
    $amessages['add_new'] => ''
);
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=recruitment';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
    $amessages['add_new'] => $tabLink . '&mod=add',
	'Danh sách ứng tuyển' => $tabLink . '&mod=listapplicants',
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
# Allow some javascript
$template->assign('ckEditor', 1);

$listAreas = $areas->getObjects(1,"a.status = 1",['a`.`name' => 'ASC']);
if ($listAreas) $template->assign('listAreas', $listAreas);
// var_dump($listAreas);die;

# Get list of custom fields
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='recruitment'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);

// # Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='recruitment'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

$doo = strtolower(trim((string)($request->element('doo') ?? ($_POST['doo'] ?? ''))));
if ($doo === 'submit') {
    # Validate the data input
    $validate = validateData($request);
    if ($validate['invalid']) {    # data input is not in valid form
        $template->assign('error', $validate);
        # Get list of custom options
        $fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='recruitment'", array('position' => 'ASC'));
        if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
    } else {
        #File Avatar
        $fileAvatr = isset($_FILES['logo']) ? $_FILES['logo'] : '';
        if ($fileAvatr) {
            
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
            $avatarid = $imgs->addData($data);
        }
        # Check if duplicate slug
        $textFilter = new TextFilter();
        $slug = $textFilter->urlize($request->element('name'), false, '-');
        $i = 0;
        $slug .= $i ? '-' . $i : '';

        $properties["avatar"] =  $avatarid;
        $properties["detail"] = $request->element("detail");
        $properties["sapo"] = $request->element("sapo");

        # Custom fields
        foreach($fieldList as $field) {
            $properties[$field->getName()] = $request->element($field->getName());
        }

        $data = array(
            'store_id' => 1,
            'parent_id' => 0,
            'slug' => $slug,
            'name' => $request->element("name"),
            'income' => $request->element("income"),
            'degree' => $request->element("degree"),
            'experience' => $request->element("experience"),
            'rank' => $request->element("rank"),
            'location' => $request->element("location"),
            'number_recruits' => $request->element("number_recruits") ? $request->element("number_recruits") : 1,
            'date_exp' => $request->element("date_exp") ? $request->element("date_exp") : date("Y-m-d H:i:s"),
            'detail' => $request->element("detail"),
            'job_location' => $request->element("job_location"),
            'gender' => $request->element("gender"),
            'age' => $request->element("age"),
            'status' => 1,
            'properties' => serialize($properties),
            'date_created' => date("Y-m-d H:i:s")
        );
        // var_dump($data);die;
        $newId = $recruitments->addData($data);

        // custom options
        if ($newId) {
            foreach ($fieldOptionList as $field) {
                $valueType = stripslashes($request->element($field->getFieldName()));
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

        header("Location: /admin.php?op=manage&act=recruitment&mod=list");

       

    }
}

function validateData($request)
{
    global $amessages;
    include_once(ROOT_PATH . 'classes/data/validate.class.php');
    $error = array();
    $validate = new Validate();
    # Paste value of custom fields
    $error['invalid'] = 0;

    $error['INPUT']['name'] = $validate->pasteString($request->element('name'));
    $error['INPUT']['detail'] = $validate->pasteString($request->element('detail'));
    $nameVal = trim((string)$error['INPUT']['name']['value']);
    $detailVal = trim((string)$error['INPUT']['detail']['value']);

    # Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}


    if ($nameVal === '') {
        $error['INPUT']['name']['error']   = 1;
        $error['INPUT']['name']['message'] = 'Vui lòng nhập vị trí tuyển dụng';
        $error['invalid'] = 1;
    }
    if ($detailVal === '') {
        $error['INPUT']['detail']['error']   = 1;
        $error['INPUT']['detail']['message'] = 'Vui lòng nhập chi tiết';
        $error['invalid'] = 1;
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


	