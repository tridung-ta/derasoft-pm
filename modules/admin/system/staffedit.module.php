<?php

/*************************************************************************
Editing staff module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 29/09/2011
Edit log:
- 29/09/2011 - Mai Minh: Check ID, add filter to form's fields
 **************************************************************************/
$templateFile = 'systemstaff.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/imgs.class.php');
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");

$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);
$imgs = new Imgs();
$template->assign('imgs', $imgs);

$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";
$fields = new Fields($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['manage_staff'] => '/' . ADMIN_SCRIPT . '?op=system&act=staff',
	$amessages['add_new'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=staff';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['edit_item'] => '',
	$amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
if ($userInfo->isSiteFounder()) $listTabs[$amessages['tracking_title']] = $tabLink . '&mod=listTracking';
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

$uId = $request->element('id');
$uInfo = $users->getObject($uId);

# Get avatar image
$avatarImg = null;
if ($uInfo->getAvatar()) {
    $avatarImg = $uploads->getObject($uInfo->getAvatar());
}
$template->assign('avatarImg', $avatarImg);

# ================= DELETE AVATAR =================
if ($request->element('doo') == 'delAvatar') {
    $avatarId = $uInfo->getAvatar();

    if ($avatarId) {
        $upload = $uploads->getObject($avatarId);
        if ($upload) {
            $upload->deleteFiles();
            $uploads->DeteImg($avatarId);
            }
            
            $users->updateData([
                'avatar' => 0
                ], $uId);
        $uInfo = $users->getObject($uId);
    }
}

if (!$uInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	$uType = $uInfo->getType();
	if ($uType == U_SITE_FOUNDER && !$userInfo->isSiteFounder()) { # Neu user duoc edit la Founder thi chi co founder moi co quyen edit
		header("location: /admin.php?op=accessdenied");
		exit;
	} else {
		$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='staff'", array('position' => 'ASC'));
		if ($fieldList) $template->assign('fieldList', $fieldList);
		if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
			# Get list of custom fields
			$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='staff'", array('position' => 'ASC'));
			if ($fieldList) $template->assign('fieldList', $fieldList);
			# Validate the data input
			$validate = validateData($request);
			if ($validate['invalid']) {	# data input is not in valid form
				$template->assign('error', $validate);
			} else { # Valid data input
				# Check new password and confirm password
				if ($request->element('password')) {
					$new_password = md5($request->element('password'));
					$confirm_password = md5($request->element('confirm_password'));
					if ($new_password != $confirm_password) { # New password is same as confirm password
						$validate['INPUT']['confirm_password']['message'] = $amessages['invalid_confirm_password'];
						$validate['INPUT']['confirm_password']['error'] = 1;
						$validate['invalid'] = 1;
						$template->assign('error', $validate);
					}
				}
				# check duplicate email
				if ($users->checkDuplicate($request->element('email'), 'email', "`id` <>'$uId'")) {
					$validate['INPUT']['email']['message'] = $amessages['email_duplicated'];
					$validate['INPUT']['email']['error'] = 1;
					$validate['invalid'] = 1;
					$template->assign('error', $validate);
				}
				# Upload album
				$thisYearAlbum = getOrCreateYearUploadAlbum($storeId, $uploadAlbums);

				# Avatar upload
				$avatarUploadId = uploadAvatar(
					$thisYearAlbum,
					$uploads,
					$userInfo,
					'logo',
					'user'
				);
				# Everything is ok. Update data to DB
				if (!$validate['invalid']) {
					# Custom fields
					foreach ($fieldList as $field) {
						$properties[$field->getName()] = stripslashes($request->element($field->getName()));
					}
					
					$data = array(
						'store_id' => Filter($storeId),
						'email' => Filter($request->element('email')),
						'fullname' => Filter($request->element('fullname')),
						'address' => Filter($request->element('address')),
						'tel' => Filter($request->element('telephone')),
						'avatar' => $avatarUploadId ? (int)$avatarUploadId : (int)$uInfo->getAvatar(),
						'type' => Filter($request->element('user_group')),
						'properties' => serialize($properties),
						'status' => S_ENABLED
					);
					if ($request->element('password')) $data['password'] = md5($request->element('password'));
					if ($request->element('user_group') == U_SITE_FOUNDER && !$userInfo->isSiteFounder()) {
						header("location: /admin.php?op=accessdenied");
						exit;
					}
					$users->updateData($data, $uId);

					# Operation tracking
					$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_user'], $request->element('username')), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

					# Redirect to editing page
					header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=staff&mod=edit&lang=$lang&id=$uId&rcode=7");
				}
			}
		} else { # Load user information to edit
			$template->assign('item', $uInfo);
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
	$error['INPUT']['id'] = $validate->pasteString($request->element('id'));
	$error['INPUT']['username'] = $validate->pasteString($request->element('username'));
	if ($request->element('password')) $error['INPUT']['password'] = $validate->validPassword($request->element('password'));
	else $error['INPUT']['password'] = $validate->pasteString($request->element('password'));
	if ($request->element('confirm_password')) $error['INPUT']['confirm_password'] = $validate->validPassword($request->element('confirm_password'), $amessages['confirm_password']);
	else $error['INPUT']['confirm_password'] = $validate->pasteString($request->element('confirm_password'));
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'), $amessages['fullname']);
	$error['INPUT']['email'] = $validate->validEmail($request->element('email'));
	$error['INPUT']['address'] = $validate->pasteString($request->element('address'));
	$error['INPUT']['telephone'] = $validate->pasteString($request->element('telephone'));
	$error['INPUT']['user_group'] = $validate->pasteString($request->element('user_group'));

	if ($error['INPUT']['username']['error'] || $error['INPUT']['password']['error'] || $error['INPUT']['confirm_password']['error'] || $error['INPUT']['fullname']['error'] || $error['INPUT']['email']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
