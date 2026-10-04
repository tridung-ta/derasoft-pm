<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/services/pmprojectservice.class.php';
require ROOT_PATH.'classes/services/pmallocationservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
class AllocationFixtureConnection extends mysqli {
    public function beginFixture(): bool{return parent::begin_transaction();}
    public function endFixture(): bool{return parent::rollback();}
    public function begin_transaction(int $flags=0,?string $name=null): bool{return $this->query('SAVEPOINT allocation_step');}
    public function commit(int $flags=0,?string $name=null): bool{return $this->query('RELEASE SAVEPOINT allocation_step');}
    public function rollback(int $flags=0,?string $name=null): bool{$this->query('ROLLBACK TO SAVEPOINT allocation_step');return $this->query('RELEASE SAVEPOINT allocation_step');}
}
$connection=new AllocationFixtureConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$db=(object)['connection'=>$connection];$q=new PmDb($db);
$admin=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");
if(!$admin)throw new RuntimeException('No Admin.');$store=(int)$admin['store_id'];$actor=(int)$admin['id'];
$other=$q->fetchOne("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) LIMIT 1",'i',[$store]);if(!$other)throw new RuntimeException('No non-Admin.');$owner=(int)$other['id'];
function allocationCheck(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function allocationDenied(callable $fn): void {try{$fn();}catch(InvalidArgumentException|DomainException|OutOfBoundsException $e){return;}throw new RuntimeException('Expected rejection.');}
$connection->beginFixture();
try{
    $q->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='PM'",'i',[$store]);
    $q->execute("INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='PM'",'iii',[$store,$owner,$store]);
    $projects=new PmProjectService($db,$store,$actor);$code='ALLOC_'.bin2hex(random_bytes(4));
    $p1=$projects->saveProject(['code'=>$code,'name'=>'Hidden project <script>','manager_id'=>$actor,'status'=>'active','budget'=>'0','start_date'=>'','end_date'=>'']);
    $p2=$projects->saveProject(['code'=>$code.'_PM','name'=>'Managed project','manager_id'=>$owner,'status'=>'active','budget'=>'0','start_date'=>'','end_date'=>'']);
    $projects->setMember($p1,$owner,true);$projects->setMember($p2,$actor,true);
    $taskData=['name'=>'Allocation task','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'4','due_date'=>''];
    $t1=$projects->saveTask($p1,$taskData);$t2=$projects->saveTask($p2,$taskData);$taskData['assignee_id']=$owner;$t3=$projects->saveTask($p2,$taskData);
    $service=new PmAllocationService($db,$store,$actor);$dao=new PmAllocations($db,$store);
    $data=['task_id'=>$t1,'user_id'=>$actor,'work_date'=>'2026-12-31','hours'=>'3','start_time'=>'09:00','end_time'=>'12:00'];
    $first=$service->save($data)['id'];
    allocationCheck($service->get($first)['hours']==='3.00','CRUD failed.');
    allocationCheck($dao->overlapping($actor,'2026-12-31','11:00','13:00'),'Partial overlap right.');
    allocationCheck($dao->overlapping($actor,'2026-12-31','08:00','10:00'),'Partial overlap left.');
    allocationCheck($dao->overlapping($actor,'2026-12-31','08:00','13:00'),'New contains existing.');
    allocationCheck($dao->overlapping($actor,'2026-12-31','10:00','11:00'),'Existing contains new.');
    allocationCheck($dao->overlapping($actor,'2026-12-31','09:00','12:00'),'Exact overlap.');
    foreach([['12:00','14:00'],['07:00','09:00'],['13:00','14:00'],[null,'12:00'],['09:00',null],[null,null]] as [$s,$e])allocationCheck(!$dao->overlapping($actor,'2026-12-31',$s,$e),'Adjacent/separate/unknown falsely overlaps.');
    allocationCheck(!$dao->overlapping($actor,'2026-12-31','09:00','12:00',$first),'Exclude ID failed.');
    foreach([['hours'=>'0'],['hours'=>'24.01'],['hours'=>'1.001'],['work_date'=>'2026-02-30'],['start_time'=>'','end_time'=>'12:00'],['start_time'=>'23:00','end_time'=>'01:00'],['hours'=>'2'],['user_id'=>$owner],['task_id'=>999999999]] as $invalid)allocationDenied(fn()=>$service->save(array_replace($data,$invalid)));
    $capacity=['user_id'=>$actor,'daily_limit_hours'=>'8','weekly_limit_hours'=>'40','effective_from'=>'2026-12-28','effective_to'=>'2027-01-03'];
    $cap=$service->saveCapacity($capacity);
    foreach([['2026-12-29','2027-01-01'],['2026-12-01','2027-02-01'],['2027-01-03','2027-01-04'],['2026-12-20','2026-12-28'],['2026-12-20',null]] as [$from,$to])allocationCheck($service->hasOverlappingCapacity($actor,$from,$to),'Capacity symmetric overlap failed.');
    allocationCheck(!$service->hasOverlappingCapacity($actor,'2027-01-04',null),'Adjacent capacity failed.');
    allocationCheck(!$service->hasOverlappingCapacity($actor,'2026-12-28','2027-01-03',$cap),'Capacity exclude ID.');
    allocationDenied(fn()=>$service->saveCapacity(array_replace($capacity,['effective_from'=>'2027-01-03','effective_to'=>null])));
    $service->saveCapacity($capacity,$cap);
    $second=$service->save(array_replace($data,['hours'=>'5','start_time'=>'12:00','end_time'=>'17:00']));
    allocationCheck(!in_array('Vượt ngưỡng phân bổ ngày.',$second['warnings'],true),'Exactly daily limit is not overload.');
    $third=$service->save(array_replace($data,['hours'=>'1','start_time'=>'11:00','end_time'=>'12:00']));
    allocationCheck(in_array('Vượt ngưỡng phân bổ ngày.',$third['warnings'],true)&&in_array('Trùng khoảng giờ với một phân bổ khác.',$third['warnings'],true),'Load or overlap warning missing.');
    $service->delete($third['id']);allocationDenied(fn()=>$service->get($third['id']));
    // Move, fractional rounding, missing schedule and historical load.
    $service->save(array_replace($data,['work_date'=>'2027-01-01','hours'=>'0.02','start_time'=>'09:00','end_time'=>'09:01']),$first);
    allocationCheck($service->get($first)['work_date']==='2027-01-01','Move date failed.');
    $unknown=$service->save(array_replace($data,['work_date'=>'2027-01-01','hours'=>'8','start_time'=>'','end_time'=>'']));
    allocationCheck(in_array('Chưa đủ lịch chi tiết để xác nhận trùng giờ.',$unknown['warnings'],true),'Unknown interval warning missing.');
    $service->save(array_replace($data,['work_date'=>'2026-12-28','hours'=>'24','start_time'=>'','end_time'=>'']));
    $weekly=$service->save(array_replace($data,['work_date'=>'2026-12-29','hours'=>'16','start_time'=>'','end_time'=>'']));
    allocationCheck(in_array('Vượt ngưỡng phân bổ tuần (ngưỡng tại thứ Hai).',$weekly['warnings'],true),'Week spanning year / weekly limit failed.');
    $rows=$service->dashboard(['week'=>'2027-01-01','project_id'=>$p1]);allocationCheck($rows['from']==='2026-12-28'&&$rows['to']==='2027-01-03','Week boundary failed.');
    // Midweek capacity: daily follows date; weekly anchored Monday.
    $service->deleteCapacity($cap);
    $mid=$service->saveCapacity(array_replace($capacity,['effective_from'=>'2026-12-31','daily_limit_hours'=>'4','weekly_limit_hours'=>'20']));
    $rows=$service->dashboard(['week'=>'2027-01-01','project_id'=>$p1]);foreach($rows['rows'] as $row)if($row['work_date']==='2026-12-31')allocationCheck($row['daily_limit']==='4.00'&&$row['weekly_limit']===$q->fetchOne('SELECT weekly_limit_hours FROM dc_users WHERE store_id=? AND id=?','ii',[$store,$actor])['weekly_limit_hours'],'Midweek capacity rule failed.');
    $service->deleteCapacity($mid);allocationCheck(!$service->hasOverlappingCapacity($actor,'2026-12-31','2027-01-03'),'Deleted override overlaps.');
    // Employee only reads own; HR has no allocation role.
    $q->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=? AND code IN ('PM','HR')",'i',[$store]);
    $q->execute("INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='EMPLOYEE'",'iii',[$store,$owner,$store]);
    $ownId=$service->save(['task_id'=>$t3,'user_id'=>$owner,'work_date'=>'2026-12-31','hours'=>'1'])['id'];
    $employee=new PmAllocationService($db,$store,$owner);allocationCheck(count($employee->dashboard(['week'=>'2026-12-31'])['rows'])===1,'Employee list scope.');
    allocationDenied(fn()=>$employee->get($first));allocationDenied(fn()=>$employee->save($data));allocationDenied(fn()=>$employee->delete($ownId));allocationDenied(fn()=>$employee->suggestions($p2,'2026-12-31','1'));
    $q->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=? AND code='EMPLOYEE'",'i',[$store]);$q->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='HR'",'i',[$store]);
    $q->execute("INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='HR'",'iii',[$store,$owner,$store]);allocationDenied(fn()=>(new PmAllocationService($db,$store,$owner))->dashboard());
    $q->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='PM'",'i',[$store]);$q->execute("INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='PM'",'iii',[$store,$owner,$store]);
    $pm=new PmAllocationService($db,$store,$owner);allocationDenied(fn()=>$pm->dashboard(['project_id'=>$p1]));allocationDenied(fn()=>$pm->get($first));allocationDenied(fn()=>$pm->saveCapacity($capacity));
    $managed=$service->save(['task_id'=>$t2,'user_id'=>$actor,'work_date'=>'2026-12-31','hours'=>'1'])['id'];
    $view=$pm->dashboard(['week'=>'2026-12-31','project_id'=>$p2]);
    foreach($view['rows'] as $row)if((int)$row['id']===$managed)allocationCheck($row['day_total']==='6.00','Outside-project load omitted or duplicated.');
    allocationCheck(!str_contains(json_encode($view),'Hidden project'),'PM leaked outside project name.');
    allocationCheck($view['capacities']===[]&&$view['users']===[],'PM leaked capacity administration.');
    $suggestions=$pm->suggestions($p2,'2026-12-31','24');allocationCheck($suggestions===[],'Over-capacity suggestions.');
    $pm->save(['task_id'=>$t2,'user_id'=>$actor,'work_date'=>'2027-01-04','hours'=>'1']);
    $openCap=$service->saveCapacity(['user_id'=>$actor,'daily_limit_hours'=>'12','weekly_limit_hours'=>'10','effective_from'=>'2027-01-04','effective_to'=>'']);
    allocationCheck($service->hasOverlappingCapacity($actor,'2030-01-01',null),'Open-ended capacity failed.');
    $exactWeek=$service->save(['task_id'=>$t1,'user_id'=>$actor,'work_date'=>'2027-01-04','hours'=>'9']);
    allocationCheck(!in_array('Vượt ngưỡng phân bổ tuần (ngưỡng tại thứ Hai).',$exactWeek['warnings'],true),'Exactly weekly limit is not overload.');
    $overWeek=$service->save(['task_id'=>$t1,'user_id'=>$actor,'work_date'=>'2027-01-05','hours'=>'0.01']);
    allocationCheck(in_array('Vượt ngưỡng phân bổ tuần (ngưỡng tại thứ Hai).',$overWeek['warnings'],true),'Fractional weekly exceed missing.');
    $service->deleteCapacity($openCap);
    $service->save(['task_id'=>$t3,'user_id'=>$owner,'work_date'=>'2027-01-05','hours'=>'1'],$first);
    allocationCheck((int)$service->get($first)['user_id']===$owner,'Move user/task failed.');
    $q->execute('UPDATE dc_pm_project_members SET status=0 WHERE store_id=? AND project_id=? AND user_id=?','iii',[$store,$p2,$actor]);allocationDenied(fn()=>$service->save(['task_id'=>$t2,'user_id'=>$actor,'work_date'=>'2027-01-05','hours'=>'1']));$q->execute('UPDATE dc_pm_project_members SET status=1 WHERE store_id=? AND project_id=? AND user_id=?','iii',[$store,$p2,$actor]);
    $beforeSuggestions=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_allocations')['total'];
    allocationCheck(count($pm->suggestions($p2,'2030-01-01','1'))===2,'Available membership suggestions missing.');
    allocationCheck($q->fetchOne('SELECT COUNT(*) total FROM dc_pm_allocations')['total']===$beforeSuggestions,'Suggestions wrote allocations.');
    allocationDenied(fn()=>(new PmAllocationService($db,$store+99999,$actor))->dashboard());
    foreach([['week'=>['x']],['week'=>'2026-02-30'],['project_id'=>'-1']] as $filter)allocationDenied(fn()=>$service->dashboard($filter));
    $projects->deleteTask($p1,$t1);allocationDenied(fn()=>$service->save($data));
    $view=$service->dashboard(['week'=>'2027-01-01','project_id'=>$p1]);allocationCheck((bool)$view['rows'][0]['historical'],'Historical task label missing.');
    $projects->deleteProject($p1);$view=$service->dashboard(['week'=>'2027-01-01','project_id'=>$p1]);allocationCheck(count($view['rows'])>0,'Historical project load disappeared.');
    allocationCheck((int)$q->fetchOne("SELECT COUNT(*) total FROM dc_pm_audit_logs WHERE store_id=? AND entity_type IN ('allocation','capacity')",'i',[$store])['total']>0,'Missing audit.');
    echo "PASS groups 1–4: allocation CRUD/audit, exact and exceeded capacity, time-bound symmetric overlap, all half-open interval cases, year boundary, role/tenant scopes, outside-project load, suggestions and soft-delete history. Fixtures rolled back.\n";
}finally{$connection->endFixture();}
