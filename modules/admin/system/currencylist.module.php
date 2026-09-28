<?php
/*************************************************************************
Currency listing module
----------------------------------------------------------------
Derasoft CMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 28/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemcurrency.tpl.html';
include_once(ROOT_PATH.'classes/dao/currencies.class.php');
$currencies = new Currencies($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_currency'] => '/'.ADMIN_SCRIPT.'?op=system&act=currency',
				$amessages['list_item'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=currency';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

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
$pId = $request->element('pId')?$request->element('pId'):0;
if($pId) {
	$gfId = $currencies->getParentIdFromId($pId);
	$template->assign('pId',$pId);
	$template->assign('gfId',$gfId);
	$topNav[$amessages['list_item']] = '/'.ADMIN_SCRIPT.'?op=system&act=currency&mod=list';
	$topNav[$currencies->getNameFromId($pId)] = '';
}

# Build WHERE condition
$condition = "1>0";
if($kw) $condition = "(`id`='$kw' OR `name` LIKE '%$kw%' OR `display` LIKE '%$kw%')";
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $currencies->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=system&act=currency&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=%d";
$pager = Url::genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $currencies->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=system&act=currency&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=$page";
$template->assign('link',$link);

# Show URL Popup
#$template->assign('urlPopup',1);

if($_POST) {
	switch($do) {
		case 'enable':
			$id = $request->element('id');
			if($id) {
				$currencies->changeStatus($id,S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_currency'],$currencies->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$currencies->changeStatus($id,S_ENABLED);
						$listArticle .= ($listArticle?',&nbsp;':'').$currencies->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_currency'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$id = $request->element('id');
			if($id) {
				if(!$currencies->checkPrimaryFromId($id)) {
					$currencies->changeStatus($id,S_DISABLED);
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_currency'],$currencies->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 2;
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						if(!$currencies->checkPrimaryFromId($id)) {
							$currencies->changeStatus($id,S_DISABLED);
							$listArticle .= ($listArticle?',&nbsp;':'').$currencies->getNameFromId($id);
						}
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_currency'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$id = $request->element('id');
			if($id) {
				if(!$currencies->checkPrimaryFromId($id)) {
					$currencies->changeStatus($id,S_DELETED,"`primary` <> '1'");
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_currency'],$currencies->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 3;
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						if(!$currencies->checkPrimaryFromId($id)) {
							$currencies->changeStatus($id,S_DELETED,"`primary` <> '1'");
							$listArticle .= ($listArticle?',&nbsp;':'').$currencies->getNameFromId($id);
						}
					}
					if($listArticle) {
						$result_code = 3;
						# Operation tracking
						$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_currency'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					} else $error_code = 3;
				} else $error_code = 5;
			}
			break;
		case 'cleantrash':
			checkPermission(3);
			$currencies->cleanTrash();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_currency'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'update':
			$rates = $request->element('rates');
			$positions = $request->element('positions');
			$decimal = $request->element('decimal');
			if($positions) {
				foreach ($positions as $key=>$value) {
					$currencies->changePosition($key,$value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_currency_position'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			}
			if($rates) {
				foreach ($rates as $key=>$value) {
					if($currencies->checkPrimaryFromId($key)) $value = 1;
					if($value<0) $value = 1;
					$currencies->changeRate($key,str_replace(",","",$value));
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_currency_rate'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			}
			if($decimal) {
				foreach ($decimal as $key=>$value) {
					$currencies->changeDecimal($key,$value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_currency_rate'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'primary':
			$id = $request->element('id');
			if($id) {
				$currencies->setPrimary($id);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['set_primary_currency'],$currencies->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$error_code = 5;
			}
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=currency&mod=list&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=currency&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {

}
?>