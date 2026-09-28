<?php
/*************************************************************************
Report module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
**************************************************************************/
checkPermission(array(2,3));
$templateFile = 'support.tpl.html';
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['support'] => ''
				);
?>