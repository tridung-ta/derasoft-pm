<?php
// No web endpoint, shared secret or implicit tenant. Default execution is read-only.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('ROOT_PATH',dirname(__DIR__).'/');
$options=['commit'=>false,'store'=>null,'job'=>'all','date'=>null];
foreach(array_slice($argv,1) as $arg){
    if($arg==='--help'){echo "PM reminders: --store-id=N [--job=all|task|timesheet] [--date=YYYY-MM-DD (dry-run only)] [--commit]\nDefault dry-run. Timezone Asia/Ho_Chi_Minh. Enable PM_REMINDERS_ENABLED=1 before commit.\n";exit(0);}
    if($arg==='--commit')$options['commit']=true;
    elseif(preg_match('/^--store-id=([1-9][0-9]{0,17})$/D',$arg,$m)&&filter_var($m[1],FILTER_VALIDATE_INT)!==false)$options['store']=(int)$m[1];
    elseif(preg_match('/^--job=(all|task|timesheet)$/D',$arg,$m))$options['job']=$m[1];
    elseif(preg_match('/^--date=(\d{4}-\d{2}-\d{2})$/D',$arg,$m))$options['date']=$m[1];
    else{fwrite(STDERR,"Invalid arguments. Use --help.\n");exit(2);}
}
if(!$options['store']||($options['commit']&&$options['date']!==null)){fwrite(STDERR,"Explicit store required; commit cannot override date.\n");exit(2);}
$zone=new DateTimeZone('Asia/Ho_Chi_Minh');$clock=new DateTimeImmutable('now',$zone);
if($options['date']!==null){$clock=DateTimeImmutable::createFromFormat('!Y-m-d',$options['date'],$zone);if(!$clock||$clock->format('Y-m-d')!==$options['date']){fwrite(STDERR,"Invalid date.\n");exit(2);}$clock=$clock->setTime(18,0);}
require ROOT_PATH.'includes/pm_reminders.inc.php';
if($options['commit']&&!PM_REMINDERS_ENABLED){fwrite(STDERR,"Reminders disabled. Verify schema/UAT before enabling.\n");exit(2);}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=null;
try {
    require ROOT_PATH.'includes/constant.inc.php';require ROOT_PATH.'includes/config.inc.php';
    require ROOT_PATH.'classes/database/mysql.class.php';require ROOT_PATH.'classes/services/pmreminderservice.class.php';
    $db=new DB();$dao=new PmNotifications(new PmDb($db),$options['store']);$service=new PmReminderService($dao);$failed=false;
    foreach($options['job']==='all'?['timesheet','task']:[$options['job']] as $job){$stats=$service->run($job,$options['commit'],$clock);echo json_encode($stats,JSON_THROW_ON_ERROR)."\n";$failed=$failed||$stats['failed']>0;}
    $code=$failed?1:0;
}catch(Throwable $e){error_log('PM reminder runner failed.');fwrite(STDERR,"PM reminders failed. Check schema and deployment configuration.\n");$code=1;}
finally{if($db&&isset($db->connection)&&$db->connection instanceof mysqli){$db->connection->close();$db->connection=null;}}
exit($code);
