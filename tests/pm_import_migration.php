<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');
if(in_array('--apply',$argv,true))foreach(['database/migrations/006_create_pm_import_staging.sql','database/seeds/006_seed_pm_import_permissions.sql'] as $file){
    if(!$connection->multi_query(file_get_contents(ROOT_PATH.$file)))throw new RuntimeException('Import staging SQL failed.');
    do{if($result=$connection->store_result())$result->free();if(!$connection->more_results())break;}while($connection->next_result());
    if($connection->errno)throw new RuntimeException('Import staging SQL did not finish.');
}
$q=new PmDb((object)['connection'=>$connection]);
foreach(['dc_pm_import_logs','dc_pm_import_staging'] as $table){
    $row=$q->fetchOne("SELECT t.engine AS engine,c.column_type AS column_type,c.is_nullable AS is_nullable FROM information_schema.tables t JOIN information_schema.columns c ON c.table_schema=t.table_schema AND c.table_name=t.table_name AND c.column_name='store_id' WHERE t.table_schema=DATABASE() AND t.table_name=?",'s',[$table]);
    if(!$row||$row['engine']!=='InnoDB'||$row['column_type']!=='bigint unsigned'||$row['is_nullable']!=='NO')throw new RuntimeException('Invalid tenant schema.');
}
$grants=$q->fetchAll("SELECT r.code role_code,p.code permission_code FROM dc_pm_role_permissions rp JOIN dc_pm_roles r ON r.store_id=rp.store_id AND r.id=rp.role_id JOIN dc_pm_permissions p ON p.store_id=rp.store_id AND p.id=rp.permission_id WHERE p.code='pm.imports.manage'");
foreach($grants as $g)if(!in_array($g['role_code'],$g['permission_code']==='pm.allocations.view'?['ADMIN']:['ADMIN'],true))throw new RuntimeException('Unexpected allocation grant.');
echo "PASS: Import staging additive InnoDB tenant schema and scoped grants.\n";
