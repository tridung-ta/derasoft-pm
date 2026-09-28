<?php
$templateFile = 'systemcustomproductoption.tpl.html';
include_once(ROOT_PATH . 'classes/dao/customproductoptiondefault.class.php');

$customProductOptionDefault = new CustomProductOptionDefault($storeId);

$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	'Quản lý product options' => '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption',
	'Dọn rác option mặc định' => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=customproductoption';
$listTabs = array(
	'Danh sách options' => $tabLink . '&mod=list',
	// 'Danh sách values' => $tabLink . '&mod=listvalue',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	'Danh sách option mặc định' => $tabLink . '&mod=listdefault',
	'Thêm mới option mặc định' => $tabLink . '&mod=listdefaultadd',
	'Dọn rác option mặc định' => $tabLink . '&mod=listdefaultcleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 5);

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
$gId = $request->element('gId', '-1');
if ($gId) $template->assign('gId', $gId);
