<?php
/*************************************************************************
Area listing module
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 07/05/2012
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
$templateFile = 'systemmasterarea.tpl.html';
include_once(ROOT_PATH.'classes/dao/areas.class.php');
include_once(ROOT_PATH.'classes/dao/countries.class.php');
$areas = new Areas($storeId);
$countries = new Countries($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_master_data'] => '/'.ADMIN_SCRIPT.'?op=system&act=master',
				$amessages['manage_master_data_area'] => '/'.ADMIN_SCRIPT.'?op=system&act=master&mod=arealist',
				$amessages['list_item'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=master';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=arealist',
				$amessages['add_new'] => $tabLink.'&mod=areaadd',
				$amessages['clean_trash'] => $tabLink.'&mod=areacleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

# Items per page
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
if($items_per_page) $template->assign('ipp',$items_per_page);
$page = $request->element('pg')?$request->element('pg'):1;
if($page) $template->assign('pg',$page);

# Sort key
$sort_key = $request->element('sk')?$request->element('sk'):'name';
if($sort_key) $template->assign('sk',$sort_key);

# Sort direction
$sort_direction = $request->element('sd')?$request->element('sd'):'ASC';
if($sort_direction) $template->assign('sd',$sort_direction);

# Action
$do = $request->element('doo')?$request->element('doo'):'';
if($do) $template->assign('do',$do);
$kw = $request->element('kw')?$request->element('kw'):'';
if($kw) $template->assign('kw',$kw);

# Filter country
$filter_country = $request->element('filter_country')!=''?$request->element('filter_country'):'';
if(isset($filter_country)) $template->assign('filter_country',$filter_country);
if($do != 'search' && !$filter_country) $filter_country = 'all';

# Countries combo box
$countriesCombo = $countries->generateCombo($request->element('filter_country'));
if($countriesCombo) $template->assign('countriesCombo',$countriesCombo);

# Build WHERE condition
$condition = "(1>0)";
if($filter_country != '' && $filter_country != 'all') $condition .= " AND (`country_id`='$filter_country')";
if($kw) $condition = "(a.`id`='".controlBackSlashMySQL($kw)."' OR a.`name` LIKE '%".controlBackSlashMySQL($kw)."%')";
$pages_condition = "a.`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $areas->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=system&act=master&mod=arealist&doo=$do&kw=".urlencode($kw)."&filter_country=$filter_country&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $areas->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=system&act=master&mod=arealist&kw=".urlencode($kw)."&filter_country=$filter_country&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=$page";
$template->assign('link',$link);

# Show URL Popup
$template->assign('urlPopup',1);

if($_POST) {
	switch($do) {
		case 'enable':
			$id = $request->element('id');
			if($id) {
				$areas->changeStatus($id,S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_area'],$areas->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$areas->changeStatus($id,S_ENABLED);
						$listArticle .= ($listArticle?',&nbsp;':'').$areas->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_area'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$id = $request->element('id');
			if($id) {
				$areas->changeStatus($id,S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_area'],$areas->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$areas->changeStatus($id,S_DISABLED);
						$listArticle .= ($listArticle?',&nbsp;':'').$areas->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_area'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$id = $request->element('id');
			if($id) {
				$areas->changeStatus($id,S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_area'],$areas->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$areas->changeStatus($id,S_DELETED);
						$listArticle .= ($listArticle?',&nbsp;':'').$areas->getNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_area'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changegroup':
			$ids = $request->element('ids');
			$parent_id = $request->element('parent_id');
			if(!$parent_id) $error_code = 9;
			else {
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$areas->changeCatId($id,$parent_id);
						$listArticle .= ($listArticle?',&nbsp;':'').$areas->getNameFromId($id);
					}
					$result_code = 4;
					$pId = $parent_id;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['change_article_group'],$listArticle,$areas->getNameFromId($parent_id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changeposition':
			$positions = $request->element('positions');
			if($positions) {
				foreach ($positions as $key=>$value) {
					$areas->changePosition($key,$value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_area_position'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			checkPermission(3);
			$areas->cleanTrash();
			$result_code = 5;
			
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_area'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;		
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=master&mod=arealist&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=master&mod=arealist&doo=$do&kw=".urlencode($kw)."&filter_country=$filter_country&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {

}
?>