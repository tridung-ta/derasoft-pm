<?php
/*************************************************************************
Staff clean trash module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
**************************************************************************/
$templateFile = 'systemstaff.tpl.html';
include_once(ROOT_PATH.'classes/dao/users.class.php');
$users = new Users($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_staff'] => '/'.ADMIN_SCRIPT.'?op=system&act=staff',
				$amessages['list_item'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=staff';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
if($userInfo->isSiteFounder()) $listTabs[$amessages['tracking_title']] = $tabLink.'&mod=listTracking';
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',3);

# Get parameters
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
if($items_per_page) $template->assign('ipp',$items_per_page);
$page = $request->element('pg')?$request->element('pg'):1;
if($page) $template->assign('pg',$page);
$sort_key = $request->element('sk')?$request->element('sk'):'id';
if($sort_key) $template->assign('sk',$sort_key);
$sort_direction = $request->element('sd')?$request->element('sd'):'DESC';
if($sort_direction) $template->assign('sd',$sort_direction);
$do = $request->element('doo')?$request->element('doo'):'';
if($do) $template->assign('do',$do);
$kw = $request->element('kw')?$request->element('kw'):'';
if($kw) $template->assign('kw',$kw);
$gId = $request->element('gId','-1');
if($gId) $template->assign('gId',$gId);
?>