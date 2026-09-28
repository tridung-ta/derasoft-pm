<?php
/*************************************************************************
Adding article module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Checked by: Mai Minh (13/06/2025)
**************************************************************************/
# Check permission
// error_reporting(E_ALL);
//     ini_set('display_errors', 1);
$userInfo->checkPermission('article', 'add');

$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
include_once(ROOT_PATH . 'classes/dao/uploadalbums.class.php');
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php'); 
include_once(ROOT_PATH . 'classes/dao/articlegroups.class.php');

$articleGroups = new ArticleGroups($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$uploads = new Uploads($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$articleCategories = new ArticleCategories($storeId);
$articles = new Articles($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);

# Allow some javascript
$template->assign('ckEditor', 1);
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";

# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_article'] => '/' . ADMIN_SCRIPT . '?op=manage&act=article',
	$amessages['add_new_article'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=article';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_article_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_article_category'] => $tabLink . '&mod=addcategory',
	$amessages['list_articlegroup'] => $tabLink.'&mod=listgroup',
	$amessages['add_articlegroup'] => $tabLink.'&mod=addgroup',	
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get article categories array for generating nested combo
$arrayCategories = $articleCategories->getObjectsForCombo();

# Category combo box
$categoryCombo = $articleCategories->generateNestedCombo($arrayCategories, $request->element('category_id'));
$template->assign('categoryCombo', $categoryCombo);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='article'", array('position' => 'ASC'),999);
if ($fieldList) $template->assign('fieldList', $fieldList);

# Get list of custom options
$fieldOptionList = $optionStructure->getObjects(1, "`status`='1' AND `module`='article'", array('position' => 'ASC'));
if ($fieldOptionList) $template->assign('fieldOptionList', $fieldOptionList);

# Get list of article groups
$articleGroupList = $articleGroups->getObjects(1, "`status`='1'", array('id' => 'DESC'),999);
if ($articleGroupList) $template->assign('articleGroupList', $articleGroupList);

if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Check if the image upload album for this year exists. If not exists, create it
	$thisYearAlbum = $uploadAlbums->getObject(date("Y"),"name");
	if(!$thisYearAlbum) { # Album for this year not exists, create it
		$newAlbumData = array('store_id' => $storeId,
							 'name' => date("Y"),
							 'status' => 1,
							 'folder' => date("Y"),
							 'date_created' => date("Y-m-d H:i:s"),
							 'properties' => serialize(array()));
		$thisYearAlbumId = $uploadAlbums->addData($newAlbumData);
		
		# Get album object that just created
		$thisYearAlbum = $uploadAlbums->getObject($thisYearAlbumId);
	} else { # Album for this year exists
		# Return error if cannot create the image upload folder
		if($thisYearAlbum->getProperty('create_album_error')) {
			$template->assign('albumCreateError',$amessages['cannot_create_folder']." ".$thisYearAlbum->getAbsoluteFolder());
		}
	}
	
	# Check if album and folder exists now. If not, stop here and ask the staff to set permission for the folder
	if(!$thisYearAlbum->getProperty('create_album_error')) {
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);

			# Category combo box
			$categoryCombo = $articleCategories->generateNestedCombo($arrayCategories, $request->element('category_id'));
			if ($categoryCombo) $template->assign('categoryCombo', $categoryCombo);
		} else { # Valid data input
			$duplicate_title = 0;
			$categoryInfo = $articleCategories->getObject($request->element('category_id'));
			if($categoryInfo){
				# Check duplicate article name
				# This setting be modified from level "estore" to "article_category"
				if ($categoryInfo->getCheckDuplicateTitle() == 1) {
					if ($articles->checkDuplicate($request->element('title'), 'title', "category_id = '" . $request->element('category_id') . "'")) {
						$validate['INPUT']['title']['message'] = $amessages['title_duplicated'];
						$validate['INPUT']['title']['error'] = 1;
						$validate['invalid'] = 1;
						$template->assign('error', $validate);
						$duplicate_title = 1;
					}
				}
			}

			# Continue if title in this category is not duplicated
			if(!$duplicate_title) {
				# Check if duplicate slug
				$textFilter = new TextFilter();
				$slug = $textFilter->urlize($request->element('title'), false, '-');
				$i = 0;
				$dup = 1;
				while ($dup) {
					$dup = $articles->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug', "category_id = '" . $request->element('category_id') . "'");
					if ($dup) $i++;
				}
				$slug .= $i ? '-' . $i : '';

				# Everything is ok. Add data to DB
				if (!$validate['invalid']) {
					$properties = array('');

					# Avatar upload
					$fileAvatar = isset($_FILES['avatar']) ? $_FILES['avatar'] : '';
					# Only upload if uploaded file is image
					if(isImage($fileAvatar['name'])) {
						$newAvatarId = '';
						if($fileAvatar) {
							$newAvatarId = $uploads->uploadFile($thisYearAlbum,
																$fileAvatar['name'],
																$fileAvatar['tmp_name'],
																$fileAvatar['size'],
																'article',
																$userInfo->getId(),
																0);
							$newAvatarObject = $uploads->getObject($newAvatarId);
							if($newAvatarObject) {
								$newAvatarUrl = '/'.$newAvatarObject->getPath().'/'.$newAvatarObject->getUrlA();
								$newAvatarLargeUrl = '/'.$newAvatarObject->getPath().'/'.$newAvatarObject->getUrlL();
							}

							# Properties
							$properties['avatarId'] = $newAvatarId;
							$properties['avatarUrl'] = $newAvatarUrl;
							$properties['avatarLargeUrl'] = $newAvatarLargeUrl;
						}
					} # End if(isImage($fileAvatar['name'])) {
					
					# Files upload
					$files = isset($_FILES['files']) ? $_FILES['files'] : '';
					$newFileIds = [];
					if (is_array($files)) {
						for ($i = 0; $i < count($files['name']); $i++) {
							$newFileId = $uploads->uploadFile($thisYearAlbum,
															  $files['name'][$i],
															  $files['tmp_name'][$i],
															  $files['size'][$i],
															  'article',
															  $userInfo->getId(),
															  0);
							if($newFileId) array_push($newFileIds, $newFileId);
						} # End for ($i =0;

						# Properties
						$properties['fileIds'] = $newFileIds;
					}
					# End Files upload
					
					# Custom fields
					foreach ($fieldList as $field) {
						$value = $request->element($field->getName());
						if (is_array($value)) {
							$value = array_map(function($item){
								return is_string($item) ? stripslashes($item) : $item;
							}, $value);
						} else {
							$value = is_string($value) ? stripslashes($value) : $value;
						}
						$properties[$field->getName()] = $value;
					}
					$properties['custom_editorial_access'] = $request->element('editorial_access') === 'premium' ? 'premium' : 'free';
					foreach (array('en', 'zh') as $translationLang) {
						foreach (array('title', 'keyword', 'description', 'detail') as $translationField) {
							$translationValue = $request->element($translationField . '_' . $translationLang);
							$properties['custom_' . $translationLang . '_' . $translationField] = is_string($translationValue)
								? stripslashes($translationValue)
								: '';
						}
					}

					$selectedLanguages = array_values(array_intersect(array('vn', 'en', 'zh'), (array)$request->element('language')));
					if (!in_array('vn', $selectedLanguages, true)) array_unshift($selectedLanguages, 'vn');

					# Prepare data to be inserted to DB
					$data = array(
						'store_id' => $storeId,
						'category_id' => (int)$request->element('category_id'),
						'slug' => $slug,
						'slug_en' => $request->element('slug_en'),
						'slug_zh' => $request->element('slug_zh'),
						'title' => $request->element('title'),
						'keyword' => $request->element('keyword'),
						'description' => $request->element('description'),
						'detail' => $request->element('detail'),
						'position' => (int)$request->element('position'),
						'status' => (int)$request->element('status'),
						'viewed' => (int)$request->element('view'),
						'poster_id' => (int)$userInfo->getId(),
						'lang' => implode(',', $selectedLanguages),
						'properties' => serialize($properties),
						'article_group_ids' => $request->element('article_group_ids') ? implode(',', $request->element('article_group_ids')) : '',
						'date_created' => date("Y-m-d H:i:s"),
						'publish_at' => !empty(trim((string)$request->element('publish_at'))) ? $request->element('publish_at') : null,
					);
					$newId = $articles->addData($data);

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
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_article'], $request->element('title')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
					header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=article&mod=list&filter_categories=" . $request->element('category_id') . "&rcode=6");
				} # End if (!$validate['invalid']) {
			} # End if(!$duplicate_title) {
		} # End if ($validate['invalid']) {
	}
} # End if ($_POST && $request->element('doo') == 'submit')

