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
if (isset($trackings, $userInfo) && $userInfo) {
	$trackings->addData(array(
		'store_id' => $storeId,
		'username' => $userInfo->getUsername(),
		'action' => $amessages['tracking']['logout_ok'],
		'date_created' => date('Y-m-d H:i:s'),
		'ip' => $_SERVER['REMOTE_ADDR'],
	));
}

# Preserve the legacy return-to-admin flow when impersonating another user.
if (!empty($_SESSION['adminId'])) {
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
