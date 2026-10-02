<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates/admin');$s->setCompileDir(sys_get_temp_dir());
$s->assign(['pageTitle'=>'Nhân sự','users'=>[['id'=>1,'username'=>'demo','fullname'=>'Demo User','email'=>'demo@example.test','tel'=>'','department_id'=>null,'department_name'=>null,'weekly_limit_hours'=>'40.00','status'=>1]],'userRoles'=>[1=>[]],'departments'=>[],'roles'=>[['id'=>1,'name'=>'Employee']],'csrfToken'=>'test','notice'=>'','error'=>'','q'=>'','page'=>1,'totalPages'=>1]);
$html=$s->fetch('pm-users.tpl.html');foreach(['Quản lý nhân sự','action" value="roles','action" value="rate','status" value="2'] as $needle){if(strpos($html,$needle)===false){fwrite(STDERR,'FAIL: '.$needle.PHP_EOL);exit(1);}}
echo "PASS: PM user management template rendered.\n";
