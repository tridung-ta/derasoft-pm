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
    'hiddenDepartments'=>[['id'=>2,'code'=>'OLD','name'=>'<script>hidden</script>']],
    'roles'=>[['id'=>1,'name'=>'Employee']],
    'csrfToken'=>'test','notice'=>'','error'=>'','q'=>'','departmentFilter'=>1,'roleFilter'=>1,'canManageUsers'=>true,'canManageRoles'=>true,'canManageRates'=>true,'canViewRates'=>true,
    'hourlyRates'=>[['id'=>1,'user_id'=>null,'role_id'=>1,'user_name'=>null,'role_name'=>'Employee','rate'=>'100000.00','currency'=>'VND','effective_from'=>'2026-01-01','effective_to'=>null,'status'=>1]],'page'=>1,'totalPages'=>1
]);
$html=$s->fetch('admin/pm-users-v2.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-users-1.html',str_replace('<head>','<head><base href="/">',$html));foreach(['Quản lý nhân sự','action" value="department_save','action" value="department_delete','action" value="roles','action" value="rate','action" value="rate_save','status" value="2','checked','selected'] as $needle){if(strpos($html,$needle)===false){fwrite(STDERR,'FAIL: '.$needle.PHP_EOL);exit(1);}}
if(strpos($html,'value="department_restore"')===false||strpos($html,'&lt;script&gt;hidden&lt;/script&gt;')===false)throw new RuntimeException('Restore form/escaping missing');
if(substr_count($html,'<button class="danger">Khóa</button>')!==2)throw new RuntimeException('User/department lock colors missing');
if(strpos($html,'class="card pm-people-list"')===false||strpos($html,'data-pm-editor-content')===false||strpos($html,'class="pm-add-person"')===false)throw new RuntimeException('Compact editor structure missing');
$baseUsers=$s->getTemplateVars('users');
$second=$baseUsers[0];$second['id']=2;$second['fullname']='<script>Second User</script>';$second['email']='second@example.test';$second['username']='second';
$s->assign('users',[$baseUsers[0],$second]);
$twoHtml=$s->fetch('admin/pm-users-v2.tpl.html');
if(strpos($twoHtml,'id="pm-person-2"')===false||strpos($twoHtml,'&lt;script&gt;Second User&lt;/script&gt;')===false)throw new RuntimeException('Distinct editor/escaping missing');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-users-two.html',str_replace('<head>','<head><base href="/">',$twoHtml));
$s->assign('users',$baseUsers);
foreach([[false,true,false],[false,false,true],[true,false,false]] as [$manage,$rbac,$rates]){
    $s->assign(['canManageUsers'=>$manage,'canManageRoles'=>$rbac,'canManageRates'=>$rates]);
    $partial=$s->fetch('admin/pm-users-v2.tpl.html');
    foreach(['save'=>$manage,'roles'=>$rbac,'rate'=>$rates] as $action=>$allowed){
        if((strpos($partial,'name="action" value="'.$action.'"')!==false)!==$allowed)throw new RuntimeException('Independent permission changed: '.$action);
    }
}
$s->assign(['canManageUsers'=>true,'canManageRoles'=>true,'canManageRates'=>true]);
$lockedUsers=$s->getTemplateVars('users');$lockedUsers[0]['status']=0;
$lockedDepartments=$s->getTemplateVars('departmentRows');$lockedDepartments[0]['status']=0;
$s->assign(['users'=>$lockedUsers,'departmentRows'=>$lockedDepartments]);
$unlockHtml=$s->fetch('admin/pm-users-v2.tpl.html');
if(substr_count($unlockHtml,'<button class="secondary">Mở khóa</button>')!==2)throw new RuntimeException('User/department unlock must be ordinary action');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-users-unlock.html',str_replace('<head>','<head><base href="/">',$unlockHtml));
$s->assign(['canManageUsers'=>false,'canManageRoles'=>false,'canManageRates'=>false,'canViewRates'=>false]);
$readOnlyHtml=$s->fetch('admin/pm-users-v2.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-users-readonly.html',str_replace('<head>','<head><base href="/">',$readOnlyHtml));foreach(['name="action" value="department_save"','name="action" value="department_delete"','name="action" value="roles"','name="action" value="rate"','name="action" value="status"','name="action" value="rate_save"'] as $forbidden){if(strpos($readOnlyHtml,$forbidden)!==false){fwrite(STDERR,'FAIL: read-only user saw '.$forbidden.PHP_EOL);exit(1);}}
if(strpos($readOnlyHtml,'department_restore')!==false)throw new RuntimeException('Read-only restore exposed');
echo "PASS: PM user management template rendered.\n";
