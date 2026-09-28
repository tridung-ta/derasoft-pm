<?php
// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);
    // }

$userInfo->checkPermission('article', 'addgroup');

$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/articlegroups.class.php");

$articleGroups = new ArticleGroups($storeId);
$fields = new Fields($storeId);
# Top navigation
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_article'] => '/' . ADMIN_SCRIPT . '?op=manage&act=article',
	$amessages['add_articlegroup'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=article';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_article_category'] => $tabLink . '&mod=listcategory',
	$amessages['add_article_category'] => $tabLink . '&mod=addcategory',
    $amessages['list_articlegroup'] => $tabLink . '&mod=listgroup',
    $amessages['add_articlegroup'] => $tabLink . '&mod=addgroup',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 6);

# Result code
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='articlegroups'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Allow some javascript
$template->assign('ckEditor', 1);

# Submitted form
if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if ($validate['invalid']) {	# data input is not in valid form
		$template->assign('error', $validate);
	} else { # Valid data input
        if ($articleGroups->checkDuplicate($request->element('name'), 'name' )) {
            $validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
            $validate['INPUT']['name']['error'] = 1;
            $validate['invalid'] = 1;
            $template->assign('error', $validate);
        }
		# Check if duplicate slug
		$textFilter = new TextFilter();
		$slug = $textFilter->urlize($request->element('name'), false, '-');
		$i = 0;
		$dup = 1;
		while ($dup) {
			$dup = $articleGroups->checkDuplicate($slug . ($i ? '-' . $i : ''), 'slug');
			if ($dup) $i++;
		}
		$slug .= $i ? '-' . $i : '';
		# Everything is ok. Add data to DB
		if (!$validate['invalid']) {
			$properties = array('');
			
			# User upload
			$userUpload = $userInfo->getId();

			# Custom fields
			foreach ($fieldList as $field) {
				$properties[$field->getName()] = stripslashes($request->element($field->getName()));
			}
			$data = array(
				'store_id' => $storeId,
                'name' => $request->element('name'),
				'name_en' => $request->element('name_en'),
				'name_zh' => $request->element('name_zh'),
                'slug' => $slug,
                'status' => (int)$request->element('status'),
                'date_created' => date("Y-m-d H:i:s"),
				'properties' => serialize($properties)
			);
			$newId = $articleGroups->addData($data);	
			# Operation tracking
			$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_articlegroup'], $request->element('name')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));
			
			header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=article&mod=listgroup&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['invalid'] = 0;

	$error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
	$error['INPUT']['name_en'] = $validate->validString($request->element('name_en'), $amessages['name_en']);
	$error['INPUT']['name_zh'] = $validate->validString($request->element('name_zh'), $amessages['name_zh']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));

	# Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

	if ($error['INPUT']['name']['error'] || $error['INPUT']['name_en']['error'] || $error['INPUT']['name_zh']['error'] || $error['INPUT']['status']['error'] ) {
		$error['invalid'] = 1;
		$error['message'] = '';
	}
	return $error;
}
