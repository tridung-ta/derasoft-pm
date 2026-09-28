<?php
/*************************************************************************
Article listing module
----------------------------------------------------------------
Derasoft CMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 05/05/2012
Coder: Mai Minh
**************************************************************************/
$userInfo->checkPermission('comment','view');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$templateFile = 'managecomment.tpl.html';
include_once(ROOT_PATH.'classes/dao/comments.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
$comments = new Comments($storeId);
$products = new Products($storeId);
$template->assign('products',$products);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['list_item'] => '/'.ADMIN_SCRIPT.'?op=manage&act=comment');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=comment';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

# Get parameters — luôn assign dù rỗng để tránh Undefined array key warning
$items_per_page = $request->element('ipp') ? $request->element('ipp') : DEFAULT_ADMIN_ROWS_PER_PAGE;
$template->assign('ipp', $items_per_page);

$page = $request->element('pg') ? $request->element('pg') : 1;
$template->assign('pg', $page);

$sort_key = $request->element('sk') ? $request->element('sk') : 'id';
$template->assign('sk', $sort_key);

$sort_direction = $request->element('sd') ? $request->element('sd') : 'DESC';
$template->assign('sd', $sort_direction);

$do = $request->element('doo') ? $request->element('doo') : '';
$template->assign('do', $do);

$kw = $request->element('kw') ? $request->element('kw') : '';
$template->assign('kw', $kw);

# pId — cần assign sớm vì dùng trong header redirect và template
$pId = $request->element('pId') ? $request->element('pId') : '';
$template->assign('pId', $pId);

# result_code / error_code — assign default trước, override sau nếu cần
$result_code = $request->element('rcode') ? $request->element('rcode') : null;
$template->assign('result_code', $result_code);

$error_code = $request->element('ecode') ? $request->element('ecode') : null;
$template->assign('error_code', $error_code);

# Build WHERE condition
$condition = "1>0";
if($kw) $condition = "(`id`='$kw' OR `fullname` LIKE '%$kw%' OR `email` LIKE '%$kw%' OR `tel` LIKE '%$kw%' OR `address` LIKE '%$kw%' OR `details` LIKE '%$kw%')";
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $comments->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=comment&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $comments->getObjects($page, $condition, $sort, $items_per_page);
$template->assign('listItems', $listItems ?: array());

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=comments&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page";
$template->assign('link', $link);

if($_POST) {
	switch($do) {
		case 'enable':
			$userInfo->checkPermission('comment','edit');
			$id = $request->element('id');
			if($id) {
				$comments->changeStatus($id, S_ENABLED);
				$result_code = 1;
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listComment = '';
					foreach ($ids as $id) {
						$comments->changeStatus($id, S_ENABLED);
						$listComment .= ($listComment ? ',&nbsp;' : '').$id;
					}
					$result_code = 1;
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('comment','edit');
			$id = $request->element('id');
			if($id) {
				$comments->changeStatus($id, S_DISABLED);
				$result_code = 2;
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listComment = '';
					foreach ($ids as $id) {
						$comments->changeStatus($id, S_DISABLED);
						$listComment .= ($listComment ? ',&nbsp;' : '').$id;
					}
					$result_code = 2;
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('comment','delete');
			$id = $request->element('id');
			if($id) {
				$comments->changeStatus($id, S_DELETED);
				$result_code = 3;
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listComment = '';
					foreach ($ids as $id) {
						$comments->changeStatus($id, S_DELETED);
						$listComment .= ($listComment ? ',&nbsp;' : '').$id;
					}
					$result_code = 3;
				} else $error_code = 5;
			}
			break;
		// case 'changeposition':
		// 	$userInfo->checkPermission('support','edit');
		// 	$positions = $request->element('positions');
		// 	if($positions) {
		// 		foreach ($positions as $key => $value) {
		// 			$comments->changePosition($key, $value);
		// 		}
		// 		$result_code = 4;
		// 	} else $error_code = 5;
		// 	break;
		case 'cleantrash':
			$userInfo->checkPermission('comment','clean', 0);
			$comments->cleanTrash();
			$result_code = 5;
			break;		
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=comment&mod=list&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=comment&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {

}
?>