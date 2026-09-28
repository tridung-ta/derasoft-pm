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
$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['logout_ok'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
$_SESSION['userId'] = 0;
$_SESSION['userName'] = '';
$_SESSION['userType'] = 0;
$_SESSION['storeId'] = '';
if(isset($_SESSION['adminId'])) {
	$_SESSION['userId'] = $_SESSION['adminId'];
	$_SESSION['adminId'] = '';
	header("location: ".ADMIN_SCRIPT."?op=admin");
} else {
	header("location: ".ADMIN_SCRIPT);
}
?>