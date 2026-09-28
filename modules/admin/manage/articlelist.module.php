<?php
/*************************************************************************
Article listing module
----------------------------------------------------------------
Derasoft CMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Coder: Mai Minh
Last updated: 11/06/2025
**************************************************************************/
# Check permission
// ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);
$userInfo->checkPermission('article','view');

# Allowed sort keys - prevent SQL injection
$allow_sort_keys = array('article_id','a.title','slug','keyword','description','poster_username','updater_username','date_created','date_updated','a.status','a.home','a.viewed','a.position','category_name');

$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH.'classes/dao/articles.class.php');
include_once(ROOT_PATH.'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH."classes/dao/searchs.class.php");
include_once(ROOT_PATH."classes/dao/users.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$articles = new Articles($storeId);
$search= new Search($storeId);
$articleCategories = new ArticleCategories($storeId);
$users = new Users($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_article'] => '/'.ADMIN_SCRIPT.'?op=manage&act=article',
				$amessages['list_item'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=article';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['list_article_category'] => $tabLink.'&mod=listcategory',
				$amessages['add_article_category'] => $tabLink.'&mod=addcategory',
				$amessages['list_articlegroup'] => $tabLink.'&mod=listgroup',
				$amessages['add_articlegroup'] => $tabLink.'&mod=addgroup',	
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

# Get parameters
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
$template->assign('ipp',$items_per_page);
$page = $request->element('pg')?$request->element('pg'):1;
$template->assign('pg',$page);

# Sort key
$sort_key = $request->element('sk','article_id');
if(!in_array($sort_key,$allow_sort_keys)) $sort_key='article_id';
$template->assign('sk',$sort_key);

# Sort direction
$sort_direction = $request->element('sd','DESC');
$template->assign('sd',$sort_direction);

$do = $request->element('doo','');
$template->assign('do',$do);
$kw = $request->element('kw','');
$template->assign('kw',$kw);

# Filter date created
$filter_date_created = $request->element('filter_date_created','');
$template->assign('filter_date_created',$filter_date_created);
if($do != 'search' && !$filter_date_created) $filter_date_created = 'all';

# Filter date updated
$filter_date_updated = $request->element('filter_date_updated','');
$template->assign('filter_date_updated',$filter_date_updated);
if($do != 'search' && !$filter_date_updated) $filter_date_updated = 'all';

# Filter status
$filter_status = $request->element('filter_status','');
$template->assign('filter_status',$filter_status);
if($do != 'search' && !$filter_status) $filter_status = 'all';

# Filter article categories
$filter_categories = $request->element('filter_categories','-1');
$template->assign('filter_categories',$filter_categories);
if($filter_categories>=0) {
	#$gfId = $articleCategories->getParentIdFromId($filter_categories);
	#$template->assign('gfId',$gfId);
	$topNav[$amessages['list_item']] = '/'.ADMIN_SCRIPT.'?op=manage&act=article&mod=list';
	$topNav[$articleCategories->getNameFromId($filter_categories)] = '';
}

# Get article categories array for generating nested combo
$arrayCategories = $articleCategories->getObjectsForCombo();

# Filter article categories Combo
# New generate combo from array that needs only one query
# It reduces number of queries, especially when we need to generate many combos
$filterArticleCategoriesCombo = $articleCategories->generateNestedCombo($arrayCategories,$filter_categories);
$template->assign('filterArticleCategoriesCombo',$filterArticleCategoriesCombo);

# Bottom article categories Combo
$bottomArticleCategoryCombo = $articleCategories->generateNestedCombo($arrayCategories);
$template->assign('bottomArticleCategoryCombo',$bottomArticleCategoryCombo);

# Filter posters
$filter_posters = $request->element('filter_posters','');
$template->assign('filter_posters',$filter_posters);
if($do != 'search' && !$filter_posters) $filter_posters = 'all';

# Filter updaters
$filter_updaters = $request->element('filter_updaters','');
$template->assign('filter_updaters',$filter_updaters);
if($do != 'search' && !$filter_updaters) $filter_updaters = 'all';

# Get users array for generating many combo
$arrayUsers = $users->getObjectsForCombo();

# Filter posters Combo
$filterPostersCombo = $users->generateComboFromArray($arrayUsers,$filter_posters);
$template->assign('filterPostersCombo',$filterPostersCombo);

# Filter updaters Combo
$filterUpdatersCombo = $users->generateComboFromArray($arrayUsers,$filter_updaters);
$template->assign('filterUpdatersCombo',$filterUpdatersCombo);

# Build WHERE condition
$condition = '1>0';

# Fileter status
if($filter_status != '' && $filter_status != 'all') $condition .= " AND a.`status`='$filter_status'";

# Filter article categories condition
$condition .= $filter_categories>0?" AND `category_id` = '$filter_categories'":"";

# Filter posters condition
$condition .= ($filter_posters != '' && $filter_posters != 'all')?" AND a.`poster_id` = '$filter_posters'":"";

# Filter updaters condition
$condition .= ($filter_updaters != '' && $filter_updaters != 'all')?" AND a.`updater_id` = '$filter_updaters'":"";

# Keyword condition
if ($kw) {
	if ($articles->searchCustomField($kw)) {
		$idsOption = $articles->searchCustomField($kw);
		$condition .= " AND (a.`id` IN $idsOption OR a.`slug` LIKE '%".controlBackSlashMySQL($kw)."%' OR a.`title` LIKE '%".controlBackSlashMySQL($kw)."%' OR a.`keyword` LIKE '%".controlBackSlashMySQL($kw)."%' OR a.`description` LIKE '%".controlBackSlashMySQL($kw)."%')";
	} else {
		$condition .= " AND (a.`id`='".controlBackSlashMySQL($kw)."' OR a.`slug` LIKE '%".controlBackSlashMySQL($kw)."%' OR a.`title` LIKE '%".controlBackSlashMySQL($kw)."%' OR a.`description` LIKE '%".controlBackSlashMySQL($kw)."%')";
	}
}

# Filter date created condition
$duration = '';
if($filter_date_created) {
        if($filter_date_created == 'onehour') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-3600);
                $condition .= " AND a.`date_created` >= '$duration'";
        } elseif($filter_date_created == 'fourhours') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-14400);
                $condition .= " AND a.`date_created` >= '$duration'";
        } elseif($filter_date_created == 'today') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y")));
                $condition .= " AND a.`date_created` >= '$duration'";
        } elseif($filter_date_created == '7') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*7);
                $condition .= " AND a.`date_created` >= '$duration'";
        } elseif($filter_date_created == '30') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*30);
                $condition .= " AND a.`date_created` >= '$duration'";
        } elseif($filter_date_created == '365') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,1,1,date("Y")));
                $condition .= " AND a.`date_created` >= '$duration'";
        } elseif($filter_date_created != 'all') {
        }
}

