<?php
/*************************************************************************
Order listing module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 13/09/2011
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('order','view');

# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('order_id','customer_id','name','address','tel','email','rname','raddress','rtel','remail','payment_method','payment_status','delivery_vendor','delivery_status','date_created','date_updated','pic_id','status');

$templateFile = 'manageorder.tpl.html';
include_once(ROOT_PATH.'classes/dao/orders.class.php');
include_once(ROOT_PATH."classes/dao/users.class.php");
$orders = new Orders($storeId);
$users = new Users($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_order'] => '/'.ADMIN_SCRIPT.'?op=manage&act=order',
				$amessages['list_item'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=order';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

# Item per pages
$items_per_page = $request->element('ipp',DEFAULT_ADMIN_ROWS_PER_PAGE);
$template->assign('ipp',$items_per_page);

# Page
$page = $request->element('pg',1);
$template->assign('pg',$page);

# Sort key
$sort_key = $request->element('sk','order_id');
if(!in_array($sort_key,$allow_sort_keys)) $sort_key='order_id';
$template->assign('sk',$sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd',$sort_direction);

# Action
$do = $request->element('doo','');
$template->assign('do',$do);

# Textbox keywords
$kw = $request->element('kw','');
$template->assign('kw',$kw);

# Filter date created
$filter_date_created = $request->element('filter_date_created','');
$template->assign('filter_date_created',$filter_date_created);
if($do != 'search' && !$filter_date_created) $filter_date_created = 'all';

# Filter status
$filter_status = $request->element('filter_status','');
$template->assign('filter_status',$filter_status);
if($do != 'search' && !$filter_status) $filter_status = 'all';

# Filter PIC
$filter_pic = $request->element('filter_pic','');
$template->assign('filter_pic',$filter_pic);
if($do != 'search' && !$filter_pic) $filter_pic = '';

# Filter posters Combo
$filterPICCombo = $users->generateCombo($filter_pic,1);
if($filterPICCombo) $template->assign('filterPICCombo',$filterPICCombo);

# Filter payment method
$filter_payment_method = $request->element('filter_payment_method','');
$template->assign('filter_payment_method',$filter_payment_method);
if($do != 'search' && !$filter_payment_method) $filter_payment_method = 'all';

# Filter payment status
$filter_payment_status = $request->element('filter_payment_status','');
$template->assign('filter_payment_status',$filter_payment_status);
if($do != 'search' && !$filter_payment_status) $filter_payment_status = 'all';

# Filter delivery vendor
$filter_delivery_vendor = $request->element('filter_delivery_vendor','');
$template->assign('filter_delivery_vendor',$filter_delivery_vendor);
if($do != 'search' && !$filter_delivery_vendor) $filter_delivery_vendor = 'all';

# Filter delivery status
$filter_delivery_status = $request->element('filter_delivery_status','');
$template->assign('filter_delivery_status',$filter_delivery_status);
if($do != 'search' && !$filter_delivery_status) $filter_delivery_status = 'all';

$uId = $request->element('uId','-1');
if($uId) $template->assign('uId',$uId);

# Build WHERE condition
$condition = $uId>=0?"`customer_id` = '$uId'":"1>0";

# Filter status condition
if($filter_status != '' && $filter_status != 'all') $condition .= " AND (o.`status`='$filter_status')";

# Filter PIC condition
if($filter_pic != '' && $filter_pic != 'all') $condition .= $filter_pic>0?" AND `o.pic_id` = '$filter_pic'":"";

# Filter payment method condition
if($filter_payment_method != '' && $filter_payment_method != 'all') $condition .= " AND (o.`payment_method`='$filter_payment_method')";
if($filter_payment_status != '' && $filter_payment_status != 'all') $condition .= " AND (o.`payment_status`='$filter_payment_status')";
if($filter_delivery_vendor != '' && $filter_delivery_vendor != 'all') $condition .= " AND (o.`delivery_vendor`='$filter_delivery_vendor')";
if($filter_delivery_status != '' && $filter_delivery_status != 'all') $condition .= " AND (o.`delivery_status`='$filter_delivery_status')";

# Filter keyword condition
if($kw) $condition = "(`order_id`='".controlBackSlashMySQL($kw)."' OR `customer_id`='".controlBackSlashMySQL($kw)."' OR `pic_id`='".controlBackSlashMySQL($kw)."' OR`name` LIKE '%".controlBackSlashMySQL($kw)."%' OR `email` LIKE '%".controlBackSlashMySQL($kw)."%' OR `address` LIKE '%".controlBackSlashMySQL($kw)."%' OR `tel` LIKE '%".controlBackSlashMySQL($kw)."%' OR `rname` LIKE '%".controlBackSlashMySQL($kw)."%' OR `remail` LIKE '%".controlBackSlashMySQL($kw)."%' OR `raddress` LIKE '%".controlBackSlashMySQL($kw)."%' OR `rtel` LIKE '%".controlBackSlashMySQL($kw)."%')";

# Filter date created condition
$duration = '';
if($filter_date_created) {
	if($filter_date_created == 'onehour') {
		$duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-3600); 
		$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date_created == 'fourhours') {
		$duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-14400);
		$condition .= " AND o.`date_created` >= '$duration'";
	} elseif($filter_date_created == 'today') {
		$duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y")));
		$condition .= " AND o.`date_created` >= '$duration'";
        } elseif($filter_date_created == '7') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*7);
                $condition .= " AND o.`date_created` >= '$duration'";
        } elseif($filter_date_created == '30') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*30);
                $condition .= " AND o.`date_created` >= '$duration'";
        } elseif($filter_date_created == '365') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,1,1,date("Y")));
                $condition .= " AND o.`date_created` >= '$duration'";
	} elseif($filter_date_created != 'all') {
	}
}

$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $orders->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=order&mod=list&doo=$do&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_payment_method=$filter_payment_method&filter_payment_status=$filter_payment_status&filter_delivery_vendor=$filter_delivery_vendor&filter_delivery_status=$filter_delivery_status&filter_pic=$filter_pic&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d&uId=$uId";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $orders->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=order&mod=list&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_payment_method=$filter_payment_method&filter_payment_status=$filter_payment_status&filter_delivery_vendor=$filter_delivery_vendor&filter_delivery_status=$filter_delivery_status&filter_pic=$filter_pic&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&uId=$uId";
$template->assign('link',$link);

# Submitted form
if($_POST) {
	switch($do) {
		case 'process_order':
			$userInfo->checkPermission('order','edit');
			$id = $request->element('id');
			if($id) {
				$orders->updateData(array('status' => ORDER_STATUS_PROCESSING,
										  'pic_id' => $userInfo->getId()
										 ),
									$id);
				$result_code = 7;

				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['process_order'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listOrder = '';
					foreach ($ids as $id) {
							$orders->updateData(array('status' => ORDER_STATUS_PROCESSING,
										  					'pic_id' => $userInfo->getId()
										 			),
													$id);
							$listOrder .= ($listOrder?',&nbsp;':'').$id;
					}
					$result_code = 7;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['process_order'],$listOrder),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'complete':
			$userInfo->checkPermission('order','edit');
			// Change status => orderpaid (5)
			$id = $request->element('id');
			if($id) {
				$orders->changeStatus($id,ORDER_STATUS_COMPLETED);
				$result_code = 7;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['complete_order'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listOrder = '';
					foreach ($ids as $id) {
						$orders->changeStatus($id,ORDER_STATUS_COMPLETED);
						$listOrder .= ($listOrder?',&nbsp;':'').$id;
					}
					$result_code = 7;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['complete_order'],$listOrder),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('order','edit');
			$id = $request->element('id');
			if($id) {
				$orders->changeStatus($id,ORDER_STATUS_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_order'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listOrder = '';
					foreach ($ids as $id) {
						$orders->changeStatus($id,ORDER_STATUS_DISABLED);
						$listOrder .= ($listOrder?',&nbsp;':'').$id;
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_order'],$listOrder),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
                case 'cancel_order':
                        $userInfo->checkPermission('order','edit');
                        $id = $request->element('id');
                        if($id) {
                                $orders->changeStatus($id,ORDER_STATUS_CANCELED);
                                $result_code = 2;
                                # Operation tracking
                                $trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['cancel_order'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
                        } else {
                                $ids = $request->element('ids');
                                if($ids) {
                                        $listOrder = '';
                                        foreach ($ids as $id) {
                                                $orders->changeStatus($id,ORDER_STATUS_CANCELED);
                                                $listOrder .= ($listOrder?',&nbsp;':'').$id;
                                        }
                                        $result_code = 2;
                                        # Operation tracking
                                        $trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['cancel_order'],$listOrder),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
                                } else $error_code = 5;
                        }
                        break;
		case 'delete':
			$userInfo->checkPermission('order','delete');
			$id = $request->element('id');
			if($id) {
				$orders->changeStatus($id,ORDER_STATUS_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_order'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listOrder = '';
					foreach ($ids as $id) {
						$orders->changeStatus($id,ORDER_STATUS_DELETED);
						$listOrder .= ($listOrder?',&nbsp;':'').$id;
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_order'],$listOrder),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changegroup':
			$userInfo->checkPermission('order','edit');
			$ids = $request->element('ids');
			$status = $request->element('status');
			if($ids) {
				$listOrder = '';
				foreach ($ids as $id) {
					$orders->changeStatus($id,$status);
					$listOrder .= ($listOrder?',&nbsp;':'').$id;
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['change_order_group'],$listOrder,$amessages['order_status'][$status]),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('order','clean',0);
			$orders->cleanTrash();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_order'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=order&mod=list&lang=$lang&ecode=7&uId=$uId");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=order&mod=list&doo=$do&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_payment_method=$filter_payment_method&filter_payment_status=$filter_payment_status&filter_delivery_vendor=$filter_delivery_vendor&filter_delivery_status=$filter_delivery_status&filter_pic=$filter_pic&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&uId=$uId");
} else {

}
?>
