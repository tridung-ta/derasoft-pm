<?php
/*************************************************************************
Admin manage module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
**************************************************************************/
// if ($_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
//         ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);
//     }

checkPermission(array(1,2,3));
if(!$act) $act = 'index';
if(!$mod) $mod = 'list';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '');
if(file_exists(ROOT_PATH.'modules/admin/manage/'.strtolower($act).strtolower($mod).'.module.php')) {
	include_once(ROOT_PATH.'modules/admin/manage/'.strtolower($act).strtolower($mod).'.module.php');
}
?>