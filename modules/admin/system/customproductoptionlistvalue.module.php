<?php

$templateFile = 'systemcustomproductoption.tpl.html';
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');

$customProductOptionValues = new CustomProductOptionValues($storeId);

$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
    'Quản lý product options' => '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption',
    $amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption';
$listTabs = array(
    'Danh sách options' => $tabLink . '&mod=list',
    'Danh sách values' => $tabLink . '&mod=listvalue',
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
    'Danh sách option mặc định' => $tabLink . '&mod=listdefault',
    'Thêm option mặc định' => $tabLink . '&mod=listdefaultadd',
    'Dọn rác option mặc định' => $tabLink . '&mod=listdefaultcleantrash'
);

$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

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
$pId = $request->element('pId') ? $request->element('pId') : 0;
if ($pId) {
    $gfId = $customProductOptionValues->getParentIdFromId($pId);
    $template->assign('pId', $pId);
    $template->assign('gfId', $gfId);
    $topNav[$amessages['list_item']] = '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption&mod=listvalue';
    $topNav[$customProductOptionValues->getNameFromId($pId)] = '';
}

# Build WHERE condition

// if ($kw) {
//     $idsOptionField = $customProductOptionValues->searchOptionField($kw);

//     if ($idsOptionField) {
//         $condition = "(`id` IN $idsOptionField OR `field_id` LIKE '%$kw%' OR `key_id` LIKE '%$kw%' OR `field_value` LIKE '%$kw%')";
//     } else {
//         $condition = "(`id`='$kw' OR `field_id` LIKE '%$kw%' OR `key_id` LIKE '%$kw%' OR `field_value` LIKE '%$kw%')";
//     }
// }

$condition = "1>0";
if ($kw) $condition = "(`id`='$kw' OR `option_id` LIKE '%$kw%' OR `value` LIKE '%$kw%' OR `price_modifier` LIKE '%$kw%')";
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);





# Page navigation
$rowsPages = $customProductOptionValues->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=system&act=customproductoption&mod=listvalue&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $customProductOptionValues->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);



# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=system&act=customproductoption&mod=listvalue&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=$page";
$template->assign('link', $link);


# Show URL Popup
#$template->assign('urlPopup',1);

// var_dump(123);
// die;

if ($_POST) {
    switch ($do) {
        case 'enable':
            $id = $request->element('id');
            if ($id) {
                $customProductOptionValues->changeStatus($id, S_ENABLED);

                $result_code = 1;
                # Operation tracking
                $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_custom_field'], $customProductOptionValues->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
            } else {
                $ids = $request->element('ids');
                if ($ids) {
                    $listArticle = '';
                    foreach ($ids as $id) {
                        $customProductOptionValues->changeStatus($id, S_ENABLED);
                        $listArticle .= ($listArticle ? ',&nbsp;' : '') . $customProductOptionValues->getNameFromId($id);
                    }
                    $result_code = 1;
                    # Operation tracking
                    $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_custom_field'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
                } else $error_code = 5;
            }
            break;


        case 'disable':
            $id = $request->element('id');
            if ($id) {
                $customProductOptionValues->changeStatus($id, S_DISABLED);
                $result_code = 2;
                # Operation tracking
                $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_custom_field'], $customProductOptionValues->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
            } else {
                $ids = $request->element('ids');
                if ($ids) {
                    $listArticle = '';
                    foreach ($ids as $id) {
                        $customProductOptionValues->changeStatus($id, S_DISABLED);
                        $listArticle .= ($listArticle ? ',&nbsp;' : '') . $customProductOptionValues->getNameFromId($id);
                    }
                    $result_code = 2;
                    # Operation tracking
                    $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_custom_field'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
                } else $error_code = 5;
            }
            break;
        case 'delete':
            $id = $request->element('id');
            if ($id) {
                $customProductOptionValues->changeStatus($id, S_DELETED);
                $result_code = 3;
                # Operation tracking
                $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_custom_field'], $customProductOptionValues->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
            } else {
                $ids = $request->element('ids');
                if ($ids) {
                    $listArticle = '';
                    foreach ($ids as $id) {
                        $customProductOptionValues->changeStatus($id, S_DELETED);
                        $listArticle .= ($listArticle ? ',&nbsp;' : '') . $customProductOptionValues->getNameFromId($id);
                    }
                    $result_code = 3;
                    # Operation tracking
                    $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_custom_field'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
                } else $error_code = 5;
            }
            break;
        case 'changeposition':
            $positions = $request->element('positions');
            if ($positions) {
                foreach ($positions as $key => $value) {
                    $customProductOptionValues->changePosition($key, $value);
                }
                $result_code = 4;
                # Operation tracking
                $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_custom_field_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
            } else $error_code = 5;
            break;
        case 'cleantrash':
            checkPermission(3);
            $customProductOptionValues->cleanTrash();
            $result_code = 5;
            # Operation tracking
            $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_custom_field'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
            break;
        case 'cancel':
            header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=customproductoption&mod=listvalue&lang=$lang&ecode=7&pId=$pId");
            exit;
            break;
    }
    header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=customproductoption&mod=listvalue&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {
}