# Filter date updated condition
$duration = '';
if($filter_date_updated) {
        if($filter_date_updated == 'onehour') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-3600);
                $condition .= " AND a.`date_updated` >= '$duration'";
        } elseif($filter_date_updated == 'fourhours') {
                $duration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"))-14400);
                $condition .= " AND a.`date_updated` >= '$duration'";
        } elseif($filter_date_updated == 'today') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y")));
                $condition .= " AND a.`date_updated` >= '$duration'";
        } elseif($filter_date_updated == '7') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*7);
                $condition .= " AND a.`date_updated` >= '$duration'";
        } elseif($filter_date_updated == '30') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d"),date("Y"))-86400*30);
                $condition .= " AND a.`date_updated` >= '$duration'";
        } elseif($filter_date_updated == '365') {
                $duration = date("Y-m-d H:i:s", mktime(0,0,0,1,1,date("Y")));
                $condition .= " AND a.`date_updated` >= '$duration'";
        } elseif($filter_date_updated != 'all') {
        }
}

$pages_condition = "a.`store_id` = '$storeId' AND ($condition)";
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $articles->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=article&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_date_created=$filter_date_created&filter_date_updated=$filter_date_updated&filter_status=$filter_status&filter_categories=$filter_categories&filter_posters=$filter_posters&filter_updaters=$filter_updaters&pg=%d";
$urls = new Url();
$pager = $urls->genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
$listItems = $articles->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Get custom options field
$customValueName = $optionStructure->getNameFromModule("article");
if ($customValueName) $template->assign('customValueName', $customValueName);
$customValueField = $optionStructure->getCustomValueField( "articles", "article"); // 1: table in db, 2: module
if ($customValueField) $template->assign('customValueField', $customValueField);
$customFieldsMapping = $optionStructure->getCustomFieldsMapping("article");
if ($customFieldsMapping) $template->assign('customFieldsMapping', $customFieldsMapping);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=article&mod=list&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&filter_date_created=$filter_date_created&filter_date_updated=$filter_date_updated&filter_status=$filter_status&filter_categories=$filter_categories&filter_posters=$filter_posters&filter_updaters=$filter_updaters&pg=$page";
$template->assign('link',$link);

# Show URL Popup
$template->assign('urlPopup',1);

