<?php
/*************************************************************************
Admin Sessions manager
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
**************************************************************************/
error_reporting(9);
if (!defined( 'ROOT_PATH' )) {
	define('ROOT_PATH', dirname(__FILE__).'/');
}
#session_start();
$pmSessionMode=defined('PM_HIDE_LEGACY') && PM_HIDE_LEGACY;

# PHP>=7. Secure is enabled automatically when the request uses HTTPS.
$pmSessionSecure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
	|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
session_start([
	'cookie_lifetime' => 43200,
	'cookie_secure' => $pmSessionSecure,
	'cookie_httponly' => true,
	'cookie_samesite' => 'Lax',
	'use_strict_mode' => true,
]);

# File manager
$_SESSION['KCFINDER'] = array();
$_SESSION['KCFINDER']['disabled'] = true;
$_SESSION['KCFINDER']['uploadURL'] = "/";
$_SESSION['KCFINDER']['uploadDir'] = "";
# File manager

if($op != 'invalidurl') {
	#Get Store Info
	$storeId = 0;
	if($sCode) {
		$stores = new EStores();
		$storeId = $stores->getStoreId("`subdomain`='$sCode' OR `domain`='$sCode'");
		if(!$storeId) die('Invalid store ID.');
		$estore = $stores->getObject($storeId);
		$template->assign('sCode',$sCode);
		if($estore) $template->assign('estore',$estore);
	}
	
	if(isset($_SESSION['userId']) && $_SESSION['userId']) {
		$userId = $pmSessionMode ? (int)$_SESSION['userId'] : $_SESSION['userId'];
		if($pmSessionMode) {
			include_once(ROOT_PATH.'classes/security/pmsessionguard.class.php');
			$sessionStore=isset($_SESSION['storeId'])?(int)$_SESSION['storeId']:null;
			if(!PmSessionGuard::valid($db,(int)$storeId,$userId,$sessionStore)) {
				$_SESSION=[];$_SESSION['KCFINDER']=['disabled'=>true];session_regenerate_id(true);
				http_response_code(401);$userId=0;$op='login';$pmSessionInvalidated=true;
			}
		}
		$users = new Users($storeId);
		$trackings = new Trackings($storeId);
		$userInfo = $users->getObject($userId,'id');
		if($userInfo) {
			$_SESSION['username'] = $userInfo->getUSername();
			$template->assign('authUser',$userInfo);
			$_SESSION['storeId'] = $storeId;
			# File manager
			if(!$pmSessionMode){$_SESSION['KCFINDER']['disabled'] = false;$_SESSION['KCFINDER']['uploadURL'] = "/upload";}
		} else {
			$_SESSION['userId'] = 0;
			$op = 'login';
		}
		
	} else {
		$_SESSION['userId'] = 0;
		$op = 'login';
		
	}
}
?>
