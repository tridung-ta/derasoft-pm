<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/services/pmprojectservice.class.php';
require ROOT_PATH.'classes/services/pmtimesheetservice.class.php';
require ROOT_PATH.'classes/dao/pmauditlogs.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
class WindowSmokeConnection extends mysqli {
    public function prepare(string $query): mysqli_stmt|false {
        // MySQL cannot reopen one temporary table for the HR self-join.
        // Refresh a second exact-schema shadow before each such SELECT.
        if(str_contains($query,'dc_users owner JOIN dc_users viewer')){
            parent::query('DELETE FROM pm_audit_viewer_users');
            parent::query('INSERT INTO pm_audit_viewer_users SELECT * FROM dc_users');
            $query=str_replace('JOIN dc_users viewer','JOIN pm_audit_viewer_users viewer',$query);
        }
        return parent::prepare($query);
    }
    public function beginFixture(): bool{return parent::begin_transaction();}
    public function endFixture(): bool{return parent::rollback();}
    public function begin_transaction(int $flags=0,?string $name=null): bool{return $this->query('SAVEPOINT pm_window_step');}
    public function commit(int $flags=0,?string $name=null): bool{return $this->query('RELEASE SAVEPOINT pm_window_step');}
    public function rollback(int $flags=0,?string $name=null): bool{$this->query('ROLLBACK TO SAVEPOINT pm_window_step');return $this->query('RELEASE SAVEPOINT pm_window_step');}
}
class WindowSmokeService extends PmTimesheetService {
    public string $date='2026-10-02';
    protected function currentDate(): DateTimeImmutable{return new DateTimeImmutable($this->date,new DateTimeZone('Asia/Ho_Chi_Minh'));}
}
$connection=new WindowSmokeConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');
$db=(object)['connection'=>$connection];$query=new PmDb($db);
// MyISAM cannot roll back user changes. Shadow the exact local schema and keep
// all copied rows connection-local; no real account data is written or logged.
$persistentUsers=$query->fetchAll('SELECT * FROM dc_users ORDER BY id');
$userDdl=$connection->query('SHOW CREATE TABLE dc_users')->fetch_row()[1];
if(!str_contains($userDdl,'ENGINE=MyISAM'))throw new RuntimeException('Unexpected user engine.');
$connection->query(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$userDdl));
$connection->query(str_replace(['CREATE TABLE','`dc_users`'],['CREATE TEMPORARY TABLE','`pm_audit_viewer_users`'],$userDdl));
if($persistentUsers){
    $columns=array_keys($persistentUsers[0]);
    foreach($columns as $column)if(!preg_match('/^[A-Za-z0-9_]+$/D',$column))throw new RuntimeException('Unsafe schema identifier.');
    $insert='INSERT INTO dc_users (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')';
    foreach($persistentUsers as $userRow)$query->execute($insert,str_repeat('s',count($columns)),array_values($userRow));
}
$admin=$query->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");
if(!$admin)throw new RuntimeException('No local Admin.');
$store=(int)$admin['store_id'];$actor=(int)$admin['id'];
$employee=$query->fetchOne("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) LIMIT 1",'i',[$store]);
if(!$employee)throw new RuntimeException('No non-admin local user.');$owner=(int)$employee['id'];
$connection->beginFixture();
try{
    $query->execute("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='EMPLOYEE' AND status=1 ON DUPLICATE KEY UPDATE is_primary=is_primary",'iii',[$store,$owner,$store]);
    $projects=new PmProjectService($db,$store,$actor);$code='WINDOW_'.bin2hex(random_bytes(4));
    $project=$projects->saveProject(['code'=>$code,'name'=>'Window QA','manager_id'=>$actor,'status'=>'active','budget'=>'0','start_date'=>'','end_date'=>'']);
    $projects->setMember($project,$owner,true);
    $task=$projects->saveTask($project,['name'=>'Window task','assignee_id'=>$owner,'status'=>'todo','priority'=>'normal','estimated_hours'=>'12','due_date'=>'']);
    $user=new WindowSmokeService($db,$store,$owner);$adminService=new WindowSmokeService($db,$store,$actor);
    $adminService->saveSettings(['standard_hours_per_day'=>'8','ot_multiplier'=>'1.50']);
    $auditData=['task_id'=>$task,'work_date'=>'2026-10-02','shift_label'=>'Audit','hours'=>'4','description'=>'Audit QA'];
    $timesheet=$user->save($auditData);
    $adminAudit=new PmAuditLogs($db,$store,$actor);
    if(!$adminAudit->list(['entity_type'=>'timesheet'])['total'])throw new RuntimeException('Admin audit missing.');
    try{(new PmAuditLogs($db,$store+99999,$actor))->list();throw new RuntimeException('Foreign audit allowed.');}catch(DomainException $expected){}
    try{$adminAudit->list(['action'=>"' OR 1=1"]);throw new RuntimeException('Invalid filter accepted.');}catch(InvalidArgumentException $expected){}
    // The owner has no ADMIN role. Exercise PM and HR independently in this rolled-back fixture.
    $query->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=? AND code IN ('PM','HR')",'i',[$store]);
    try{(new PmAuditLogs($db,$store,$owner))->list();throw new RuntimeException('Employee audit allowed.');}catch(DomainException $expected){}
    $query->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='PM'",'i',[$store]);
    $query->execute("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='PM' ON DUPLICATE KEY UPDATE is_primary=is_primary",'iii',[$store,$owner,$store]);
    if((new PmAuditLogs($db,$store,$owner))->list()['total'])throw new RuntimeException('PM viewed another manager project.');
    $query->execute('UPDATE dc_pm_projects SET manager_id=? WHERE store_id=? AND id=?','iii',[$owner,$store,$project]);
    $pmRows=(new PmAuditLogs($db,$store,$owner))->list()['rows'];
    if(!$pmRows)throw new RuntimeException('PM managed-project audit missing.');
    $taskAudit=(new PmAuditLogs($db,$store,$owner))->list(['entity_type'=>'task']);
    if(!$taskAudit['total']||!isset(json_decode($taskAudit['rows'][0]['new_values'],true)['name']))throw new RuntimeException('PM task audit scope or task snapshot missing.');
    foreach($pmRows as $row)foreach(['old_values','new_values'] as $column)if($row[$column]!==null&&str_contains($row[$column],'rate_snapshot'))throw new RuntimeException('PM rate snapshot leaked.');
    $query->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=? AND code='PM'",'i',[$store]);
    $query->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='HR'",'i',[$store]);
    $query->execute("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='HR' ON DUPLICATE KEY UPDATE is_primary=is_primary",'iii',[$store,$owner,$store]);
    $query->execute('UPDATE dc_users SET department_id=NULL WHERE store_id=? AND id=?','ii',[$store,$owner]);
    $adminTask=$projects->saveTask($project,['name'=>'Other department task','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'4','due_date'=>'']);
    $auditData['task_id']=$adminTask;$otherSheet=$adminService->save($auditData);
    $hrRows=(new PmAuditLogs($db,$store,$owner))->list()['rows'];
    if((new PmAuditLogs($db,$store,$owner))->list(['entity_type'=>'task'])['total'])throw new RuntimeException('HR task audit exposed.');
    if(!$hrRows)throw new RuntimeException('HR own audit missing.');
    foreach($hrRows as $row)if((int)$row['entity_id']===$otherSheet)throw new RuntimeException('HR without department saw another user.');
    foreach($hrRows as $row)if($row['entity_type']!=='timesheet'||str_contains((string)$row['new_values'],'rate_snapshot'))throw new RuntimeException('HR scope/redaction failed.');
    $departmentCode='AUDIT_'.bin2hex(random_bytes(4));
    $query->execute('INSERT INTO dc_pm_departments(store_id,code,name,status) VALUES(?,?,?,1)','iss',[$store,$departmentCode,'Audit department']);
    $department=(int)$query->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
    $query->execute('UPDATE dc_users SET department_id=? WHERE store_id=? AND id IN (?,?)','iiii',[$department,$store,$owner,$actor]);
    $hrRows=(new PmAuditLogs($db,$store,$owner))->list()['rows'];
    if(!in_array($otherSheet,array_map('intval',array_column($hrRows,'entity_id')),true))throw new RuntimeException('HR same-department audit missing.');
    foreach($hrRows as $row)foreach(['old_values','new_values'] as $column)if($row[$column]!==null&&(str_contains($row[$column],'rate_snapshot')||str_contains($row[$column],'cost')))throw new RuntimeException('HR financial data leaked.');
    $query->execute('UPDATE dc_users SET department_id=NULL WHERE store_id=? AND id=?','ii',[$store,$actor]);
    $hrRows=(new PmAuditLogs($db,$store,$owner))->list()['rows'];
    foreach($hrRows as $row)if((int)$row['entity_id']===$otherSheet)throw new RuntimeException('HR saw another department.');
    $query->execute('UPDATE dc_users SET department_id=? WHERE store_id=? AND id=?','iii',[$department,$store,$actor]);
    $query->execute('UPDATE dc_pm_departments SET status=0 WHERE store_id=? AND id=?','ii',[$store,$department]);
    foreach((new PmAuditLogs($db,$store,$owner))->list()['rows'] as $row)if((int)$row['entity_id']===$otherSheet)throw new RuntimeException('Inactive department scope accepted.');
}finally{
    $connection->endFixture();
    // A separate connection bypasses the temporary shadow and reads real users.
    $verifyConnection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);
    $verifyConnection->set_charset('utf8mb4');
    $verifyDb=(object)['connection'=>$verifyConnection];$verifyQuery=new PmDb($verifyDb);
    try{
        if($verifyQuery->fetchAll('SELECT * FROM dc_users ORDER BY id')!==$persistentUsers)throw new RuntimeException('Persistent users changed during audit fixture.');
        $engine=$verifyQuery->fetchOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_users'")['ENGINE'];
        if($engine!=='MyISAM')throw new RuntimeException('Persistent user engine changed.');
    }finally{$verifyConnection->close();}
}
if($query->fetchOne('SELECT id FROM dc_pm_projects WHERE store_id=? AND code=?','is',[$store,$code]))throw new RuntimeException('Fixture rollback failed.');
echo "PASS: audit role/tenant/project boundaries, financial redaction, filter validation and fixture rollback; exact-schema temporary users, persistent user rows/engine unchanged.\n";
