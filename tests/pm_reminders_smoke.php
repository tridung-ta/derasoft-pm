<?php
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/services/pmreminderservice.class.php';
require ROOT_PATH.'classes/services/pmnotificationservice.class.php';
require ROOT_PATH.'classes/template/smarty.class.php';
require ROOT_PATH.'tests/pm_navigation_fixture.php';
$checks=0;
function verifyReminder(bool $test,string $message): void {global $checks;$checks++;if(!$test)throw new RuntimeException($message);}
class ReminderFakeDao extends PmNotifications {
    public array $rows=[];public array $writes=[];public bool $busy=false;public bool $released=false;public bool $schema=false;public bool $queryFail=false;public bool $releaseFail=false;public int $insertCode=0;public int $insertCount=1;
    public function __construct(){}
    public function preflight(bool $commit=false): void {$this->schema=true;}
    public function validateUsers(): void {}
    public function lockName(string $job): string{return 'lock:'.$job;}
    public function acquire(string $name): bool{return !$this->busy;}
    public function release(string $name): void{$this->released=true;if($this->releaseFail)throw new RuntimeException('SECRET');}
    public function candidates(string $job,string $date,int $afterUser=0,int $afterTask=0): array{if($this->queryFail)throw new RuntimeException('SECRET');return $afterUser?[]:$this->rows;}
    public function insert(array $row,string $job,string $kind,string $date): int{$this->writes[]=[$row,$job,$kind,$date];if($this->insertCode)throw new mysqli_sql_exception($this->insertCode===1062?"SECRET for key 'uq_pm_notification_event'":'SECRET',$this->insertCode);return $this->insertCount;}
}
$zone=new DateTimeZone('Asia/Ho_Chi_Minh');
foreach([['timesheet','2026-10-14 17:29',false],['timesheet','2026-10-14 17:30',true],['timesheet','2026-10-17 18:00',false],['task','2026-10-14 07:59',false],['task','2026-10-14 08:00',true]] as [$job,$time,$expected])verifyReminder(PmReminderService::eligibleTime($job,new DateTimeImmutable($time,$zone))===$expected,'Clock window');
verifyReminder(!PmReminderService::eligibleTime('timesheet',new DateTimeImmutable('2026-10-14 17:00',new DateTimeZone('UTC'))),'VN midnight clock');
$dao=new ReminderFakeDao();$dao->rows=[['recipient_id'=>7,'entity_id'=>0]];$service=new PmReminderService($dao);$now=new DateTimeImmutable('2026-10-14 18:00',$zone);
$out=$service->run('timesheet',false,$now);verifyReminder($out['candidates']===1&&!$dao->writes&&!$dao->schema&&!$dao->released,'Dry run no write/schema/lock');
$dao->rows=array_fill(0,100,['recipient_id'=>7,'entity_id'=>0]);$out=$service->run('timesheet',false,$now);verifyReminder($out['candidates']===100&&!$dao->writes,'Chunk cursor termination');$dao->rows=[['recipient_id'=>7,'entity_id'=>0]];
$out=$service->run('timesheet',true,$now);verifyReminder($out['created']===1&&$dao->released&&$dao->schema&&$dao->writes[0][2]==='timesheet_missing','Commit branch');
$dao=new ReminderFakeDao();$dao->busy=true;$out=(new PmReminderService($dao))->run('task',true,$now);verifyReminder($out['skipped_busy']&&!$dao->writes&&!$dao->released,'Busy skip');
$dao=new ReminderFakeDao();$dao->rows=[['recipient_id'=>7,'entity_id'=>11,'due_date'=>'2026-10-13'],['recipient_id'=>7,'entity_id'=>12,'due_date'=>'2026-10-14'],['recipient_id'=>7,'entity_id'=>13,'due_date'=>'2026-10-15']];$out=(new PmReminderService($dao))->run('task',true,$now);verifyReminder(array_column($dao->writes,2)===['task_overdue','task_today','task_tomorrow'],'Deadline kinds');
$dao->writes=[];$dao->insertCode=1062;$out=(new PmReminderService($dao))->run('task',true,$now);verifyReminder($out['duplicate']===3&&$out['failed']===0&&$dao->released,'1062 distinct');
$dao->insertCode=1406;$out=(new PmReminderService($dao))->run('task',true,$now);verifyReminder($out['failed']===3&&$out['duplicate']===0,'Other errors not ignored');
$dao->insertCode=0;$dao->insertCount=0;$out=(new PmReminderService($dao))->run('task',true,$now);verifyReminder($out['stale']===3,'Stale insert select');
$dao->queryFail=true;$dao->released=false;try{(new PmReminderService($dao))->run('task',true,$now);throw new LogicException('Query should fail');}catch(RuntimeException $e){verifyReminder($dao->released,'Finally on query error');}
$dao->queryFail=false;$dao->releaseFail=true;$out=(new PmReminderService($dao))->run('task',true,$now);verifyReminder($out['failed']===1,'Release failure reported');
class ReminderRecordingDb extends PmDb {
    public array $queries=[];
    public function __construct(){}
    private function record(string $sql,string $types,array $params): void{verifyReminder(strlen($types)===count($params)&&substr_count($sql,'?')===count($params),'Prepared binding');$this->queries[]=[$sql,$params];}
    public function fetchAll(string $sql,string $types='',array $params=[]): array{$this->record($sql,$types,$params);return [];}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array{$this->record($sql,$types,$params);return $sql==='SELECT DATABASE() name'?['name'=>'fixture']:null;}
    public function execute(string $sql,string $types='',array $params=[]): int{$this->record($sql,$types,$params);return 1;}
}
$db=new ReminderRecordingDb();$dao=new PmNotifications($db,9);
$dao->candidates('timesheet','2026-10-14');$dao->candidates('task','2026-10-14');$dao->insert(['recipient_id'=>7,'entity_id'=>0],'timesheet','timesheet_missing','2026-10-14');$dao->insert(['recipient_id'=>7,'entity_id'=>11,'due_date'=>'2026-10-14'],'task','task_today','2026-10-14');$dao->list(7,'2026-10-14');verifyReminder(!$dao->markRead(7,999,'2026-10-14'),'Missing/foreign id cannot update');
foreach($db->queries as [$sql,$params]){verifyReminder(in_array(9,$params,true),'Tenant in binds');if(str_contains($sql,'SELECT u.id')||str_contains($sql,'INSERT INTO'))verifyReminder(str_contains($sql,'u.status=1')&&str_contains($sql,'r.status=1')&&str_contains($sql,'pp.store_id=rp.store_id'),'Eligibility/current permissions');}
verifyReminder((new PmNotifications($db,10))->lockName('task')!==$dao->lockName('task')&&$dao->lockName('task')!==$dao->lockName('timesheet'),'Lock scoping');
$sql=$dao->eligibleSql('task');verifyReminder(str_contains($sql,'m.status=1')&&str_contains($sql,"r.code='PM'")&&str_contains($sql,"t.status<>'done'")&&str_contains($sql,'p.deleted_at IS NULL'),'Task eligibility SQL contract');
$ddl=file_get_contents(ROOT_PATH.'database/migrations/011_create_pm_notifications.sql');verifyReminder(str_contains($ddl,'CREATE TABLE IF NOT EXISTS')&&!preg_match('/^\s*(DROP|DELETE|UPDATE|ALTER|TRUNCATE)\b/im',$ddl),'Additive DDL');
try{(new PmNotifications($db,9))->preflight();throw new LogicException('Missing schema accepted');}catch(RuntimeException $e){verifyReminder(str_contains($e->getMessage(),'schema'),'Missing schema fails');}
// Capture fault logs and assert the raw exception sentinel is never emitted.
$log=ROOT_PATH.'.local/pm-reminder-fault-test.log';file_put_contents($log,'');$oldLog=ini_get('error_log');ini_set('error_log',$log);
$fault=new ReminderFakeDao();$fault->rows=[['recipient_id'=>7,'entity_id'=>0]];$fault->insertCode=1406;$fault->releaseFail=true;(new PmReminderService($fault))->run('timesheet',true,$now);ini_set('error_log',$oldLog);
verifyReminder(!str_contains(file_get_contents($log),'SECRET')&&str_contains(file_get_contents($log),'PM reminder insert failed.'),'Fault logs sanitized');
class ReminderSchemaDb extends PmDb {
    public bool $badDefault=false;public bool $badIndex=false;
    public function __construct(){}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array{return ['engine'=>'InnoDB','collation_name'=>'utf8mb4_unicode_ci'];}
    public function fetchAll(string $sql,string $types='',array $params=[]): array {
        if(str_contains($sql,'STATISTICS'))return [['name'=>'PRIMARY','non_unique'=>0,'cols'=>'id'],['name'=>'uq_pm_notification_event','non_unique'=>$this->badIndex?1:0,'cols'=>'store_id,recipient_id,kind,entity_id,event_date'],['name'=>'idx_pm_notification_recipient','non_unique'=>1,'cols'=>'store_id,recipient_id,read_at,id']];
        $types=['id'=>'bigint unsigned','store_id'=>'bigint unsigned','recipient_id'=>'int(10) unsigned','kind'=>'varchar(32)','entity_id'=>'bigint unsigned','event_date'=>'date','read_at'=>'datetime','created_at'=>'datetime'];$rows=[];
        foreach($types as $name=>$type)$rows[]=['name'=>$name,'type'=>$type,'nullable'=>$name==='read_at'?'YES':'NO','extra'=>$name==='id'?'auto_increment':'','default_value'=>$name==='created_at'?'CURRENT_TIMESTAMP()':($name==='entity_id'?'0':($name==='read_at'&&$this->badDefault?'CURRENT_TIMESTAMP()':null))];
        return $rows;
    }
}
$schemaDb=new ReminderSchemaDb();(new PmNotifications($schemaDb,9))->preflight();verifyReminder(true,'Valid schema/int display width');
foreach(['badDefault','badIndex'] as $property){$schemaDb->$property=true;try{(new PmNotifications($schemaDb,9))->preflight();throw new LogicException('Schema mismatch accepted');}catch(RuntimeException $e){verifyReminder(true,'Schema mismatch refused');}$schemaDb->$property=false;}
$cli=ROOT_PATH.'cron/pm-reminders.php';foreach([['--help',0],['--store-id=0',2],['--store-id=9 --commit --date=2026-10-14',2],['--store-id=9 --date=2026-02-30',2]] as [$args,$code]){exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($cli).' '.$args.' 2>&1',$lines,$exit);verifyReminder($exit===$code,'CLI args exit');}
$smarty=new Smarty();$smarty->setTemplateDir(ROOT_PATH.'templates/admin');$smarty->setCompileDir(ROOT_PATH.'.local/smarty');
$row=['id'=>1,'kind'=>'task_today','event_date'=>'2026-10-14','read_at'=>null,'title'=>'Công việc đến hạn hôm nay','task_name'=>'<script>unsafe</script>','url'=>'?op=pmprojects&project_id=11'];
$smarty->assign(['pageTitle'=>'Thông báo','notificationRows'=>[$row],'page'=>1,'totalPages'=>1,'error'=>'','csrfToken'=>'fixture','pmNotificationsEnabled'=>true,'pmNotificationPreview'=>[$row],'pmNotificationUnread'=>1,'pmNavigation'=>PmUiService::navigation(new PmNavigationFixtureAccess(),'pmnotifications')]);
$html=$smarty->fetch('pm-notifications.tpl.html');verifyReminder(!str_contains($html,'<script>unsafe')&&str_contains($html,'&lt;script&gt;unsafe')&&str_contains($html,'name="csrf_token"'),'SSR escaping/CSRF');
file_put_contents(ROOT_PATH.'.local/pm-notifications-preview.html',str_replace('<head>','<head><base href="/">',$html));
$smarty->assign('notificationRows',[]);$smarty->assign('pmNotificationPreview',[]);$smarty->assign('pmNotificationUnread',0);file_put_contents(ROOT_PATH.'.local/pm-notifications-empty.html',str_replace('<head>','<head><base href="/">',$smarty->fetch('pm-notifications.tpl.html')));
echo "PASS: $checks reminder checks; real service branches + recording DAO SQL/binds + CLI validation + Smarty. No DB connected; SQL execution, concurrency, eligibility on real rows and schema/EXPLAIN NOT RUN.\n";
