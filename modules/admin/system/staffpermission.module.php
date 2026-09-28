<?php
/*************************************************************************
Editing staff permission module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 22/05/2012
**************************************************************************/
$templateFile = 'systemstaff.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_staff'] => '/'.ADMIN_SCRIPT.'?op=system&act=staff',
				$amessages['add_new'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=staff';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_item'] => '',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
if($userInfo->isSiteFounder()) $listTabs[$amessages['tracking_title']] = $tabLink.'&mod=listTracking';
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

$uId = $request->element('id');
$uInfo = $users->getObject($uId);
if(!$uInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);
	$uType = $uInfo->getType();
	if($uType == U_SITE_FOUNDER && !$userInfo->isSiteFounder()) { # Neu user duoc edit la Founder thi chi co founder moi co quyen edit
		header("location: /admin.php?op=accessdenied");
		exit;
	} else {
		if($_POST && $request->element('doo') == 'submit') { # if form is submitted
			# Validate the data input
			$validate = validateData($request);
			if($validate['invalid']) {	# data input is not in valid form
				$template->assign('error',$validate);
				$template->assign('userInfo',$uInfo);
			} else { # Valid data input	
				# Everything is ok. Update data to DB
				if(!$validate['invalid']) {
					$properties = $uInfo->getProperties();
					$permissions = array();
					$permissions['order'] = $request->element('order');
					$permissions['customer'] = $request->element('customer');
					$permissions['pro_cat'] = $request->element('pro_cat');
					$permissions['product'] = $request->element('product');
					$permissions['category'] = $request->element('category');
					$permissions['article'] = $request->element('article');
					$permissions['menu'] = $request->element('menu');
					$permissions['banner'] = $request->element('banner');
					$permissions['support'] = $request->element('support');
					$permissions['static'] = $request->element('static');
					$permissions['comment'] = $request->element('comment');
					$properties['permissions'] = $permissions;
					$data = array('properties' => serialize($properties));
					$users->updateData($data,$uId);
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_user_permission'],$request->element('username')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=staff&mod=permission&lang=$lang&id=$uId&rcode=7");
				}
			}
		} else { # Load user information to edit
			$template->assign('item',$uInfo);
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	$error['invalid'] = 0;
	return $error;
}
?>