<?php
/*************************************************************************
Adding product category module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 29/08/2011
**************************************************************************/
$userInfo->checkPermission('banner','add');
$templateFile = 'manageadsaddcategory.tpl.html';
include_once(ROOT_PATH.'classes/dao/adscategories.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$groupads = new GroupAds($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_banner'] => '/'.ADMIN_SCRIPT.'?op=manage&act=ads',
				$amessages['add_banner_category'] => '');
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
# Category combo box
/*$categoryCombo = $groupads->generateCombo($request->element('pId'));
if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);
*/
if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		# check duplicate category name
		if($groupads->checkDuplicate($request->element('name'))) {
			$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
			$validate['INPUT']['name']['error'] = 1;
			$validate['invalid'] = 1;
			$template->assign('error',$validate);
		}
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$properties = array('');
			/*$properties['sort_type'] = $request->element('sort_type');
			$properties['sort_dir'] = $request->element('sort_dir');*/
			$properties['display'] = $request->element('display');
			$properties['ipp'] = $request->element('ipp');
			$data = array('store_id' => $storeId,
						  'name' => Filter($request->element('name')),
						  'status' => $request->element('status'),
						  'properties' => serialize($properties));
						  
			$groupads->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_ads_category'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=ads&mod=listcategory&pId=".$request->element('parent_id')."&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	/*$error['INPUT']['parent_id'] = $validate->pasteString($request->element('parent_id'));*/
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);

	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	/*$error['INPUT']['sort_type'] = $validate->pasteString($request->element('sort_type'));
	$error['INPUT']['sort_dir'] = $validate->pasteString($request->element('sort_dir'));*/
	$error['INPUT']['display'] = $validate->pasteString($request->element('display'));
	$error['INPUT']['ipp'] = $validate->validInteger($request->element('ipp'),$amessages['items_per_page']);
	/*$error['INPUT']['landing'] = $validate->pasteString($request->element('landing'));
	if($error['INPUT']['landing']['value']) $error['INPUT']['landing_page'] = $validate->validString($request->element('landing_page'),$amessages['landing_page']);*/
	
	if($error['INPUT']['name']['error'] || $error['INPUT']['ipp']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>