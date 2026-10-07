<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/services/pmprojectservice.class.php';
require ROOT_PATH.'classes/services/pmtimesheetservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
class WindowSmokeConnection extends mysqli {
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
    $data=['task_id'=>$task,'work_date'=>'2026-10-02','shift_label'=>'Day','hours'=>'6','description'=>'Window QA'];
    $id=$user->save($data);$data['hours']='4';$second=$user->save($data);
    $user->date='2026-10-04';$user->save($data,$second);
    if($user->isLocked('2026-10-02'))throw new RuntimeException('Locked before third day ended.');
    $user->date='2026-10-05';
    if(!$user->isLocked('2026-10-02'))throw new RuntimeException('Fourth-day boundary failed.');
    $count=$query->fetchOne('SELECT COUNT(*) total FROM dc_pm_audit_logs WHERE store_id=?','i',[$store])['total'];
    foreach(['save','delete','move','backdate','future'] as $operation){
        try{
            if($operation==='delete')$user->delete($id);
            else{$attempt=$data;if($operation==='move')$attempt['work_date']='2026-10-05';if($operation==='future')$attempt['work_date']='2026-10-06';$user->save($attempt,in_array($operation,['backdate','future'],true)?null:$id);}
            throw new RuntimeException('Locked write accepted: '.$operation);
        }catch(DomainException|InvalidArgumentException $expected){}
    }
    if($query->fetchOne('SELECT COUNT(*) total FROM dc_pm_audit_logs WHERE store_id=?','i',[$store])['total']!==$count)throw new RuntimeException('Denied operation wrote audit.');
    $adminService->date='2026-10-05';$data['hours']='7';$adminService->save($data,$id);
    if((int)$adminService->getTimesheet($id)['user_id']!==$owner||$user->getOwn($second)['ot_hours']!=='3.00')throw new RuntimeException('Admin override changed owner or recalculated wrong day.');
    if(!$query->fetchOne("SELECT id FROM dc_pm_audit_logs WHERE store_id=? AND actor_id=? AND entity_id=? AND action='admin_update_locked'",'iii',[$store,$actor,$id]))throw new RuntimeException('Admin locked-update audit missing.');
    $projects->deleteTask($project,$task);
    $data['hours']='8';$adminService->save($data,$id);
    $adminService->delete($id);
    if($user->getOwn($second)['regular_hours']!=='4.00'||$user->getOwn($second)['ot_hours']!=='0.00')throw new RuntimeException('Admin delete failed to recalculate owner day.');
    if(!$query->fetchOne("SELECT id FROM dc_pm_audit_logs WHERE store_id=? AND entity_id=? AND action='admin_delete_locked'",'ii',[$store,$id]))throw new RuntimeException('Admin locked-delete audit missing.');
    try{(new WindowSmokeService($db,$store+99999,$actor))->getTimesheet($second);throw new RuntimeException('Cross-tenant Admin read accepted.');}catch(DomainException|OutOfBoundsException $expected){}
}finally{$connection->endFixture();}
if($query->fetchOne('SELECT id FROM dc_pm_projects WHERE store_id=? AND code=?','is',[$store,$code]))throw new RuntimeException('Fixture rollback failed.');
echo "PASS: three-day boundaries, locked/future/backdate denial, Admin owner override, deleted-task correction, recalculation, special audit and fixture rollback.\n";
