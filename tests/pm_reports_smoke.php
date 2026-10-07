<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/services/pmreportservice.class.php';require ROOT_PATH.'classes/services/pmprojectservice.class.php';require ROOT_PATH.'classes/services/pmtimesheetservice.class.php';
if(in_array('--preview',$argv,true))require ROOT_PATH.'classes/template/smarty.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
class ReportFixtureConnection extends mysqli {
    public function beginFixture(): bool{return parent::begin_transaction();}public function endFixture(): bool{return parent::rollback();}
    public function begin_transaction(int $flags=0,?string $name=null): bool{return $this->query('SAVEPOINT report_step');}
    public function commit(int $flags=0,?string $name=null): bool{return $this->query('RELEASE SAVEPOINT report_step');}
    public function rollback(int $flags=0,?string $name=null): bool{$this->query('ROLLBACK TO SAVEPOINT report_step');return $this->query('RELEASE SAVEPOINT report_step');}
}
$connection=new ReportFixtureConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$db=(object)['connection'=>$connection];$q=new PmDb($db);
$a=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");$store=(int)$a['store_id'];$actor=(int)$a['id'];
$other=$q->fetchOne("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) LIMIT 1",'i',[$store]);$owner=(int)$other['id'];
function reportCheck(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function reportDenied(callable $fn): void {try{$fn();}catch(InvalidArgumentException|DomainException $e){return;}throw new RuntimeException('Expected report rejection.');}
$connection->beginFixture();$path=null;
try{
    $projects=new PmProjectService($db,$store,$actor);$code='REPORT_'.bin2hex(random_bytes(4));$p=$projects->saveProject(['code'=>$code,'name'=>'=HYPERLINK("bad")','manager_id'=>$actor,'status'=>'active','budget'=>'0','start_date'=>'','end_date'=>'']);
    $t=$projects->saveTask($p,['name'=>'<script>task</script>','assignee_id'=>$actor,'status'=>'todo','priority'=>'normal','estimated_hours'=>'2','due_date'=>'']);
    $timesheets=new PmTimesheetService($db,$store,$actor);$entry=['task_id'=>$t,'work_date'=>'2026-10-01','shift_label'=>'=1+1','hours'=>'6','description'=>'@SUM(1,1)'];$first=$timesheets->save($entry);$entry['hours']='4';$second=$timesheets->save($entry);
    $service=new PmReportService($db,$store,$actor);$filter=['from'=>'2026-10-01','to'=>'2026-10-01','project_id'=>$p];$r=$service->report($filter);
    reportCheck($r['data']['summary']['hours']==='10.00'&&count($r['data']['rows'])===2,'Hours inclusive/summary failed.');
    $directory=$service->report(['mode'=>'users','user_id'=>$actor]);reportCheck(count($directory['data']['rows'])===1&&!isset($directory['data']['rows'][0]['password_hash']),'Personnel export leaked secret or wrong scope.');
    $directory=$service->report(['mode'=>'projects','project_id'=>$p]);reportCheck(count($directory['data']['rows'])===1&&!isset($directory['data']['rows'][0]['budget']),'Project directory leaked budget.');
    foreach(['users','projects'] as $mode)reportCheck(str_starts_with($service->xlsx(['mode'=>$mode]),'PK'),'Directory XLSX failed.');
    $directoryBytes=$service->xlsx(['mode'=>'projects','project_id'=>$p]);$directoryPath=tempnam(sys_get_temp_dir(),'pm-directory-test-');try{file_put_contents($directoryPath,$directoryBytes);$directoryBook=\PhpOffice\PhpSpreadsheet\IOFactory::load($directoryPath);reportCheck($directoryBook->getActiveSheet()->getCell('C3')->getDataType()==='s'&&$directoryBook->getActiveSheet()->getCell('C3')->getValue()==='=HYPERLINK("bad")','Project directory formula injection');$directoryBook->disconnectWorksheets();}finally{unlink($directoryPath);}
    reportCheck(!isset($r['data']['rows'][0]['cost'])&&!isset($r['data']['rows'][0]['rate_snapshot']),'Hours report leaked money.');
    reportCheck($r['data']['by_week'][0]['week_start']==='2026-09-28'&&$r['data']['by_week'][0]['hours']==='10.00','Weekly totals wrong.');
    reportCheck($service->report(array_replace($filter,['role_code'=>'ADMIN']))['data']['summary']['hours']==='10.00','Role filter wrong.');
    reportDenied(fn()=>$service->report(array_replace($filter,['role_code'=>['ADMIN']])));
    $q->execute("UPDATE dc_pm_tasks SET status='done',due_date='2026-10-01',completed_at='2026-10-02 12:00:00' WHERE store_id=? AND id=?",'ii',[$store,$t]);
    $late=$projects->saveTask($p,['name'=>'Overdue task','assignee_id'=>$actor,'due_date'=>'2026-10-01']);
    $unknown=$projects->saveTask($p,['name'=>'=1+1','assignee_id'=>$actor,'status'=>'done','due_date'=>'2026-10-01']);$q->execute('UPDATE dc_pm_tasks SET completed_at=NULL WHERE store_id=? AND id=?','ii',[$store,$unknown]);
    $taskFilter=array_replace($filter,['mode'=>'tasks','to'=>'2026-10-03']);$taskReport=$service->report($taskFilter);
    reportCheck((int)$taskReport['data']['summary']['completed']===1&&(int)$taskReport['data']['summary']['overdue']===1&&(int)$taskReport['data']['summary']['completed_late']===1&&(int)$taskReport['data']['summary']['completion_unknown']===1,'Task statistics/unknown dates wrong.');
    reportCheck(count($taskReport['data']['by_project_user'])===1,'Task group aggregate wrong.');
    reportCheck((int)$taskReport['data']['by_week'][0]['completed']===1&&$taskReport['data']['rows'][0]['late_days']===1,'Task weekly/late days wrong.');
    reportCheck(str_starts_with($service->xlsx($taskFilter),'PK'),'Task XLSX failed.');
    if(in_array('--preview',$argv,true)){$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());$s->assign(['pageTitle'=>'Task reports','report'=>$taskReport,'error'=>'','csrfToken'=>'fixture','canExportReports'=>true,'canReportCosts'=>true]);file_put_contents(ROOT_PATH.'.local/phase11-tasks.html',str_replace('<head>','<head><base href="/">',$s->fetch('admin/pm-reports.tpl.html')));}
    foreach([['from'=>'2026-02-30'],['from'=>'2026-10-02','to'=>'2026-10-01'],['page'=>'0'],['project_id'=>['1']],['mode'=>'invalid']] as $bad)reportDenied(fn()=>$service->report(array_replace($filter,$bad)));
    reportCheck($service->report(array_replace($filter,['from'=>'2000-01-01','to'=>'2000-01-01']))['data']['summary']['entries']===0,'Empty report failed.');
    $bytes=$service->xlsx($filter);reportCheck(str_starts_with($bytes,'PK'),'Not XLSX.');$path=tempnam(sys_get_temp_dir(),'pm-report-test-');file_put_contents($path,$bytes);
    if(in_array('--preview',$argv,true)){
        $s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
        $s->assign(['pageTitle'=>'Báo cáo thử','report'=>$r,'error'=>'','csrfToken'=>'preview-token','canExportReports'=>true,'canReportCosts'=>true]);
        file_put_contents(ROOT_PATH.'.local/phase8-preview.html',str_replace('<head>','<head><base href="/">',$s->fetch('admin/pm-reports.tpl.html')));file_put_contents(ROOT_PATH.'.local/phase8-preview.xlsx',$bytes);
    }
    $book=\PhpOffice\PhpSpreadsheet\IOFactory::load($path);$sheet=$book->getActiveSheet();
    reportCheck($sheet->getCell('C3')->getDataType()==='s'&&$sheet->getCell('C3')->getValue()==='=HYPERLINK("bad")','Formula injection/type failed.');
    reportCheck($sheet->getCell('E3')->getDataType()==='s'&&$sheet->getCell('I3')->getValue()==='@SUM(1,1)','Formula-like shift/description unsafe.');$book->disconnectWorksheets();unlink($path);$path=null;
    $q->execute('UPDATE dc_pm_timesheets SET cost=? WHERE store_id=? AND id=?','sii',['1234567890123456.78',$store,$first]);
    $costFilter=array_replace($filter,['mode'=>'costs']);$r=$service->report($costFilter);reportCheck($r['data']['summary']['cost']==='1234567890123456.78','DECIMAL report altered money.');
    reportCheck($service->report(array_replace($costFilter,['user_id'=>$actor,'role_code'=>'ADMIN']))['data']['summary']['cost']==='1234567890123456.78','Cost person/role filter lost DECIMAL.');
    $q->execute("UPDATE dc_pm_timesheets SET currency='USD' WHERE store_id=? AND id=?",'ii',[$store,$second]);$r=$service->report($costFilter);reportCheck($r['data']['summary']['cost']===null,'Mixed-currency report added money.');
    reportCheck(str_starts_with($service->xlsx($costFilter),'PK'),'Cost XLSX failed.');
    $q->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=? AND code IN ('PM','HR')",'i',[$store]);$q->execute("INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='EMPLOYEE'",'iii',[$store,$owner,$store]);
    $q->execute('UPDATE dc_pm_timesheets SET user_id=? WHERE store_id=? AND id=?','iii',[$owner,$store,$first]);$employee=new PmReportService($db,$store,$owner);$r=$employee->report($filter);reportCheck($r['data']['summary']['hours']==='6.00','Own report scope failed.');reportDenied(fn()=>$employee->report(array_replace($filter,['user_id'=>$actor])));reportDenied(fn()=>$employee->xlsx($costFilter));
    reportDenied(fn()=>$employee->report($taskFilter));reportDenied(fn()=>$employee->report(array_replace($taskFilter,['user_id'=>$actor])));
    $q->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='PM'",'i',[$store]);$q->execute("INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='PM'",'iii',[$store,$owner,$store]);$pm=new PmReportService($db,$store,$owner);reportDenied(fn()=>$pm->report($filter));reportDenied(fn()=>$pm->xlsx($filter));
    reportDenied(fn()=>(new PmReportService($db,$store+99999,$actor))->report($filter));
    $q->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=? AND code IN ('PM','EMPLOYEE')",'i',[$store]);$q->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=? AND code='HR'",'i',[$store]);$q->execute("INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,0 FROM dc_pm_roles WHERE store_id=? AND code='HR'",'iii',[$store,$owner,$store]);
    $hr=new PmReportService($db,$store,$owner);reportCheck($hr->report($filter)['data']['summary']['hours']==='6.00','HR own-only report failed.');reportDenied(fn()=>$hr->xlsx($costFilter));
    for($i=0;$i<12;$i++)$q->execute('INSERT INTO dc_pm_timesheets(store_id,user_id,project_id,task_id,work_date,shift_label,hours,description,rate_snapshot,rate_source,currency,regular_hours,ot_hours,ot_multiplier_snapshot,cost) SELECT store_id,user_id,project_id,task_id,work_date,shift_label,hours,description,rate_snapshot,rate_source,currency,regular_hours,ot_hours,ot_multiplier_snapshot,cost FROM dc_pm_timesheets WHERE store_id=? AND project_id=?','ii',[$store,$p]);
    $paged=$service->report(array_replace($filter,['page'=>2]));reportCheck(count($paged['data']['rows'])===50&&$paged['data']['page']===2,'Report pagination failed.');reportDenied(fn()=>$service->xlsx($filter));
    $q->execute('UPDATE dc_pm_timesheets SET deleted_at=NOW() WHERE store_id=? AND project_id=? AND id NOT IN (?,?)','iiii',[$store,$p,$first,$second]);
    $projects->deleteTask($p,$t);reportCheck((bool)$service->report($filter)['data']['rows'][0]['historical'],'Historical task missing.');$projects->deleteProject($p);reportCheck($service->report($filter)['data']['summary']['hours']==='10.00','Historical project lost hours.');
    echo "PASS: reports hours/scope/filter/history, XLSX round-trip and formula strings, DECIMAL and currency guards; fixtures rolled back.\n";
}finally{if($path&&is_file($path))unlink($path);$connection->endFixture();}
