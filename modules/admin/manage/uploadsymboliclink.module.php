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
$templateFile = 'manageuploadimgsymboliclink.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/imgs.class.php");
$fields = new Fields($storeId);
$imgs = new Imgs();
$template->assign('imgs', $imgs);
$Catid = $request->element('id');
$template->assign('Catid', $Catid);
$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
    $amessages['manage_gallery'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=uploadimg';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=symboliclinklist&id=' . $Catid,
    $amessages['add_new'] => $tabLink . '&mod=add',

);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);
# Allow some javascript
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
$id = $request->element('id');
$template->assign('id', $id);
#
$listsym = $imgs->getObjects(1, "`cat_id` = $id", array('id' => 'ASC'), 99);
$template->assign('listsym', $listsym);

$noti = $request->element('noti');
$template->assign('noti', $noti);
$template->assign('ckEditor', 1);
if ($_POST["doo"] == "submit") {
    $id = $request->element('id');
    $title = $request->element('title');
    # Check if duplicate slug
    $linknew = str_replace([" ", "/"], "-", stripUnicode($title)) . "-" . $code = rand(10000000, 99999999) . ".png";

    // Target file path
    $target = $gallery_path . $imgs->getUrlFromId($id);

    // Path where the symbolic link will be created
    $link = $gallery_path . $linknew;

    // Check if target file exists
    if (file_exists($target)) {
        // Check if the symbolic link already exists
        if (!file_exists($link)) {
            // Try to create a symbolic link
            if (symlink($target, $link)) {
                if (empty($imgs->getObject($title, 'name'))) {
                    $data = array(
                        "url_l" => $linknew,
                        "url_a" => $linknew,
                        "status" => 1,
                        "store_id" => 1,
                        "cat_id" => $id,
                        "date_created" => date("Y-m-d H:i:s"),
                        "name" => $title,
                    );
                    $imgs->addData($data);
                    header("Location: /admin.php?op=manage&act=uploadimg&mod=symboliclinklist&id=$id");
                }else{
                    
                }
            }
        }
    }
}
