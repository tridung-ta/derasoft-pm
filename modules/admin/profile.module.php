<?php
/*************************************************************************
Admin manage module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
**************************************************************************/
checkPermission(array(1,2,3));
if(!$act) $act = 'information';
if(!$mod) $mod = '';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['profile'] => '');
include_once(ROOT_PATH.'modules/admin/profile/'.strtolower($act).strtolower($mod).'.module.php');
?>