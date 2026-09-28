<?php
/*************************************************************************
Menus clean trash module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 07/05/2012
Coder: Mai Minh
Checked by: Mai Minh (07/05/2012)
**************************************************************************/
// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
//     ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);
// }
// $userInfo->checkPermission('menu','clean',0);
$templateFile = 'managecontact.tpl.html';
include_once(ROOT_PATH.'classes/dao/contacts.class.php');
$contacts = new Contacts($storeId);
$topNav = array(
  $amessages['dash_board']      => '/' . ADMIN_SCRIPT . '?op=dashboard',
  $amessages['manage_website']  => '/' . ADMIN_SCRIPT . '?op=manage',
  $amessages['manage_contact']  => '/'.ADMIN_SCRIPT.'?op=manage&act=contact',
  $amessages['edit']             => ''
);
$template->assign('topNav', $topNav);

// Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=contact';
$listTabs = array(
  $amessages['list_item']   => $tabLink . '&mod=list',
  // $amessages['add_new']        => $tabLink . '&mod=add',
  $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 3);

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
?>