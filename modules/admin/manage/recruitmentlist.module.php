<?php

/*************************************************************************
Product listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 10/05/2012
Checked by: Mai Minh (10/05/2012)
 **************************************************************************/

// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
//         ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);
//     }


// $userInfo->checkPermission('recruitment', 'view');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
$templateFile = 'managerecruitment.tpl.html';
include_once(ROOT_PATH . 'classes/dao/recruitment.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$recruitments = new Recruitments(1);
$fields = new Fields($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	'Tuyển dụng' => '/' . ADMIN_SCRIPT . '?op=manage&act=recruitment',
	$amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=recruitment';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	'Danh sách ứng tuyển' => $tabLink . '&mod=listapplicants',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);


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
$gId = $request->element('gId','-1');
if($gId) $template->assign('gId',$gId);
$pId = $request->element('pId','0');
if($pId) $template->assign('pId',$pId);

# Build WHERE condition
$condition = $gId>=0?"`parent_id` = 0 AND `gid` = '$gId'":"`parent_id` = 0 AND 1>0";
if ($kw) {
	if ($recruitments->searchCustomField($kw)) {
		$idsOption = $recruitments->searchCustomField($kw);
		$condition = "(`id` IN $idsOption OR `id`='$kw')";
	} else {
		$condition = "(`id`='$kw' )";
	}
}
// if($kw) $condition = "(`id`='$kw' )";
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $recruitments->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);

if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=recruitment&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);
# Get objects

$listItems = $recruitments->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("recruitment");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "recruitment", "recruitment"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("recruitment");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);
# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=recruitment&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=$page";
$template->assign('link', $link);
if ($_POST) {
	switch ($do) {
		case 'enable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$recruitments->changeStatus($id, S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_recruitment'], $recruitments->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$recruitments->changeStatus($id, S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $recruitments->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_recruitment'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$recruitments->changeStatus($id, S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_recruitment'], $recruitments->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$recruitments->changeStatus($id, S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $recruitments->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_recruitment'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('product', 'delete');
			$id = $request->element('id');
			if ($id) {
				$recruitments->changeStatus($id, S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_recruitment'], $recruitments->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$recruitments->changeStatus($id, S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $recruitments->getNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_recruitment'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'cleantrash':
			$cleanApply = $request->element('cleanapply');
			$userInfo->checkPermission('product', 'clean', 0);
			$recruitments->cleanTrash('`parent_id` = 0');
			$fieldValue->deleteData();
			$result_code = 5;

			if ($cleanApply == 1) {
				$recruitments->cleanTrash('`parent_id` <> 0');
				$result_code = 5;
			}
			# Operation tracking
			// $trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_recruitment'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=recruitment&mod=list&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=recruitment&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId&pId2=$pId2");
}
