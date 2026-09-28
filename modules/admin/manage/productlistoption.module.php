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
$userInfo->checkPermission('product', 'view');
$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$productOptions = new ProductOptions($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['list_product_option'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
	$amessages['list_item_lop'] => $tabLink . '&mod=list',
	$amessages['add_lop'] => $tabLink . '&mod=add',
	$amessages['list_item_pk'] => $tabLink . '&mod=listaccessory',
	$amessages['add_pk'] => $tabLink . '&mod=addaccessory',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_tramk'] => $tabLink . '&mod=listoption',
	$amessages['add_tramk'] => $tabLink . '&mod=addoption',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 7);

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
$pId = $request->element('pId') ? $request->element('pId') : 0;
$pId2 = $request->element('pId2') ? $request->element('pId2') : 0;
if ($pId2) $cat_id = $pId2;
# Build WHERE condition
$condition = $pId > 0 ? "`pc_id` = '$pId' AND `cat_id` = '$cat_id'" : "1>0 AND `cat_id` = '0'";
if ($kw) $condition = "(`name` LIKE '%$kw%')";
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $productOptions->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listoption&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId2=$pId2&pId=$pId&pg=%d";

$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $productOptions->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listoption&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId2=$pId2&pId=$pId&pg=$page";
$template->assign('link', $link);

#bottom Action Combo
$categoryCombo = $productCategories->generateCombo($pId, 1);
if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);

# ALlow URL popup
$template->assign('urlPopup', 1);

if ($_POST) {
	switch ($do) {
		case 'enable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productOptions->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_product_option'], $productOptions->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$productOptions->changeStatus($id, S_ENABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $productOptions->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_product_option'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productOptions->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_product_option'], $productOptions->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$productOptions->changeStatus($id, S_DISABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $productOptions->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_product_option'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('product', 'delete');
			$id = $request->element('id');
			if ($id) {
				$productOptions->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_product_option'], $productOptions->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$productOptions->changeStatus($id, S_DELETED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $productOptions->getNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_product_option'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changeposition':
			$userInfo->checkPermission('product', 'edit');
			$positions = $request->element('positions');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$productOptions->changePosition($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_product_option_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('product', 'clean', 0);
			$cleanCategories = $request->element('categories');
			$cleanItems = $request->element('items');
			if ($cleanCategories == 1) {
				$productCategories->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product_category'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			if ($cleanItems == 1) {
				$products->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'sethome':
	
			$userInfo->checkPermission('production', 'edit');
			$id = $request->element('id');
			$pId = $productOptions->getPcIdFromId($id);
			$pId2 = $productOptions->getCatIdFromId($id);
			if ($id) {
				$productOptions->changeHome($id, S_ENABLED);
				$result_code = 7;
				# Operation tracking
			}
			break;
		case 'deletehome':
			$userInfo->checkPermission('production', 'edit');
			$id = $request->element('id');
			$pId = $productOptions->getPcIdFromId($id);
			$pId2 = $productOptions->getCatIdFromId($id);
			if ($id) {
				$productOptions->changeHome($id, S_DISABLED);
				$result_code = 7;
				# Operation tracking
			}
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listoption&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listoption&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId2=$pId2&pId=$pId");
} else {
}
