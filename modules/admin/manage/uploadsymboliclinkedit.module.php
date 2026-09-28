<?php

/*************************************************************************
Adding product category module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 29/08/2011
 **************************************************************************/
$templateFile = 'manageuploadimgsymboliclinkedit.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/imgs.class.php");
$fields = new Fields($storeId);
$imgs = new Imgs();

$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
    $amessages['manage_gallery'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=uploadimg';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
    $amessages['add_new'] => $tabLink . '&mod=add',

);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);
# Allow some javascript
$noti = $request->element('rcode');
$template->assign('noti', $noti);
$template->assign('ckEditor', 1);
$id = $request->element('id');
$template->assign('id', $id);
$detail = $imgs->getObject($id);
$template->assign('detail', $detail);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
if ($_POST["doo"] == "submit") {

    // Đường dẫn của tệp ảnh ban đầu
    $duong_dan_cul = $gallery_path . $detail->getUrlL();
    $duong_dan_cua = $gallery_path . $detail->getUrlA();
    #
    $textFilter = new TextFilter();

    // Lấy 3 ký tự cuối của tên tệp
    $type = strtolower(substr($detail->getUrlL(), -3));
    // Loại bỏ dấu tiếng Việt và chuyển đổi khoảng trắng thành dấu "-"
    $file_name_normalized = str_replace(' ', '-', strtolower($textFilter->convert_vi_to_en($request->element('name'))));

    $imgl = addslashes(Filter($file_name_normalized . "_l_" . rand() . "." . $type));
    $imga = addslashes(Filter($file_name_normalized . "_a_" . rand() . "." . $type));


    // Đường dẫn mới của tệp ảnh sau khi đổi tên (ảnh lớn)
    $duong_dan_moil = $gallery_path . $imgl;
    // Đường dẫn mới của tệp ảnh sau khi đổi tên (ảnh nhỏ)
    $duong_dan_moia = $gallery_path . $imga;


    // Sử dụng hàm rename để đổi tên tệp ảnh
    rename($duong_dan_cul, $duong_dan_moil);
    rename($duong_dan_cua, $duong_dan_moia);

    #
    $data = array(
        "name" => $request->element('name'),
        "url_l" => $imgl,
        "url_a" => $imga
    );

    $imgs->updateData($data, $id);


    header("Location: /admin.php?op=manage&act=uploadimg&mod=edit&id=$id&rcode=7");
}
