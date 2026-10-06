<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/services/pmprojectservice.class.php';
require ROOT_PATH.'classes/services/pmtimesheetservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local')throw new RuntimeException('Refusing non-local database.');
// Preserve one outer transaction; service transactions become savepoints.
// This exercises real prepared queries and real role checks without committed fixtures.
class ProjectSmokeConnection extends mysqli {
    public function beginFixture(): bool{return parent::begin_transaction();}
    public function endFixture(): bool{return parent::rollback();}
    public function begin_transaction(int $flags=0,?string $name=null): bool{return $this->query('SAVEPOINT pm_project_smoke_step');}
    public function commit(int $flags=0,?string $name=null): bool{return $this->query('RELEASE SAVEPOINT pm_project_smoke_step');}
    public function rollback(int $flags=0,?string $name=null): bool{$this->query('ROLLBACK TO SAVEPOINT pm_project_smoke_step');return $this->query('RELEASE SAVEPOINT pm_project_smoke_step');}
}
$connection=new ProjectSmokeConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');
$db=(object)['connection'=>$connection];
$admin=$connection->query("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.code='ADMIN' AND r.status=1 ORDER BY u.id LIMIT 1")->fetch_assoc();
if(!$admin)throw new RuntimeException('No local admin fixture available.');
$store=(int)$admin['store_id'];$actor=(int)$admin['id'];$service=new PmProjectService($db,$store,$actor);
$connection->beginFixture();
try{
    $code='TS_'.bin2hex(random_bytes(4));
    $project=$service->saveProject(['code'=>$code,'name'=>'Timesheet QA','manager_id'=>$actor,'status'=>'active','budget'=>'0','start_date'=>'','end_date'=>'']);
    $task=$service->saveTask($project,['name'=>'Assigned QA','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'10','due_date'=>'']);
    $query=new PmDb($db);
    $query->execute("INSERT INTO dc_pm_hourly_rates(store_id,user_id,rate,currency,effective_from,effective_to,status,created_by) VALUES(?,?,?,'VND','2026-06-01','2026-06-02',1,?)",'iisi',[$store,$actor,'1234567890123.45',$actor]);
    $ts=new PmTimesheetService($db,$store,$actor);
    $ts->saveSettings(['standard_hours_per_day'=>'8','ot_multiplier'=>'1.50']);
    $input=['task_id'=>$task,'work_date'=>'2026-06-01','shift_label'=>'Morning','hours'=>'6','description'=>'<script>QA</script>'];
    $first=$ts->save($input);$input['hours']='4';$second=$ts->save($input);
    $row=$ts->getOwn($second);
    $other=$query->fetchOne("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.id<>? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) LIMIT 1",'ii',[$store,$actor]);
    if(!$other)throw new RuntimeException('No second local user for ownership check.');
    $otherId=(int)$other['id'];
    $query->execute("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='EMPLOYEE' AND status=1 ON DUPLICATE KEY UPDATE is_primary=is_primary",'iii',[$store,$otherId,$store]);
    $otherTs=new PmTimesheetService($db,$store,$otherId);
    try{$otherTs->weeklySummary('2026-06-01',$actor);throw new RuntimeException('Another user read weekly totals.');}catch(DomainException $expected){}
    try{$otherTs->getOwn($second);throw new RuntimeException('Another user read timesheet.');}catch(OutOfBoundsException $expected){}
    try{$otherTs->save($input,$second);throw new RuntimeException('Another user edited timesheet.');}catch(OutOfBoundsException $expected){}
    try{$otherTs->delete($second);throw new RuntimeException('Another user deleted timesheet.');}catch(OutOfBoundsException $expected){}
    if(!(new PmAccess($db,$store,$otherId))->hasRole('ADMIN')){
        try{$otherTs->saveSettings(['standard_hours_per_day'=>'1','ot_multiplier'=>'2']);throw new RuntimeException('Non-admin changed OT settings.');}catch(DomainException $expected){}
    }
    if($row['regular_hours']!=='2.00'||$row['ot_hours']!=='2.00')throw new RuntimeException('Daily OT allocation failed.');
    if($row['cost']!=='6172839450617.25')throw new RuntimeException('Exact decimal OT cost failed.');
    $week=$ts->weeklySummary('2026-06-07');
    $aggregate=$query->fetchOne('SELECT SUM(hours) hours,SUM(regular_hours) regular_hours,SUM(ot_hours) ot_hours FROM dc_pm_timesheets WHERE store_id=? AND user_id=? AND work_date=? AND deleted_at IS NULL','iis',[$store,$actor,'2026-06-01']);
    if(count($week['days'])!==7||$week['start']!=='01/06/2026'||$week['end']!=='07/06/2026'||$week['days'][0]['hours']!==$aggregate['hours']||$week['days'][0]['ot_hours']!==$aggregate['ot_hours'])throw new RuntimeException('Weekly persisted aggregation failed.');
    try{$ts->weeklySummary('2026-02-30');throw new RuntimeException('Invalid weekly date accepted.');}catch(InvalidArgumentException $expected){}
    // More than one history page; same user, another user, foreign tenant, deleted row.
    $insert="INSERT INTO dc_pm_timesheets(store_id,user_id,project_id,task_id,work_date,shift_label,hours,regular_hours,ot_hours,deleted_at,rate_snapshot,rate_source,currency,ot_multiplier_snapshot) VALUES(?,?,?,?,?,?,?,?,?,?,0,'fallback','VND',1)";
    for($i=0;$i<21;$i++)$query->execute($insert,'iiiissssss',[$store,$actor,$project,$task,'2099-12-31','Weekly QA','0.01','0.01','0.00',null]);
    foreach([[$store,$otherId,null],[$store+99999,$actor,null],[$store,$actor,'2099-12-31 00:00:00']] as [$testStore,$testUser,$deleted])$query->execute($insert,'iiiissssss',[$testStore,$testUser,$project,$task,'2099-12-31','Weekly excluded','1.00','1.00','0.00',$deleted]);
    $full=$ts->weeklySummary('2100-01-03');
    if($full['start']!=='28/12/2099'||$full['end']!=='03/01/2100'||$full['days'][3]['hours']!=='0.21'||$full['days'][3]['regular_hours']!=='0.21'||$full['days'][3]['has_ot']||$full['days'][0]['has_entries']||$full['days'][0]['hours']!=='0.00')throw new RuntimeException('Weekly pagination/tenant/user/deleted/year boundary failed.');
    if($ts->weeklySummary('2099-12-31',$otherId)['days'][3]['hours']!=='1.00')throw new RuntimeException('Admin edit-target weekly scope failed.');
    $query->execute('UPDATE dc_pm_hourly_rates SET rate=? WHERE store_id=? AND user_id=? AND effective_from=?','siis',['100.25',$store,$actor,'2026-06-01']);
    $input['hours']='4';$ts->save($input,$second);
    if($ts->getOwn($second)['rate_snapshot']!=='1234567890123.45')throw new RuntimeException('Same-day rate snapshot changed.');
    $input['hours']='24';
    try{$ts->save($input);throw new RuntimeException('24 hour limit not enforced.');}catch(DomainException $expected){}
    if($ts->listOwn()['total']<2)throw new RuntimeException('Rollback damaged existing rows.');
    $ts->delete($first);$row=$ts->getOwn($second);
    if($row['regular_hours']!=='4.00'||$row['ot_hours']!=='0.00')throw new RuntimeException('Delete did not recalculate day.');
    $input['hours']='10';$input['work_date']='2026-06-02';$ts->save($input,$second);
    if($ts->getOwn($second)['rate_snapshot']!=='100.25')throw new RuntimeException('Moved date rate did not resolve.');
    if($ts->getOwn($second)['ot_hours']!=='2.00')throw new RuntimeException('Move did not recalculate new day.');
    $ts->saveSettings(['standard_hours_per_day'=>'4','ot_multiplier'=>'2']);
    $input['hours']='1';$third=$ts->save($input);
    if($ts->getOwn($third)['ot_multiplier_snapshot']!=='1.50')throw new RuntimeException('Existing day settings changed.');
    try{(new PmTimesheetService($db,$store+99999,$actor))->save($input);throw new RuntimeException('Cross tenant write accepted.');}catch(DomainException $expected){}
    $input['task_id']=2147483647;
    try{$ts->save($input);throw new RuntimeException('Unassigned task accepted.');}catch(DomainException $expected){}
    $query=new PmDb($db);
    if(!$query->fetchOne("SELECT id FROM dc_pm_audit_logs WHERE store_id=? AND entity_id=? AND action='recalculate'",'ii',[$store,$second]))throw new RuntimeException('Recalculation audit missing.');
}finally{$connection->endFixture();}
$check=$connection->prepare('SELECT id FROM dc_pm_projects WHERE store_id=? AND code=?');$check->bind_param('is',$store,$code);$check->execute();
if($check->get_result()->num_rows)throw new RuntimeException('Fixture rollback failed.');
echo "PASS: daily OT, delete/move recalculation, settings snapshot, 24h rollback, tenant/task boundaries and audit (fixtures rolled back).\n";
