<?php
/*************************************************************************
Editing Aticle Categorey module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Checked by: Mai Minh (14/06/2025)
**************************************************************************/
# Check permission
$userInfo->checkPermission('category','edit');

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
if ($fieldValue) $template->assign('fieldValue', $fieldValue);

# Top navigation
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_article'] => '/'.ADMIN_SCRIPT.'?op=manage&act=article',
				$amessages['edit_article_category'] => '');

# Tabs
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=article';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['list_article_category'] => $tabLink.'&mod=listcategory',
				$amessages['edit_article_category'] => '#',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',4);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='articlecategory'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

$id = $request->element('id');
if($id) $template->assign('id',$id);
$categoryInfo = $articleCategories->getObject($id);
if(!$categoryInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);

	# Allow some javascript
	$template->assign('ckEditor',1);

	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);

		# Get list of custom options
		$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='articlecategory'", array('position' => 'ASC'));
		if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
	
			# Get article categories array for generating nested combo
			$arrayCategories = $articleCategories->getObjectsForCombo();
			
			# Category combo box
			$categoryCombo = $articleCategories->generateNestedCombo($arrayCategories,$request->element('parent_id'));
			if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

			$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='articlecategories'",array('position' => 'ASC'));
			if($fieldList) $template->assign('fieldList',$fieldList);

		} else { # Valid data input
			# Category combo box
			$categoryCombo = $articleCategories->generateCombo($request->element('parent_id',0));
			if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);
			
			$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='articlecategories'",array('position' => 'ASC'));
			if($fieldList) $template->assign('fieldList',$fieldList);
			
			# check duplicate category name
			if($articleCategories->checkDuplicate($request->element('name'),'name',"`id` <> '$id'")) {
				$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
				$validate['INPUT']['name']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			
			# Check if duplicate slug
			$textFilter = new TextFilter();
			$slug = $textFilter->urlize($request->element('slug'),false,'-');
			if($articleCategories->checkDuplicate($slug,'slug',"`id` <> '$id'")) {
				$validate['INPUT']['slug']['message'] = $amessages['slug_duplicated'];
				$validate['INPUT']['slug']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
					
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$properties = array('');

				# Custom fields
				foreach($fieldList as $field) {
					$properties[$field->getName()] = $request->element($field->getName());
				}
				
				$data = array('store_id' => $storeId,
							  'parent_id' => (int)$request->element('parent_id'),
							  'slug' => $request->element('slug'),
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
				$result = $articleCategories->updateData($data,$id);

				# Custom Options
				if ($result) {
					foreach ($fieldOptionList as $field) {
						$fieldId = $field->getId();
						$valueType = $request->element($field->getFieldName());
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

						$result = $fieldValue->updateOrInsertFieldValue($valueType, $fieldId, $id, $storeId);
					}
				} 
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_article_category'],$articleCategories->getNameFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				# Redirect to editing page
				header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=article&mod=editcategory&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load article category information to edit
		$template->assign('item',$categoryInfo);
		
		# Get article categories array for generating nested combo
		$arrayCategories = $articleCategories->getObjectsForCombo();
		
		# Category combo box
		$categoryCombo = $articleCategories->generateNestedCombo($arrayCategories, $categoryInfo->getParentId());
		if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

		$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='articlecategories'",array('position' => 'ASC'));
		if($fieldList) $template->assign('fieldList',$fieldList);

	}
}

# Check validate input
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['parent_id'] = $validate->validInteger($request->element('parent_id'));
	$error['INPUT']['slug'] = $validate->validString($request->element('slug'),$amessages['slug']);
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['keyword'] = $validate->validString($request->element('keyword'),$amessages['keyword']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'),$amessages['description']);
	$error['INPUT']['position'] = $validate->validInteger($request->element('position'));
	$error['INPUT']['status'] = $validate->validInteger($request->element('status'));
	$error['INPUT']['check_duplicate_article_title'] = $validate->validInteger($request->element('check_duplicate_article_title'),1);
	$error['INPUT']['sort_key'] = $validate->pasteString($request->element('sort_key'),'date_created');
	$error['INPUT']['sort_direction'] = $validate->pasteString($request->element('sort_direction'),'ASC');
	$error['INPUT']['layout'] = $validate->pasteString($request->element('layout'),'1column_rows');
	$error['INPUT']['items_per_page'] = $validate->validInteger($request->element('items_per_page'),$amessages['items_per_page']);
	$error['INPUT']['landing'] = $validate->pasteString($request->element('landing'));
	$error['INPUT']['detail'] = $validate->pasteString($request->element('detail'));
	
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

	if($error['INPUT']['name']['error'] || $error['INPUT']['keyword']['error'] || $error['INPUT']['description']['error']  || $error['INPUT']['slug']['error'] || $error['INPUT']['check_duplicate_article_title']['error'] || $error['INPUT']['sort_key']['error'] || $error['INPUT']['sort_direction']['error'] || $error['INPUT']['layout']['error'] || $error['INPUT']['items_per_page']['error'] || $error['INPUT']['landing']['error'] || $error['INPUT']['detail']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;

	return $error;
}
?>