<?php
// Real new parser/service/DAO with recording adapters. Never opens a database.
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/services/pmreminderoptions.class.php';
require ROOT_PATH.'classes/services/pmreminderservice.class.php';
require ROOT_PATH.'classes/services/pmnotificationservice.class.php';
$checks=0;
function throttleCheck(bool $ok,string $name): void {global $checks;$checks++;if(!$ok)throw new RuntimeException($name);}
$base=['--store-id=9'];
foreach([['--commit'],['--commit','--user-ids=1','--all-users'],['--user-ids='],['--user-ids=1,1'],['--user-ids=abc'],['--user-ids=0'],['--user-ids='.implode(',',range(1,101))]] as $args){try{PmReminderOptions::parse([...$base,...$args]);throw new LogicException('Invalid scope accepted');}catch(InvalidArgumentException $e){throttleCheck(true,'Parser rejection');}}
foreach([['--commit','--all-users'],['--commit','--user-ids=1,2'],['--user-ids=1'],[]] as $args){$o=PmReminderOptions::parse([...$base,...$args]);throttleCheck($o['store']===9,'Valid parser scope');}
$cli=ROOT_PATH.'cron/pm-reminders.php';
foreach(['--store-id=9 --commit','--store-id=9 --commit --user-ids=1 --all-users','--store-id=9 --commit --user-ids=abc'] as $args){$lines=[];exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($cli).' '.$args.' 2>&1',$lines,$exit);throttleCheck($exit===2,'Real CLI scope refusal before DB');}
class ThrottleDb extends PmDb {
 public array $calls=[];public bool $snapshot=true;public bool $deadlineKey=true;public bool $recipientKey=true;public bool $badDue=false;public array $userRows=[['id'=>1],['id'=>2]];
 public function __construct(){}
 private function record(string $sql,string $types,array $params): void {throttleCheck(strlen($types)===count($params)&&substr_count($sql,'?')===count($params),'Binding arity');$this->calls[]=[$sql,$params];}
 public function fetchOne(string $sql,string $types='',array $params=[]): ?array{$this->record($sql,$types,$params);if(str_contains($sql,'information_schema.TABLES'))return ['engine'=>'InnoDB','collation_name'=>'utf8mb4_unicode_ci'];return null;}
 public function fetchAll(string $sql,string $types='',array $params=[]): array{
  $this->record($sql,$types,$params);
  if(str_contains($sql,'information_schema.COLUMNS')){
   $types=['id'=>'bigint unsigned','store_id'=>'bigint unsigned','recipient_id'=>'int unsigned','kind'=>'varchar(32)','entity_id'=>'bigint unsigned','event_date'=>'date','read_at'=>'datetime','created_at'=>'datetime'];if($this->snapshot)$types['due_date']='date';$rows=[];
   foreach($types as $name=>$type)$rows[]=['name'=>$name,'type'=>$type,'nullable'=>in_array($name,['read_at','due_date'],true)&&!($name==='due_date'&&$this->badDue)?'YES':'NO','extra'=>$name==='id'?'auto_increment':'','default_value'=>$name==='created_at'?'CURRENT_TIMESTAMP()':($name==='entity_id'?'0':null)];return $rows;
  }
  if(str_contains($sql,'information_schema.STATISTICS')){
   $rows=[['name'=>'PRIMARY','cols'=>'id','non_unique'=>0,'prefixes'=>0],['name'=>'uq_pm_notification_event','cols'=>'store_id,recipient_id,kind,entity_id,event_date','non_unique'=>0,'prefixes'=>0]];
   if($this->recipientKey)$rows[]=['name'=>'idx_pm_notification_recipient','cols'=>'store_id,recipient_id,read_at,id','non_unique'=>1,'prefixes'=>0];
   if($this->deadlineKey)$rows[]=['name'=>'uq_pm_notification_deadline','cols'=>'store_id,recipient_id,kind,entity_id,due_date','non_unique'=>0,'prefixes'=>0];return $rows;
  }
  if(str_starts_with($sql,'SELECT id FROM dc_users'))return $this->userRows;
  return [];
 }
 public function execute(string $sql,string $types='',array $params=[]): int {$this->record($sql,$types,$params);return 1;}
}
$db=new ThrottleDb();$dao=new PmNotifications($db,9,[1,2]);$dao->preflight(true);$dao->validateUsers();throttleCheck(true,'Commit012 full schema');
foreach(['snapshot','deadlineKey','recipientKey'] as $property){$db->$property=false;try{$dao->preflight(true);throw new LogicException('Missing schema accepted');}catch(RuntimeException $e){throttleCheck(true,'Strict preflight refusal');}$db->$property=true;}
$db->badDue=true;try{$dao->preflight(true);throw new LogicException('Bad due definition accepted');}catch(RuntimeException $e){throttleCheck(true,'Nullable due required');}$db->badDue=false;
$db->snapshot=false;$db->deadlineKey=false;$dao->preflight();throttleCheck(true,'Web011 base read allowed');
$db->snapshot=true;$dao->preflight();throttleCheck(true,'Web with optional due column but no012 key allowed');$db->snapshot=false;
$morning=new DateTimeImmutable('2026-10-14 07:00',new DateTimeZone('Asia/Ho_Chi_Minh'));
try{(new PmReminderService($dao))->run('task',true,$morning);throw new LogicException('Commit before-window schema bypass');}catch(RuntimeException $e){throttleCheck(true,'Commit preflight before clock');}
$dry=(new PmReminderService($dao))->run('task',false,$morning);throttleCheck($dry['skipped_window'],'Dry-run no012 allowed');
$db->snapshot=true;$db->deadlineKey=true;$db->userRows=[];
try{$dao->validateUsers();throw new LogicException('Foreign ids accepted');}catch(InvalidArgumentException $e){throttleCheck($e->getMessage()===PmReminderOptions::USER_ERROR,'Foreign/malformed same message');}
$db->userRows=[['id'=>1],['id'=>2]];$db->calls=[];
$dao->candidates('task','2026-10-14');[$sql,$params]=end($db->calls);throttleCheck(str_contains($sql,'t.due_date>=?')&&in_array('2026-10-07',$params,true)&&str_contains($sql,'u.id IN (?,?)'),'Create-only7day and narrow scope');
$dao->candidates('timesheet','2026-10-14');[$sql,$params]=end($db->calls);throttleCheck(str_contains($sql,"dt.status='in_progress'")&&str_contains($sql,'a.work_date=?')&&str_contains($sql,'a.hours>0')&&!str_contains($sql,'start_date'),'Work allocation OR in-progress, no date branch');
$dao->insert(['recipient_id'=>1,'entity_id'=>11,'due_date'=>'2026-10-13'],'task','task_overdue','2026-10-14');[$sql,$params]=end($db->calls);throttleCheck(str_contains($sql,'created_at,due_date)')&&str_contains($sql,'?,?,t.due_date')&&str_contains($sql,'t.due_date=?')&&str_contains($sql,'t.due_date>=?')&&str_contains($sql,'u.id IN (?,?)'),'Snapshot from rechecked source and scope');
$dao->list(1,'2026-10-14');foreach(array_slice($db->calls,-2) as [$sql])throttleCheck(!str_contains($sql,'t.due_date>=?')&&str_contains($sql,'due_date<=?'),'Read/unread unaffected by past window');
throttleCheck(str_contains(end($db->calls)[0],'current_due_date'),'No snapshot/current deadline collision');
class ThrottleDuplicateDao extends PmNotifications {
 public string $key='uq_pm_notification_event';public bool $released=false;
 public function __construct(){}
 public function preflight(bool $commit=false): void {}
 public function validateUsers(): void {}
 public function lockName(string $job): string{return 'test';}
 public function acquire(string $name): bool{return true;}
 public function release(string $name): void{$this->released=true;}
 public function candidates(string $job,string $date,int $afterUser=0,int $afterTask=0): array{return [['recipient_id'=>1,'entity_id'=>11,'due_date'=>'2026-10-13']];}
 public function insert(array $row,string $job,string $kind,string $date): int {throw new mysqli_sql_exception("Duplicate entry 'fixture' for key 'dc_pm_notifications.".$this->key."'",1062);}
}
$duplicate=new ThrottleDuplicateDao();foreach(['uq_pm_notification_event'=>'duplicate_event','uq_pm_notification_deadline'=>'duplicate_deadline'] as $key=>$counter){$duplicate->released=false;$duplicate->key=$key;$out=(new PmReminderService($duplicate))->run('task',true,$morning->setTime(18,0));throttleCheck($out['duplicate']===1&&$out[$counter]===1&&$out['failed']===0&&$duplicate->released,'1062 classified by key');}
$duplicate->key='PRIMARY';$out=(new PmReminderService($duplicate))->run('task',true,$morning->setTime(18,0));throttleCheck($out['failed']===1&&$out['duplicate']===0,'Unknown1062 not hidden as reminded');
class ThrottleReadDao extends PmNotifications {
 public function __construct(){}
 public function preflight(bool $commit=false): void {}
 public function list(int $actor,string $date,int $page=1,int $size=20): array{return ['rows'=>[['kind'=>'task_overdue','due_date'=>'2026-10-13','current_due_date'=>'2026-10-14','project_id'=>11]],'total'=>1,'unread'=>1,'page'=>1];}
}
$read=(new PmNotificationService(new ThrottleReadDao(),1,$morning))->listing();throttleCheck($read['rows'][0]['title']==='Công việc đến hạn hôm nay'&&$read['rows'][0]['url']==='?op=pmprojects&project_id=11','Current task deadline not snapshot drives title; injectable read clock');
echo "PASS: $checks throttle checks, parser/scope/strict012 preflight/read011/SQL contracts/two1062 classifications; no database connected. SQL/HTTP integration NOT RUN.\n";