if($_POST) {
	switch($do) {
		case 'duplicate':
			$userInfo->checkPermission('article','add');
			$id = $request->element('id');
			if($id) {
				$articleInfo = $articles->getObject($id);
				$property = $articleInfo->getProperties();
				$properties = array('user_upload' =>  $userInfo->getUsername(),
									'avatar' => '',
									'photos' => '',
									'videos' => '',
									'files' =>  ''
									);
				$slug = $articleInfo->getSlug();
				$category_id = $articleInfo->getCategoryId();
				
				# Check if duplicate slug
				include_once(ROOT_PATH.'classes/data/textfilter.class.php');
				$textFilter = new TextFilter();
				$slug = $textFilter->urlize($slug,false,'-');
				$i = 0;
				$dup = 1;
				while($dup) {
					$dup = $articles->checkDuplicate($slug.($i?'-'.$i:''),'slug',"category_id = '$category_id'");
					if($dup) $i++;
				}
				$slug .= $i?'-'.$i:'';

				$data = array('store_id' => $storeId,
						  'category_id' => $articleInfo->getCategoryId(),
						  'poster_id' => $userInfo->getId(),
						  'slug' => $slug,
						  'title' => $articleInfo->getTitle(),
						  'keyword' => $articleInfo->getKeyword(),
						  'description' => $articleInfo->getDescription(),
						  'detail' => $articleInfo->getDetail(),
						  'viewed' => 0,
						  'status' => 0,
						  'home' => 0,
						  'position' =>$articleInfo->getPosition(),
						  'status' => $articleInfo->getStatus(),
						  'properties' => serialize($properties),
						  'date_created' => date("Y-m-d H:i:s"));
				$articles->addData($data);
				$result_code = 8;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['duplicate_article'],$articleInfo->getTitle()),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} 
			break;
		case 'enable':
			$userInfo->checkPermission('article','edit');
			$id = $request->element('id');
			if($id) {
				$articles->changeStatus($id,S_ENABLED);
                $search->changeStatus('article',$id,S_ENABLED);
				$fieldValue->changeStatus($id, S_ENABLED);
				$result_code = 1;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_article'],$articles->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$articles->changeStatus($id,S_ENABLED);
                        $search->changeStatus('article',$id,S_ENABLED);
						$fieldValue->changeStatus($id, S_ENABLED);
						$listArticle .= ($listArticle?',&nbsp;':'').$articles->getTitleFromId($id);
					}
					$result_code = 1;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_article'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('article','edit');
			$id = $request->element('id');
			if($id) {
				$articles->changeStatus($id,S_DISABLED);
                $search->changeStatus('article',$id,S_DISABLED);
				$result_code = 2;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_article'],$articles->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$articles->changeStatus($id,S_DISABLED);
                        $search->changeStatus('article',$id,S_DISABLED);
						$listArticle .= ($listArticle?',&nbsp;':'').$articles->getTitleFromId($id);
					}
					$result_code = 2;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_article'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('article','delete');
			$id = $request->element('id');
			if($id) {
				$articles->changeStatus($id,S_DELETED);
                $search->changeStatus('article',$id,S_DELETED);
				$fieldValue->changeStatus($id, S_DELETED);
				$result_code = 3;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_article'],$articles->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$articles->changeStatus($id,S_DELETED);
                        $search->changeStatus('article',$id,S_DELETED);
						$fieldValue->changeStatus($id, S_DELETED);
						$listArticle .= ($listArticle?',&nbsp;':'').$articles->getTitleFromId($id);
					}
					$result_code = 3;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_article'],$listArticle),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'sethome':
			$userInfo->checkPermission('article','edit');
			$id = $request->element('id');
			if($id) {
				$articles->changeHome($id,S_ENABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_home_article'],$articles->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} 
			break;
		case 'deletehome':
			$userInfo->checkPermission('article','edit');
			$id = $request->element('id');
			if($id) {
				$articles->changeHome($id,S_DISABLED);
				$result_code = 7;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_home_article'],$articles->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			}
			break;
		case 'changegroup':
			$userInfo->checkPermission('article','edit');
			$ids = $request->element('ids');
			$new_category_id = $request->element('new_category_id');
			if(!$new_category_id) $error_code = 9;
			else {
				if($ids) {
					$listArticle = '';
					foreach ($ids as $id) {
						$articles->changeCategoryId($id,$new_category_id);
						$listArticle .= ($listArticle?',&nbsp;':'').$articles->getTitleFromId($id);
					}
					$result_code = 4;
					$filter_categories = $new_category_id;
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['change_article_group'],$listArticle,$articleCategories->getNameFromId($new_category_id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					echo "a";
				} else $error_code = 5;
			}
			break;
		case 'changeposition':
			$userInfo->checkPermission('article','edit');
			$positions = $request->element('positions');
			if($positions) {
				foreach ($positions as $key=>$value) {
					$articles->changePosition($key,$value);
				}
				$result_code = 4;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_article_position'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('article','clean',0);
			$cleanCategories = $request->element('categories'); 
			$cleanItems = $request->element('items');
			$fieldValue->deleteData();
			if($cleanCategories == 1) { 
				$articleCategories->cleanTrash();
               // $search->cleanTrash('article');
				$result_code = 5;
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_article_category'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			}
			if($cleanItems == 1) {
				$articles->cleanTrash();
				$result_code = 5;
				
				# Operation tracking
                $search->cleanTrash('article');
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_article'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			}
			break;		
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=list&lang=$lang&ecode=7&filter_date_created=$filter_date_created&filter_date_updated=$filter_date_updated&filter_status=$filter_status&filter_categories=$filter_categories&filter_posters=$filter_posters&filter_updaters=$filter_updaters");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=list&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&filter_date_created=$filter_date_created&filter_date_updated=$filter_date_updated&filter_status=$filter_status&filter_categories=$filter_categories&filter_posters=$filter_posters&filter_updaters=$filter_updaters");
} else {

}
?>