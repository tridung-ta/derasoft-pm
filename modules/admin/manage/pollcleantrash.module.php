<?php
/*************************************************************************
Adding question module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 28/06/2012
Coder: Thai Nguyen
**************************************************************************/
$userInfo->checkPermission('poll','add');
$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH.'classes/dao/answers.class.php');
include_once(ROOT_PATH.'classes/dao/questions.class.php');
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$question = new Questions();
$answer = new Answers();
$fields = new Fields($storeId);
$gallery_root = ROOT_PATH."upload/$storeId/";
$gallery_path = $gallery_root."articles/";
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_poll'] => '/'.ADMIN_SCRIPT.'?op=manage&act=poll',
				$amessages['add_question'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=poll';
$listTabs = array($amessages['list_answer'] => $tabLink.'&mod=list',
				$amessages['add_answer'] => $tabLink.'&mod=addanswer',
				$amessages['list_question'] => $tabLink.'&mod=listquestion',
				$amessages['add_question'] => $tabLink.'&mod=addquestion',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',5);

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
$qId = $request->element('qId','-1');
if($qId>=0) {
	//$gfId = $articleCategories->getParentIdFromId($pId);
	$template->assign('qId',$qId);
	//$template->assign('gfId',$gfId);
	$topNav[$amessages['list_item']] = '/'.ADMIN_SCRIPT.'?op=manage&act=poll&mod=list';
	$topNav[$answer->getTitleFromId($qId)] = '';
}

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=poll&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&qId=$qId&pg=$page";
$template->assign('link',$link);
?>