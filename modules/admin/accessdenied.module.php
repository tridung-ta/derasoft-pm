<?php
/*************************************************************************
Admin index module
----------------------------------------------------------------
Bido.vn Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 04/07/2011
**************************************************************************/
$templateFile = 'coreaccessdenied.tpl.html';
$error['error']['message'][] = $amessages['access_denied'];
$template->assign('error',$error);
?>