<?php
/*************************************************************************
Admin index module
----------------------------------------------------------------
Bido.vn Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 04/07/2011
**************************************************************************/
$templateFile = 'invalidurl.tpl.html';
$error['error']['message'][] = $amessages['notes_invalid_url'];
$template->assign('error',$error);
?>