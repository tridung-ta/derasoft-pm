<?php
/*************************************************************************
Slugs listing module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Checked by: Mai Minh (06/08/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('slug','view');

# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('s.id','slug','module','object_id','creator_name','updater_name','date_created','date_updated','s.status');

$templateFile = 'manageslug.tpl.html';
include_once(ROOT_PATH . 'classes/dao/slugs.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$slugs = new Slugs($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_slug'] => '/'.ADMIN_SCRIPT.'?op=manage&act=slug',
				$amessages['list_item'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=slug';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

# Items per page
$items_per_page = $request->element('ipp',DEFAULT_ADMIN_ROWS_PER_PAGE);
$template->assign('ipp',$items_per_page);
$page = $request->element('pg',1);
$template->assign('pg',$page);

# Sort key
$sort_key = $request->element('sk','s.id');
if(!in_array($sort_key,$allow_sort_keys)) $sort_key='s.id';
$template->assign('sk',$sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd',$sort_direction);
$do = $request->element('doo','');
$template->assign('do',$do);

# Keywords
$kw = $request->element('kw','');
$template->assign('kw',$kw);

# Filter status
$filter_status = $request->element('filter_status','');
$template->assign('filter_status',$filter_status);
if($do != 'search' && !$filter_status) $filter_status = 'all';

# Build WHERE condition
$condition = "1>0";
if($filter_status != '' && $filter_status != 'all') $condition .= " AND (s.`status`='$filter_status')";

if ($kw) {
	if ($slugs->searchCustomField($kw)) {
		$idsOption = $slugs->searchCustomField($kw);
		$condition .= " AND (s.`id` IN $idsOption OR s.`slug` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`module` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`object_id` LIKE '%".controlBackSlashMySQL($kw)."%')";
	} else {
		$condition .= " AND (s.`id`='".controlBackSlashMySQL($kw)."' OR s.`slug` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`module` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`object_id` LIKE '%".controlBackSlashMySQL($kw)."%')";
	}
}

$pages_condition = "`store_id` = '$storeId' AND $condition";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $slugs->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=slug&mod=list&doo=$do&kw=$kw&filter_status=$filter_status&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $slugs->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("slug");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "slugs", "slug"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("slug");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

$fieldList = $optionStructure->getObjects(1, "`status`='1' AND `module`='slug'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=slug&mod=list&kw=$kw&filter_status=$filter_status&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page";
$template->assign('link',$link);

#Show URL Popup
$template->assign('urlPopup',1);

# Submitted form
if($_POST) {
	switch($do) {
		case 'duplicate':
			$userInfo->checkPermission('slug','add');
			$id = $request->element('id');
			if($id) {
				$slugInfo = $slugs->getObject($id);
				$properties = array();
				$slug = $slugInfo->getSlug();
				
				# Check if duplicate slug
				$i = 0;
				$dup = 1;
				while($dup) {
					$dup = $slugs->checkDuplicate($slug.($i?'-'.$i:''),'slug',"id = '$id'");
					if($dup) $i++;
				}
				$slug .= $i?'-'.$i:'';

				$data = array('store_id' => $storeId,
				  			  'slug' => $slug,
							  'module' => $slugInfo->getModule(),
							  'object_id' => $slugInfo->getObjectId(),
							  'status' => $slugInfo->getStatus(),
							  'properties' => serialize($properties),
							  'date_created' => date("Y-m-d H:i:s"),
							  'creator_id' => $userInfo->getId());
				
				$slugs->addData($data);
				$result_code = 8;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['duplicate_slug'],$slugInfo->getSlug()),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} 
			break;
		case 'enable':
			$userInfo->checkPermission('slug','edit');
			$id = $request->element('id');
			if($id) {
				$slugs->changeStatus($id,S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_slug'],$slugs->getSlugFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listSlugs = '';
					foreach ($ids as $id) {
						$slugs->changeStatus($id,S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listSlugs .= ($listSlugs?',&nbsp;':'').$slugs->getSlugFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_slug'],$listSlugs),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('slug','edit');
			$id = $request->element('id');
			if($id) {
				$slugs->changeStatus($id,S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_slug'],$slugs->getSlugFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listSlugs = '';
					foreach ($ids as $id) {
						$slugs->changeStatus($id,S_DISABLED);
						$listSlugs .= ($listSlugs?',&nbsp;':'').$slugs->getSlugFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_slug'],$listSlugs),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('slug','delete');
			$id = $request->element('id');
			if($id) {
				$slugs->changeStatus($id,S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_slug'],$slugs->getSlugFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listSlugs = '';
					foreach ($ids as $id) {
						$slugs->changeStatus($id,S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listSlugs .= ($listSlugs?',&nbsp;':'').$slugs->getSlugFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_slug'],$listSlugs),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		
		case 'cleantrash':
			$userInfo->checkPermission('slug','clean',0);
			$slugs->cleanTrash();
			$fieldValue->deleteData();
			$result_code = 5;
			
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_slug'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=slug&mod=list&lang=$lang&ecode=7");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=slug&mod=list&doo=$do&kw=$kw&filter_status=$filter_status&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {

}
?>
