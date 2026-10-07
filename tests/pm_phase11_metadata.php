<?php
define('ROOT_PATH',dirname(__DIR__).'/');require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local DB');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$query=new PmDb((object)['connection'=>$connection]);
foreach([['dc_pm_projects','client_name','varchar(150)'],['dc_pm_project_members','project_role','varchar(50)'],['dc_pm_tasks','start_date','date'],['dc_pm_tasks','completed_at','datetime']] as [$table,$column,$type]){
 $row=$query->fetchOne('SELECT column_type AS expected_type,is_nullable AS nullable_value FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?','ss',[$table,$column]);
 if(!$row||strtolower($row['expected_type'])!==$type||$row['nullable_value']!=='YES')throw new RuntimeException('Unexpected metadata column: '.$table.'.'.$column);
}
$sql=file_get_contents(ROOT_PATH.'database/migrations/009_add_pm_project_task_metadata.sql');
if(preg_match('/\b(DROP|TRUNCATE|RENAME)\s+(TABLE|COLUMN)\b/i',preg_replace('/--[^\n]*/','',$sql)))throw new RuntimeException('Destructive migration');
echo "PASS: four additive nullable metadata columns, no destructive SQL; read-only local schema verification.\n";
