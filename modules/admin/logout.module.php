<?php
/*************************************************************************
Admin Logout module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 02/07/2008
**************************************************************************/
# Operation tracking
if(defined('PM_HIDE_LEGACY') && PM_HIDE_LEGACY){
	if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);header('Allow: POST');exit('Đăng xuất cần POST.');}
	$token=$request->element('csrf_token');if(!is_string($token)||empty($_SESSION['pm_csrf_token'])||!hash_equals($_SESSION['pm_csrf_token'],$token)){http_response_code(400);exit('Phiên làm việc đã hết hạn.');}
}
if (isset($trackings, $userInfo) && $userInfo) {
	$trackings->addData(array(
		'store_id' => $storeId,
		'username' => defined('PM_HIDE_LEGACY') && PM_HIDE_LEGACY ? (string)(int)$userInfo->getId() : $userInfo->getUsername(),
		'action' => $amessages['tracking']['logout_ok'],
		'date_created' => date('Y-m-d H:i:s'),
		'ip' => defined('PM_HIDE_LEGACY') && PM_HIDE_LEGACY ? '' : $_SERVER['REMOTE_ADDR'],
	));
}

# Preserve the legacy return-to-admin flow when impersonating another user.
if (!(defined('PM_HIDE_LEGACY') && PM_HIDE_LEGACY) && !empty($_SESSION['adminId'])) {
	$adminId = (int) $_SESSION['adminId'];
	$_SESSION = array('userId' => $adminId);
	header('Location: '.ADMIN_SCRIPT.'?op=admin');
	exit;
}

# End the authenticated session completely.
$_SESSION = array();
if (ini_get('session.use_cookies')) {
	$cookie = session_get_cookie_params();
	setcookie(
		session_name(),
		'',
		time() - 42000,
		$cookie['path'],
		$cookie['domain'],
		$cookie['secure'],
		$cookie['httponly']
	);
}
session_destroy();

header('Location: '.ADMIN_SCRIPT);
exit;
?>
