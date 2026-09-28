<?php
$userInfo->checkPermission('specifications', 'view');
$templateFile = 'managespecifications.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/specifications.class.php');
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');

$imgs = new Imgs();
$template->assign('imgs', $imgs);
$productOptions = new ProductOptions($storeId);
$specifications = new Specifications(1);
$fields = new Fields($storeId);
$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
    $amessages['manage_specifications'] => '/' . ADMIN_SCRIPT . '?op=manage&act=specifications',
    $amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=specifications';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
    $amessages['edit'] =>  '#',
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);

$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
$id = $request->element("id");
#danh sách thuong hiệu
$listproductOptions = $productOptions->getObjects(1, "`status`='1'", array('id' => 'ASC'), 9999);
if ($listproductOptions) $template->assign('listproductOptions', $listproductOptions);
#danh sách nhóm cha dòng gai
$listspecifications = $specifications->getObjects(1, "`status`='1' AND `parent_id` = '1' AND `cat_id`= '0'", array('id' => 'ASC'), 9999);
if ($listspecifications) $template->assign('listspecifications', $listspecifications);
# Allow some javascript
$template->assign('ckEditor', 1);
$specificationsData = $specifications->getObject($id);
$template->assign('specificationsData', $specificationsData);

if ($request->element('doo') == 'delPhoto') {
    $properties = $specificationsData->getProperties();
    foreach ($properties['photos'] as $key => $value) {
        if ($value == $request->element('photo')) {
            // unlink($gallery_path . "l_" . $properties['photos'][$key]);
            // unlink($gallery_path . "t_" . $properties['photos'][$key]);
            // unlink($gallery_path . "m_" . $properties['photos'][$key]);
            // unlink($gallery_path . "a_" . $properties['photos'][$key]);
            unset($properties['photos'][$key]);
            $data = array('properties' => serialize($properties));
            $specifications->updateData($data, $id);
            $specificationsData = $specifications->getObject($id);
            break;
        }
    }
    header("Location: /admin.php?op=manage&act=specifications&mod=edit&id=$id&rcode=7");
}

if ($_POST["doo"] == "submit") {
    # Validate the data input
    $validate = validateData($request);
    if ($validate['invalid']) {    # data input is not in valid form
        $template->assign('error', $validate);
    } else {
        $properties = $specificationsData->getProperties();
        if ($specifications->checkDuplicate($_POST["name"], 'name', "mc_id = '" . (int)$_POST["type"] . "' AND `id` <> '" . $id . "'")) {
            $validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
            $validate['INPUT']['name']['error'] = 1;
            $validate['invalid'] = 1;
            $template->assign('error', $validate);
        } else {
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

            $properties["detail"] = $request->element("detail");

            $data = array(
                'store_id' => 1,
                'parent_id' => $request->element("type"), #nhóm
                'mc_id' => $request->element("trademark"), # thương hiệu
                'cat_id' => $request->element("cat_idds"), #thuộc dòng gai
                'name' => $request->element("name"),
                'position' => "0",
                'status' => 1,
                'url' => "",
                'properties' => serialize($properties),
                'date_created' => date("Y-m-d H:i:s")
            );
           
            $specifications->updateData($data, $id);
            header("Location: /admin.php?op=manage&act=specifications&mod=edit&id=$id&rcode=7");
        }
    }
}
function validateData($request)
{
    global $amessages;
    include_once(ROOT_PATH . 'classes/data/validate.class.php');
    $error = array();
    $validate = new Validate();
    $error['INPUT']['cat_id'] = $validate->pasteString($request->element('cat_id'));
    $error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
    $error['INPUT']['detail'] = $validate->pasteString($request->element('detail'), $amessages['detail']);

    # Paste value of custom fields
    if ($error['INPUT']['name']['error']) {
        $error['invalid'] = 1;
        $error['message'] = '';
        return $error;
    }
    $error['invalid'] = 0;
}
