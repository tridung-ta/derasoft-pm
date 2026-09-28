<?php
/*************************************************************************
product feature listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Coder: Mai Minh
Checked by: Mai Minh (07/05/2012)
**************************************************************************/
    //    ini_set('display_errors', 1);
    //     ini_set('display_startup_errors', 1);
    //     error_reporting(E_ALL);
$userInfo->checkPermission('product','viewfeature');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/customproductoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptiondefault.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/productfeatures.class.php");
$template->assign('imgs',$imgs);
$productFeatures = new ProductFeatures($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
				$amessages['list_item'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=product';
$listTabs = array(
                $amessages['list_item'] => $tabLink . '&mod=list',
                $amessages['add_new'] => $tabLink . '&mod=add',
                $amessages['list_category'] => $tabLink . '&mod=listcategory',
                $amessages['add_product_category'] => $tabLink . '&mod=addcategory',
                $amessages['list_product_features'] => $tabLink . '&mod=listfeature',
                $amessages['add_product_features'] => $tabLink . '&mod=addfeature',
                $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
                );			
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

# Build WHERE condition
$condition = "1>0";
if ($kw) {
    $kw = controlBackSlashMySQL($kw);
    $idsOption = $productFeatures->searchCustomField($kw);

    if (!empty($idsOption)) {
        $idsString = implode(',', $idsOption);
        $condition .= " AND (`id` IN ($idsString) OR `slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
    } else {
        $condition .= " AND (`slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
    }
}
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $productFeatures->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=product&mod=listfeature&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%g";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $productFeatures->getObjects($page,$condition,$sort,$items_per_page);
if ($listItems) {
	foreach ($listItems as $item) {
		if ($avatarId = (int)$item->getAvatar()) {
			$item->avatarImg = $uploads->getObject($avatarId);
		}
	}
	$template->assign('listItems', $listItems);
}

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("productfeature");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "product_features", "productfeature"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("productfeature");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=product&mod=listfeature&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page";
$template->assign('link',$link);

if($_POST) {
	switch($do) {
		case 'enable':
			$userInfo->checkPermission('product','editfeature');
			$id = $request->element('id');
			if($id) {
				$productFeatures->changeStatus($id,S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_productfeature'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listAd = '';
					foreach ($ids as $id) {
						$productFeatures->changeStatus($id,S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listAd .= ($listAd?',&nbsp;':'').$id;
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_productfeature'],$listAd),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('product','editfeature');
			$id = $request->element('id');
			if($id) {
				$productFeatures->changeStatus($id,S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_productfeature'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listAd = '';
					foreach ($ids as $id) {
						$productFeatures->changeStatus($id,S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listAd .= ($listAd?',&nbsp;':'').$id;
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_productfeature'],$listAd),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('product','deletefeature');
			$id = $request->element('id');
			if($id) { 
				$productFeatures->changeStatus($id,S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_productfeature'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listAd = '';
					foreach ($ids as $id) {
						$productFeatures->changeStatus($id,S_DELETED);
						$listAd .= ($listAd?',&nbsp;':'').$id;
						$fieldValue->changeStatus($id, S_DELETED);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_productfeature'],$listAd),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'cleantrash':
			$userInfo->checkPermission('product','cleanfeature',0);
			$productFeatures->cleanTrash();
			$fieldValue->deleteData();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_productfeature'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=product&mod=listfeature&lang=$lang&ecode=7");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=product&mod=listfeature&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {

}
?>