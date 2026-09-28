<?php
/*************************************************************************
Admin staff listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 22/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemstaff.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_staff'] => '/'.ADMIN_SCRIPT.'?op=system&act=staff',
				$amessages['list_item'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=staff';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
if($userInfo->isSiteFounder()) $listTabs[$amessages['tracking_title']] = $tabLink.'&mod=listTracking';
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

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

# Build WHERE condition
$condition = '1>0';
if($kw) $condition = "(id='$kw' OR username LIKE '%$kw%' OR fullname LIKE '%$kw%' OR email LIKE '%$kw%')";
if(!$userInfo->isSiteFounder()) $condition .= " AND `type` <> '".U_SITE_FOUNDER."'";
$pages_condition = "`store_id` ='$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $users->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=system&act=staff&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $users->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=system&act=staff&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page";
$template->assign('link',$link);

if($_POST) {
	switch($do) {
		case 'enable':
			$id = $request->element('id');
			if($id) {
				$uType = $users->getUserType("`id`='$id'");
				if($uType == U_SITE_FOUNDER && !$userInfo->isSiteFounder()) { # Neu user can kich hoat la Founder thi chi co founder moi co quyen enable
					header("location: /admin.php?op=accessdenied");
					exit;
				} else {
					$users->changeStatus($id,S_ENABLED);
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_user'],$users->getUsername("`id`='$id'")),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				}
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listUser = '';
					foreach ($ids as $id) {
						$uType = $users->getUserType("`id`='$id'");
						if($uType == U_SITE_FOUNDER) {	# Neu user can kich hoat la Founder thi chi co founder moi co quyen enable
							if($userInfo->isSiteFounder()) {
								$users->changeStatus($id,S_ENABLED);
								$listUser .= ($listUser?',&nbsp;':'').$users->getUsername("`id`='$id'");
							}
						} else {
							$users->changeStatus($id,S_ENABLED);
							$listUser .= ($listUser?',&nbsp;':'').$users->getUsername("`id`='$id'");
						}
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_user'],$listUser),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$id = $request->element('id');
			if($id) {
				$uType = $users->getUserType("`id`='$id'");
				if($uType == U_SITE_FOUNDER && !$userInfo->isSiteFounder()) { # Neu user bi xoa la Founder thi chi co founder moi co quyen disable
					header("location: /admin.php?op=accessdenied");
					exit;
				} else {
					$users->changeStatus($id,S_DISABLED);
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_user'],$users->getUsername("`id`='$id'")),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				}
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listUser = '';
					foreach ($ids as $id) {
						$uType = $users->getUserType("`id`='$id'");
						if($uType == U_SITE_FOUNDER) {	# Neu user bi xoa la Founder thi chi co founder moi co quyen disable
							if($userInfo->isSiteFounder()) {
								$users->changeStatus($id,S_DISABLED);
								$listUser .= ($listUser?',&nbsp;':'').$users->getUsername("`id`='$id'");
							}
						} else {
							$users->changeStatus($id,S_DISABLED);
							$listUser .= ($listUser?',&nbsp;':'').$users->getUsername("`id`='$id'");
						}
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_user'],$listUser),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$id = $request->element('id');
			if($id) {
				$uType = $users->getUserType("`id`='$id'");
				if($uType == U_SITE_FOUNDER && !$userInfo->isSiteFounder()) { # Neu user bi xoa la Founder thi chi co founder moi co quyen xoa
					header("location: /admin.php?op=accessdenied");
					exit;
				} else {
					$users->changeStatus($id,S_DELETED);
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_user'],$users->getUsername("`id`='$id'")),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				}
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listUser = '';
					foreach ($ids as $id) {
						$uType = $users->getUserType("`id`='$id'");
						if($uType == U_SITE_FOUNDER) {	# Neu user bi xoa la Founder thi chi co founder moi co quyen xoa
							if($userInfo->isSiteFounder()) {
								$users->changeStatus($id,S_DELETED);
								$listUser .= ($listUser?',&nbsp;':'').$users->getUsername("`id`='$id'");
							}
						} else {
							$users->changeStatus($id,S_DELETED);
							$listUser .= ($listUser?',&nbsp;':'').$users->getUsername("`id`='$id'");
						}
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_user'],$listUser),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changegroup':
			$ids = $request->element('ids');
			$type = $request->element('type');
			$listType = array();
			if($userInfo->isSiteAdmin()) $listType = array(U_SITE_STAFF,U_SITE_ADMIN);
			if($userInfo->isSiteFounder()) $listType = array(U_SITE_STAFF,U_SITE_ADMIN,U_SITE_FOUNDER);
			if($ids) {
				if($type && in_array($type,$listType)) {
					$listUser = '';
					foreach ($ids as $id) {
						$users->changeType($id,$type);
						$listUser .= ($listUser?',&nbsp;':'').$users->getUsername("`id`='$id'");
					}
					$result_code = 4;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['change_user_group'],$listUser,$amessages['type_user'][$type]),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 6;
			} else $error_code = 5;
			break;
		case 'cleantrash':
			checkPermission(3);
			$users->cleanTrash();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_user'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=staff&mod=list&lang=$lang&ecode=7");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=staff&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {

}
?>