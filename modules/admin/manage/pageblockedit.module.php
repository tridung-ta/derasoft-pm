<?php
$userInfo->checkPermission('pageblock','edit');

$templateFile = 'managepageblock.tpl.html';
include_once(ROOT_PATH.'classes/dao/pageblocks.class.php');
include_once(ROOT_PATH.'classes/dao/menus.class.php');

$pageblocks = new PageBlocks($storeId);
$menus = new Menus($storeId);

$topNav = array(
    $amessages['dash_board']     => '/'.ADMIN_SCRIPT.'?op=dashboard',
    $amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
    $amessages['list_item']      => '/'.ADMIN_SCRIPT.'?op=manage&act=pageblock&mod=list',
    $amessages['edit_item']      => ''
);
$template->assign('topNav', $topNav);

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=pageblock';
$listTabs = array(
    $amessages['list_item'] => $tabLink.'&mod=list',
    $amessages['edit_item'] => '#',
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$lang = $request->element('lang') ? $request->element('lang') : 'vi';
$template->assign('lang', $lang);

$result_code = $request->element('rcode') ? $request->element('rcode') : null;
$template->assign('result_code', $result_code);
$error_code = $request->element('ecode') ? $request->element('ecode') : null;
$template->assign('error_code', $error_code);

$menu_id = $request->element('menu_id') ? (int)$request->element('menu_id') : 0;
if (!$menu_id) {
    header('location:/' . ADMIN_SCRIPT . '?op=manage&act=pageblock&mod=list&lang=' . $lang);
    exit;
}

$menuObj = $menus->getObject($menu_id);
$template->assign('menuObj', $menuObj);
$template->assign('menu_id', $menu_id);

$doo = $request->element('doo');

# Xóa một block
if ($doo == 'delete') {
    $userInfo->checkPermission('pageblock', 'delete');
    $block_id = (int)$request->element('block_id');
    if ($block_id) {
        $pageblocks->deleteBlock($block_id);
    }
    header('location:/' . ADMIN_SCRIPT . '?op=manage&act=pageblock&mod=edit&menu_id=' . $menu_id . '&rcode=8');
    exit;
}

# Lưu (thêm mới + cập nhật các block hiện có)
if ($_POST && $doo == 'submit') {
    $userInfo->checkPermission('pageblock', 'edit');

    // Cập nhật các block hiện có
    $block_ids    = $request->element('block_id');    // array
    $block_keys   = $request->element('block_key');   // array
    $block_labels = $request->element('block_label'); // array
    $object_ids   = $request->element('object_ids');  // array
    $sort_orders  = $request->element('sort_order');  // array

    if ($block_ids) {
        foreach ($block_ids as $i => $bid) {
            $bid = (int)$bid;
            if (!$bid) continue;
            $pageblocks->updateBlock($bid, array(
                'block_key'   => trim($block_keys[$i]),
                'block_label' => trim($block_labels[$i]),
                'object_ids'  => trim($object_ids[$i]),
                'sort_order'  => (int)$sort_orders[$i],
            ));
        }
    }

    // Thêm block mới nếu có
    $new_key   = trim($request->element('new_block_key'));
    $new_label = trim($request->element('new_block_label'));
    $new_ids   = trim($request->element('new_object_ids'));
    if ($new_key && $new_label) {
        $pageblocks->saveBlock($menu_id, $new_key, $new_label, $new_ids, 99);
    }

    header('location:/' . ADMIN_SCRIPT . '?op=manage&act=pageblock&mod=edit&menu_id=' . $menu_id . '&rcode=7');
    exit;
}

# Load danh sách block từ DB
$blockList = $pageblocks->getByPage($menu_id);
$template->assign('blockList', array_values($blockList));
?>