<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/dao/pmallocations.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$db=(object)['connection'=>$connection];$q=new PmDb($db);
$admin=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");if(!$admin)throw new RuntimeException('No Admin.');
$store=(int)$admin['store_id'];$actor=(int)$admin['id'];$other=(int)$q->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id<>? AND status=1 LIMIT 1','ii',[$store,$actor])['id'];$dao=new PmAllocations($db,$store);
$keys=[[$actor,'2035-12-31'],[$other,'2036-01-01']];
if(in_array('--worker',$argv,true)){
    $connection->begin_transaction();try{$dao->lockUsers(array_reverse($keys));echo "ACQUIRED\n";}finally{$connection->rollback();}exit;
}
$before=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_allocation_locks')['total'];$process=null;$pipes=[];
$connection->begin_transaction();
try{
    $dao->lockUsers($keys);
    $process=proc_open([PHP_BINARY,__FILE__,'--worker'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,ROOT_PATH);if(!is_resource($process))throw new RuntimeException('Worker unavailable.');fclose($pipes[0]);
    usleep(700000);
    if(!proc_get_status($process)['running'])throw new RuntimeException('Second writer bypassed user/day mutex.');
    $connection->rollback();
    stream_set_timeout($pipes[1],10);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);$process=null;
    if($code!==0||trim($out)!=='ACQUIRED'||$err!=='')throw new RuntimeException('Reverse-order worker failed after lock release.');
    if($q->fetchOne('SELECT COUNT(*) total FROM dc_pm_allocation_locks')['total']!==$before)throw new RuntimeException('Lock fixture was not rolled back.');
    echo "PASS group 6: two real connections serialize user/week/capacity sentinel and sorted user/day locks; reversed input order completes after release, fixtures rolled back.\n";
}finally{$connection->rollback();if(is_resource($process)){proc_terminate($process);proc_close($process);}}
