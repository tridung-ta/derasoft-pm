<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');
if(in_array('--apply',$argv,true)){
    $sql=file_get_contents(ROOT_PATH.'database/seeds/002_seed_pm_phase5_audit_permissions.sql');
    if(!$connection->multi_query($sql))throw new RuntimeException('Audit permission seed failed.');
    do{if($result=$connection->store_result())$result->free();if(!$connection->more_results())break;}while($connection->next_result());
    if($connection->errno)throw new RuntimeException('Audit permission seed did not finish.');
}
$query=new PmDb((object)['connection'=>$connection]);
$missing=$query->fetchOne("SELECT COUNT(*) total FROM dc_pm_roles r WHERE r.status=1 AND r.code IN ('ADMIN','PM','HR') AND NOT EXISTS(SELECT 1 FROM dc_pm_role_permissions rp JOIN dc_pm_permissions p ON p.store_id=rp.store_id AND p.id=rp.permission_id WHERE rp.store_id=r.store_id AND rp.role_id=r.id AND p.code='pm.audit.view')");
if((int)$missing['total']!==0)throw new RuntimeException('Audit role grants missing.');
echo "PASS: tenant-scoped Admin/PM/HR audit grants.\n";
