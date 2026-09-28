<?php
$userInfo->checkPermission('pageblock','view');

$templateFile = 'managepageblock.tpl.html';
include_once(ROOT_PATH.'classes/dao/pageblocks.class.php');
include_once(ROOT_PATH.'classes/dao/menus.class.php');

$pageblocks = new PageBlocks($storeId);
$template->assign('pageblocks', $pageblocks);
$menus = new Menus($storeId);

$topNav = array(
    $amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
    $amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
    $amessages['list_item'] => '/'.ADMIN_SCRIPT.'?op=manage&act=pageblock'
);
$template->assign('topNav', $topNav);

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=pageblock';
$listTabs = array(
    $amessages['list_item'] => $tabLink.'&mod=list'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

$lang = $request->element('lang') ? $request->element('lang') : 'vi';
$template->assign('lang', $lang);

$result_code = $request->element('rcode') ? $request->element('rcode') : null;
$template->assign('result_code', $result_code);
$error_code = $request->element('ecode') ? $request->element('ecode') : null;
$template->assign('error_code', $error_code);

# Drill-down theo parent_id
$pId = $request->element('pId') ? (int)$request->element('pId') : 0;
$template->assign('pId', $pId);

if ($pId) {
    $parentMenu = $menus->getObject($pId);
    $template->assign('parentMenu', $parentMenu);
}

$menuList = $menus->getObjects(1, "`store_id` = '$storeId' AND `status` = '1' AND `parent_id` = '$pId'", array("id" => "ASC"), 2000);
$template->assign('menuList', $menuList);
?>