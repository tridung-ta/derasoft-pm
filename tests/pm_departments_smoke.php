<?php
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
define('DEBUG', false); define('QUERY_ERROR', false); define('QUERY_DEBUG', false);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/mysql.class.php';
require ROOT_PATH.'classes/dao/pmdepartments.class.php';
if (($config['db_name'] ?? '') !== 'derasoft_pm_local') exit("Refusing non-local database.\n");

$db=new DB();$departments=new PmDepartments($db);$connection=$db->connection;$fail=[];
$tenant=$connection->query('SELECT store_id FROM dc_users ORDER BY id LIMIT 1')->fetch_assoc();
$storeId=(int)($tenant['store_id']??0);
if($storeId<=0)exit("No local tenant fixture available.\n");

$connection->begin_transaction();
try {
    $code='QA_'.bin2hex(random_bytes(4));
    $id=$departments->save($storeId,null,$code,'Quality Assurance');
    if(!$departments->exists($storeId,$id))$fail[]='created department is unavailable in tenant';
    if($departments->exists($storeId+999999,$id))$fail[]='department leaked across tenant';
    $departments->save($storeId,$id,$code,'Quality Engineering');
    if($departments->codeExists($storeId,$code,$id))$fail[]='department code collided with its own row';
    $updated=array_values(array_filter($departments->list($storeId),fn(array $row): bool=>(int)$row['id']===$id));
    if(($updated[0]['name']??'')!=='Quality Engineering')$fail[]='department update was not persisted';
    try{$departments->save($storeId+999999,$id,$code,'Cross tenant');$fail[]='cross-tenant update was accepted';}catch(OutOfBoundsException $e){}
    if(!$departments->setStatus($storeId,$id,0))$fail[]='department lock failed';
    if(!$departments->softDelete($storeId,$id))$fail[]='department soft delete failed';
    if(!$departments->hiddenCodeExists($storeId,$code))$fail[]='hidden code not identified';
    if($departments->restore($storeId+999999,$id))$fail[]='cross-tenant restore accepted';
    $hidden=$departments->listHidden($storeId);
    if(!in_array($id,array_map(fn($r)=>(int)$r['id'],$hidden),true))$fail[]='hidden department unavailable for restore';
    if(!$departments->restore($storeId,$id)||!$departments->exists($storeId,$id))$fail[]='restore failed';
    if($departments->hiddenCodeExists($storeId,$code))$fail[]='restored code still hidden';
    if($departments->restore($storeId,$id))$fail[]='active department restore accepted';
} finally {
    $connection->rollback();
}
if($fail){foreach($fail as $item)fwrite(STDERR,'FAIL: '.$item.PHP_EOL);exit(1);}
echo "PASS: tenant department CRUD and soft delete verified with rollback.\n";
