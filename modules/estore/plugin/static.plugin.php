<?php
#Contact
include_once(ROOT_PATH.'classes/dao/static.class.php');
$statics = new StaticPage($sId);
$aboutUs = $statics->getObjectFromSlug('about-us'); 
if($aboutUs) $template->assign('aboutUs',$aboutUs);
$services = $statics->getObjectFromSlug('services-facilities');
if($services) $template->assign('services',$services);
?>