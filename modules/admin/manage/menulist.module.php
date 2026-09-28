<?php

/*************************************************************************
Menus listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 19/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (07/05/2012)
 **************************************************************************/

// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
//         ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);
//     }

$userInfo->checkPermission('menu', 'view');
$templateFile = 'managemenu.tpl.html';
include_once(ROOT_PATH . 'classes/dao/menus.class.php');
include_once(ROOT_PATH . 'classes/dao/menucategories.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$menus = new Menus($storeId);
$menuCategories = new MenuCategories();
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_menu'] => '/' . ADMIN_SCRIPT . '?op=manage&act=menu',
	$amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=menu';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_menu_category'] => $tabLink . '&mod=listcategory',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

# Get parameters
$items_per_page = $request->element('ipp') ? $request->element('ipp') : DEFAULT_ADMIN_ROWS_PER_PAGE;
if ($items_per_page) $template->assign('ipp', $items_per_page);
$page = $request->element('pg') ? $request->element('pg') : 1;
if ($page) $template->assign('pg', $page);
$sort_key = $request->element('sk') ? $request->element('sk') : 'id';
if ($sort_key) $template->assign('sk', $sort_key);
$sort_direction = $request->element('sd') ? $request->element('sd') : 'DESC';
if ($sort_direction) $template->assign('sd', $sort_direction);
$do = $request->element('doo') ? $request->element('doo') : '';
if ($do) $template->assign('do', $do);
$kw = $request->element('kw') ? $request->element('kw') : '';
if ($kw) $template->assign('kw', $kw);
$cId = $request->element('cId', '-1');
if ($cId > 0) $template->assign('cId', $cId);

$pId = $request->element('pId') ? $request->element('pId') : 0;
if ($pId) {
	$gfId = $menus->getParentIdFromId($pId);
	$template->assign('pId', $pId);
	$template->assign('gfId', $gfId);
}
# Build WHERE condition
$condition = $cId > 0 ? "`mc_id` = '$cId'" : "1>0";
$condition .= " AND `parent_id` = '$pId'";
if ($kw) {
	if ($menus->searchCustomField($kw)) {
		$idsOption = $menus->searchCustomField($kw);
		$condition = "(`id` IN $idsOption OR `url` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
	} else {
		$condition = "(`id`='$kw' OR `url` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
	}
}
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $menus->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=menu&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&cId=$cId&pId=$pId&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $menus->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("menu");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "menus", "menu"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("menu");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=menu&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&cId=$cId&pId=$pId&pg=$page";
$template->assign('link', $link);

#bottom Action Combo
$categoryCombo = $menuCategories->generateCombo($cId, 1);
if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

if ($_POST) {
	switch ($do) {
		case 'sethome':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$menus->changeHome($id, S_ENABLED);
				$result_code = 7;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf("set home menu", $menus->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deletehome':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$menus->changeHome($id, S_DISABLED);
				$result_code = 7;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf('disable home menu', $menus->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'enable':
			$userInfo->checkPermission('menu', 'edit');
			$id = $request->element('id');
			if ($id) {
				$menus->changeStatus($id, S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_menu'], $menus->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listItems = '';
					foreach ($ids as $id) {
						$menus->changeStatus($id, S_ENABLED);
						$listItems .= ($listItems ? ',&nbsp;' : '') . $menus->getNameFromId($id);
						$fieldValue->changeStatus($id, S_ENABLED);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_menu'], $listItems), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('menu', 'edit');
			$id = $request->element('id');
			if ($id) {
				$menus->changeStatus($id, S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_menu'], $menus->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listItems = '';
					foreach ($ids as $id) {
						$menus->changeStatus($id, S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listItems .= ($listItems ? ',&nbsp;' : '') . $menus->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_menu'], $listItems), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('menu', 'delete');
			$id = $request->element('id');
			if ($id) {
				$menus->changeStatus($id, S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_menu'], $menus->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listItems = '';
					foreach ($ids as $id) {
						$menus->changeStatus($id, S_DELETED);
						$listItems .= ($listItems ? ',&nbsp;' : '') . $menus->getNameFromId($id);
						$fieldValue->changeStatus($id, S_DELETED);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_menu'], $listItems), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changegroup':
			$userInfo->checkPermission('menu', 'edit');
			$ids = $request->element('ids');
			$parent_id = $request->element('parent_id');
			if ($ids) {
				$listItems = '';
				foreach ($ids as $id) {
					$menus->changeCId($id, $parent_id);
					$listItems .= ($listItems ? ',&nbsp;' : '') . $menus->getNameFromId($id);
				}
				$result_code = 4;
				$pId = $parent_id;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['change_menu_group'], $listItems, $menuCategories->getNameFromId($parent_id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'changeposition':
			$userInfo->checkPermission('menu', 'edit');
			$positions = $request->element('positions');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$menus->changePosition($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_menu_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('menu', 'clean', 0);
			$menus->cleanTrash();
			$fieldValue->deleteData();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_menu'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=menu&mod=list&lang=$lang&ecode=7&mId=$mId&cId=$cId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=menu&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&mId=$mId&cId=$cId");
} else {
}
