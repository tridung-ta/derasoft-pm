<?php
/*************************************************************************
System master area module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                                                    
Last updated: 16/07/2008
Coder: Mai Minh
LAst updated: 06/06/2025
**************************************************************************/
$templateFile = 'systemmasterward.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
				$amessages['manage_area'] => '');

if(!$mod) $mod = 'list';
if($mod) include_once(ROOT_PATH.'modules/admin/system/'.strtolower($act).strtolower($mod).'.module.php');
?>