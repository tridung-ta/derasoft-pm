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
$userInfo->checkPermission('specifications', 'view');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
$templateFile = 'managespecifications.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/specifications.class.php');
$productOptions = new ProductOptions($storeId);
$specifications = new Specifications(1);
$fields = new Fields($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_specifications'] => '/' . ADMIN_SCRIPT . '?op=manage&act=specifications',
	$amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=specifications';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);
#danh sách thuong hiệu
$listproductOptions = $productOptions->getObjects(1, "`status`='1'", array('id' => 'ASC'), 9999);
if ($listproductOptions) $template->assign('listproductOptions', $listproductOptions);

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
$pId = $request->element('pId', '-1');
if ($pId)$template->assign('pId', $pId);
$trademark = $request->element('trademark', ' ');
if ($trademark)$template->assign('trademark', $trademark);
$pId2 = $request->element('pId2') ? $request->element('pId2') : 0;
if ($pId2) $cat_id = $pId2;
# Build WHERE condition

$condition = $pId > 0 ? "`parent_id` = '$pId' AND `cat_id` = '$cat_id'" : "1>0 AND `cat_id` = '0'";
if ($kw) $condition = "`name` LIKE '%$kw%' ";
if($trademark)$condition .= "AND `mc_id` = '$trademark'";
$pages_condition = "`store_id` = '$storeId' AND $condition";
$sort = array($sort_key => $sort_direction);
# Page navigation
$rowsPages = $specifications->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=specifications&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&trademark=$trademark&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);
# Get objects

$listItems = $specifications->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);
# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);
# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=specifications&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&trademark=$trademark&pg=$page";
$template->assign('link', $link);
if ($_POST) {
	switch ($do) {
		case 'enable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$specifications->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_specifications'], $specifications->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$specifications->changeStatus($id, S_ENABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $specifications->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_specifications'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$specifications->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_specifications'], $specifications->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$specifications->changeStatus($id, S_DISABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $specifications->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_specifications'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('product', 'delete');
			$id = $request->element('id');
			if ($id) {
				$specifications->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_specifications'], $specifications->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$specifications->changeStatus($id, S_DELETED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $specifications->getNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_specifications'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'cleantrash':
			$userInfo->checkPermission('product', 'clean', 0);
				$specifications->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_specifications'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=specifications&mod=list&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=specifications&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId&pId2=$pId2&trademark=$trademark");
}
