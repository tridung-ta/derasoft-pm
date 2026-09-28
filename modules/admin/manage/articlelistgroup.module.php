<?php
    //    ini_set('display_errors', 1);
    //     ini_set('display_startup_errors', 1);
    //     error_reporting(E_ALL);
$userInfo->checkPermission('article','group');

$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/articlegroups.class.php");
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 

$fields = new Fields($storeId);
$articleGroups = new ArticleGroups($storeId);
$fieldValue = new OptionValue($storeId);
if ($fieldValue) $template->assign('fieldValue', $fieldValue);

$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_article'] => '/' . ADMIN_SCRIPT . '?op=manage&act=article',
				$amessages['list_item'] => '');

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=article';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_article_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_article_category'] => $tabLink . '&mod=addcategory',
    $amessages['list_articlegroup'] => $tabLink . '&mod=listgroup',
    $amessages['add_articlegroup'] => $tabLink . '&mod=addgroup',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 5);

# Get parameters
$items_per_page = $request->element('ipp', DEFAULT_ADMIN_ROWS_PER_PAGE);
$page           = $request->element('pg', 1);
$sort_key       = $request->element('sk', 'id');
$sort_direction = $request->element('sd', 'DESC');
$do             = $request->element('doo', '');
$kw             = $request->element('kw', '');

$template->assign('ipp', $items_per_page);
$template->assign('pg', $page);
$template->assign('sk', $sort_key);
$template->assign('sd', $sort_direction);
$template->assign('do', $do);
$template->assign('kw', $kw);

# Build WHERE condition
$condition = "1>0";
if ($kw) {
    $kw = controlBackSlashMySQL($kw);
    $idsOption = $articleGroups->searchCustomField($kw);

    if (!empty($idsOption)) {
        $idsString = implode(',', $idsOption);
        $condition .= " AND (`id` IN ($idsString) OR `slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
    } else {
        $condition .= " AND (`slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
    }
}
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $articleGroups->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listgroup&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%g";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $articleGroups->getObjects($page,$condition,$sort,$items_per_page);
$template->assign('listItems', $listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listgroup&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page";
$template->assign('link',$link);

if($_POST) {
	switch($do) {
		case 'enable':
			$userInfo->checkPermission('article','editgroup');
			$id = $request->element('id');
			if($id) {
				$articleGroups->changeStatus($id,S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_articlegroup'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listAd = '';
					foreach ($ids as $id) {
						$articleGroups->changeStatus($id,S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listAd .= ($listAd?',&nbsp;':'').$id;
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_articlegroup'],$listAd),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('article','editgroup');
			$id = $request->element('id');
			if($id) {
				$articleGroups->changeStatus($id,S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_articlegroup'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listAd = '';
					foreach ($ids as $id) {
						$articleGroups->changeStatus($id,S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listAd .= ($listAd?',&nbsp;':'').$id;
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_articlegroup'],$listAd),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('article','deletegroup');
			$id = $request->element('id');
			if($id) { 
				$articleGroups->changeStatus($id,S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_articlegroup'],$id),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listAd = '';
					foreach ($ids as $id) {
						$articleGroups->changeStatus($id,S_DELETED);
						$listAd .= ($listAd?',&nbsp;':'').$id;
						$fieldValue->changeStatus($id, S_DELETED);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_articlegroup'],$listAd),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'cleantrash':
			$userInfo->checkPermission('article','cleangroup',0);
			$articleGroups->cleanTrash();
			$fieldValue->deleteData();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_articlegroup'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listgroup&lang=$lang&ecode=7");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listgroup&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {

}
?>