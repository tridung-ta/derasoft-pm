<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$s->assign([
    'pageTitle'=>'Nhân sự',
    'users'=>[['id'=>1,'username'=>'demo','fullname'=>'Demo User','email'=>'demo@example.test','tel'=>'','department_id'=>1,'department_name'=>'Engineering','weekly_limit_hours'=>'40.00','status'=>1]],
    'userRoles'=>[1=>[['role_id'=>1,'is_primary'=>1]]],
    'userRoleState'=>[1=>[1=>true]],
    'userPrimaryRoles'=>[1=>1],
    'departments'=>[['id'=>1,'code'=>'ENG','name'=>'Engineering']],
    'departmentRows'=>[['id'=>1,'code'=>'ENG','name'=>'Engineering','status'=>1]],
    'roles'=>[['id'=>1,'name'=>'Employee']],
    'csrfToken'=>'test','notice'=>'','error'=>'','q'=>'','departmentFilter'=>1,'roleFilter'=>1,'canManageUsers'=>true,'canManageRoles'=>true,'canManageRates'=>true,'canViewRates'=>true,
    'hourlyRates'=>[['id'=>1,'user_id'=>null,'role_id'=>1,'user_name'=>null,'role_name'=>'Employee','rate'=>'100000.00','currency'=>'VND','effective_from'=>'2026-01-01','effective_to'=>null,'status'=>1]],'page'=>1,'totalPages'=>1
]);
$html=$s->fetch('admin/pm-users-v2.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-users-1.html',str_replace('<head>','<head><base href="/">',$html));foreach(['Quản lý nhân sự','action" value="department_save','action" value="department_delete','action" value="roles','action" value="rate','action" value="rate_save','status" value="2','checked','selected'] as $needle){if(strpos($html,$needle)===false){fwrite(STDERR,'FAIL: '.$needle.PHP_EOL);exit(1);}}
$s->assign(['canManageUsers'=>false,'canManageRoles'=>false,'canManageRates'=>false,'canViewRates'=>false]);
$readOnlyHtml=$s->fetch('admin/pm-users-v2.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-users-readonly.html',str_replace('<head>','<head><base href="/">',$readOnlyHtml));foreach(['name="action" value="department_save"','name="action" value="department_delete"','name="action" value="roles"','name="action" value="rate"','name="action" value="status"','name="action" value="rate_save"'] as $forbidden){if(strpos($readOnlyHtml,$forbidden)!==false){fwrite(STDERR,'FAIL: read-only user saw '.$forbidden.PHP_EOL);exit(1);}}
echo "PASS: PM user management template rendered.\n";
