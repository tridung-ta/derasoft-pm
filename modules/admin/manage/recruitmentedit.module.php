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
//     ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);
// }

$userInfo->checkPermission('recruitment', 'view');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
$templateFile = 'managerecruitment.tpl.html';
include_once(ROOT_PATH . 'classes/dao/recruitment.class.php');
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');
include_once(ROOT_PATH . 'classes/dao/areas.class.php');
$areas = new Areas($storeId);
$imgs = new Imgs();
$template->assign('imgs', $imgs);
$recruitments = new Recruitments(1);
$fields = new Fields($storeId);
$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
    'Tuyển dụng' => '/' . ADMIN_SCRIPT . '?op=manage&act=recruitment',
    $amessages['edit'] => ''
);
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=recruitment';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
    $amessages['edit'] => '#',
	'Danh sách ứng tuyển' => $tabLink . '&mod=listapplicants',
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
$id = $request->element("id");
# Allow some javascript
$template->assign('ckEditor', 1);
$recruitmentsData = $recruitments->getObject($id);
$template->assign('recruitmentsData', $recruitmentsData);

# Get list of custom fields
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='recruitment'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);

$listAreas = $areas->getObjects(1,"a.status = 1",['a`.`name' => 'ASC']);
// if ($listAreas) $template->assign('listAreas', $listAreas);
$sel = trim((string)($request->element('location') ?: $recruitmentsData->getLocation() ?: ''));

$opts = '';
foreach ($listAreas as $area) {
    $name = (string)$area->getName();
    $selected = (strcasecmp($name, $sel) === 0) ? ' selected' : '';
    $opts .= '<option value="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"'.$selected.'>'.$name.'</option>';
}
$template->assign('provinceOptions', $opts);

if ($request->element('doo') == 'delPhoto') {
    $properties = $specificationsData->getProperties();
    foreach ($properties['photos'] as $key => $value) {
        if ($value == $request->element('photo')) {
            unset($properties['photos'][$key]);
            $data = array('properties' => serialize($properties));
            $specifications->updateData($data, $id);
            $specificationsData = $specifications->getObject($id);
            break;
        }
    }
    header("Location: /admin.php?op=manage&act=recruitment&mod=edit&id=$id&rcode=7");
}
// if ($_POST["doo"] == "submit") {
if ($_POST && $request->element('doo') == 'submit') {
    # Validate the data input
    $validate = validateData($request);
    if ($validate['invalid']) {    # data input is not in valid form
        $template->assign('error', $validate);
    } else {
        $properties = $recruitmentsData->getProperties();
        #File Avatar
        $fileAvatr = isset($_FILES['logo']) ? $_FILES['logo'] : '';
        if ($fileAvatr) {
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
            $avatarid = $imgs->addData($data);
            $properties["avatar"] =  $avatarid;
        }
        # Check if duplicate slug
        $textFilter = new TextFilter();
        $slug = $textFilter->urlize($request->element('name'), false, '-');
        $i = 0;
        $slug .= $i ? '-' . $i : '';

        $properties["detail"] = $request->element("detail");
        // $properties["sapo"] = $request->element("sapo");

        # Custom fields
        foreach($fieldList as $field) {
            $properties[$field->getName()] = $request->element($field->getName());
        }

        $data = array(
            'store_id' => 1,
            // 'slug' => $slug,
            'parent_id' => 0,
            'slug' => $request->element("slug"),
            'detail' => $request->element("detail"),
            'income' => $request->element("income"),
            'degree' => $request->element("degree"),
            'experience' => $request->element("experience"),
            'rank' => $request->element("rank"),
            'location' => $request->element("location"),
            'number_recruits' => $request->element("number_recruits"),
            'date_exp' => $request->element("date_exp"),
            'name' => $request->element("name"),
            'job_location' => $request->element("job_location"),
            'gender' => $request->element("gender"),
            'age' => $request->element("age"),
            'status' => 1,
            'properties' => serialize($properties),
            // 'date_created' => date("Y-m-d H:i:s")
        );
        $recruitments->updateData($data, $id);

        header('location:/'.ADMIN_SCRIPT."?op=manage&act=recruitment&mod=edit&id=$id&lang=$lang&rcode=7");
    }
}

function validateData($request)
{
    global $amessages;
    include_once(ROOT_PATH . 'classes/data/validate.class.php');
    $error = array();
    $validate = new Validate();
    $error['invalid'] = 0;

    # Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}
}
