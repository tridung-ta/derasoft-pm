<?php
/*************************************************************************
Adding article category module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 14/06/2025
**************************************************************************/
# Check permisson
$userInfo->checkPermission('category','add');

$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH.'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
$fields = new Fields($storeId);
$articleCategories = new ArticleCategories($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_article'] => '/'.ADMIN_SCRIPT.'?op=manage&act=article',
				$amessages['add_article_category'] => '');

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
$template->assign('currentTab',4);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Get article categories array for generating nested combo
$arrayCategories = $articleCategories->getObjectsForCombo();

# Category combo box
$categoryCombo = $articleCategories->generateNestedCombo($arrayCategories,$request->element('parent_id'));
if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='articlecategories'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='articlecategory'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Allow some javascript
$template->assign('ckEditor',1);

# Default value
$template->assign('default_items_per_page',DEFAULT_ROWS_PER_PAGE);
	
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	
	# Get list of custom options
	$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='articlecategory'", array('position' => 'ASC'));
	if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate category name
		if($articleCategories->checkDuplicate($request->element('name'))) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		
		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('name'),false,'-');
		$i = 0;
		$dup = 1;
		while($dup) {
			echo "a";
			$dup = $articleCategories->checkDuplicate($slug.($i?'-'.$i:''),'slug');
			if($dup) $i++;
		}
		$slug .= $i?'-'.$i:'';
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$properties = array('');

			# Custom fields
			foreach($fieldList as $field) {
				$properties[$field->getName()] = $request->element($field->getName());
			}
			echo "a".$request->element('check_duplicate_article_title')."b";
			$data = array('store_id' => $storeId,
						  'parent_id' => (int)$request->element('parent_id'),
						  'slug' => $slug,
						  'name' => $request->element('name'),
						  'keyword' => $request->element('keyword'),
						  'description' => $request->element('description'),
						  'landing' => (int)$request->element('landing'),
						  'detail' => $request->element('detail'),
						  'position' => (int)$request->element('position'),
						  'status' => (int)$request->element('status'),
						  'check_duplicate_title' => (int)$request->element('check_duplicate_article_title'),
						  'sort_key' => Filter($request->element('sort_key')),
						  'sort_direction' => Filter($request->element('sort_direction')),
						  'layout' => Filter($request->element('layout')),
						  'items_per_page' => (int)$request->element('items_per_page'),
						  'properties' => serialize($properties));
			$newId = $articleCategories->addData($data);

			# Custom Options
			if ($newId) {
				foreach ($fieldOptionList as $field) {
					$valueType = stripslashes($request->element($field->getFieldName()));
					if ($field->getFieldType() == 4 || $field->getFieldType() == 7) {
						$selectedKeys = (array) $request->element($field->getFieldName());
						$options = $field->getValue(); 
						$selectedValues = array_map(function ($key) use ($options) {
							return isset($options[$key]) ? $options[$key] : $key;
						}, $selectedKeys);

						$valueType = implode(", ", $selectedValues);
					}
					if ($field->getFieldType() == 5 || $field->getFieldType() == 6) {
						$options = $field->getValue();
						$valueType = isset($options[$valueType]) ? $options[$valueType] : $valueType; 
					}
					$fieldData = array(
						'store_id' => $storeId,
						'field_id' => $field->getId(),
						'key_id' => $newId,
						'field_value' => $valueType,
						'status' => 1,
					);
					$newFieldValue = $fieldValue->addData($fieldData);
				}
			}
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_article_category'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=listcategory&pId=".$request->element('parent_id')."&rcode=6");
		}
	}
}

# Check validate input
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['parent_id'] = $validate->validInteger($request->element('parent_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'),$amessages['keyword']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'),$amessages['description']);
	$error['INPUT']['landing'] = $validate->pasteString($request->element('landing'),0);
	$error['INPUT']['detail'] = $validate->pasteString($request->element('detail'),'');
	$error['INPUT']['check_duplicate_article_title'] = $validate->validInteger($request->element('check_duplicate_article_title'),1);
	$error['INPUT']['sort_key'] = $validate->pasteString($request->element('sort_key'),'date_created');
	$error['INPUT']['sort_direction'] = $validate->pasteString($request->element('sort_direction'),'ASC');
	$error['INPUT']['layout'] = $validate->pasteString($request->element('layout'),'1column_rows');
	$error['INPUT']['items_per_page'] = $validate->validInteger($request->element('items_per_page'),$amessages['items_per_page']);


	# Paste value of custom fields
	global $fieldList;
	foreach($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

	global $fieldOptionList;
	foreach ($fieldOptionList as $field) {

		$fieldName = $field->getFieldName();
		$fieldValue = $request->element($fieldName);

		if ((is_null($fieldValue) || $fieldValue === '') && $field->getRequired() == 1) {
			$error['INPUT'][$fieldName] = [
				'value' => $fieldValue,
				'error' => 1,
				'message' => $amessages["field"] . " - " . $amessages['invalid_field']
			];
			$error['invalid'] = 1;
		} else {
			$error['INPUT'][$fieldName] = [
				'value' => $fieldValue,
				'error' => 0,
				'message' => ''
			];
		}
	}

	if($error['INPUT']['name']['error'] || $error['INPUT']['keyword']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;

	return $error;
}
?>