<?php
// Synthetic Admin navigation only for standalone Smarty fixtures; never loaded by runtime.
require_once ROOT_PATH.'classes/services/pmuiservice.class.php';
class PmNavigationFixtureAccess {
 public function hasRole($role){return $role==='ADMIN';}
 public function hasPermission($permission){return true;}
}
function pmAssignNavigationFixture($smarty,string $op): void {
 $smarty->assign('pmNavigation',PmUiService::navigation(new PmNavigationFixtureAccess(),$op));
}
