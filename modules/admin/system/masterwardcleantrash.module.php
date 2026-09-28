<?php
/*************************************************************************
Area listing module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
**************************************************************************/
$templateFile = 'systemmasterarea.tpl.html';
include_once(ROOT_PATH.'classes/dao/wards.class.php');
$wards = new Wards($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_master_data'] => '/'.ADMIN_SCRIPT.'?op=system&act=master',
				$amessages['manage_master_data_area'] => '/'.ADMIN_SCRIPT.'?op=system&act=master&mod=wardlist',
				$amessages['clean_trash'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=master';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=arealist',
				$amessages['add_new'] => $tabLink.'&mod=areaadd',
				$amessages['clean_trash'] => '');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',3);

# Item per pages
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