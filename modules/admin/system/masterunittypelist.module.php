<?php
$templateFile = 'systemmasterunittype.tpl.html';
include_once(ROOT_PATH . 'classes/dao/unittypes.class.php');
$unitTypes = new UnitTypes($storeId);

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['system_unit'] => '/' . ADMIN_SCRIPT . '?op=system&act=master',
	$amessages['list_item'] => '');

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=master';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=unittypelist',
	$amessages['add_new'] => $tabLink . '&mod=unitadd',
	$amessages['clean_trash'] => $tabLink . '&mod=unitcleantrash');
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

# Filter status
$filter_status = $request->element('filter_status')!=''?$request->element('filter_status'):'';
if(isset($filter_status)) $template->assign('filter_status',$filter_status);
if($do != 'search' && $filter_status === '') $filter_status = '';

# Filter type
$filter_type = $request->element('filter_type')!=''?$request->element('filter_type'):'';
if(isset($filter_type)) $template->assign('filter_type',$filter_type);
if($do != 'search' && $filter_type === '') $filter_type = '';

# Items per pages
$items_per_page = $request->element('ipp') ? $request->element('ipp') : DEFAULT_ADMIN_ROWS_PER_PAGE;
if ($items_per_page) $template->assign('ipp', $items_per_page);

# Page
$page = $request->element('pg') ? $request->element('pg') : 1;
if ($page) $template->assign('pg', $page);

# Sort key
$sort_key = $request->element('sk') ? $request->element('sk') : 'id';
if ($sort_key) $template->assign('sk', $sort_key);

# Sort direction
$sort_direction = $request->element('sd') ? $request->element('sd') : 'DESC';
if ($sort_direction) $template->assign('sd', $sort_direction);

# Action
$do = $request->element('doo') ? $request->element('doo') : '';
if ($do) $template->assign('do', $do);

# Keywords
$kw = $request->element('kw')?$request->element('kw') : '';
if($kw) $template->assign('kw',$kw);

# Filter date created
$filter_date_created = $request->element('filter_date_created')!=''?$request->element('filter_date_created'):'';
if($filter_date_created) $template->assign('filter_date_created',$filter_date_created);
if($do != 'search' && !$filter_date_created) $filter_date_created = 'all';

# Build WHERE condition
$condition = "1>0";
if ($kw) $condition = "(`id`='$kw' OR `unit_code` LIKE '%$kw%' OR `symbol` LIKE '%$kw%' OR `name` LIKE '%$kw%' OR `type` LIKE '%$kw%' OR `conversion_rate_to_base` LIKE '%$kw%' OR `base_unit_code` LIKE '%$kw%' OR `description` LIKE '%$kw%' OR `position` LIKE '%$kw%' OR `date_created` LIKE '%$kw%')";
if($filter_status != '' && $filter_status != 'all') $condition .= " AND `status`= $filter_status ";

# Filter date created condition
$duration = '';
if($filter_date_created) {
	if($filter_date_created == 'onehour') {
			$duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-3600);
			$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date_created == 'fourhours') {
			$duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-14400);
			$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date_created == 'today') {
			$duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y")));
			$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date_created == '7') {
			$duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*7);
			$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date_created == '30') {
			$duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*30);
			$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date_created == '365') {
			$duration = date("Y-m-d H:i:s", mktime(0,0,0,1,1,date("Y")));
			$condition .= " AND `date_created` >= '$duration'";
	} elseif($filter_date_created != 'all') {
	}
}
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $unitTypes->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=system&act=master&mod=unittypelist&doo=$do&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_type=$filter_type&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&module=$module&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $unitTypes->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=system&act=master&mod=unittypelist&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_type=$filter_type&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&module=$module&pg=$page";
$template->assign('link', $link);

if ($_POST) {
	switch ($do) {
		case 'enable':
			$id = $request->element('id');
			if ($id) {
				$unitTypes->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_unit'], $unitTypes->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$unitTypes->changeStatus($id, S_ENABLED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $unitTypes->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_unit'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$id = $request->element('id');
			if ($id) {
				$unitTypes->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_unit'], $unitTypes->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$unitTypes->changeStatus($id, S_DISABLED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $unitTypes->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_unit'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$id = $request->element('id');
			if ($id) {
				$unitTypes->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_unit'], $unitTypes->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$unitTypes->changeStatus($id, S_DELETED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $unitTypes->getNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_unit'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changeposition':
			$positions = $request->element('positions');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$unitTypes->changePosition($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_unit_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			checkPermission(3);
			$unitTypes->cleanTrash();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_unit'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=master&mod=unittypelist&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=master&mod=unittypelist&doo=$do&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_type=$filter_type&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {
}
