<?php
/*************************************************************************
Admin staff listing module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 24/07/2011
**************************************************************************/
checkPermission(3);
$templateFile = 'systemstaff.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_staff'] => '/'.ADMIN_SCRIPT.'?op=system&act=staff',
				$amessages['tracking_title'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=staff';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
if($userInfo->isSiteFounder()) $listTabs[$amessages['tracking_title']] = $tabLink.'&mod=listTracking';
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',4);

# Get parameters
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
if($items_per_page) $template->assign('ipp',$items_per_page);
$page = $request->element('pg')?$request->element('pg'):1;
if($page) $template->assign('pg',$page);
$sort_key = $request->element('sk')?$request->element('sk'):'date_created';
if($sort_key) $template->assign('sk',$sort_key);
$sort_direction = $request->element('sd')?$request->element('sd'):'DESC';
if($sort_direction) $template->assign('sd',$sort_direction);
$do = $request->element('doo')?$request->element('doo'):'';
if($do) $template->assign('do',$do);
$kw = $request->element('kw')?$request->element('kw'):'';
if($kw) $template->assign('kw',$kw);
$username = $request->element('id')?$request->element('id'):'';
if($username) $template->assign('username',$username);
$filter_date = $request->element('filter_date');
if($filter_date) $template->assign('filter_date',$filter_date);

# Build WHERE condition
$condition = '1>0';
if($username) $condition = "username='$username'";
if($kw) $condition .= " AND (action LIKE '%$kw%' OR ip LIKE '%$kw%' OR date_created LIKE '%$kw%')";
$duration = '';
if($filter_date) {
	if($filter_date == 'today') {
		$duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y")));
		$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date != 'all') {
		$duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*$filter_date);
		$condition .= " AND `date_created` >= '$duration'";
	}
}
$pages_condition = "`store_id` ='$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $trackings->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=system&act=staff&mod=listTracking&id=$username&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_date=$filter_date&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $trackings->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

if($_POST) {
	switch($do) {
		case 'clear':
			$condition = '1>0';
			if($username) $condition .= "`username` = '$username'";
			$from_date = $request->element('from_date');
			if(!$from_date) {
				header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=staff&mod=listTracking&id=$username&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=5&filter_date=$filter_date");
				exit;
			}
			if($from_date == 'all') $condition .= ' AND 1>0';
			else {
				$duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-86400*$from_date);
				$condition .= " AND `date_created` < '$duration'";
			}
			$trackings->clean($condition);
			$result_code = 3;
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['clear_tracking_log'],$from_date,$username),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=staff&mod=listTracking&id=$username&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&filter_date=$filter_date");
}
# Link
$link = '/'.ADMIN_SCRIPT."?op=system&act=staff&mod=listTracking&id=$username&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_date=$filter_date&pg=$page";
$template->assign('link',$link);
?>