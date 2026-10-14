<?php
// No web endpoint, shared secret or implicit tenant. Default execution is read-only.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/services/pmreminderoptions.class.php';
if(in_array('--help',array_slice($argv,1),true)){echo "PM reminders: --store-id=N [--job=all|task|timesheet] [--user-ids=ID1,ID2 | --all-users] [--date=YYYY-MM-DD (dry-run only)] [--commit]\nDefault dry-run; commit needs exactly one explicit scope.\n";exit(0);}
try{$options=PmReminderOptions::parse(array_slice($argv,1));}catch(InvalidArgumentException $e){fwrite(STDERR,$e->getMessage()."\n");exit(2);}
$zone=new DateTimeZone('Asia/Ho_Chi_Minh');$clock=new DateTimeImmutable('now',$zone);
if($options['date']!==null){$clock=DateTimeImmutable::createFromFormat('!Y-m-d',$options['date'],$zone);if(!$clock||$clock->format('Y-m-d')!==$options['date']){fwrite(STDERR,"Invalid date.\n");exit(2);}$clock=$clock->setTime(18,0);}
require ROOT_PATH.'includes/pm_reminders.inc.php';
if($options['commit']&&!PM_REMINDERS_ENABLED){fwrite(STDERR,"Reminders disabled. Verify schema/UAT before enabling.\n");exit(2);}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=null;
try {
    require ROOT_PATH.'includes/constant.inc.php';require ROOT_PATH.'includes/config.inc.php';
    require ROOT_PATH.'classes/database/mysql.class.php';require ROOT_PATH.'classes/services/pmreminderservice.class.php';
    $db=new DB();$dao=new PmNotifications(new PmDb($db),$options['store'],$options['user_ids']);$service=new PmReminderService($dao);$failed=false;
    foreach($options['job']==='all'?['timesheet','task']:[$options['job']] as $job){$stats=$service->run($job,$options['commit'],$clock);echo json_encode($stats,JSON_THROW_ON_ERROR)."\n";$failed=$failed||$stats['failed']>0;}
    $code=$failed?1:0;
}catch(InvalidArgumentException $e){fwrite(STDERR,PmReminderOptions::USER_ERROR."\n");$code=2;}catch(Throwable $e){error_log('PM reminder runner failed.');fwrite(STDERR,"PM reminders failed. Check schema and deployment configuration.\n");$code=1;}
finally{if($db&&isset($db->connection)&&$db->connection instanceof mysqli){$db->connection->close();$db->connection=null;}}
exit($code);
