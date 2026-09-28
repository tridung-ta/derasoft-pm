<?php
/*************************************************************************
Admin dashboard module
----------------------------------------------------------------
Derasoft BiDo Project
Coder: Mai Minh
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 20/11/2011
**************************************************************************/
checkPermission(array(1,2,3));
if(!$act) $act = 'index';
if(!$mod) $mod = 'list';
$file = '';
if($act) $file .= $act;
if($mod) $file .= $mod;
include_once(ROOT_PATH.'modules/admin/dashboard/'.strtolower($file).'.module.php');
?>