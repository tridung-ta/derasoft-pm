<?php
/*************************************************************************
Product category listing module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Coder: Mai Minh                                 
Reviewd by: Mai Minh (16/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('pro_cat', 'view');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
$productCategories = new ProductCategories($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['list_product_category'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	// $amessages['list_product_features'] => $tabLink . '&mod=listfeature',
	// $amessages['add_product_features'] => $tabLink . '&mod=addfeature',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 3);

# Get parameters
$items_per_page = $request->element('ipp', DEFAULT_ADMIN_ROWS_PER_PAGE);
$template->assign('ipp', $items_per_page);
$page = $request->element('pg',1);
$template->assign('pg', $page);

# Sort key
$sort_key = $request->element('sk','id');
$template->assign('sk', $sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd', $sort_direction);

# Action
$do = $request->element('doo','');
$template->assign('do', $do);

# Keyword
$kw = $request->element('kw','');
$template->assign('kw', $kw);

# pId
$pId = $request->element('pId','');
$template->assign('pId', $pId);

# Filter product cateogries combo
$filter_categories = $request->element('filter_categories',0);
$template->assign('filter_categories', $filter_categories);

$gfId = 0;
if ($filter_categories) {
	#$gfId = $productCategories->getParentIdFromId($pId);
	#$template->assign('gfId', $gfId);
	$topNav[$amessages['list_product_category']] = '/' . ADMIN_SCRIPT . '?op=manage&act=product&mod=listcategory';
	$topNav[$productCategories->getNameFromId($filter_categories)] = '';
}
$template->assign('gfId', $gfId);

# Get product categories array for generating nested combo
$arrayCategories = $productCategories->getObjectsForCombo();

# Filter product categories Combo
# New generate combo from array that needs only one query
# It reduces number of queries, especially when we need to generate many combos
$filterProductCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$filter_categories);
if($filterProductCategoriesCombo) $template->assign('filterProductCategoriesCombo',$filterProductCategoriesCombo);

# Bottom product categories Combo
$bottomProductCategoryCombo = $productCategories->generateNestedCombo($arrayCategories);
if($bottomProductCategoryCombo) $template->assign('bottomProductCategoryCombo',$bottomProductCategoryCombo);

# Build WHERE condition
$condition = $filter_categories > 0?"`parent_id` = '$filter_categories'":"1>0";

# Keyword
if ($kw) {
    $kw =controlBackSlashMySQL($kw);
    $idsOption = $productCategories->searchCustomField($kw); 
    
    if (!empty($idsOption)) {
        $idsString = implode(',', $idsOption);
        $condition .= " AND (`id` IN ($idsString) OR `slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
    } else {
        $condition .= " AND (`id`='$kw' OR `slug` LIKE '%$kw%' OR `name` LIKE '%$kw%')";
    }
}
# Page condition
$pages_condition = "`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $productCategories->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listcategory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $productCategories->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("productlistcategory");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "product_categories", "productlistcategory"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("productlistcategory");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listcategory&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$filter_categories&filter_categories=$page";
$template->assign('link', $link);

# ALlow URL popup
$template->assign('urlPopup', 1);

if ($_POST) {
	switch ($do) {
		case 'sethome':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productCategories->changeHome($id, S_ENABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_home_product_category'], $productCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deletehome':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productCategories->changeHome($id, S_DISABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_home_product_category'], $productCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'setabout':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productCategories->changeAbout($id, S_ENABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_about_product_category'], $productCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deleteabout':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productCategories->changeAbout($id, S_DISABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_about_product_category'], $productCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'enable':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productCategories->changeStatus($id, S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_product_category'], $productCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listCategory = '';
					foreach ($ids as $id) {
						$productCategories->changeStatus($id, S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listCategory .= ($listCategory ? ',&nbsp;' : '') . $productCategories->getNameFromId($id);
					}
					$result_code = 1;
					
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_product_category'], $listCategory), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productCategories->changeStatus($id, S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$result_code = 2;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_product_category'], $productCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listCategory = '';
					foreach ($ids as $id) {
						$productCategories->changeStatus($id, S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listCategory .= ($listCategory ? ',&nbsp;' : '') . $productCategories->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_product_category'], $listCategory), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('pro_cat', 'delete');
			$id = $request->element('id');
			if ($id) {
				$rowsPages = $productCategories->getNumItems('id', "`parent_id`='$id' AND `status` <> '" . S_DELETED . "'", 1);
				$fieldValue->changeStatus($id, S_DELETED);
				if ($rowsPages['rows']) {
					$error_code = 10;
				} else {
					$productCategories->changeStatus($id, S_DELETED);
					$result_code = 3;
					
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_product_category'], $productCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				}
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listCategory = '';
					$warning = 0;
					foreach ($ids as $id) {
						$rowsPages = $productCategories->getNumItems('id', "`parent_id`='$id' AND `status` <> '" . S_DELETED . "'", 1);
						$fieldValue->changeStatus($id, S_DELETED);
						if (!$rowsPages['rows']) {
							$productCategories->changeStatus($id, S_DELETED);
							$listCategory .= ($listCategory ? ',&nbsp;' : '') . $productCategories->getNameFromId($id);
						} else $warning = 1;
					}
					if ($warning) $error_code = 10;
					else $result_code = 3;
					
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_product_category'], $listCategory), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changegroup':
			$userInfo->checkPermission('pro_cat', 'edit');
			$ids = $request->element('ids');
			$new_parent_id = $request->element('new_parent_id');
			if ($ids) {
				$listCategory = '';
				foreach ($ids as $id) {
					$productCategories->changeParentId($id, $new_parent_id);
					$listCategory .= ($listCategory ? ',&nbsp;' : '') . $productCategories->getNameFromId($id);
				}
				$result_code = 4;
				$pId = $parent_id;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['change_parent_product_category'], $listCategory, $productCategories->getNameFromId($parent_id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'changeposition':
			$userInfo->checkPermission('pro_cat', 'edit');
			$positions = $request->element('positions');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$productCategories->changePosition($key, $value);
				}
				$result_code = 4;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_position_product_category'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listcategory&lang=$lang&ecode=7&filter_categories=$filter_categories");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listcategory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&filter_categories=$filter_categories");
} else {
}
