<?php
if(PHP_SAPI!=='cli')exit;
if(in_array('--help',array_slice($argv,1),true)||!in_array('--run',array_slice($argv,1),true)){echo "Manual012 runner: apply approved012 yourself, verify backup .sql.sha256, then run with --run. Default does not connect DB.\n";exit(0);}
foreach(array_slice($argv,1) as $arg)if($arg!=='--run'){fwrite(STDERR,"Unknown runner argument.\n");exit(2);}
define('ROOT_PATH',dirname(__DIR__).'/');require ROOT_PATH.'tests/pm_cron_db_fixture.php';require ROOT_PATH.'classes/services/pmreminderservice.class.php';require ROOT_PATH.'classes/services/pmreminderoptions.class.php';
$checks=[];$errors=[];$processes=[];$stores=[];$ownedStores=[];$observer=pmCronLocalConnection();$c=pmCronLocalConnection();$harness='pmthrottle-test:'.substr(hash('sha256','derasoft_pm_local'),0,40);$hs=$observer->prepare('SELECT GET_LOCK(?,0) acquired');$hs->bind_param('s',$harness);$hs->execute();if((int)$hs->get_result()->fetch_assoc()['acquired']!==1){echo "Another throttle fixture runner is active; refusing concurrent run.\n";exit(2);}$before=pmCronFingerprint($observer);
function cronCheck(bool $ok,string $name): void {global $checks;$checks[]=['case'=>$name,'result'=>$ok?'PASS':'FAIL'];echo ($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;if(!$ok)throw new RuntimeException($name);}
function cronStart(array $cmd,array $env): array {$p=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,ROOT_PATH,$env);if(!is_resource($p))throw new RuntimeException('Process start');fclose($pipes[0]);return [$p,$pipes];}
function cronFinish(array $proc): array {[$p,$pipes]=$proc;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);$json=json_decode(trim($out),true);return [$code,$json,$err];}
function cronWaitFile(string $path): void {$end=microtime(true)+10;while(!is_file($path)){if(microtime(true)>$end)throw new RuntimeException('Worker barrier not ready');usleep(20000);}}
try{
 $backups=glob(ROOT_PATH.'.local/cron-backups/*.sql.sha256');sort($backups);$backup=end($backups);$dump=$backup?substr($backup,0,-7):'';if(!$backup||!is_file($dump)||filesize($dump)<100||hash_file('sha256',$dump)!==strtolower(trim(file_get_contents($backup))))throw new RuntimeException('Verified backup required');$header=file_get_contents($dump,false,null,0,4096);if(!preg_match('/-- Host: (?:localhost|127\.0\.0\.1)\s+Database: derasoft_pm_local/',$header))throw new RuntimeException('Backup database provenance mismatch');
 $q=new PmDb((object)['connection'=>$c]);(new PmNotifications($q,1))->preflight(true);cronCheck(true,'012 strict preflight: operator-applied schema, no migration executed by runner');
 // Newly added empty table is expected; data in all pre-existing tables must stay equal.
 $postSchema=pmCronFingerprint($observer);foreach($before as $table=>$hash)cronCheck($postSchema[$table]===$hash,'Migration preserves '.$table);
 $base=random_int(800000000,850000000);$stores=[$base,$base+1];foreach($stores as $s){foreach(array_keys($postSchema) as $table){$columns=$observer->query('SHOW COLUMNS FROM '.$table)->fetch_all(MYSQLI_ASSOC);if(!in_array('store_id',array_column($columns,'Field'),true))continue;$r=$observer->prepare('SELECT COUNT(*) n FROM '.$table.' WHERE store_id=?');$r->bind_param('i',$s);$r->execute();if((int)$r->get_result()->fetch_assoc()['n'])throw new RuntimeException('Fixture store collision');}}
 $ownedStores=$stores;
 $nonce=bin2hex(random_bytes(6));$token=bin2hex(random_bytes(32));$date=(new DateTimeImmutable('2026-10-14',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');$users=[];$roleMap=[];
 foreach($stores as $i=>$store){
  foreach(['EMPLOYEE','PM'] as $role){$id=pmCronInsert($c,'dc_pm_roles',['store_id'=>$store,'code'=>$role,'name'=>'Cron fixture','status'=>1]);$roleMap[$store][$role]=$id;}
  foreach(['pm.timesheets.own','pm.projects.view','pm.tasks.view'] as $permission){$id=pmCronInsert($c,'dc_pm_permissions',['store_id'=>$store,'code'=>$permission,'name'=>'Cron fixture','module'=>'pm']);foreach(['EMPLOYEE','PM'] as $role)if($role==='EMPLOYEE'||$permission!=='pm.timesheets.own')pmCronInsert($c,'dc_pm_role_permissions',['store_id'=>$store,'role_id'=>$roleMap[$store][$role],'permission_id'=>$id]);}
  for($n=1;$n<=12;$n++){$user=['id'=>900000000+$i*100+$n,'store'=>$store,'status'=>$n===4?0:1,'n'=>$n];$users[]=$user;if($n!==5)pmCronInsert($c,'dc_pm_user_roles',['store_id'=>$store,'user_id'=>$user['id'],'role_id'=>$roleMap[$store][in_array($n,[6,7],true)?'PM':'EMPLOYEE'],'is_primary'=>1]);}
 }
 $f=['nonce'=>$nonce,'token'=>$token,'stores'=>$stores,'date'=>$date,'users'=>$users,'tasks'=>[],'projects'=>[]];
 foreach($stores as $i=>$store){$manager=900000000+$i*100+6;$assignee=900000000+$i*100+1;$project=pmCronInsert($c,'dc_pm_projects',['store_id'=>$store,'code'=>'CRON-'.$nonce.'-'.$i,'name'=>'Cron fixture project','manager_id'=>$manager,'status'=>'active','created_by'=>$manager]);$f['projects'][$store]=$project;
  pmCronInsert($c,'dc_pm_project_members',['store_id'=>$store,'project_id'=>$project,'user_id'=>$assignee,'status'=>1]);
  foreach([$assignee+1,$manager] as $member)pmCronInsert($c,'dc_pm_project_members',['store_id'=>$store,'project_id'=>$project,'user_id'=>$member,'status'=>1]);
  foreach(['today'=>0,'tomorrow'=>1,'old400'=>-400,'old8'=>-8,'old7'=>-7,'selfpm'=>-1,'future'=>2,'done'=>-1,'hidden'=>-1,'null'=>null,'unassigned'=>0] as $label=>$offset){$due=$offset===null?null:(new DateTimeImmutable($date))->modify(($offset>=0?'+':'').$offset.' days')->format('Y-m-d');$task=pmCronInsert($c,'dc_pm_tasks',['store_id'=>$store,'project_id'=>$project,'name'=>'Cron '.$label,'assignee_id'=>$label==='unassigned'?null:($label==='selfpm'?$manager:$assignee),'status'=>$label==='done'?'done':'todo','due_date'=>$due,'deleted_at'=>$label==='hidden'?$date.' 00:00:00':null,'created_by'=>$manager]);$f['tasks'][$store][$label]=$task;}
  pmCronInsert($c,'dc_pm_timesheets',['store_id'=>$store,'user_id'=>900000000+$i*100+3,'project_id'=>$project,'task_id'=>$f['tasks'][$store]['today'],'work_date'=>$date,'shift_label'=>'Ca linh hoạt','hours'=>'1','rate_snapshot'=>'0','rate_source'=>'user','currency'=>'VND','ot_multiplier_snapshot'=>'1','cost'=>'0']);
  $progress=pmCronInsert($c,'dc_pm_tasks',['store_id'=>$store,'project_id'=>$project,'name'=>'Cron in-progress fixture','assignee_id'=>$assignee+1,'status'=>'in_progress','start_date'=>(new DateTimeImmutable($date))->modify('+10 days')->format('Y-m-d'),'due_date'=>null,'created_by'=>$manager]);$f['tasks'][$store]['progress']=$progress;
  foreach([[1,1,null,'today'],[9,0,null,'today'],[10,1,$date.' 00:00:00','today'],[11,1,null,'done'],[5,1,null,'today'],[4,1,null,'today']] as [$suffix,$hours,$hidden,$label])pmCronInsert($c,'dc_pm_allocations',['store_id'=>$store,'user_id'=>900000000+$i*100+$suffix,'project_id'=>$project,'task_id'=>$f['tasks'][$store][$label],'work_date'=>$date,'hours'=>$hours,'deleted_at'=>$hidden,'created_by'=>$manager,'updated_by'=>$manager]);

 }
 file_put_contents(ROOT_PATH.'.local/cron-throttle-fixture.json',json_encode($f,JSON_THROW_ON_ERROR));pmCronMirrorUsers($c,$f);$dao=new PmNotifications($q,$stores[0]);$clock=new DateTimeImmutable($date.' 18:00',new DateTimeZone('Asia/Ho_Chi_Minh'));
 $legacySql='SELECT COUNT(*) n FROM dc_users u WHERE u.store_id=? AND '.$dao->eligibleSql('timesheet');
 $legacy=(int)$q->fetchOne($legacySql,'is',[$stores[0],$date])['n'];
 $allocationCount=(int)$q->fetchOne($legacySql.' AND EXISTS(SELECT 1 FROM dc_pm_allocations a JOIN dc_pm_tasks t ON t.store_id=a.store_id AND t.id=a.task_id JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id WHERE a.store_id=u.store_id AND a.user_id=u.id AND a.work_date=? AND a.hours>0 AND a.deleted_at IS NULL AND t.deleted_at IS NULL AND t.status<>? AND p.deleted_at IS NULL)','isss',[$stores[0],$date,$date,'done'])['n'];
 $recipients=array_map('intval',array_column($dao->candidates('timesheet',$date),'recipient_id'));sort($recipients);
 cronCheck($legacy===7&&$allocationCount===1&&$recipients===[900000001,900000002],'Fixture recipient counts: legacy7/allocation1/allocation-or-progress2, invalid eligibility excluded');
 $narrow=new PmNotifications($q,$stores[0],[900000002]);$narrow->validateUsers();cronCheck(count($narrow->candidates('timesheet',$date))===1,'Narrow scope selects only requested recipient');
 try{(new PmNotifications($q,$stores[0],[900000101]))->validateUsers();throw new LogicException('Foreign scope accepted');}catch(InvalidArgumentException $e){cronCheck($e->getMessage()===PmReminderOptions::USER_ERROR,'Foreign id same generic message as malformed');}
 $tasks=$dao->candidates('task',$date);cronCheck(count($tasks)===8,'Task candidates8: excludes400/D-8, includesD-7, self-PM single');
 $entities=array_map('intval',array_column($tasks,'entity_id'));cronCheck(!in_array($f['tasks'][$stores[0]]['old400'],$entities,true)&&!in_array($f['tasks'][$stores[0]]['old8'],$entities,true)&&in_array($f['tasks'][$stores[0]]['old7'],$entities,true),'Create window400=0,D-8=0,D-7=1');
 $service=new PmReminderService($dao);$first=$service->run('task',true,$clock);$again=$service->run('task',true,$clock);cronCheck($first['created']===8&&$again['duplicate']===8&&$again['created']===0,'Real snapshot INSERT and native1062 rerun');
 $snapshot=$q->fetchOne('SELECT due_date FROM dc_pm_notifications WHERE store_id=? AND entity_id=?','ii',[$stores[0],$f['tasks'][$stores[0]]['selfpm']]);cronCheck($snapshot['due_date']===(new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d'),'Stored due_date exactly matches rechecked task deadline');
 $self=$f['tasks'][$stores[0]]['selfpm'];$manager=900000006;$deadlineA=(new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');$deadlineB=(new DateTimeImmutable($date))->modify('-2 days')->format('Y-m-d');
 $day2=(new DateTimeImmutable($date,new DateTimeZone('Asia/Ho_Chi_Minh')))->modify('+1 day')->setTime(18,0);$service->run('task',true,$day2);
 $countSelf=fn()=>(int)$q->fetchOne('SELECT COUNT(*) n FROM dc_pm_notifications WHERE store_id=? AND entity_id=? AND kind=?','iis',[$stores[0],$self,'task_overdue'])['n'];cronCheck($countSelf()===1,'Day2 no new overdue for unchanged deadline; assignee=PM one recipient');
 $oldRows=$dao->list(900000001,$day2->format('Y-m-d'));cronCheck(in_array($f['tasks'][$stores[0]]['old7'],array_map('intval',array_column($oldRows['rows'],'entity_id')),true)&&$oldRows['unread']>0,'Old unread persists after task leaves7day window');
 $q->execute('UPDATE dc_pm_tasks SET due_date=? WHERE store_id=? AND id=?','sii',[$deadlineB,$stores[0],$self]);
 cronCheck($dao->insert(['recipient_id'=>$manager,'entity_id'=>$self,'due_date'=>$deadlineA],'task','task_overdue',$date)===0,'Deadline changed between candidate and INSERT => stale0');
 try{$dao->insert(['recipient_id'=>$manager,'entity_id'=>$self,'due_date'=>$deadlineB],'task','task_overdue',$date);throw new LogicException('Same day duplicate accepted');}catch(mysqli_sql_exception $e){cronCheck((int)$e->getCode()===1062&&str_contains($e->getMessage(),'uq_pm_notification_event'),'Native1062 old key only, changed deadline same day');}
 $service->run('task',true,$day2);cronCheck($countSelf()===2,'Changed deadline sends again next day');
 $q->execute('UPDATE dc_pm_tasks SET due_date=? WHERE store_id=? AND id=?','sii',[$deadlineA,$stores[0],$self]);
 try{$dao->insert(['recipient_id'=>$manager,'entity_id'=>$self,'due_date'=>$deadlineA],'task','task_overdue',$day2->modify('+1 day')->format('Y-m-d'));throw new LogicException('Old snapshot accepted');}catch(mysqli_sql_exception $e){cronCheck((int)$e->getCode()===1062&&str_contains($e->getMessage(),'uq_pm_notification_deadline'),'Native1062 new key only, reverting deadline suppressed');}
 $time1=$service->run('timesheet',true,$clock);$time2=$service->run('timesheet',true,$clock);cronCheck($time1['created']===2&&$time2['duplicate']===2,'Timesheet work recipients2; null snapshots use daily unique');
 $env=getenv();$env['PM_CRON_TEST_TOKEN']=$token;$env['PM_CRON_FIXTURE_NAME']='cron-throttle-fixture.json';$worker=ROOT_PATH.'tests/pm_cron_throttle_worker.php';$barrier=ROOT_PATH.'.local/cron-barrier-'.$nonce;
 $hold=cronStart([PHP_BINARY,$worker,(string)$stores[0],'hold',$date],$env);$processes[]=$hold;cronWaitFile($barrier.'.ready');
 $parallel=cronFinish(cronStart([PHP_BINARY,$worker,(string)$stores[0],'run',$date],$env));cronCheck($parallel[0]===0&&($parallel[1]['skipped_busy']??false),'Two processes same tenant: second skipped_busy while lock held');
 $other=cronFinish(cronStart([PHP_BINARY,$worker,(string)$stores[1],'run',$date],$env));cronCheck($other[0]===0&&!$other[1]['skipped_busy']&&$other[1]['created']===8,'Two tenants: B writes while A lock held');
 file_put_contents($barrier.'.go','go');$held=cronFinish($hold);cronCheck($held[0]===0&&$held[1]['duplicate']===8,'Holder resumes/release after same-tenant test');unlink($barrier.'.go');unlink($barrier.'.ready');
 $q->execute('DELETE FROM dc_pm_notifications WHERE store_id=?','i',[$stores[0]]);
 $future=$date;$crash=cronStart([PHP_BINARY,$worker,(string)$stores[0],'crash',$future],$env);$processes[]=$crash;cronWaitFile($barrier.'.ready');proc_terminate($crash[0]);cronFinish($crash);
 $end=microtime(true)+10;$lock=$dao->lockName('task');do{$free=(int)($q->fetchOne('SELECT IS_FREE_LOCK(?) free_lock','s',[$lock])['free_lock']??0);if($free)break;usleep(20000);}while(microtime(true)<$end);cronCheck($free===1,'Crash frees connection lock with bounded polling');
 $recovered=cronFinish(cronStart([PHP_BINARY,$worker,(string)$stores[0],'run',$future],$env));$futureCandidates=count($dao->candidates('task',$future));cronCheck($recovered[0]===0&&$recovered[1]['duplicate']===1&&$recovered[1]['created']===$futureCandidates-1,'Crash recovery preserves first insert, resumes without duplicates');
 $id=(int)$q->fetchOne('SELECT id FROM dc_pm_notifications WHERE store_id=? AND recipient_id=? AND entity_id=? AND event_date=?','iiis',[$stores[0],900000001,$f['tasks'][$stores[0]]['today'],$date])['id'];
 $port=18781;$existing=@fsockopen('127.0.0.1',$port,$errno,$message,0.1);if($existing){fclose($existing);throw new RuntimeException('HTTP test port occupied; refusing reuse');}
 $server=cronStart([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',ROOT_PATH,ROOT_PATH.'tests/pm_cron_throttle_http_router.php'],$env);$processes[]=$server;$ready=false;$end=microtime(true)+5;do{$socket=@fsockopen('127.0.0.1',$port,$errno,$message,0.1);if($socket){fclose($socket);$ready=true;break;}usleep(20000);}while(microtime(true)<$end);cronCheck($ready,'HTTP fixture server ready on free loopback port');
 $http=function(array $post,?int $actor=null)use($port,$token){$url='http://127.0.0.1:'.$port.'/admin.php?op=pmnotifications'.($actor?'&fixture_actor='.$actor:'');$context=stream_context_create(['http'=>['method'=>'POST','content'=>http_build_query($post),'header'=>"Content-Type: application/x-www-form-urlencoded\r\nX-PM-Test-Token: ".$token."\r\nConnection: close\r\n",'timeout'=>5,'ignore_errors'=>true,'follow_location'=>0]]);$body=file_get_contents($url,false,$context);$status=0;if(preg_match('/^HTTP\/\S+ (\d{3})/',$http_response_header[0]??'',$match))$status=(int)$match[1];return [$status,$body];};
 $valid=['op'=>'pmnotifications','action'=>'read','id'=>$id,'csrf_token'=>hash('sha256',$token)];$bad=$http(array_replace($valid,['csrf_token'=>'wrong']));cronCheck($bad[0]===403,'HTTP mark-read CSRF reject');
 $ok=$http($valid);$read=$q->fetchOne('SELECT read_at FROM dc_pm_notifications WHERE id=?','i',[$id]);cronCheck($ok[0]===303&&$read['read_at']!==null,'HTTP real controller mark-read persists and redirects303');
 $foreign=$http($valid,900000101);$missing=$http(array_replace($valid,['id'=>999999999]),900000101);cronCheck($foreign[0]===404&&$missing[0]===404&&str_contains($foreign[1],'Không tìm thấy thông báo.')&&str_contains($missing[1],'Không tìm thấy thông báo.'),'HTTP foreign/missing same404/message');
 proc_terminate($server[0]);cronFinish($server);
 cronCheck($dao->list(900000005,$date)['total']===0,'No-role list contains no task data');
 $q->execute('UPDATE dc_pm_project_members SET status=0 WHERE store_id=? AND project_id=? AND user_id=?','iii',[$stores[0],$f['projects'][$stores[0]],900000001]);cronCheck($dao->list(900000001,$date)['total']===0,'Membership loss removes details/unread');
 $q->execute('UPDATE dc_pm_project_members SET status=1 WHERE store_id=? AND project_id=? AND user_id=?','iii',[$stores[0],$f['projects'][$stores[0]],900000001]);
 class CronExplainDb extends PmDb {public array $queries=[];public function __construct(){}public function fetchAll(string $s,string $t='',array $p=[]): array{$this->queries[]=[$s,$t,$p];return [];}public function fetchOne(string $s,string $t='',array $p=[]): ?array{$this->queries[]=[$s,$t,$p];return null;}}
 $record=new CronExplainDb();$explainDao=new PmNotifications($record,$stores[0]);$explainDao->candidates('timesheet',$date);$explainDao->candidates('task',$date);$explainDao->list(900000001,$date);$explainDao->markRead(900000001,$id,$date);$plans=[];
 foreach($record->queries as $i=>[$sql,$types,$params]){$plans[]=['query'=>$i+1,'plan'=>$q->fetchAll('EXPLAIN '.$sql,$types,$params)];}cronCheck(count($plans)===5,'Five real reminder SELECT shapes EXPLAIN, no index changes');file_put_contents(ROOT_PATH.'.local/cron-throttle-db-explain.json',json_encode($plans,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}catch(Throwable $e){$errors[]=['message'=>$e instanceof mysqli_sql_exception?'SQL error code '.(int)$e->getCode():$e->getMessage()];echo 'FAIL: '.end($errors)['message'].PHP_EOL;}
finally{
 $workersStopped=true;
 foreach($processes as [$p,$pipes])if(is_resource($p)){
  proc_terminate($p);$end=microtime(true)+5;do{$state=proc_get_status($p);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$end);
  if($state['running']){$workersStopped=false;continue;}
  foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($p);
 }

 // Only rows in two positively reserved, previously empty fixture stores are removed.
 try{if(!$workersStopped)throw new RuntimeException('Worker did not stop; refusing cleanup race');foreach(['dc_pm_notifications','dc_pm_allocations','dc_pm_timesheets','dc_pm_tasks','dc_pm_project_members','dc_pm_projects','dc_pm_user_roles','dc_pm_role_permissions','dc_pm_permissions','dc_pm_roles'] as $table)foreach($ownedStores as $s){$st=$observer->prepare('DELETE FROM '.$table.' WHERE store_id=?');$st->bind_param('i',$s);$st->execute();}
  $after=pmCronFingerprint($observer);$changed=[];foreach($before as $table=>$hash)if(($after[$table]??null)!==$hash)$changed[]=$table;
  if(!isset($before['dc_pm_notifications'])&&($after['dc_pm_notifications']['rows']??-1)!==0)$changed[]='dc_pm_notifications';cronCheck(!$changed,'Persistent dc_users and all pre-existing PM data hashes unchanged; new011 table empty');
  file_put_contents(ROOT_PATH.'.local/cron-throttle-db-checksums.json',json_encode(['before'=>$before,'after'=>$after,'changed'=>$changed],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
 }catch(Throwable $e){$errors[]=['message'=>'Cleanup/checksum failed','code'=>(int)$e->getCode()];echo "FAIL: cleanup/checksum.\n";}
 file_put_contents(ROOT_PATH.'.local/cron-throttle-db-results.json',json_encode(['cases'=>$checks,'errors'=>$errors,'cleanup'=>$errors?false:true,'schema_applied_by_runner'=>false],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
 $c->close();$observer->close();
}
echo 'RESULT: '.count(array_filter($checks,fn($r)=>$r['result']==='PASS')).' PASS; '.count($errors).' errors. Real local DB, users exact-schema mirrors; no host accessed.'.PHP_EOL;
exit($errors?1:0);
