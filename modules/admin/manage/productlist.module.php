<?php
	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);
# Check permission
$userInfo->checkPermission('product', 'view');

# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('id','category_id','name','price','position','viewed','status','date_created','date_updated','a.status','a.home','a.viewed','a.position','category_name');

$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/customproductoptions.class.php');
include_once(ROOT_PATH . 'classes/dao/customproductoptionvalue.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
$customProductOptions = new CustomProductOptions($storeId);
$customProductOptionValues = new CustomProductOptionValues($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$productOptions = new ProductOptions($storeId);
$search = new Search($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
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
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_product_category'] => $tabLink . '&mod=addcategory',
	// $amessages['list_product_features'] => $tabLink . '&mod=listfeature',
	// $amessages['add_product_features'] => $tabLink . '&mod=addfeature',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

# Item per pages
$items_per_page = $request->element('ipp',DEFAULT_ADMIN_ROWS_PER_PAGE);
$template->assign('ipp', $items_per_page);

# Page
$page = $request->element('pg',1);
$template->assign('pg', $page);

# Sort key
$sort_key = $request->element('sk','id');
if(!in_array($sort_key,$allow_sort_keys)) $sort_key='id';
$template->assign('sk', $sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd', $sort_direction);

# Action
$do = $request->element('doo','');
$template->assign('do', $do);

# Keywords
$kw = $request->element('kw','');
$template->assign('kw', $kw);

$pId = $request->element('pId','-1');
$template->assign('pId', $pId);

# Filter product categories
$filter_categories = $request->element('filter_categories','-1');
$template->assign('filter_categories',$filter_categories);
if ($filter_categories > 0) {
	#$gfId = $productCategories->getParentIdFromId($pId);
	#$template->assign('gfId', $gfId);
	$topNav[$amessages['list_item']] = '/' . ADMIN_SCRIPT . '?op=manage&act=product&mod=list';
	$topNav[$productCategories->getNameFromId($pId)] = '';
}

# Get product categories array for generating nested combo
$arrayCategories = $productCategories->getObjectsForCombo();

# Filter product categories Combo
# New generate combo from array that needs only one query
# It reduces number of queries, especially when we need to generate many combos
$filterProductCategoriesCombo = $productCategories->generateNestedCombo($arrayCategories,$filter_categories);
$template->assign('filterProductCategoriesCombo',$filterProductCategoriesCombo);

# Bottom product categories Combo
$bottomProductCategoryCombo = $productCategories->generateNestedCombo($arrayCategories);
$template->assign('bottomProductCategoryCombo',$bottomProductCategoryCombo);

# Build WHERE condition
$condition = $filter_categories > 0 ? "p.`category_id` = '$filter_categories'" : "1>0";

# Keyword
if ($kw) {
    $idsOption = $products->searchCustomField($kw);
    if ($idsOption) {
        $condition .= " AND (p.`id` IN $idsOption OR p.`slug` LIKE '%$kw%' OR p.`name` LIKE '%$kw%' OR p.`id` LIKE '%$kw%')";
    } else {
        $condition .= " AND (p.`slug` LIKE '%$kw%' OR p.`name` LIKE '%$kw%' OR p.`id` LIKE '%$kw%')";
    }
}

# Page condition
$condition_no_alias = str_replace('p.`', '`', $condition);
$pages_condition = "`store_id` = '$storeId' AND $condition_no_alias";
// $pages_condition = "`store_id` = '$storeId' AND $condition";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $products->getNumItems('id', $pages_condition, $items_per_page);
$template->assign('rowsPages', $rowsPages);
if ($page < 1) $page = 1;
if ($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page - 1) * $items_per_page + 1;
$template->assign('startNum', $start_num);
$url = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_categories=$filter_categories&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url, $rowsPages['pages'], $page);
$template->assign('pager', $pager);

# Get objects
$listItems = $products->getObjects($page, $condition, $sort, $items_per_page);
// if ($listItems) $template->assign('listItems', $listItems);
if ($listItems) {
	foreach ($listItems as $item) {
		if ($avatarId = (int)$item->getAvatar()) {
			$item->avatarImg = $uploads->getObject($avatarId);
		}
	}
	$template->assign('listItems', $listItems);
}

# Get custom product options
$template->assign('customProductOptions', $customProductOptions);

# Get custom options
$customValueName = $optionStructure->getNameFromModule("product");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "products", "product"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("product");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);

# Link
$link = '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_categories=$filter_categories&pg=$page";
$template->assign('link', $link);

# ALlow URL popup
$template->assign('urlPopup', 1);

# Submitted form
if ($_POST) {
	switch ($do) {
		case 'duplicate':
			$userInfo->checkPermission('product', 'add');
			$id = $request->element('id');
			if ($id) {
				$productInfo = $products->getObject($id);
				$property = $productInfo->getProperties();
				$properties = array(
					'user_upload' =>  $userInfo->getUsername(),
					'avatar' => '',
					'photos' => '',
					'videos' => '',
					'files' =>  ''
				);
				$slug = $productInfo->getSlug();
				$category_id = $productInfo->getCategoryId();
				# Check if duplicate slug
				include_once(ROOT_PATH . 'classes/data/textfilter.class.php');
				// $slug = TextFilter::urlize($slug, false, '-');
				$textFilter = new TextFilter();
				$slug = $textFilter->urlize($slug, false, '-');   
				$i = 0;
				$dup = 1;
				while ($dup) {
					$dup = $products->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "category_id = '$category_id'");
					if ($dup) $i++;
				}
				$slug .= $i ? '-' . $i : '';

				$data = array(
					'store_id' => $storeId,
					'category_id' => $productInfo->getCategoryId(),
					'slug' => $slug,
					'name' => $productInfo->getName(),
					'keyword' => $productInfo->getKeyword(),
					'position' => $productInfo->getPosition(),
					'status' => $productInfo->getStatus(),
					'description' => $productInfo->getDescription(),
					'detail' => $productInfo->getDetail(),
					'properties' => serialize($properties),
					'date_created' => date("Y-m-d H:i:s"),
					'price' => $productInfo->getPrice(),
					'expiration_date' => $productInfo->getExpirationDate(),
				);
				$products->addData($data);
				$result_code = 8;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['duplicate_product'], $productInfo->getName()), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'enable':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeStatus($id, S_ENABLED);
				// $newOptionId = $customProductOptions->changeStatusByProductId($id, S_ENABLED);
				// $customProductOptionValues->changeStatusByOptionIds($newOptionId, S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$search->changeStatus('product', $id, S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$products->changeStatus($id, S_ENABLED);
						$search->changeStatus('product', $id, S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $products->getNameFromId($id);
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
				$products->changeStatus($id, S_DISABLED);
				$fieldValue->changeStatus($id, S_DISABLED);
				// $newOptionId = $customProductOptions->changeStatusByProductId($id, S_DISABLED);
				// $customProductOptionValues->changeStatusByOptionIds($newOptionId, S_DISABLED);
				$search->changeStatus('product', $id, S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$products->changeStatus($id, S_DISABLED);
						$search->changeStatus('product', $id, S_DISABLED);
						$fieldValue->changeStatus($id, S_DISABLED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $products->getNameFromId($id);
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
				$products->changeStatus($id, S_DELETED);
				$newOptionId = $customProductOptions->changeStatusByProductId($id, S_DELETED);
				$customProductOptionValues->changeStatusByOptionIds($newOptionId, S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$search->changeStatus('product', $id, S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['delete_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if ($ids) {
					$listProduct = '';
					foreach ($ids as $id) {
						$products->changeStatus($id, S_DELETED);
						$search->changeStatus('product', $id, S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $products->getNameFromId($id);
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
				$products->changeHome($id, S_ENABLED);
				$result_code = 7;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['set_home_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deletehome':
			$userInfo->checkPermission('product', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeHome($id, S_DISABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_home_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'setabout':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeAbout($id, S_ENABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_about_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deleteabout':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeAbout($id, S_DISABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_about_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'setfooter':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeFooter($id, S_ENABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_footer_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deletefooter':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeFooter($id, S_DISABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_footer_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'setprofessional':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeProfessional($id, S_ENABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['enable_professional_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'deleteprofessional':
			$userInfo->checkPermission('pro_cat', 'edit');
			$id = $request->element('id');
			if ($id) {
				$products->changeProfessional($id, S_DISABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['disable_professional_product'], $products->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
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
						$products->changeCategoryId($id, $parent_id);
						$listProduct .= ($listProduct ? ',&nbsp;' : '') . $products->getNameFromId($id);
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
			// $prices = $request->element('prices');
			if ($positions) {
				foreach ($positions as $key => $value) {
					$products->changePosition($key, $value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_product_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			// if ($prices) {
			// 	foreach ($prices as $key => $value) {
			// 		$products->changePrice($key, $value);
			// 	}
			// 	$result_code = 4;
			// 	# Operation tracking
			// 	$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['change_product_position'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			// } else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('product', 'clean', 0);
			$cleanCategories = $request->element('categories');
			$cleanItems = $request->element('items');
			$productOption = $request->element('productoption');
			$fieldValue->deleteData();
			if ($cleanCategories == 1) {
				$productCategories->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product_category'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			if ($cleanItems == 1) {

				$uploadIds = $products->getTrashUploadIds();
				if ($uploadIds) {
					$uploads->cleanTrashByIds($uploadIds);
				}
				$products->cleanTrash();
				$result_code = 5;
				$search->cleanTrash('product');
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			if ($productOption == 1) {
				$customProductOptions->cleanTrash();
				$customProductOptionValues->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['tracking']['clean_trash_product_category'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'productoptions':
			$id = $request->element('id');
			// header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=customproductoption&mod=list&doo=search&kw=$id&lang=$lang&ipp=20&sk=id&sd=DESC&pg=1&ecode=&rcode=&filter_categories=$filter_categories");
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=picklist&id=$id&lang=$lang");
			exit;
			break;
		case 'cancel':
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=list&lang=$lang&ecode=7&pId=$pId");
			exit;
			break;
	}
	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&filter_categories=$filter_categories");
} else {
}
