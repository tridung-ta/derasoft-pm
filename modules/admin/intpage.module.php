<?php
/*************************************************************************
Internal pages module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com
Coder: Mai Minh                                    
Last updated: 13/05/2012
**************************************************************************/
if(!$act) $act = 'static';
if(!$mod) $mod = '';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '');
include_once(ROOT_PATH.'modules/admin/intpage/'.strtolower($act).strtolower($mod).'.module.php');
?>