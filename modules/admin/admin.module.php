<?php
/*************************************************************************
Admin module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 10/08/2008
**************************************************************************/
$act = $request->element("act");
if(!$act) $act = "index";
$error = '';
$errorClass = '';

if ($act) include_once(ROOT_PATH."modules/admin/admin".strtolower($act).".module.php");
$template->assign("error",$error);
$template->assign("infoClass",$errorClass);
?>
