<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/services/pmprojectservice.class.php';
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
    $data=['code'=>'QA_'.bin2hex(random_bytes(4)),'name'=>'Project smoke','manager_id'=>$actor,'status'=>'active','budget'=>'1000','start_date'=>'2026-01-01','end_date'=>'2026-12-31'];
    $id=$service->saveProject($data);
    $adminAccess=new PmAccess($db,$store,$actor);
    if(!$adminAccess->hasProjectAccess($id))throw new RuntimeException('Existing project access failed.');
    if($service->getProject($id)['name']!=='Project smoke')throw new RuntimeException('Project creation failed.');
    $data['name']='Project updated';$service->saveProject($data,$id);
    if($service->getProject($id)['name']!=='Project updated')throw new RuntimeException('Project update failed.');
    try{(new PmProjectService($db,$store+999999,$actor))->getProject($id);throw new RuntimeException('Cross tenant read accepted.');}catch(DomainException|OutOfBoundsException $expected){}
    $task=['name'=>'Smoke task','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'4','due_date'=>'2026-02-01'];
    $taskId=$service->saveTask($id,$task);$task['status']='done';$service->saveTask($id,$task,$taskId);
    if($service->tasks($id)[0]['status']!=='done')throw new RuntimeException('Kanban update failed.');
    $task['assignee_id']=2147483647;
    try{$service->saveTask($id,$task);throw new RuntimeException('Non-member assignee accepted.');}catch(InvalidArgumentException $expected){}
    $s=$connection->prepare("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) ORDER BY u.id LIMIT 1");$s->bind_param('i',$store);$s->execute();$outsider=$s->get_result()->fetch_assoc();$s->close();
    if(!$outsider)throw new RuntimeException('No non-admin user available to verify PM boundaries.');
    $other=(int)$outsider['id'];
    $s=$connection->prepare("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='PM' AND status=1 ON DUPLICATE KEY UPDATE is_primary=is_primary");$s->bind_param('iii',$store,$other,$store);$s->execute();$s->close();
    $pm=new PmProjectService($db,$store,$other);
    try{$pm->getProject($id);throw new RuntimeException('Non-member read accepted.');}catch(DomainException $expected){}
    $service->setMember($id,$other,true);
    try{$service->setMember($id,2147483647,false);throw new RuntimeException('Unknown membership removal accepted.');}catch(OutOfBoundsException $expected){}
    try{$pm->saveTask($id,['name'=>'Forbidden task']);throw new RuntimeException('PM managed another manager project.');}catch(DomainException $expected){}
    $task['assignee_id']=$other;$task['status']='todo';$service->saveTask($id,$task,$taskId);
    try{$service->setMember($id,$other,false);throw new RuntimeException('Assigned member removal accepted.');}catch(DomainException $expected){}
    $service->deleteTask($id,$taskId);$service->setMember($id,$other,false);
    if($service->tasks($id)!==[])throw new RuntimeException('Soft deleted task still visible.');
    $service->deleteProject($id);
    if($adminAccess->hasProjectAccess($id))throw new RuntimeException('Deleted project access accepted.');
    try{$service->getProject($id);throw new RuntimeException('Soft deleted project still visible.');}catch(OutOfBoundsException $expected){}
}finally{$connection->endFixture();}
$s=$connection->prepare('SELECT id FROM dc_pm_projects WHERE store_id=? AND code=?');$s->bind_param('is',$store,$data['code']);$s->execute();
if($s->get_result()->num_rows!==0)throw new RuntimeException('Fixture rollback did not remove project data.');$s->close();
echo "PASS: project CRUD, membership, task assignment, Kanban, tenant and PM boundaries (rollback).\n";
