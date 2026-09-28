<?php
/*************************************************************************
Acticle category listing module
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Coder: Mai Minh
Checked by: Mai Minh (14/06/2025)
**************************************************************************/
// error_reporting(E_ALL);
// 	ini_set('display_errors', TRUE);
// 	ini_set('display_startup_errors', TRUE);

# Check permission
$userInfo->checkPermission('category','view');

# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('id','slug','name','keyword','description','sort_key','items_per_page','layout','position','article_count','viewed','date_created','date_updated','status','creator_id','updater_id');

$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH.'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH.'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$articleCategories = new ArticleCategories($storeId);
$articles = new Articles($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_article'] => '/'.ADMIN_SCRIPT.'?op=manage&act=article',
				$amessages['list_article_category'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=article';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['list_article_category'] => $tabLink.'&mod=listcategory',
				$amessages['add_article_category'] => $tabLink.'&mod=addcategory',
				$amessages['list_articlegroup'] => $tabLink.'&mod=listgroup',
				$amessages['add_articlegroup'] => $tabLink.'&mod=addgroup',	
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',3);

# Get parameters
$items_per_page = $request->element('ipp',DEFAULT_ADMIN_ROWS_PER_PAGE);
$template->assign('ipp',$items_per_page);
$page = $request->element('pg',1);
$template->assign('pg',$page);

# Sort key
$sort_key = $request->element('sk','id');
if(!in_array($sort_key,$allow_sort_keys)) $sort_key='id';
$template->assign('sk',$sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd',$sort_direction);
$do = $request->element('doo','');
$template->assign('do',$do);

# Get article categories array for generating nested combo
$arrayCategories = $articleCategories->getObjectsForCombo();

# Bottom article categories Combo
$bottomArticleCategoryCombo = $articleCategories->generateNestedCombo($arrayCategories);
$template->assign('bottomArticleCategoryCombo',$bottomArticleCategoryCombo);

# Keywords
$kw = $request->element('kw','');
$template->assign('kw',$kw);

# Parent id
$pId = $request->element('pId',0);
if($pId>0) {
	$gfId = $articleCategories->getParentIdFromId($pId);
	$template->assign('pId',$pId);
	$template->assign('gfId',$gfId);
	$topNav[$amessages['list_article_category']] = '/'.ADMIN_SCRIPT.'?op=manage&act=article&mod=listcategory';
	$topNav[$articleCategories->getNameFromId($pId)] = '';
}
$template->assign('pId',$pId);


# Build WHERE condition
$condition = "c.`parent_id` = '$pId'";

if ($kw) {
	if ($articleCategories->searchCustomField($kw)) {
		$idsOption = $articleCategories->searchCustomField($kw);
		$condition = "(`id` IN $idsOption OR `slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
	} else {
		$condition = "(`id`='$kw' OR `slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
	}
}
$pages_condition = "c.`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $articleCategories->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listcategory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $articleCategories->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("articlecategory");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "article_categories", "articlecategory"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("articlecategory");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listcategory&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=$page";
$template->assign('link',$link);

# Show URL Popup
$template->assign('urlPopup',1);

if($_POST) {
	switch($do) {
		case 'enable':
			$userInfo->checkPermission('category','edit');
			$id = $request->element('id');
			if($id) {
				$articleCategories->changeStatus($id,S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_article_category'],$articleCategories->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listCategory = '';
					foreach ($ids as $id) {
						$articleCategories->changeStatus($id,S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listCategory .= ($listCategory?',&nbsp;':'').$articleCategories->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_article_category'],$listCategory),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('category','edit');
			$id = $request->element('id');
			if($id) {
				$articleCategories->changeStatus($id,S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_article_category'],$articleCategories->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listCategory = '';
					foreach ($ids as $id) {
						$articleCategories->changeStatus($id,S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listCategory .= ($listCategory?',&nbsp;':'').$articleCategories->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_article_category'],$listCategory),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('category','delete');
			$id = $request->element('id');
			if($id) {
				$articleCategories->changeStatus($id,S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_article_category'],$articleCategories->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listCategory = '';
					foreach ($ids as $id) {
						$articleCategories->changeStatus($id,S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listCategory .= ($listCategory?',&nbsp;':'').$articleCategories->getNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_article_category'],$listCategory),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changegroup':
			$userInfo->checkPermission('category','edit');
			$ids = $request->element('ids');
			$new_parent_id = $request->element('new_parent_id');
			if($ids) {
				$listCategory = '';
				foreach ($ids as $id) {
					$articleCategories->changeParentId($id,$new_parent_id);
					$listCategory .= ($listCategory?',&nbsp;':'').$articleCategories->getNameFromId($id);
				}
				$result_code = 4;
				$pId = $new_parent_id;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['change_parent_article_category'],$listCategory,$articleCategories->getNameFromId($new_parent_id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'changeposition':
			$userInfo->checkPermission('category','edit');
			$positions = $request->element('positions');
			if($positions) {
				foreach ($positions as $key=>$value) {
					$articleCategories->changePosition($key,$value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_position_article_category'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listcategory&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listcategory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {

}
?>