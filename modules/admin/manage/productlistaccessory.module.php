<?php
/*************************************************************************
Product listing module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 10/05/2012
Checked by: Mai Minh (16/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('product', 'view');


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('id','category_id','name','price','position','viewed','status','date_created','date_updated','a.status','a.home','a.viewed','a.position','category_name');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
// include_once(ROOT_PATH . 'classes/dao/imgs.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
// $imgs = new Imgs();
$template->assign('imgs', $imgs);
$productaccessorys = new Productaccessorys($storeId);
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$productOptions = new ProductOptions($storeId);
$search = new Search($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_product'] => '/' . ADMIN_SCRIPT . '?op=manage&act=product',
	$amessages['list_item'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
	$amessages['list_item_lop'] => $tabLink . '&mod=list',
	$amessages['add_lop'] => $tabLink . '&mod=add',
	$amessages['list_item_pk'] => $tabLink . '&mod=listaccessory',
	$amessages['add_pk'] => $tabLink . '&mod=addaccessory',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_tramk'] => $tabLink . '&mod=listoption',
	$amessages['add_tramk'] => $tabLink . '&mod=addoption',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 3);

# Get product categories array for generating nested combo
$arrayCategories = $productCategories->getObjectsForCombo();

# Filter product categories Combo
$filter_categories = $request->element('filter_categories','-1');
if ($filter_categories)	$template->assign('filter_categories',$filter_categories);

# New generate combo from array that needs only one query
# It reduces number of queries, especially when we need to generate many combos
$filterProductCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$filter_categories);
if($filterProductCategoriesCombo) $template->assign('filterProductCategoriesCombo',$filterProductCategoriesCombo);

# Bottom product categories Combo
$bottomProductCategoryCombo = $productCategories->generateNestedCombo($arrayCategories);
if($bottomProductCategoryCombo) $template->assign('bottomProductCategoryCombo',$bottomProductCategoryCombo);

# Get parameters
$items_per_page = $request->element('ipp') ? $request->element('ipp') : DEFAULT_ADMIN_ROWS_PER_PAGE;
if ($items_per_page) $template->assign('ipp', $items_per_page);
$page = $request->element('pg') ? $request->element('pg') : 1;
if ($page) $template->assign('pg', $page);

# Sort key
$sort_key = $request->element('sk') ? $request->element('sk') : 'id';
if(!in_array($sort_key,$allow_sort_keys)) $sort_key='id';
if ($sort_key) $template->assign('sk', $sort_key);

# Sort direction
$sort_direction = $request->element('sd') ? $request->element('sd') : 'DESC';
if ($sort_direction) $template->assign('sd', $sort_direction);

# Action
$do = $request->element('doo') ? $request->element('doo') : '';
if ($do) $template->assign('do', $do);

# Keyword
$kw = $request->element('kw') ? $request->element('kw') : '';
if ($kw) $template->assign('kw', $kw);
$pId = $request->element('filter_categories') ? $request->element('filter_categories') : 0;

# Build WHERE condition
$condition = $filter_categories > 0 ? "`category_id` = '$filter_categories'" : "1>0";
if ($kw) {
	if ($productCategories->searchCustomField($kw)) {
		$idsOption = $productCategories->searchCustomField($kw);
		$condition .= " AND (`id` IN $idsOption OR `slug` LIKE '%$kw%' OR `name` LIKE '%$kw%' OR `id` LIKE '%$kw%')";
	} else {
		$condition = " AND (`slug` LIKE '%$kw%' OR `name` LIKE '%$kw%' OR `id` LIKE '%$kw%')";
	}
}

# Page condition
$pages_condition = "`store_id` = '$storeId' AND $condition";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $productaccessorys->getNumItems('id', $pages_condition, $items_per_page);

$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listaccessory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $productaccessorys->getObjects($page, $condition, $sort, $items_per_page);
if ($listItems) $template->assign('listItems', $listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("productaccessory");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "productaccessory", "productaccessory"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("productaccessory");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listaccessory&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pId=$pId&pg=$page";
$template->assign('link', $link);

# ALlow URL popup
$template->assign('urlPopup', 1);

if ($_POST) {
	switch ($do) {
		case 'duplicate':
			$userInfo->checkPermission('product', 'add');
			$id = $request->element('id');
			if ($id) {
				$productInfo = $productaccessorys->getObject($id);
				$property = $productInfo->getProperties();
				$properties = array(
					'user_upload' =>  $userInfo->getUsername(),
					'avatar' => '',
					'photos' => '',
					'videos' => '',
					'files' =>  ''
				);
				$slug = $productInfo->getSlug();
				$cat_id = $productInfo->getCatId();
				# Check if duplicate slug
				include_once(ROOT_PATH . 'classes/data/textfilter.class.php');
				$slug = TextFilter::urlize($slug, false, '-');
				$i = 0;
				$dup = 1;
				while ($dup) {
					$dup = $productaccessorys->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "cat_id = '$cat_id'");
					if ($dup) $i++;
				}
				$slug .= $i ? '-' . $i : '';

				$data = array(
					'store_id' => $storeId,
					'cat_id' => $productInfo->getCatId(),
					'slug' => $slug,
					'name' => $productInfo->getName(),
					'keyword' => $productInfo->getKeyword(),
					'sku' => $productInfo->getSku(),
					'position' => $productInfo->getPosition(),
					'status' => $productInfo->getStatus(),
					'currency' => $productInfo->getCurrency(),
					'price' => $productInfo->getPrice(),
					'market_price' => $productInfo->getMarketPrice(),
					'description' => $productInfo->getDescription(),
					'detail' => $productInfo->getDetail(),
					'properties' => serialize($properties),
					'created' => date("Y-m-d H:i:s")
				);
				$productaccessorys->addData($data);
				$result_code = 8;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['duplicate_product'], $productInfo->getName()), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'enable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productaccessorys->changeStatus($id, S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$search->changeStatus('product', $id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_product'], $productaccessorys->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$productaccessorys->changeStatus($id, S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$search->changeStatus('product', $id, S_ENABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $productaccessorys->getNameFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_product'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productaccessorys->changeStatus($id, S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				$search->changeStatus('product', $id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_product'], $productaccessorys->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$productaccessorys->changeStatus($id, S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$search->changeStatus('product', $id, S_DISABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $productaccessorys->getNameFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_product'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('product', 'delete');
			$id = $request->element('id');
			if ($id) {
				$productaccessorys->changeStatus($id, S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$search->changeStatus('product', $id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_product'], $productaccessorys->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$productaccessorys->changeStatus($id, S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$search->changeStatus('product', $id, S_DELETED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $productaccessorys->getNameFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_product'], $listProduct), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'sethome':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productaccessorys->changeHome($id, S_ENABLED);
				$result_code = 7;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['set_home_product'], $productaccessorys->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deletehome':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$productaccessorys->changeHome($id, S_DISABLED);
				$result_code = 7;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_home_product'], $productaccessorys->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'changegroup':
			$userInfo->checkPermission('product', 'edit');
			$ids = $request->element('ids');
			$parent_id = $request->element('parent_id');
			if (!$parent_id) $error_code = 9;
			else {
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$productaccessorys->changeCatId($id, $parent_id);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $productaccessorys->getNameFromId($id);
					}
					$result_code = 4;
					$pId = $parent_id;
					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['change_product_group'], $listProduct, $productCategories->getNameFromId($parent_id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'changeposition':
			$userInfo->checkPermission('product', 'edit');
			$positions = $request->element('positions');
			$prices = $request->element('prices');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$productaccessorys->changePosition($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_product_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			if ($prices) {
				foreach ($prices as $key => $value) {
					$productaccessorys->changePrice($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_product_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('product', 'clean', 0);
			$cleanCategories = $request->element('categories');
			$cleanOptions = $request->element('options');
			$cleanItems = $request->element('items');
			$fieldValue->cleanTrash();
			if ($cleanCategories == 1) {
				$productCategories->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product_category'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			if ($cleanOptions == 1) {
				$productOptions->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product_options'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			if ($cleanItems == 1) {
				$productaccessorys->cleanTrash();
				$result_code = 5;
				$search->cleanTrash('product');
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listaccessory&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=listaccessory&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&pId=$pId");
} else {
}
