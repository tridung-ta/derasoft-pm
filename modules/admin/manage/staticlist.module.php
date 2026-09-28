<?php
$userInfo->checkPermission('static','view');

# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('s.id','s.menu_id','title','slug','keyword','description','creator_name','updater_name','date_created','date_updated','s.status','position');

$templateFile = 'managestatic.tpl.html';
include_once(ROOT_PATH.'classes/dao/static.class.php');
include_once(ROOT_PATH."classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/menus.class.php');

$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$statics = new StaticPage($storeId);
$search= new Search($storeId);
$menus = new Menus($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_static'] => '/'.ADMIN_SCRIPT.'?op=manage&act=static',
				$amessages['list_item'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=static';
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

$filter_categories = $request->element('filter_categories','-1');
$template->assign('filter_categories',$filter_categories);
if($filter_categories>=0) {
	$topNav[$amessages['list_item']] = '/'.ADMIN_SCRIPT.'?op=manage&act=static&mod=list';
	$topNav[$menus->getNameFromId($filter_categories)] = '';
}

# Get menus  array for generating nested combo
$arrayCategories = $menus->getObjectsForCombo();

# Filter menus categories Combo
$filterMenusCombo = $menus->generateNestedCombo($arrayCategories,$filter_categories);
$template->assign('filterMenusCombo',$filterMenusCombo);

# Build WHERE condition
$condition = "1>0";
if($filter_status != '' && $filter_status != 'all') $condition .= " AND (s.`status`='$filter_status')";

# Filter menus condition
$condition .= $filter_categories>0?" AND `menu_id` = '$filter_categories'":"";

if ($kw) {
	if ($statics->searchCustomField($kw)) {
		$idsOption = $statics->searchCustomField($kw);
		$condition .= " AND (s.`id` IN $idsOption OR s.`slug` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`keyword` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`title` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`description` LIKE '%".controlBackSlashMySQL($kw)."%')";
	} else {
		$condition .= " AND (s.`id`='".controlBackSlashMySQL($kw)."' OR s.`slug` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`keyword` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`title` LIKE '%".controlBackSlashMySQL($kw)."%' OR s.`description` LIKE '%".controlBackSlashMySQL($kw)."%')";
	}
}

$pages_condition = "`store_id` = '$storeId' AND $condition";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $statics->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=static&mod=list&doo=$do&kw=$kw&filter_status=$filter_status&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $statics->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("static");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "static", "static"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("static");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

$fieldList = $optionStructure->getObjects(1, "`status`='1' AND `module`='static'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=static&mod=list&kw=$kw&filter_status=$filter_status&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&filter_categories=$filter_categories";
$template->assign('link',$link);

#Show URL Popup
$template->assign('urlPopup',1);

# Submitted form
if($_POST) {
	switch($do) {
		case 'duplicate':
			$userInfo->checkPermission('static','add');
			$id = $request->element('id');
			if($id) {
				$staticInfo = $statics->getObject($id);
				$property = $staticInfo->getProperties();
				$properties = array();
				$properties['user_upload'] = $property['user_upload'];
				$properties['user_update'] = $property['user_update'];
				$properties['photos'] = '';#$property['photos'];
				$properties['videos'] = '';#$property['videos'];
				$properties['files'] = '';#$property['files'];
				$slug = $staticInfo->getSlug();
				
				# Check if duplicate slug
				$i = 0;
				$dup = 1;
				while($dup) {
					$dup = $statics->checkDuplicate($slug.($i?'-'.$i:''),'slug',"id = '$id'");
					if($dup) $i++;
				}
				$slug .= $i?'-'.$i:'';

				$data = array('store_id' => $storeId,
				  			  'slug' => $slug,
							  'keywords' => $staticInfo->getKeywords(),
							  'title' => $staticInfo->getTitle(),
				  			  'sapo' => $staticInfo->getSapo(),
							  'details' => $staticInfo->getDetails(),
							  'status' => $staticInfo->getStatus(),
							  'properties' => serialize($properties),
							  'date_created' => date("Y-m-d H:i:s"));
				$statics->addData($data);
				$result_code = 8;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['duplicate_static'],$staticInfo->getTitle()),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} 
			break;
		case 'enable':
			$userInfo->checkPermission('static','edit');
			$id = $request->element('id');
			if($id) {
				$statics->changeStatus($id,S_ENABLED);
                $search->changeStatus('static',$id,S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_static'],$statics->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$statics->changeStatus($id,S_ENABLED);
                        $search->changeStatus('static',$id,S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listArticle .= ($listArticle?',&nbsp;':'').$statics->getTitleFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_static'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('static','edit');
			$id = $request->element('id');
			if($id) {
				$statics->changeStatus($id,S_DISABLED);
                $search->changeStatus('static',$id,S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_static'],$statics->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$statics->changeStatus($id,S_DISABLED);
                        $search->changeStatus('static',$id,S_DISABLED);
						$listArticle .= ($listArticle?',&nbsp;':'').$statics->getTitleFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_static'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('static','delete');
			$id = $request->element('id');
			if($id) {
				$statics->changeStatus($id,S_DELETED);
                $search->changeStatus('static',$id,S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_static'],$statics->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$statics->changeStatus($id,S_DELETED);
                        $search->changeStatus('static',$id,S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listArticle .= ($listArticle?',&nbsp;':'').$statics->getTitleFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_static'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		
		case 'changeposition':
			$userInfo->checkPermission('static','edit');
			$positions = $request->element('positions');
			if($positions) {
				foreach ($positions as $key=>$value) {
					$statics->changePosition($key,$value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_static_position'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('static','clean',0);
			$statics->cleanTrash();
			$fieldValue->deleteData();
			$result_code = 5;
            $search->cleanTrash('static');
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_static'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=static&mod=list&lang=$lang&ecode=7");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=static&mod=list&doo=$do&kw=$kw&filter_status=$filter_status&filter_categories=$filter_categories&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {

}
?>
