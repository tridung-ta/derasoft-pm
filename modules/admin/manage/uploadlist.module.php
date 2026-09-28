<?php
/*************************************************************************
Files upload listing module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Reviewed by: Mai Minh (19/06/2025)
*************************************************************************/
# Check permission
$userInfo->checkPermission('upload','view');

# Allowed sort keys - prevent SQL injection via URL
$allow_sort_keys = array('id','object','type','name','status','date_created','status','position');

$templateFile = 'manageupload.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
$fields = new Fields($storeId);
$uploads = new Uploads($storeId);
$uploadAlbums = new UploadAlbums($storeId);

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_gallery'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=upload';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

# Items per page
$items_per_page = $request->element('ipp',DEFAULT_ADMIN_ROWS_PER_PAGE);
$template->assign('ipp', $items_per_page);
$page = $request->element('pg',1);
$template->assign('pg', $page);

# Sort key
$sort_key = $request->element('sk','id');
$template->assign('sk', $sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd', $sort_direction);

# Actions
$do = $request->element('doo','');
$template->assign('do', $do);

# Keyword
$kw = $request->element('kw','');
$template->assign('kw', $kw);

# Filter status
$filter_status = $request->element('filter_status','');
$template->assign('filter_status',$filter_status);
if($do != 'search' && !$filter_status) $filter_status = 'all';

# Filter object
$filter_objects = $request->element('filter_objects','all');
$template->assign('filter_objects', $filter_objects);

# Filter type
$filter_types = $request->element('filter_types','all');
$template->assign('filter_types', $filter_types);

# Filter album
$filter_albums = $request->element('filter_albums','all');
$template->assign('filter_albums', $filter_albums);

# Generate image upload albums combo
$filterImgAlbumsCombo = $uploadAlbums->generateCombo($filter_albums);
$template->assign('filterImgAlbumsCombo', $filterImgAlbumsCombo);

# Build WHERE condition
$condition = "1>0";
if($filter_status != '' && $filter_status != 'all') $condition .= " AND (u.`status`='$filter_status')";

# Filter object
if($filter_objects != '' && $filter_objects != 'all') $condition .= " AND u.object = '$filter_objects'";

# Filter type
if($filter_types != '' && $filter_types != 'all') $condition .= " AND u.type = '$filter_types'";

# Filter album
if($filter_albums != '' && $filter_albums != 'all') $condition .= " AND u.album_id = '$filter_albums'";

# Keyword
if ($kw) $condition .= " AND (u.`name` LIKE '%".controlBackSlashMySQL($kw)."%' OR u.`url_l` LIKE '%".controlBackSlashMySQL($kw)."%' OR u.`id` = '$kw')";

# Page condition
$pages_condition = "u.`store_id` = '$storeId' AND $condition";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $uploads->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=upload&mod=list&doo=$do&kw=$kw&filter_status=$filter_status&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_albums=$filter_albums&filter_objects=$filter_objects&filter_types=$filter_types&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $uploads->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=upload&mod=list&kw=$kw&filter_status=$filter_status&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_albums=$filter_albums&filter_objects=$filter_objects&filter_types=$filter_types&pg=$page";
$template->assign('link', $link);
if ($_POST) {
	switch ($do) {
		case 'enable':
			$userInfo->checkPermission('img', 'edit');
			$id = $request->element('id');
			if ($id) {
				$uploads->changeStatus($id,S_ENABLED);
				$result_code = 1;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_upload'],$id), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listUploads = '';
					foreach ($ids as $id) {
						$uploads->changeStatus($id,S_ENABLED);
						$listUploads .= ($listUploads?',&nbsp;':'').$id;
					}
					$result_code = 1;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_upload'],$listUploads),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('img', 'edit');
			$id = $request->element('id');
			if ($id) {
				$uploads->changeStatus($id,S_DISABLED);
				$result_code = 3;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_upload'],$id), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listUploads = '';
					foreach ($ids as $id) {
						$uploads->changeStatus($id,S_DISABLED);
						$listUploads .= ($listUploads?',&nbsp;':'').$id;
					}
					$result_code = 3;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_upload'],$listUploads),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('img', 'delete');
			$id = $request->element('id');
			if ($id) {
				$uploads->changeStatus($id,S_DELETED);
				$result_code = 3;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_upload'],$id), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listUploads = '';
					foreach ($ids as $id) {
						$uploads->changeStatus($id,S_DELETED);
						$listUploads .= ($listUploads?',&nbsp;':'').$id;
					}
					$result_code = 3;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_upload'],$listUploads),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'cleantrash':
			$userInfo->checkPermission('img','clean',0);
			$uploads->cleanTrash();
			$result_code = 5;

			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_upload'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;		
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=upload&mod=list&filter_status=$filter_status&lang=$lang&ecode=7&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&filter_albums=$filter_albums&filter_objects=$filter_objects&filter_types=$filter_types");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=upload&mod=list&doo=$do&kw=$kw&filter_status=$filter_status&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&filter_albums=$filter_albums&filter_objects=$filter_objects&filter_types=$filter_types");
}
