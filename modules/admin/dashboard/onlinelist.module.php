<?php
/*************************************************************************
Order listing module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Last updated: 13/09/2011
Coder: Tran Thi My Xuyen
**************************************************************************/
$templateFile = 'dashboardonlinelist.tpl.html';

include_once(ROOT_PATH.'classes/dao/onlineusers.class.php');
$onlineUsers = new OnlineUsers($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['online_users'] => '');

# Get parameters
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
if($items_per_page) $template->assign('ipp',$items_per_page);
$page = $request->element('pg')?$request->element('pg'):1;
if($page) $template->assign('pg',$page);
$sort_key = $request->element('sk')?$request->element('sk'):'last_updated';
if($sort_key) $template->assign('sk',$sort_key);
$sort_direction = $request->element('sd')?$request->element('sd'):'DESC';
if($sort_direction) $template->assign('sd',$sort_direction);
$do = $request->element('doo')?$request->element('doo'):'';
if($do) $template->assign('do',$do);
$kw = $request->element('kw')?$request->element('kw'):'';
if($kw) $template->assign('kw',$kw);
$times = $request->element('times')?$request->element('times'):'';
if($times) $template->assign('times',$times);
$fiter_status = $request->element('fiter_status')?$request->element('fiter_status'):'';
if($fiter_status) $template->assign('fiter_status',$fiter_status);
$uId = $request->element('uId')?$request->element('uId'):'-1';
if($uId) $template->assign('uId',$uId);

# Build WHERE condition
$condition = $uId>=0?"`user_id` = '$uId'":"1>0";
if($fiter_status) $condition = "(`status`='$fiter_status')";
if($kw) $condition = "(`id`='$kw' OR `username` LIKE '%$kw%' OR `ip` LIKE '%$kw%' OR `last_page` LIKE '%$kw%')";

$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $onlineUsers->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=dashboard&act=online&mod=list&doo=$do&kw=$kw&fiter_status=$fiter_status&times=$times&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d&uId=$uId";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $onlineUsers->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=order&mod=list&kw=$kw&fiter_status=$fiter_status&times=$times&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&uId=$uId";
$template->assign('link',$link);

if($_POST) {
	switch($do) {
		case 'delete':
			$id = $request->element('id');
			if($id) {
				$onlineUsers->delete("`store_id` = '".$storeId."' AND `id` = '$id'");
				$result_code = 3;
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$onlineUsers->delete("`store_id` = '".$storeId."' AND `id` = '$id'");
					}
					$result_code = 3;
				} else $error_code = 5;
			}
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=dashboard&act=online&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {

}
?>