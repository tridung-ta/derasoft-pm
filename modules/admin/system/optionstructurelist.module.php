 <?php

$templateFile = 'systemoptionstructure.tpl.html';
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/optionobject.class.php');
$fields = new OptionStructure($storeId);
$optionValue = new OptionValue();
$optionObject = new OptionObject($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['system_custom_options'] => '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure',
	$amessages['list_item'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['custom_list_field_value'] => $tabLink . '&mod=listvalue',
	$amessages['custom_list_field_object'] => $tabLink . '&mod=listobject',
	$amessages['add_new_object'] => $tabLink . '&mod=listobjectadd',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash',
	$amessages['clean_object'] => $tabLink . '&mod=listobjectcleantrash'
);

$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

# Filter status
$filter_status = $request->element('filter_status')!=''?$request->element('filter_status'):'';
if(isset($filter_status)) $template->assign('filter_status',$filter_status);
if($do != 'search' && $filter_status === '') $filter_status = '';

# Filter module
$listModule = $optionObject->getObjects(1, "`status` = 1 ", array("id" => "ASC"), 99);
if ($listModule) $template->assign('listModule', $listModule);
$module = $request->element('module') ? $request->element('module') : '';
$template->assign('module', $module);

# Filter type
$filter_type = $request->element('filter_type')!=''?$request->element('filter_type'):'';
if(isset($filter_type)) $template->assign('filter_type',$filter_type);
if($do != 'search' && $filter_type === '') $filter_type = '';

# Filter required
$filter_required = $request->element('filter_required')!=''?$request->element('filter_required'):'';
if(isset($filter_required)) $template->assign('filter_required',$filter_required);
if($do != 'search' && $filter_required === '') $filter_required = '';

# Filter appearance
$filter_appearance = $request->element('filter_appearance')!=''?$request->element('filter_appearance'):'';
if(isset($filter_appearance)) $template->assign('filter_appearance',$filter_appearance);
if($do != 'search' && $filter_appearance === '') $filter_appearance = '';

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
$pId = $request->element('pId') ? $request->element('pId') : 0;


if ($pId) {
	$gfId = $fields->getParentIdFromId($pId);
	$template->assign('pId', $pId);
	$template->assign('gfId', $gfId);
	$topNav[$amessages['list_item']] = '/' . ADMIN_SCRIPT . '?op=system&act=optionstructure&mod=list';
	$topNav[$fields->getFieldNameFromId($pId)] = '';
}

# Build WHERE condition
$condition = "1>0";
if ($kw) $condition = "(`id`='$kw' OR `module` LIKE '%$kw%' OR `field_name` LIKE '%$kw%' OR `field_title` LIKE '%$kw%' OR `field_type` LIKE '%$kw%' OR `required` LIKE '%$kw%' OR `appearance` LIKE '%$kw%')";
if ($module != "") $condition .= " AND `module_id`= $module"; //search module
if($filter_status != '' && $filter_status != 'all') $condition .= " AND `status`= $filter_status ";
if($filter_type != '' && $filter_type != 'all') $condition .= " AND `field_type`= $filter_type ";
if($filter_required != '' && $filter_required != 'all') $condition .= " AND `required`= $filter_required ";
if($filter_appearance != '' && $filter_appearance != 'all') $condition .= " AND `appearance`= $filter_appearance ";

$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $fields->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=system&act=optionstructure&mod=list&doo=$do&kw=$kw&filter_appearance=$filter_appearance&filter_required=$filter_required&filter_status=$filter_status&filter_type=$filter_type&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&module=$module&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $fields->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=system&act=optionstructure&mod=list&kw=$kw&filter_appearance=$filter_appearance&filter_required=$filter_required&filter_status=$filter_status&filter_type=$filter_type&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&module=$module&pg=$page";
$template->assign('link', $link);

# Show URL Popup
#$template->assign('urlPopup',1);

#bottom Action Combo
$categoryCombo = $optionObject->generateCombo($pId);
if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

if ($_POST) {
	switch ($do) {
		case 'enable':
			$id = $request->element('id');
			if ($id) {
				$fields->changeStatus($id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_custom_option'], $fields->getFieldNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$fields->changeStatus($id, S_ENABLED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $fields->getFieldNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_custom_option'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$id = $request->element('id');
			if ($id) {
				$fields->changeStatus($id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_custom_option'], $fields->getFieldNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$fields->changeStatus($id, S_DISABLED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $fields->getFieldNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_custom_option'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$id = $request->element('id');
			if ($id) {
				$fields->changeStatus($id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_custom_option'], $fields->getFieldNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$fields->changeStatus($id, S_DELETED);
						$listArticle .= ($listArticle ? ',&nbsp;' : '') . $fields->getFieldNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_custom_option'], $listArticle), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changeposition':
			$positions = $request->element('positions');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$fields->changePosition($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_custom_option_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			checkPermission(3);
			$fields->cleanTrash();
			$result_code = 5;
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_custom_option'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=optionstructure&mod=list&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=optionstructure&mod=list&doo=$do&kw=$kw&filter_appearance=$filter_appearance&filter_required=$filter_required&filter_status=$filter_status&filter_type=$filter_type&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId&module=$module");
} else {
}
