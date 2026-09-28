<?php
/*************************************************************************
Admin change password module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2011
**************************************************************************/
checkPermission(array(1,2,3));
$templateFile = 'profilepassword.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['profile'] => '/'.ADMIN_SCRIPT.'?op=profile',
				$amessages['change_password'] => '');

if($_POST) { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);
	} else { # Valid data input
		$current_password = $request->element('current_password');
		$userId = $users->authenticateUser($userInfo->getUsername(),$current_password);
		if($userId) { # Check if the current password is same as in DB
			$new_password = md5($request->element('new_password'));
			$confirm_password = md5($request->element('confirm_password'));
			if($new_password == $confirm_password) { # New password is same as confirm password
				if($new_password != md5($current_password)) { # Every thing is ok, update the DB
					$return = $users->updateData(array('password' => $new_password), $userId, 'id');
					if($return) { # update DB successfully
						$success = $amessages['change_password_ok'];
						$template->assign('success',$success);
						$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_password'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					} else { # error when update DB
						$validate['message'] = $amessages['update_db_error'];
						$template->assign('error',$validate);
					}
				} else { # new password is same as current password
					$validate['message'] = $amessages['new_password_same_as_current'];
					$validate['INPUT']['new_password']['error'] = 1;
					$template->assign('error',$validate);
				}
			} else { # New password and confirm password is different
				$validate['message'] = $amessages['invalid_confirm_password'];
				$validate['INPUT']['confirm_password']['error'] = 1;
				$template->assign('error',$validate);
			}
		} else { # Current password incorrect
			$validate['message'] = $amessages['invalid_current_password'];
			$validate['INPUT']['current_password']['error'] = 1;
			$template->assign('error',$validate);
		}
	}
}

function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['current_password'] = $validate->validPassword($request->element('current_password'),$amessages['current_password']);
	$error['INPUT']['new_password'] = $validate->validPassword($request->element('new_password'),$amessages['new_password']);
	$error['INPUT']['confirm_password'] = $validate->validPassword($request->element('confirm_password'),$amessages['confirm_password']);
	
	if($error['INPUT']['current_password']['error'] || $error['INPUT']['new_password']['error'] || $error['INPUT']['confirm_password']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>