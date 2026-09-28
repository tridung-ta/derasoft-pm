<?php

/*************************************************************************
View order module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 16/09/2011
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('order', 'view');

$templateFile = 'manageorderview.tpl.html';
include_once(ROOT_PATH . 'classes/dao/orders.class.php');
include_once(ROOT_PATH . 'classes/dao/orderitems.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/orderlogs.class.php');
include_once(ROOT_PATH . 'classes/dao/carts.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
$productaccessorys = new Productaccessorys($storeId);
$template->assign('productaccessorys', $productaccessorys);
$products = new Products(1);
$template->assign('products', $products);
$carts          = new Carts(1);
$template->assign('carts', $carts);
$orders = new Orders($storeId);
$orderlogs = new OrderLogs($storeId);
include_once(ROOT_PATH . "classes/dao/provinces.class.php");
$provinces = new Provinces();
$template->assign('provinces', $provinces);
include_once(ROOT_PATH . "classes/dao/district.class.php");
$districts = new District();
$template->assign('districts', $districts);
include_once(ROOT_PATH . "classes/dao/ward.class.php");
$wards = new Ward();
$template->assign('wards', $wards);

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_order'] => '/' . ADMIN_SCRIPT . '?op=manage&act=order',
	$amessages['list_item'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=order';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$id = $request->element('id');
if ($id) {
	$orderItems = new OrderItems($storeId);
	$orderInfo = $orders->getObject($id);
	$idCart = $orderInfo->getProperty('idCart');
	$template->assign('item', $orderInfo);
	#$listProducs = $carts->getObjects(1, "`id`IN ($idCart)", array('id' => 'DESC'), 99);
	$template->assign('listProducs', $listProducs);
	$template->assign('id', $id);
}
?>
