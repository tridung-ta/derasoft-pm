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

# PHP>=7
session_start(['cookie_lifetime' => 43200,'cookie_secure' => false,'cookie_httponly' => true]);

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
		$userId = $_SESSION['userId'];
		$users = new Users($storeId);
		$trackings = new Trackings($storeId);
		$userInfo = $users->getObject($userId,'id');
		if($userInfo) {
			$_SESSION['username'] = $userInfo->getUSername();
			$template->assign('authUser',$userInfo);
			$_SESSION['storeId'] = $storeId;
			# File manager
			$_SESSION['KCFINDER']['disabled'] = false;
			$_SESSION['KCFINDER']['uploadURL'] = "/upload";
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
