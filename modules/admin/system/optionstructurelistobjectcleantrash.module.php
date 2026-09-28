<?php
$templateFile = 'systemoptionstructure.tpl.html';
include_once(ROOT_PATH . 'classes/dao/optionobject.class.php');

$optionObject = new OptionObject($storeId);

$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['system_custom_options'] => '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure',
	$amessages['clean_trash'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['custom_list_field_value'] => $tabLink . '&mod=listvalue',
	$amessages['custom_list_field_object'] => $tabLink . '&mod=listobject',
	$amessages['add_new_object'] => $tabLink . '&mod=listobjectadd',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	$amessages['clean_object'] => $tabLink . '&mod=listobjectcleantrash'

);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 7);

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
