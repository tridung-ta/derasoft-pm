<?php

/*************************************************************************
csau listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 15/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (07/05/2012)
 **************************************************************************/
$userInfo->checkPermission('csau', 'view');
$templateFile = 'managecsau.tpl.html';
include_once(ROOT_PATH . 'classes/dao/csaus.class.php');
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$csaus = new Csaus($storeId);
$search = new Search($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_csau'] => '/' . ADMIN_SCRIPT . '?op=manage&act=csau',
	$amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=csau';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

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
$CatId = $request->element('cat_id') ? $request->element('cat_id') : '';
if ($CatId) $template->assign('CatId', $CatId);

# Build WHERE condition
$condition = "1>0";
// if ($kw) $condition = "(`id`='$kw' OR `fullname` LIKE '%$kw%')";
if ($kw) {
	if ($csaus->searchCustomField($kw)) {
		$idsOption = $csaus->searchCustomField($kw);
		$condition = "(`id` IN $idsOption OR `fullname` LIKE '%$kw%')";
	} else {
		$condition = "(`id`='$kw' OR `fullname` LIKE '%$kw%')";
	}
}
if ($CatId) $condition .= " AND `cat_id`='$CatId'";
$pages_condition = "`store_id` = '$storeId' AND $condition";

$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $csaus->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=csau&mod=list&doo=$do&kw=$kw&cat_id=$CatId&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $csaus->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("csau");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "csau", "csau"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("csau");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=csau&mod=list&kw=$kw&cat_id=$CatId&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page";
$template->assign('link', $link);

#Show URL Popup
$template->assign('urlPopup', 1);

if ($_POST) {
	switch ($do) {

		case 'enable':
			$userInfo->checkPermission('csau', 'edit');
			$id = $request->element('id');
			if ($id) {
				$csaus->changeStatus($id, S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_csau'], $csaus->getTitleFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$csaus->changeStatus($id, S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $csaus->getTitleFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_csau'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('csau', 'edit');
			$id = $request->element('id');
			if ($id) {
				$csaus->changeStatus($id, S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_csau'], $csaus->getTitleFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$csaus->changeStatus($id, S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $csaus->getTitleFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_csau'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('csau', 'delete');
			$id = $request->element('id');
			if ($id) {
				$csaus->changeStatus($id, S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_csau'], $csaus->getTitleFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$csaus->changeStatus($id, S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $csaus->getTitleFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_csau'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;

		case 'changeposition':
			$userInfo->checkPermission('csau', 'edit');
			$positions = $request->element('positions');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$csaus->changePosition($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_csau_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('csau', 'clean', 0);
			$csaus->cleanTrash();
			$fieldValue->deleteData();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_csau'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=csau&mod=list&lang=$lang&ecode=7");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=csau&mod=list&doo=$do&kw=$kw&cat_id=$CatId&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {
}
