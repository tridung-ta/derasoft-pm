<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/services/pmprojectservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local')throw new RuntimeException('Refusing non-local database.');
// Preserve one outer transaction; service transactions become savepoints.
// This exercises real prepared queries and real role checks without committed fixtures.
class ProjectSmokeConnection extends mysqli {
    public array $progressQueries=[];
    public bool $failAudit=false;
    public function prepare(string $query): mysqli_stmt|false {
        if($this->failAudit&&str_starts_with($query,'INSERT INTO dc_pm_audit_logs'))throw new RuntimeException('Injected audit failure');
        if(str_contains($query,"SUM(status='done')"))$this->progressQueries[]=$query;
        return parent::prepare($query);
    }
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
    $data=['code'=>'QA_'.bin2hex(random_bytes(4)),'name'=>'Project smoke','manager_id'=>$actor,'status'=>'active','budget'=>'1000','start_date'=>'2026-01-01','end_date'=>'2026-12-31'];
    $id=$service->saveProject($data);
    $emptyData=$data;$emptyData['code'].='_EMPTY';$emptyData['name']='Empty project';
    $emptyId=$service->saveProject($emptyData);
    $connection->progressQueries=[];$list=$service->listProjects($data['code']);
    if(count($connection->progressQueries)!==1||count($list['rows'])!==2)throw new RuntimeException('Progress must use one page aggregate.');
    foreach($list['rows'] as $r)if($r['task_total']!==0||$r['task_done']!==0||$r['progress_percent']!==null)throw new RuntimeException('Empty project progress invented.');
    $adminAccess=new PmAccess($db,$store,$actor);
    if(!$adminAccess->hasProjectAccess($id))throw new RuntimeException('Existing project access failed.');
    if($service->getProject($id)['name']!=='Project smoke')throw new RuntimeException('Project creation failed.');
    $data['name']='Project updated';$service->saveProject($data,$id);
    if($service->getProject($id)['name']!=='Project updated')throw new RuntimeException('Project update failed.');
    $dateBefore=$service->getProject($id);
    $dateRowCount=$connection->query('SELECT COUNT(*) n FROM dc_pm_projects')->fetch_assoc()['n'];
    foreach([null,$id] as $dateId)foreach(['2026-10-02','2026-10-01'] as $dateEnd){
        $dateData=$data;$dateData['code']='DATE_'.bin2hex(random_bytes(4));$dateData['start_date']='2026-10-02';$dateData['end_date']=$dateEnd;
        try{$service->saveProject($dateData,$dateId);throw new LogicException('Same-day/reversed project accepted.');}catch(PmProjectDateRangeException $expected){}
        if($service->getProject($id)!==$dateBefore)throw new RuntimeException('Invalid project dates overwrote data.');
        if($connection->query('SELECT COUNT(*) n FROM dc_pm_projects')->fetch_assoc()['n']!==$dateRowCount)throw new RuntimeException('Invalid dates created a project.');
    }
    $dayData=$data;$dayData['code']='DAY_'.bin2hex(random_bytes(4));$dayData['start_date']='2026-10-02';$dayData['end_date']='2026-10-03';
    $dayId=$service->saveProject($dayData);$dayData['name']='One-day interval updated';$service->saveProject($dayData,$dayId);
    $dayProject=$service->getProject($dayId);if($dayProject['start_date']!=='2026-10-02'||$dayProject['end_date']!=='2026-10-03')throw new RuntimeException('Next-day project dates rejected.');
    $data['client_name']='Client <script>fixture</script>';$service->saveProject($data,$id);
    if($service->getProject($id)['client_name']!==$data['client_name'])throw new RuntimeException('Client metadata missing.');
    try{(new PmProjectService($db,$store+999999,$actor))->getProject($id);throw new RuntimeException('Cross tenant read accepted.');}catch(DomainException|OutOfBoundsException $expected){}
    $task=['name'=>'Smoke task','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'4','due_date'=>'2026-02-01'];
    $task['start_date']=$task['due_date'];
    $taskId=$service->saveTask($id,$task);$task['status']='done';$service->saveTask($id,$task,$taskId);
    $sameDayTask=$service->tasks($id)[0];if($sameDayTask['start_date']!==$sameDayTask['due_date'])throw new RuntimeException('Same-day task dates rejected.');
    if($service->tasks($id)[0]['status']!=='done')throw new RuntimeException('Kanban update failed.');
    $completed=$service->tasks($id)[0]['completed_at'];if(!$completed)throw new RuntimeException('Completion timestamp missing.');
    $task['start_date']='2026-01-01';$service->saveTask($id,$task,$taskId);
    if($service->tasks($id)[0]['completed_at']!==$completed||$service->tasks($id)[0]['start_date']!=='2026-01-01')throw new RuntimeException('Done edit changed completion or start date lost.');
    try{$service->saveTask($id,array_replace($task,['start_date'=>'2026-03-01']),$taskId);throw new LogicException('Invalid date range accepted');}catch(InvalidArgumentException $expected){}
    $pending=$task;$pending['status']='todo';$pendingId=$service->saveTask($id,$pending);
    $query=new PmDb($db);
    $query->execute("INSERT INTO dc_pm_tasks(store_id,project_id,name,status,created_by) VALUES(?,?,'Other tenant fixture','done',?)",'iii',[$store+999999,$id,$actor]);
    $connection->progressQueries=[];$rows=array_column($service->listProjects($data['code'])['rows'],null,'id');
    if(count($connection->progressQueries)!==1||$rows[$id]['task_total']!==2||$rows[$id]['task_done']!==1||$rows[$id]['progress_percent']!==50.0)throw new RuntimeException('Progress ratio or tenant scope failed.');
    $plan=$query->fetchAll('EXPLAIN '.$connection->progressQueries[0],'iii',[$store,$emptyId,$id]);
    if(!$plan)throw new RuntimeException('Progress query EXPLAIN failed.');
    $thirdId=$service->saveTask($id,$pending);
    $rows=array_column($service->listProjects($data['code'])['rows'],null,'id');
    if($rows[$id]['progress_percent']!==33.3)throw new RuntimeException('Progress rounding failed.');
    $service->deleteTask($id,$thirdId);
    $service->deleteTask($id,$pendingId);
    $rows=array_column($service->listProjects($data['code'])['rows'],null,'id');
    if($rows[$id]['task_total']!==1||$rows[$id]['progress_percent']!==100.0)throw new RuntimeException('Deleted task counted in progress.');
    $connection->progressQueries=[];$none=$service->listProjects('NONEXISTENT_'.bin2hex(random_bytes(6)));
    if($none['rows']||$connection->progressQueries)throw new RuntimeException('Empty page queried task aggregation.');
    $task['assignee_id']=2147483647;
    try{$service->saveTask($id,$task);throw new RuntimeException('Non-member assignee accepted.');}catch(InvalidArgumentException $expected){}
    $s=$connection->prepare("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) ORDER BY u.id LIMIT 1");$s->bind_param('i',$store);$s->execute();$outsider=$s->get_result()->fetch_assoc();$s->close();
    if(!$outsider)throw new RuntimeException('No non-admin user available to verify PM boundaries.');
    $other=(int)$outsider['id'];
    $s=$connection->prepare("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='PM' AND status=1 ON DUPLICATE KEY UPDATE is_primary=is_primary");$s->bind_param('iii',$store,$other,$store);$s->execute();$s->close();
    $pm=new PmProjectService($db,$store,$other);
    try{$pm->getProject($id);throw new RuntimeException('Non-member read accepted.');}catch(DomainException $expected){}
    $service->setMember($id,$other,true);
    $service->setMember($id,$other,true,'qa');
    $memberRows=array_column($service->members($id),null,'user_id');if($memberRows[$other]['project_role']!=='qa')throw new RuntimeException('Project role missing.');
    try{$service->setMember($id,$other,true,'ADMIN');throw new LogicException('RBAC role accepted as project role');}catch(InvalidArgumentException $expected){}
    $visible=$pm->listProjects($data['code']);
    if(count($visible['rows'])!==1||(int)$visible['rows'][0]['id']!==$id)throw new RuntimeException('Progress project-membership scope failed.');
    // Simulate removal of task-view permission in this service's access snapshot.
    // No role grants or persistent permission records are changed.
    $restricted=new PmProjectService($db,$store,$other);
    $access=(new ReflectionProperty(PmProjectService::class,'access'))->getValue($restricted);
    $permissions=new ReflectionProperty(PmAccess::class,'permissionCodes');
    $permissions->setValue($access,array_values(array_filter($access->getPermissionCodes(),static fn(string $code): bool=>$code!=='pm.tasks.view')));
    $connection->progressQueries=[];$restrictedRows=$restricted->listProjects($data['code'])['rows'];
    if(count($restrictedRows)!==1||$connection->progressQueries||$restrictedRows[0]['task_total']!==null||$restrictedRows[0]['task_done']!==null||$restrictedRows[0]['progress_percent']!==null)throw new RuntimeException('Task counts leaked without task-view permission.');
    try{$service->setMember($id,2147483647,false);throw new RuntimeException('Unknown membership removal accepted.');}catch(OutOfBoundsException $expected){}
    try{$pm->saveTask($id,['name'=>'Forbidden task']);throw new RuntimeException('PM managed another manager project.');}catch(DomainException $expected){}
    $task['assignee_id']=$other;$task['status']='todo';$service->saveTask($id,$task,$taskId);
    if($query->fetchOne('SELECT completed_at FROM dc_pm_tasks WHERE store_id=? AND id=?','ii',[$store,$taskId])['completed_at']!==null)throw new RuntimeException('Reopened task kept completion timestamp.');
    $rows=array_column($service->listProjects($data['code'])['rows'],null,'id');
    if($rows[$id]['progress_percent']!==0.0)throw new RuntimeException('Zero progress lost.');
    try{$service->setMember($id,$other,false);throw new RuntimeException('Assigned member removal accepted.');}catch(DomainException $expected){}
    $auditRows=$query->fetchAll("SELECT * FROM dc_pm_audit_logs WHERE store_id=? AND entity_type='task' AND entity_id=? ORDER BY id",'ii',[$store,$taskId]);
    if(count($auditRows)!==4||$auditRows[0]['action']!=='create'||$auditRows[0]['old_values']!==null||(int)$auditRows[0]['actor_id']!==$actor||!$auditRows[0]['created_at'])throw new RuntimeException('Task create audit missing.');
    if(json_decode($auditRows[1]['old_values'],true)['status']!=='todo'||json_decode($auditRows[1]['new_values'],true)['status']!=='done')throw new RuntimeException('Task update audit snapshots wrong.');
    $connection->failAudit=true;
    try{$service->saveTask($id,['name'=>'Must roll back'],$taskId);throw new LogicException('Audit failure accepted');}catch(RuntimeException $expected){if($expected->getMessage()!=='Injected audit failure')throw $expected;}finally{$connection->failAudit=false;}
    if($query->fetchOne('SELECT name FROM dc_pm_tasks WHERE store_id=? AND id=?','ii',[$store,$taskId])['name']!=='Smoke task')throw new RuntimeException('Task edit survived audit failure.');
    $beforeStatus=$query->fetchOne('SELECT * FROM dc_pm_tasks WHERE store_id=? AND id=?','ii',[$store,$taskId]);
    $statusResult=$service->changeTaskStatus($id,$taskId,'done');
    if($statusResult['status']!=='done'||!$statusResult['completed_at'])throw new RuntimeException('Status-only completion failed.');
    $afterStatus=$query->fetchOne('SELECT * FROM dc_pm_tasks WHERE store_id=? AND id=?','ii',[$store,$taskId]);
    foreach(['name','description','assignee_id','priority','estimated_hours','start_date','due_date'] as $field)if($afterStatus[$field]!==$beforeStatus[$field])throw new RuntimeException('Status-only write overwrote task fields.');
    $statusAudit=$query->fetchOne("SELECT * FROM dc_pm_audit_logs WHERE entity_type='task' AND store_id=? AND entity_id=? ORDER BY id DESC LIMIT 1",'ii',[$store,$taskId]);
    if((int)$statusAudit['actor_id']!==$actor||json_decode($statusAudit['old_values'],true)['status']!=='todo'||json_decode($statusAudit['new_values'],true)['status']!=='done')throw new RuntimeException('Status-only audit failed.');
    $auditCount=$query->fetchOne('SELECT COUNT(*) n FROM dc_pm_audit_logs')['n'];
    if($service->changeTaskStatus($id,$taskId,'done')!==$statusResult||$query->fetchOne('SELECT COUNT(*) n FROM dc_pm_audit_logs')['n']!==$auditCount)throw new RuntimeException('Status-only no-op changed completion/audit.');
    foreach([[$service,$id,$taskId,'invalid'],[$service,$id,$taskId+9999999,'done'],[$pm,$id,$taskId,'review'],[new PmProjectService($db,$store+999999,$actor),$id,$taskId,'done']] as [$statusService,$statusProject,$statusTask,$targetState]){
        try{$statusService->changeTaskStatus($statusProject,$statusTask,$targetState);throw new LogicException('Status boundary accepted.');}catch(InvalidArgumentException|DomainException|OutOfBoundsException $expected){}
    }
    $connection->failAudit=true;
    try{$service->changeTaskStatus($id,$taskId,'review');throw new LogicException('Status audit failure accepted.');}catch(RuntimeException $expected){if($expected->getMessage()!=='Injected audit failure')throw $expected;}finally{$connection->failAudit=false;}
    if($query->fetchOne('SELECT status FROM dc_pm_tasks WHERE store_id=? AND id=?','ii',[$store,$taskId])['status']!=='done')throw new RuntimeException('Status update survived audit failure.');
    if($service->changeTaskStatus($id,$taskId,'review')['completed_at']!==null)throw new RuntimeException('Status reopen kept completed_at.');
    $service->deleteTask($id,$taskId);$service->setMember($id,$other,false);
    try{$service->changeTaskStatus($id,$taskId,'todo');throw new LogicException('Hidden task status accepted.');}catch(OutOfBoundsException $expected){}
    $deletedAudit=$query->fetchOne("SELECT new_values FROM dc_pm_audit_logs WHERE store_id=? AND entity_type='task' AND entity_id=? AND action='soft_delete'",'ii',[$store,$taskId]);
    if(!json_decode($deletedAudit['new_values'],true)['deleted_at'])throw new RuntimeException('Task soft delete audit missing.');
    if($service->tasks($id)!==[])throw new RuntimeException('Soft deleted task still visible.');
    $service->deleteProject($id);
    if($adminAccess->hasProjectAccess($id))throw new RuntimeException('Deleted project access accepted.');
    try{$service->getProject($id);throw new RuntimeException('Soft deleted project still visible.');}catch(OutOfBoundsException $expected){}
}finally{$connection->endFixture();}
$s=$connection->prepare('SELECT id FROM dc_pm_projects WHERE store_id=? AND code=?');$s->bind_param('is',$store,$data['code']);$s->execute();
if($s->get_result()->num_rows!==0)throw new RuntimeException('Fixture rollback did not remove project data.');$s->close();
echo "PASS: project CRUD, membership, tasks/Kanban and tenant/PM boundaries; batched progress, empty/zero/50/100, soft delete, task-view denial and aggregate EXPLAIN (rollback).\n";
