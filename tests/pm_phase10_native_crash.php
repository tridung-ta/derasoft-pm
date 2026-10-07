<?php
/** Successful native MyISAM INSERT -> terminate worker -> new-connection resume. */
define('ROOT_PATH',dirname(__DIR__).'/');require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/services/pmimportservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local source refused.');
$fixture=json_decode(file_get_contents(ROOT_PATH.'.local/phase10/fixture.json'),true,512,JSON_THROW_ON_ERROR);
if($fixture['database']!=='derasoft_pm_phase10_20261004')throw new RuntimeException('Unexpected fixture database.');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$fixture['database']);$c->set_charset('utf8mb4');$db=(object)['connection'=>$c];$q=new PmDb($db);
if($q->fetchOne("SELECT ENGINE FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_users'")['ENGINE']!=='MyISAM')throw new RuntimeException('Native engine required.');
$admin=(int)$fixture['ids']['ADMIN'];
class NativeCrashImport extends PmImportService {
    public string $marker;
    protected function afterUserInsert(): void {file_put_contents($this->marker,'inserted');while(true)usleep(100000);}
}
if(($argv[1]??'')==='--worker'){
    $service=new NativeCrashImport($db,1,$admin);$service->marker=ROOT_PATH.'.local/phase10/crash-ready-'.(int)$argv[2];$service->apply((int)$argv[2]);exit;
}
$suffix=bin2hex(random_bytes(6));$r=['username'=>'crash_'.$suffix,'fullname'=>'Native crash fixture','email'=>'crash_'.$suffix.'@example.test','department_code'=>'','department_id'=>null,'weekly_limit_hours'=>'40.00','role_code'=>'EMPLOYEE'];
$q->execute("INSERT INTO dc_pm_import_logs(store_id,actor_id,status,row_count) VALUES(1,?,'staged',1)",'i',[$admin]);$id=(int)$q->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
$q->execute('INSERT INTO dc_pm_import_staging(store_id,import_id,source_row,payload) VALUES(1,?,2,?)','is',[$id,json_encode($r,JSON_THROW_ON_ERROR)]);
$marker=ROOT_PATH.'.local/phase10/crash-ready-'.$id;if(is_file($marker))throw new RuntimeException('Prior marker exists; preserve previous run, no implicit reset.');
$worker=proc_open([PHP_BINARY,__FILE__,'--worker',(string)$id],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/phase10/crash-worker.log','a'],2=>['file',ROOT_PATH.'.local/phase10/crash-worker.log','a']],$pipes);if(!is_resource($worker))throw new RuntimeException('No crash worker');fclose($pipes[0]);
try{
    $ready=false;for($i=0;$i<100;$i++){clearstatcache();if(is_file($marker)){$ready=true;break;}if(!proc_get_status($worker)['running'])break;usleep(100000);}
    if(!$ready)throw new RuntimeException('Worker did not reach successful INSERT.');
    if((int)$q->fetchOne('SELECT COUNT(*) total FROM dc_users WHERE store_id=1 AND email=?','s',[$r['email']])['total']!==1)throw new RuntimeException('Native row missing before kill.');
    proc_terminate($worker);proc_close($worker);$worker=null;
    $fresh=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$fixture['database']);$fresh->set_charset('utf8mb4');
    $service=new PmImportService((object)['connection'=>$fresh],1,$admin);$state=$service->detail($id);
    if($state['status']!=='in_progress'||$state['counts']['pending']!==1)throw new RuntimeException('Durable intent lost.');
    $res=$service->apply($id,true);$row=$res['results'][0];
    if($res['status']!=='completed'||$row['status']!=='applied'||$row['reason_code']!=='recovered_inactive'||(int)$row['attempt_count']!==2)throw new RuntimeException('Native resume failed.');
    if($service->apply($id)['results'][0]['attempt_count']!==$row['attempt_count'])throw new RuntimeException('Completed replay changed result.');
    if((int)$q->fetchOne('SELECT COUNT(*) total FROM dc_users WHERE store_id=1 AND email=?','s',[$r['email']])['total']!==1)throw new RuntimeException('Native replay duplicated user.');
    $q->execute('UPDATE dc_users SET status=2 WHERE id=?','i',[(int)$row['user_id']]);
    $source=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);
    if((int)$source->query('SELECT COUNT(*) total FROM dc_users')->fetch_assoc()['total']!==(int)$fixture['source_user_count'])throw new RuntimeException('Current users changed.');
    echo "PASS: native MyISAM successful INSERT, killed worker, durable intent/in_progress, lock release, new-connection resume and idempotent replay; fixture account soft deleted only in isolated DB. Not a mysqld/OS power-loss test.\n";
}finally{if(is_resource($worker)){proc_terminate($worker);proc_close($worker);}}
