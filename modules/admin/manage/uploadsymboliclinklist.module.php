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
$templateFile = 'manageuploadimg.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/imgs.class.php");
$fields = new Fields($storeId);
$imgs = new Imgs();
$Catid = $request->element('id');
$template->assign('Catid', $Catid);

$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
    $amessages['manage_gallery'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=uploadimg';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
    $amessages['add_new'] => $tabLink . '&mod=symboliclink&id=' . $Catid,
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);


# Get parameters
$items_per_page = $request->element('ipp') ? $request->element('ipp') : DEFAULT_ADMIN_ROWS_PER_PAGE;
if ($items_per_page) $template->assign('ipp', $items_per_page);
$page = $request->element('pg') ? $request->element('pg') : 1;
if ($page) $template->assign('pg', $page);
$sort_key = $request->element('sk') ? $request->element('sk') : 'id';
if ($sort_key) $template->assign('sk', $sort_key);
$sort_direction = $request->element('sd') ? $request->element('sd') : 'DESC';
if ($sort_direction) $template->assign('sd', $sort_direction);
$do = $request->element('doo') ? $request->element('doo') : '';
if ($do) $template->assign('do', $do);
$kw = $request->element('kw') ? $request->element('kw') : '';
if ($kw) $template->assign('kw', $kw);
$pId = $request->element('pId', '-1');
if ($pId) $template->assign('pId', $pId);
$trademark = $request->element('trademark', ' ');
if ($trademark) $template->assign('trademark', $trademark);
$pId2 = $request->element('pId2') ? $request->element('pId2') : 0;
if ($pId2) $cat_id = $pId2;
# Build WHERE condition

$condition = "1>0 AND `cat_id` = $Catid";
if ($kw) $condition = "(`name` LIKE '%$kw%' OR `url_l` LIKE '%$kw%' OR `id` = '$kw')";
$pages_condition = "`store_id` = '$storeId' AND $condition";
$sort = array($sort_key => $sort_direction);
# Page navigation
$rowsPages = $imgs->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=uploadimg&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&trademark=$trademark&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);
# Get objects

$listItems = $imgs->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);
# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);
# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=uploadimg&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&trademark=$trademark&pg=$page";
$template->assign('link', $link);
if ($_POST) {
    switch ($do) {
        case 'deleteimg':
            $userInfo->checkPermission('img', 'deleteimg');
            $id = $request->element('id');
            if ($id) {
                $detail = $imgs->getObject($id);
                if ($detail) {
                    $gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
                    unlink($gallery_path . $detail->getUrlL());
                }
                $result_code = 3;
                # Operation tracking
                $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => "Xóa hình ảnh " . $imgs->getNameFromId($id), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
                $imgs->DeteImg($id);
            }
            break;
    }
    header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=uploadimg&mod=symboliclinklist&id=$Catid");
}
