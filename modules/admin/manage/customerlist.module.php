<?php
/*************************************************************************
Customer listing module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 09/09/2011
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (09/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('customer','view');

# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('c.id','username','fullname','address','tel','email','tax_code','group_id','country_id','area_id','ward_id','creator_name','date_created','updater_name','date_updated','last_login','status');

$templateFile = 'managecustomer.tpl.html';
include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH.'classes/dao/customergroups.class.php');
include_once(ROOT_PATH.'classes/dao/countries.class.php');
include_once(ROOT_PATH.'classes/dao/areas.class.php');
include_once(ROOT_PATH.'classes/dao/wards.class.php');
include_once(ROOT_PATH.'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH.'classes/dao/optionvalue.class.php');
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$customers = new Customers($storeId);
$customerGroups = new CustomerGroups($storeId);
$countries = new Countries($storeId);
$areas = new Areas($storeId);
$wards = new Wards($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_customer'] => '/'.ADMIN_SCRIPT.'?op=manage&act=customer',
				$amessages['list_item'] => '');
# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=customer';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

# Items per pages
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
$template->assign('ipp',$items_per_page);

# Page
$page = $request->element('pg',1);
$template->assign('pg',$page);

# Sort key
$sort_key = $request->element('sk','c.id');
if(!in_array($sort_key,$allow_sort_keys)) $sort_key='c.id';
$template->assign('sk',$sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd',$sort_direction);

# Action
$do = $request->element('doo','');
$template->assign('do',$do);

# Keywords
$kw = $request->element('kw','');
$template->assign('kw',$kw);

# Filter date created
$filter_date_created = $request->element('filter_date_created','');
$template->assign('filter_date_created',$filter_date_created);
if($do != 'search' && !$filter_date_created) $filter_date_created = 'all';

# Filter last login
$filter_last_login = $request->element('filter_last_login','');
$template->assign('filter_last_login',$filter_last_login);
if($do != 'search' && !$filter_last_login) $filter_last_login = 'all';

# Filter status
$filter_status = $request->element('filter_status','');
$template->assign('filter_status',$filter_status);
if($do != 'search' && !$filter_status) $filter_status = 'all';

# Filter customer groups
$filter_groups = $request->element('filter_groups','');
$template->assign('filter_groups',$filter_groups);
if($do != 'search' && !$filter_groups) $filter_groups = 'all';

# customer groups combo box
$customerGroupsCombo = $customerGroups->generateCombo($request->element('filter_groups'));
$template->assign('customerGroupsCombo',$customerGroupsCombo);

# Filter customer countries
$filter_countries = $request->element('filter_countries','');
$template->assign('filter_countries',$filter_countries);
if($do != 'search' && !$filter_countries) $filter_countries = 'all';

# customer countries combo box
$customerCountriesCombo = $countries->generateCombo($request->element('filter_countries'));
$template->assign('customerCountriesCombo',$customerCountriesCombo);

# Filter customer areas
$filter_areas = $request->element('filter_areas','');
$template->assign('filter_areas',$filter_areas);
if($do != 'search' && !$filter_areas) $filter_areas = 'all';

# customer areas combo box
if($request->element('filter_countries') || $request->element('filter_areas')){
	$customerAreasCombo = $areas->generateCombo($request->element('filter_areas'),"`country_id` = '".$request->element('filter_countries')."'");
	$template->assign('customerAreasCombo',$customerAreasCombo);
}

# Filter customer wards
$filter_wards = $request->element('filter_wards','');
$template->assign('filter_wards',$filter_wards);
if($do != 'search' && !$filter_wards) $filter_wards = 'all';

# customer wards combo box
if($request->element('filter_areas') || $request->element('filter_wards')){
	$customerWardsCombo = $wards->generateCombo($request->element('filter_wards'),"`area_id` = '".$request->element('filter_areas')."'");
	$template->assign('customerWardsCombo',$customerWardsCombo);
}

# Build WHERE condition
$condition = "1>0";
if($kw) $condition .= " AND (c.`id`='".controlBackSlashMySQL($kw)."' OR c.`username` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`fullname` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`email` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`address` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`tel` LIKE '%".controlBackSlashMySQL($kw)."%')";

if($filter_status != '' && $filter_status != 'all') $condition .= " AND c.`status`='$filter_status'";

if($filter_groups != '' && $filter_groups != 'all') $condition .= " AND c.`group_id`='$filter_groups'";

if($filter_countries != '' && $filter_countries != 'all') $condition .= " AND a.`country_id`='$filter_countries'";

if($filter_areas != '' && $filter_areas != 'all') $condition .= " AND a.`id`='$filter_areas'";

if($filter_wards != '' && $filter_wards != 'all') $condition .= " AND c.`ward_id`='$filter_wards'";

if ($kw) {
	if ($customers->searchCustomField($kw)) {
		$idsOption = $customers->searchCustomField($kw);
		$condition = "(c.`id` IN $idsOption OR c.`username` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`fullname` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`email` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`address` LIKE '%".controlBackSlashMySQL($kw)."%' OR c.`tel` LIKE '%".controlBackSlashMySQL($kw)."%')";
	} 
}

# Filter date created condition
$duration = '';
if($filter_date_created) {
        if($filter_date_created == 'onehour') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-3600);
                $condition .= " AND c.`date_created` >= '$duration'";
        } elseif($filter_date_created == 'fourhours') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-14400);
                $condition .= " AND c.`date_created` >= '$duration'";
        } elseif($filter_date_created == 'today') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y")));
                $condition .= " AND c.`date_created` >= '$duration'";
        } elseif($filter_date_created == '7') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*7);
                $condition .= " AND c.`date_created` >= '$duration'";
        } elseif($filter_date_created == '30') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*30);
                $condition .= " AND c.`date_created` >= '$duration'";
        } elseif($filter_date_created == '365') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,1,1,date("Y")));
                $condition .= " AND c.`date_created` >= '$duration'";
        } elseif($filter_date_created != 'all') {
        }
}

$duration = '';
if($filter_last_login) {
        if($filter_last_login == 'onehour') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-3600);
                $condition .= " AND c.`last_login` >= '$duration'";
        } elseif($filter_last_login == 'fourhours') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-14400);
                $condition .= " AND c.`last_login` >= '$duration'";
        } elseif($filter_last_login == 'today') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y")));
                $condition .= " AND c.`last_login` >= '$duration'";
        } elseif($filter_last_login == '7') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*7);
                $condition .= " AND c.`last_login` >= '$duration'";
        } elseif($filter_last_login == '30') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*30);
                $condition .= " AND c.`last_login` >= '$duration'";
        } elseif($filter_last_login == '365') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,1,1,date("Y")));
                $condition .= " AND c.`last_login` >= '$duration'";
        } elseif($filter_last_login != 'all') {
        }
}

$pages_condition = "c.`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $customers->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=customer&mod=list&doo=$do&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_last_login=$filter_last_login&filter_groups=$filter_groups&filter_countries=$filter_countries&filter_areas=$filter_areas&filter_wards=$filter_wards&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $customers->getObjects($page,$pages_condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Get custom options
$customValueName = $optionStructure->getNameFromModule("customer");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "customers", "customer"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("customer");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=customer&mod=list&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_last_login=$filter_last_login&filter_groups=$filter_groups&filter_countries=$filter_countries&filter_areas=$filter_areas&filter_wards=$filter_wards&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page";
$template->assign('link',$link);

# Submitted form
if($_POST) {
	switch($do) {
		case 'enable':
			$userInfo->checkPermission('customer','edit');
			$id = $request->element('id');
			if($id) {
				$customers->changeStatus($id,S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_customer'],$customers->getUserNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listCustomer = '';
					foreach ($ids as $id) {
						$customers->changeStatus($id,S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listCustomer .= ($listCustomer?',&nbsp;':'').$customers->getUserNameFromId($id);
					}
					$result_code = 1;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_customer'],$listCustomer),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('customer','edit');
			$id = $request->element('id');
			if($id) {
				$customers->changeStatus($id,S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_customer'],$customers->getUserNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listCustomer = '';
					foreach ($ids as $id) {
						$customers->changeStatus($id,S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listCustomer .= ($listCustomer?',&nbsp;':'').$customers->getUserNameFromId($id);
					}
					$result_code = 2;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_customer'],$listCustomer),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('customer','delete');
			$id = $request->element('id');
			if($id) {
				$customers->changeStatus($id,S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_customer'],$customers->getUserNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listCustomer = '';
					foreach ($ids as $id) {
						$customers->changeStatus($id,S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listCustomer .= ($listCustomer?',&nbsp;':'').$customers->getUserNameFromId($id);
					}
					$result_code = 3;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_customer'],$listCustomer),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'cleantrash':
			$userInfo->checkPermission('customer','clean',0);
			$customers->cleanTrash();
			$result_code = 5;
			
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_customer'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			break;
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=customer&mod=list&lang=$lang&ecode=7");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=customer&mod=list&doo=$do&kw=".urlencode($kw)."&filter_status=$filter_status&filter_date_created=$filter_date_created&filter_last_login=$filter_last_login&filter_groups=$filter_groups&filter_countries=$filter_countries&filter_areas=$filter_areas&filter_wards=$filter_wards&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code");
} else {

}
?>
