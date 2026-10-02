<?php
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
define('DEBUG', false); define('QUERY_ERROR', false); define('QUERY_DEBUG', false);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/mysql.class.php';
require ROOT_PATH.'classes/services/pmrateservice.class.php';
if (($config['db_name'] ?? '') !== 'derasoft_pm_local') exit("Refusing non-local database.\n");
$db=new DB();$service=new PmRateService($db);$fail=[];
foreach([[null,null],[1,2]] as [$u,$r]){try{$service->validateRateOwner($u,$r);$fail[]='owner XOR accepted invalid input';}catch(InvalidArgumentException $e){}}
$service->validateRateOwner(1,null);$service->validateRateOwner(null,1);
foreach(['abc','-1','1.123','10000000000000','1e3'] as $invalidRate){
    try{$service->saveRate(1,['user_id'=>1,'rate'=>$invalidRate,'effective_from'=>'2026-01-01'],1);$fail[]='invalid numeric rate accepted';}catch(InvalidArgumentException $e){}
}

$connection=$db->connection;$connection->begin_transaction();
try {
    $role=$connection->query("SELECT store_id,id FROM dc_pm_roles ORDER BY id LIMIT 1")->fetch_assoc();
    $storeId=(int)$role['store_id'];$roleId=(int)$role['id'];
    $s=$connection->prepare('INSERT INTO dc_pm_hourly_rates(store_id,user_id,role_id,rate,currency,effective_from,effective_to,status,created_by) VALUES(?,NULL,?,100000,"VND","2026-01-01","2026-01-31",1,1)');
    $s->bind_param('ii',$storeId,$roleId);$s->execute();
    if(!$service->hasOverlappingRate($storeId,null,$roleId,'2026-01-15',null))$fail[]='open-ended new rate overlap was missed';
    if($service->hasOverlappingRate($storeId,null,$roleId,'2026-02-01','2026-02-28'))$fail[]='non-overlapping rate was rejected';
} finally { $connection->rollback(); }
if($fail){foreach($fail as $f)fwrite(STDERR,'FAIL: '.$f.PHP_EOL);exit(1);}
echo "PASS: rate owner XOR and symmetric overlap checks verified.\n";
