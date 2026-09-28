<?php
/*************************************************************************
Admin dashboard module
----------------------------------------------------------------
Derasoft BiDo Project
Coder: Mai Minh
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 20/11/2011
**************************************************************************/
$templateFile = 'dashboard.tpl.html';
include_once(ROOT_PATH.'classes/dao/orders.class.php');

$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['summary'] => '');

if(!$act) $act = 'index';
if(!$mod) $mod = 'list';
include_once(ROOT_PATH.'modules/admin/manage/'.strtolower($act).strtolower($mod).'.module.php');

# Get latest orders
$orders = new Orders($storeId);
$latestOrders = $orders->getObjects(1,'1>0',array('created' => 'DESC'),10);
if($latestOrders) $template->assign('latestOrders',$latestOrders);

# Get top ordered products
$topOrderedProducts = array();
$last30days = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*300);
$results = $db->query("SELECT oi.product_id,oi.name,AVG(oi.price) AS price,count(oi.product_id) AS total FROM `bd_order_items` oi,`bd_orders` o WHERE o.store_id = '$storeId' AND oi.order_id=o.id and o.created >='$last30days' group by oi.product_id ORDER BY total DESC LIMIT 0,10");
if(mysql_num_rows($results)) {
	while($row=mysql_fetch_array($results)) {
		$topOrderedProducts[] = $row;
	}
}
$template->assign('topOrderedProducts',$topOrderedProducts);

# Get top viewed products
$topViewedProducts = array();
$results = $db->query("SELECT id,`name`,sku,price,viewed FROM `bd_products` WHERE store_id = '$storeId' AND status=1 ORDER BY viewed DESC LIMIT 0,10");
if(mysql_num_rows($results)) {
	while($row=mysql_fetch_array($results)) {
		$topViewedProducts[] = $row;
	}
}
$template->assign('topViewedProducts',$topViewedProducts);

# Get number of online users
$onlineUsers = 0;
$results = $db->query("SELECT count(store_id) FROM `bd_estore_online_users` WHERE store_id = '$storeId'");
if(mysql_num_rows($results)) {
	$row=mysql_fetch_row($results);
	$onlineUsers = $row[0];
}
$template->assign('onlineUsers',$onlineUsers);

# Get store statistic
$statistic = '';
$results = $db->query("SELECT * FROM `bd_estore_statistics` WHERE id = '$storeId'");
if(mysql_num_rows($results)) {
	$row=mysql_fetch_array($results);
	$statistic = $row;
}
$template->assign('statistic',$statistic);
?>