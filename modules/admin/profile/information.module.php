<?php
/*************************************************************************
Editing staff module
----------------------------------------------------------------
BiDo Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 29/12/2011
Edit log:
- 29/12/2011 - Mai Minh: Initialize
**************************************************************************/

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

// Tạo một đối tượng GoogleAuthenticator
$googleAuthenticator = new GoogleAuthenticator();

$templateFile = 'profileinformation.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['profile'] => '/'.ADMIN_SCRIPT.'?op=profile',
				$amessages['information'] => '');

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

#$uId = $request->element('id');
#$uInfo = $users->getObject($uId);
$uId = $userInfo->getId();
$uInfo = $userInfo;
if(!$uInfo) {
	$template->assign('validItem',0);
} else {
	$template->assign('validItem',1);
	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
		} else { # Valid data input
			# check duplicate email
			if($users->checkDuplicate($request->element('email'),'email',"`id` <>'$uId'")) {
				$validate['INPUT']['email']['message'] = $amessages['email_duplicated'];
				$validate['INPUT']['email']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
			
			$code = $request->element('code'); 
			$qrcode = $request->element('qrcode');
			if($request->element('myCheckbox')=="on"){
				if($request->element('getActive2fa')!="1"){//Đã kích hoạt thì không vào đây nữa
					if ($googleAuthenticator->checkCode($qrcode, $code)) {
					} else {
						$validate['INPUT']['code']['message'] = $amessages['active_2fa_error'];
						$validate['INPUT']['code']['error'] = 1;
						$validate['invalid'] = 1;
						$template->assign('error',$validate);
					}
				}
			}else{
				if($request->element('getActive2fa')=="1"){//Đã kích hoạt thì mới có thể vào đây để hủy
					if ($googleAuthenticator->checkCode($qrcode, $code)) {
					} else {
						$validate['INPUT']['code']['message'] = $amessages['disable_2fa_error'];
						$validate['INPUT']['code']['error'] = 1;
						$validate['invalid'] = 1;
						$template->assign('error',$validate);
					}
				}
			}
			

			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$data = array('store_id' => $storeId,
							  'email' => Filter($request->element('email')),
							  'fullname' => Filter($request->element('fullname')),
							  'address' => Filter($request->element('address')),
							  'tel' => Filter($request->element('telephone')),
							  'active_2fa' => Filter($request->element('myCheckbox'))=="on"?1:2,
							  'code' => Filter($code),
							  'qrcode' => Filter($qrcode));
				$users->updateData($data,$uId);
				$success = $amessages['update_profile_ok'];
				$template->assign('success',$success);
				
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_user'],$request->element('username')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				
				if($request->element('myCheckbox')=="on" && $request->element('getActive2fa')!="1"){
					//Thoát account ra cần đăng nhập lại
					$_SESSION['active_2fa'] = 1;
					$_SESSION['userId'] = 0;
					$_SESSION['userName'] = '';
					$_SESSION['userType'] = 0;
					$_SESSION['storeId'] = '';
					header("location: ".ADMIN_SCRIPT);
				}else{
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=profile&act=information&mod=&lang=$lang&id=$uId&rcode=7");
				}
			}
		}
	} else { # Load user information to edit
		$template->assign('item',$uInfo);
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['id'] = $validate->pasteString($request->element('id'));
	$error['INPUT']['username'] = $validate->pasteString($request->element('username'));
	$error['INPUT']['fullname'] = $validate->validString($request->element('fullname'),$amessages['fullname']);
	$error['INPUT']['email'] = $validate->validEmail($request->element('email'));
	$error['INPUT']['address'] = $validate->pasteString($request->element('address'));
	$error['INPUT']['telephone'] = $validate->pasteString($request->element('telephone'));
	$error['INPUT']['user_group'] = $validate->pasteString($request->element('user_group'));

	$error['INPUT']['code'] 	 = $validate->pasteString($request->element('code'));
	$error['INPUT']['qrcode'] 	 = $validate->pasteString($request->element('qrcode'));
	$error['INPUT']['qrCodeUrl'] = $validate->pasteString($request->element('qrCodeUrl'));
	$error['INPUT']['myCheckbox'] = $validate->pasteString($request->element('myCheckbox'));


	
	if($error['INPUT']['username']['error'] || $error['INPUT']['fullname']['error'] || $error['INPUT']['email']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>