<?php
/*************************************************************************
Ads category listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 01/05/2012
Coder: Mai Minh
Checked by: Mai Minh (07/05/2012)
**************************************************************************/
$userInfo->checkPermission('banner','view');
$templateFile = 'manageads.tpl.html';
include_once(ROOT_PATH.'classes/dao/adscategories.class.php');
$adsCategories = new AdsCategories($storeId);
#$adsCategories = new AdsCategories();
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_banner'] => '/'.ADMIN_SCRIPT.'?op=manage&act=ads',
				$amessages['list_banner_category'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=ads';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['list_ads_category'] => $tabLink.'&mod=listcategory',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',3);

# Get parameters
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
if($items_per_page) $template->assign('ipp',$items_per_page);
$page = $request->element('pg')?$request->element('pg'):1;
if($page) $template->assign('pg',$page);
$sort_key = "store_id";
if($sort_key) $template->assign('sk',$sort_key);
$sort_direction ='ASC';
if($sort_direction) $template->assign('sd',$sort_direction);
$do = $request->element('doo')?$request->element('doo'):'';
if($do) $template->assign('do',$do);
$kw = $request->element('kw')?$request->element('kw'):'';
if($kw) $template->assign('kw',$kw);
$pId = $request->element('pId')?$request->element('pId'):0;

# Build WHERE condition
$condition = "`status` = '1'";
if($kw) $condition = "(`id`='$kw' OR `name` LIKE '%$kw%')";
$pages_condition = "(`store_id` = '$storeId' or `store_id`=0)  AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $adsCategories->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=ads&mod=listcategory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $adsCategories->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=ads&mod=listcategory&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=$page";
$template->assign('link',$link);

if($_POST) {
	switch($do) {
		case 'enable':
			$userInfo->checkPermission('banner','edit');
			$id = $request->element('id');
			if($id) {
				$properties = $estore->getProperties();
				if(!$properties) $properties = array('');
				$properties['ads_category'][$id] = array('enable' => 1,'rows'=> isset($properties['ads_category'][$id]['rows'])?$properties['ads_category'][$id]['rows']:1);
				$data = array('properties' => serialize($properties));
				# Update Estore properties
				$stores->updateData($data,$storeId);
				$estore = $stores->getObject($storeId);
				
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_ads_category'],$adsCategories->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listCategory = '';
					$properties = $estore->getProperties();
					if(!$properties) $properties = array('');
					foreach ($ids as $id) {
						$properties['ads_category'][$id] = array('enable' => 1,'rows'=> isset($properties['ads_category'][$id]['rows'])?$properties['ads_category'][$id]['rows']:1);
						$listCategory .= ($listCategory?',&nbsp;':'').$adsCategories->getNameFromId($id);
					}
					$data = array('properties' => serialize($properties));
					# Update Estore properties
					$stores->updateData($data,$storeId);
					$estore = $stores->getObject($storeId);
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_ads_category'],$listCategory),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('banner','edit');
			$id = $request->element('id');
			if($id) {
				$properties = $estore->getProperties();
				if(!$properties) $properties = array('');
				$properties['ads_category'][$id] = array('enable' => 0,'rows'=> isset($properties['ads_category'][$id]['rows'])?$properties['ads_category'][$id]['rows']:1);
				$data = array('properties' => serialize($properties));
				# Update Estore properties
				$stores->updateData($data,$storeId);
				$estore = $stores->getObject($storeId);
				
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_ads_category'],$adsCategories->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listCategory = '';
					$properties = $estore->getProperties();
					if(!$properties) $properties = array('');
					foreach ($ids as $id) {
						$properties['ads_category'][$id] = array('enable' => 0,'rows'=> isset($properties['ads_category'][$id]['rows'])?$properties['ads_category'][$id]['rows']:1);
						$listCategory .= ($listCategory?',&nbsp;':'').$adsCategories->getNameFromId($id);
					}
					$data = array('properties' => serialize($properties));
					# Update Estore properties
					$stores->updateData($data,$storeId);
					$estore = $stores->getObject($storeId);
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_ads_category'],$listCategory),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'updateIpp':
			$userInfo->checkPermission('banner','edit');
			$ipps = $request->element('ipps');
			if($ipps) {
				$listCategory = '';
				$properties = $estore->getProperties();
				if(!$properties) $properties = array('');
				foreach ($ipps as $key=>$value) {
					$properties['ads_category'][$key] = array('enable' => $properties['ads_category'][$key]['enable'],'rows'=> $value);
					$listCategory .= ($listCategory?',&nbsp;':'').$adsCategories->getNameFromId($key);
				}
				$data = array('properties' => serialize($properties));
				# Update Estore properties
				$stores->updateData($data,$storeId);
				$estore = $stores->getObject($storeId);					
					
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_ads_category'],$listCategory),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('banner','clean',0);
			$adsCategories->cleanTrash();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_ads_category'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=ads&mod=listcategory&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=ads&mod=listcategory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {

}
?>