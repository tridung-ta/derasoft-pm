<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/services/pmprojectservice.class.php';
require ROOT_PATH.'classes/services/pmtimesheetservice.class.php';
require ROOT_PATH.'classes/services/pmcostservice.class.php';
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
function checkCost(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
try{
    $projects=new PmProjectService($db,$store,$actor);$code='COST_'.bin2hex(random_bytes(4));
    $project=$projects->saveProject(['code'=>$code,'name'=>'<script>Cost QA</script>','manager_id'=>$actor,'status'=>'active','budget'=>'2000','start_date'=>'','end_date'=>'']);
    $task=$projects->saveTask($project,['name'=>'Cost task','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'10','due_date'=>'']);
    $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    $query->execute("INSERT INTO dc_pm_hourly_rates(store_id,user_id,rate,currency,effective_from,effective_to,status,created_by) VALUES(?,?,?,'VND',?,NULL,1,?)",'iissi',[$store,$actor,'100.25','2026-10-01',$actor]);
    $timesheets=new WindowSmokeService($db,$store,$actor);$timesheets->saveSettings(['standard_hours_per_day'=>'8','ot_multiplier'=>'1.50']);
    $entry=['task_id'=>$task,'work_date'=>'2026-10-01','shift_label'=>'Day','hours'=>'6','description'=>'Cost QA'];
    $first=$timesheets->save($entry);$entry['hours']='4';$second=$timesheets->save($entry);$entry['work_date']='2026-10-02';$entry['hours']='2';$third=$timesheets->save($entry);
    $costs=new PmCostService($db,$store,$actor);$filter=['project_id'=>$project,'from'=>'2026-10-01','to'=>'2026-10-01'];
    $data=$costs->dashboard($filter);
    checkCost($data['summary']['cost']==='1102.75'&&$data['summary']['hours']==='10.00'&&$data['summary']['ot_hours']==='2.00','Group 1: actual decimal/OT failed.');
    checkCost($data['projects'][0]['lifetime']['cost']==='1303.25'&&$data['estimate_total']==='1002.50','Group 1: lifetime/estimate/date label failed.');
    checkCost($data['valuation_date']===$today&&count($data['warnings'])>=1,'Valuation/snapshot classification warning missing.');
    $query->execute('UPDATE dc_pm_timesheets SET rate_snapshot=? WHERE store_id=? AND id=?','sii',['9999999999999.99',$store,$first]);
    checkCost($costs->dashboard($filter)['summary']['cost']==='1102.75','Stored cost was recomputed from rate.');
    foreach([['from'=>'2026-02-30'],['from'=>'2026-10-02','to'=>'2026-10-01'],['project_id'=>['1']],['page'=>'-1']] as $invalid){try{$costs->dashboard($invalid);throw new RuntimeException('Group 2: invalid filter accepted.');}catch(InvalidArgumentException $expected){}}
    $empty=$costs->dashboard(['project_id'=>$project,'from'=>'2000-01-01','to'=>'2000-01-01']);
    checkCost($empty['summary']['cost']==='0.00'&&$empty['summary']['entries']===0,'Group 2: empty range failed.');
    $query->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=? AND code IN ('PM','HR')",'i',[$store]);
    try{(new PmCostService($db,$store,$owner))->dashboard();throw new RuntimeException('Group 3: Employee cost access accepted.');}catch(DomainException $expected){}
    $query->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='HR'",'i',[$store]);
    $query->execute("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='HR' ON DUPLICATE KEY UPDATE is_primary=is_primary",'iii',[$store,$owner,$store]);
    try{(new PmCostService($db,$store,$owner))->dashboard();throw new RuntimeException('Group 3: HR cost access accepted.');}catch(DomainException $expected){}
    $query->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='PM'",'i',[$store]);
    $query->execute("INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='PM' ON DUPLICATE KEY UPDATE is_primary=is_primary",'iii',[$store,$owner,$store]);
    $pm=new PmCostService($db,$store,$owner);
    try{$pm->dashboard(['project_id'=>$project]);throw new RuntimeException('Group 3: PM read another manager project.');}catch(DomainException $expected){}
    $pmProject=$projects->saveProject(['code'=>$code.'_PM','name'=>'PM project','manager_id'=>$owner,'status'=>'active','budget'=>'100','start_date'=>'','end_date'=>'']);
    checkCost(count($pm->dashboard(['project_id'=>$pmProject])['projects'])===1,'PM managed-project access failed.');
    foreach($pm->dashboard()['projects'] as $p)checkCost((int)$p['id']!==$project,'PM list leaked other project.');
    try{(new PmCostService($db,$store+99999,$actor))->dashboard();throw new RuntimeException('Cross-tenant cost access accepted.');}catch(DomainException $expected){}
    // Multiple memberships and roles must never duplicate timesheet sums.
    $projects->setMember($project,$owner,true);
    checkCost($costs->dashboard($filter)['summary']['cost']==='1102.75','Group 2: membership/role double counting.');
    // Mixed-currency buckets must have null costs, while homogeneous subranges remain usable.
    $query->execute("UPDATE dc_pm_timesheets SET currency='USD' WHERE store_id=? AND id=?",'ii',[$store,$second]);
    $mixed=$costs->dashboard($filter);
    checkCost($mixed['summary']['cost']===null&&$mixed['summary']['hours']==='10.00','Currency guard suppressed hours or added mixed money.');
    foreach(['project','task','user','department','role','date'] as $g)foreach($mixed['groups'][$g] as $r)checkCost($r['cost']===null&&$r['warning']!==null,'Mixed currency group not guarded: '.$g);
    checkCost($mixed['projects'][0]['lifetime']['cost']===null&&!$mixed['projects'][0]['comparable'],'Mixed lifetime/budget guard failed.');
    $single=$costs->dashboard(['project_id'=>$project,'from'=>'2026-10-02','to'=>'2026-10-02']);
    checkCost($single['summary']['cost']==='200.50','Homogeneous range suppressed by mixed lifetime.');
    $query->execute("UPDATE dc_pm_timesheets SET currency='USD' WHERE store_id=? AND project_id=?",'ii',[$store,$project]);
    checkCost(!$costs->dashboard($filter)['projects'][0]['comparable'],'Budget VND compared against USD.');
    $query->execute("UPDATE dc_pm_timesheets SET currency='VND',cost=? WHERE store_id=? AND id=?",'sii',['1234567890123456.78',$store,$first]);
    $query->execute("UPDATE dc_pm_timesheets SET currency='VND' WHERE store_id=? AND project_id=?",'ii',[$store,$project]);
    checkCost($costs->dashboard($filter)['summary']['cost']==='1234567890123958.03','Group 4: large DECIMAL lost precision.');
    $foreignTask=$projects->saveTask($project,['name'=>'Foreign estimate','assignee_id'=>$owner,'status'=>'todo','priority'=>'normal','estimated_hours'=>'3','due_date'=>'']);
    $query->execute("INSERT INTO dc_pm_hourly_rates(store_id,user_id,rate,currency,effective_from,effective_to,status,created_by) VALUES(?,?,?,'USD',?,NULL,1,?)",'iissi',[$store,$owner,'12.34',$today,$actor]);
    checkCost($costs->dashboard($filter)['estimate_total']===null,'Estimate mixed currency silently summed.');
    $projects->deleteTask($project,$foreignTask);
    $missingTask=$projects->saveTask($project,['name'=>'Missing estimate','assignee_id'=>'','status'=>'todo','priority'=>'normal','estimated_hours'=>'2','due_date'=>'']);
    checkCost($costs->dashboard($filter)['estimate_total']===null,'Missing assignee/rate silently counted as zero.');
    $projects->deleteTask($project,$missingTask);$projects->deleteTask($project,$task);
    $historic=$costs->dashboard($filter);checkCost($historic['summary']['cost']==='1234567890123958.03'&&$historic['tasks'][0]['deleted_at']!==null,'Group 4: hidden task lost actual/history label.');
    $projects->deleteProject($project);
    try{$costs->dashboard($filter);throw new RuntimeException('Deleted project detail accepted.');}catch(DomainException $expected){}
    for($i=0;$i<21;$i++)$projects->saveProject(['code'=>$code.'_P'.$i,'name'=>'Page '.$i,'manager_id'=>$actor,'status'=>'active','budget'=>'0','start_date'=>'','end_date'=>'']);
    $page=$costs->dashboard(['page'=>2]);checkCost($page['page']===2&&count($page['projects'])>0&&count($page['projects'])<=20,'Group 2: pagination failed.');
}finally{$connection->endFixture();}
checkCost(!$query->fetchOne('SELECT id FROM dc_pm_projects WHERE store_id=? AND code=?','is',[$store,$code]),'Fixture rollback failed.');
echo "PASS groups 1-4: stored DECIMAL cost/OT/estimate, inclusive filters/empty/paging/double-counting, role/tenant/list/detail boundaries, mixed currency/lifetime guards, missing estimate, hidden task/project, rollback.\n";
