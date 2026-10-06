<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/services/pmprojectservice.class.php';require ROOT_PATH.'classes/services/pmcostservice.class.php';require ROOT_PATH.'classes/services/pmallocationservice.class.php';require ROOT_PATH.'classes/services/pmreportservice.class.php';require ROOT_PATH.'classes/services/pmimportservice.class.php';require ROOT_PATH.'classes/dao/pmauditlogs.class.php';require ROOT_PATH.'classes/dao/pmusers.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
class ExplainStatement extends mysqli_stmt {
    private array $values=[];private string $types='';
    public function __construct(private ExplainConnection $owner,private string $sql){parent::__construct($owner,$sql);}
    public function bind_param(string $types,mixed &...$vars): bool {$this->types=$types;$this->values=&$vars;return parent::bind_param($types,...$vars);}
    public function execute(?array $params=null): bool {$ok=parent::execute($params);if(preg_match('/^SELECT\b/i',$this->sql)&&preg_match('/(?:FROM|JOIN)\s+dc_/i',$this->sql))$this->owner->captured[]=[$this->sql,$this->types,array_values($this->values),$this->owner->family];return $ok;}
}
class ExplainConnection extends mysqli {
    public array $captured=[];public string $family='setup';
    public function prepare(string $sql): mysqli_stmt|false {return new ExplainStatement($this,$sql);}
    public function beginFixture(): bool{return parent::begin_transaction();}public function endFixture(): bool{return parent::rollback();}
    public function begin_transaction(int $flags=0,?string $name=null): bool{return $this->query('SAVEPOINT explain_step');}public function commit(int $flags=0,?string $name=null): bool{return $this->query('RELEASE SAVEPOINT explain_step');}public function rollback(int $flags=0,?string $name=null): bool{$this->query('ROLLBACK TO SAVEPOINT explain_step');return $this->query('RELEASE SAVEPOINT explain_step');}
}
$c=new ExplainConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$db=(object)['connection'=>$c];$q=new PmDb($db);$a=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");$store=(int)$a['store_id'];$actor=(int)$a['id'];$c->beginFixture();
try{
    $p=new PmProjectService($db,$store,$actor);$project=$p->saveProject(['code'=>'EXP_'.bin2hex(random_bytes(5)),'name'=>'Explain fixture','manager_id'=>$actor,'status'=>'active','budget'=>'100','start_date'=>'','end_date'=>'']);$task=$p->saveTask($project,['name'=>'Explain task','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'1','due_date'=>'']);
    $q->execute("INSERT INTO dc_pm_timesheets(store_id,user_id,project_id,task_id,work_date,shift_label,hours,regular_hours,ot_hours,rate_snapshot,rate_source,currency,cost,ot_multiplier_snapshot) VALUES(?,?,?,?,'2026-10-04','Fixture',1,1,0,10,'user','VND',10,1)",'iiii',[$store,$actor,$project,$task]);
    $q->execute("INSERT INTO dc_pm_allocations(store_id,user_id,project_id,task_id,work_date,hours,created_by,updated_by) VALUES(?,?,?,?,'2026-10-04',1,?,?)",'iiiiii',[$store,$actor,$project,$task,$actor,$actor]);
    $import=(int)($q->fetchOne('SELECT id FROM dc_pm_import_logs WHERE store_id=? LIMIT 1','i',[$store])['id']??0);
    if(!$import){$q->execute("INSERT INTO dc_pm_import_logs(store_id,actor_id,status,row_count) VALUES(?,?,'staged',0)",'ii',[$store,$actor]);$import=(int)$q->fetchOne('SELECT LAST_INSERT_ID() id')['id'];}
    $c->captured=[];$c->family='users';$users=new PmUsers($db);$users->list($store,['q'=>'Explain','role_id'=>null,'department_id'=>null]);$users->rolesForUsers($store,[$actor]);
    $c->family='audit';(new PmAuditLogs($db,$store,$actor))->list(['entity_type'=>'timesheet']);
    $c->family='costs';(new PmCostService($db,$store,$actor))->dashboard(['project_id'=>$project,'from'=>'2026-10-01','to'=>'2026-10-04']);
    $c->family='allocations';(new PmAllocationService($db,$store,$actor))->dashboard(['week'=>'2026-10-04','project_id'=>$project]);
    $c->family='reports';(new PmReportService($db,$store,$actor))->report(['from'=>'2026-10-01','to'=>'2026-10-04','project_id'=>$project],true);
    foreach(['tasks','users','projects','costs'] as $mode){$input=['mode'=>$mode,'from'=>'2026-10-01','to'=>'2026-10-04'];if($mode!=='users')$input['project_id']=$project;if(in_array($mode,['tasks','users','costs'],true))$input['role_code']='ADMIN';(new PmReportService($db,$store,$actor))->report($input,true);}
    $c->family='imports';$imports=new PmImportService($db,$store,$actor);$imports->history();$imports->detail($import);
    $captured=$c->captured;$report=[];$seen=[];
    foreach($captured as [$sql,$types,$params,$family]){
        $key=$family.'|'.$sql;if(isset($seen[$key]))continue;$seen[$key]=true;
        $plan=$q->fetchAll('EXPLAIN '.$sql,$types,$params);
        $report[]=['family'=>$family,'sql'=>$sql,'plan'=>$plan];
    }
    $families=array_unique(array_column($report,'family'));if(count($families)!==6)throw new RuntimeException('Missing EXPLAIN family.');
    if(in_array('--report',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-explain.json',json_encode(['environment'=>'local small dataset + rollback PM fixture; NOT production benchmark','index_changes'=>false,'queries'=>$report],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    echo 'PASS: '.count($report).' actual prepared query shapes EXPLAIN across users/audit/costs/allocations/reports/imports; no ADD INDEX, no migration, no persistent fixtures. Plans in ignored .local with --report; parameter values omitted.'.PHP_EOL;
}finally{$c->endFixture();}
