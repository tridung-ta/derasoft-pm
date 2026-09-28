<?php
/*************************************************************************
System config module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 22/05/2012
Coder: Mai Minh
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
checkPermission(array(2,3));
$templateFile = 'systemconfig.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['system_config'] => '');

if(!$mod) $mod = 'general';
if($mod) include_once(ROOT_PATH.'modules/admin/system/'.strtolower($act).strtolower($mod).'.module.php');
?>