# Check validate input
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['invalid'] = 0;

	// $error['INPUT']['category_id'] = $validate->validPlusInteger($request->element('category_id'));
	$error['INPUT']['category_id'] = $validate->pasteString($request->element('category_id'));
	$error['INPUT']['title'] = $validate->validString(htmlspecialchars($request->element('title')), $amessages['title']);
	$error['INPUT']['keyword'] = $validate->validString(htmlspecialchars($request->element('keyword')), $amessages['keyword']);
	// Ngôn ngữ được chọn
	$chooseLang = $request->element('language');

	if (!is_array($chooseLang)) {
		$chooseLang = [];
	}

	// Mặc định slug_en không có lỗi
	$error['INPUT']['slug_en'] = [
		'value' => $request->element('slug_en'),
		'error' => 0,
		'message' => ''
	];

	// Mặc định slug_zh không có lỗi
	$error['INPUT']['slug_zh'] = [
		'value' => $request->element('slug_zh'),
		'error' => 0,
		'message' => ''
	];

	// Tiếng Anh
	if (in_array('en', $chooseLang)) {
		$error['INPUT']['slug_en'] = $validate->validSlug(
			htmlspecialchars($request->element('slug_en')),
			$amessages['slug_en']
		);
	}

	// Tiếng Hoa
	if (in_array('zh', $chooseLang)) {
		$error['INPUT']['slug_zh'] = $validate->validSlug(
			htmlspecialchars($request->element('slug_zh')),
			$amessages['slug_zh']
		);
	}
	$error['INPUT']['description'] = $validate->validString($request->element('description'), $amessages['description']);
	$error['INPUT']['detail'] = $validate->validString($request->element('detail'), $amessages['detail']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['view'] = $validate->pasteString($request->element('view'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['language'] = array('value' => $chooseLang, 'error' => 0, 'message' => '');
	foreach (array('en' => 'English', 'zh' => '中文') as $translationLang => $translationLabel) {
		$error['INPUT']['title_' . $translationLang] = $validate->pasteString($request->element('title_' . $translationLang));
		$error['INPUT']['keyword_' . $translationLang] = $validate->pasteString($request->element('keyword_' . $translationLang));
		$error['INPUT']['description_' . $translationLang] = $validate->pasteString($request->element('description_' . $translationLang));
		$error['INPUT']['detail_' . $translationLang] = $validate->pasteString($request->element('detail_' . $translationLang));
		if (in_array($translationLang, $chooseLang, true)) {
			foreach (array('title', 'description', 'detail') as $requiredTranslationField) {
				$fieldKey = $requiredTranslationField . '_' . $translationLang;
				if (trim(strip_tags((string)$request->element($fieldKey))) === '') {
					$error['INPUT'][$fieldKey]['error'] = 1;
					$error['INPUT'][$fieldKey]['message'] = $translationLabel . ' - ' . $amessages['invalid_field'];
					$error['invalid'] = 1;
				}
			}
		}
	}

	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
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

	if ($error['INPUT']['category_id']['error'] || $error['INPUT']['title']['error'] || $error['INPUT']['keyword']['error'] || $error['INPUT']['slug_en']['error'] || $error['INPUT']['slug_zh']['error'] || $error['INPUT']['description']['error'] || $error['INPUT']['detail']['error'] || $error['INPUT']['status']['error']) {
		$error['invalid'] = 1;
	}

	return $error;
}
