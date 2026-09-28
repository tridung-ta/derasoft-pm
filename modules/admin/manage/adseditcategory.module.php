<?php

/*************************************************************************
Editing Ads module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 19/09/2011
Coder: Mai Minh
 **************************************************************************/
$userInfo->checkPermission('banner', 'edit');
$templateFile = 'manageads.tpl.html';
include_once(ROOT_PATH . 'classes/dao/adscategories.class.php');
include_once(ROOT_PATH . 'classes/dao/estores.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
#$adsCategories = new AdsCategories($storeId);
$adsCategories = new AdsCategories($storeId);
$estores = new EStores();
$fields = new Fields($storeId);

$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
	$amessages['manage_banner'] => '/' . ADMIN_SCRIPT . '?op=manage&act=ads',
	$amessages['update_banner_category'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=ads';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['add_new'] => $tabLink . '&mod=add',
	$amessages['list_ads_category'] => $tabLink . '&mod=listcategory',
	$amessages['edit_ads_category'] => '#',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 4);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
# Allow some javascript
$template->assign('ckEditor', 1);

$id = $request->element('id');
if ($id) $template->assign('id', $id);
$categoryInfo = $adsCategories->getObject($id);
if (!$categoryInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
			$categoryInfo = $adsCategories->getObject($id);
			if ($categoryInfo) {
				$template->assign('catInfo', $categoryInfo);
			}
		} else { # Valid data input
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {
				$categoryInfo = $adsCategories->getObject($id);
				$properties = $estore->getProperties();
				if (!$properties) $properties = array('');
				$properties['ads_category'][$id] = array('enable' => $request->element('status'), 'rows' => $request->element('ipp'));
				$properties['detail'] = $request->element('detail');
				$data = array('properties' => serialize($properties));
				# Update Estore properties
				$adsCategories->updateData($data, $id);
				$estore = $stores->getObject($storeId);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_ads_category'], $adsCategories->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=ads&mod=editcategory&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load product category information to edit
		$template->assign('item', $categoryInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH . 'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['status'] = $validate->validInteger($request->element('status'));
	if ($error['INPUT']['status']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
