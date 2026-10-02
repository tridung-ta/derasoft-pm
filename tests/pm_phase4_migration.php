<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
if(($config['db_name']??'')!=='derasoft_pm_local')throw new RuntimeException('Refusing non-local database.');
$db=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$db->set_charset('utf8mb4');
if(in_array('--apply',$argv,true)){
    $sql=file_get_contents(ROOT_PATH.'database/migrations/003_create_pm_projects_tasks.sql');
    if(!$db->multi_query($sql))throw new RuntimeException('Phase 4 migration failed.');
    do{if($result=$db->store_result())$result->free();if(!$db->more_results())break;}while($db->next_result());
    if($db->errno)throw new RuntimeException('Phase 4 migration did not finish.');
}
foreach(['dc_pm_projects','dc_pm_project_members','dc_pm_tasks'] as $table){
    $s=$db->prepare('SELECT t.engine AS engine,c.column_type AS column_type,c.is_nullable AS is_nullable FROM information_schema.tables t INNER JOIN information_schema.columns c ON c.table_schema=t.table_schema AND c.table_name=t.table_name AND c.column_name="store_id" WHERE t.table_schema=DATABASE() AND t.table_name=?');$s->bind_param('s',$table);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();
    if(!$row||$row['engine']!=='InnoDB'||$row['column_type']!=='bigint unsigned'||$row['is_nullable']!=='NO')throw new RuntimeException('Invalid Phase 4 tenant schema: '.$table);
}
echo "PASS: Phase 4 InnoDB tenant schema.\n";
