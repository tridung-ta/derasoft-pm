<?php
/*************************************************************************
Upload trash module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 05/05/2012
Coder: Mai Minh
Reviewed by: Mai Minh (25/06/2025)
**************************************************************************/
$templateFile = 'manageupload.tpl.html';
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
$uploads = new Uploads($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_gallery'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=upload';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 3);


# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=upload&mod=list";
$template->assign('link', $link);
