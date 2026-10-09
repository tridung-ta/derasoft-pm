<?php
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/services/pmdashboardservice.class.php';
require ROOT_PATH.'classes/template/smarty.class.php';
require ROOT_PATH.'classes/services/pmuiservice.class.php';

/** Records SQL contracts, never constructs a mysqli connection. */
class DashboardRecordingDb extends PmDb {
    public array $calls=[];
    public bool $fail=false;
    public function __construct() {}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array {
        $this->record($sql,$types,$params);
        if(str_contains($sql,'today_hours'))return ['today_hours'=>'0.00','week_hours'=>'8.25','today_entries'=>0];
        if(str_contains($sql,'COUNT(*) n'))return ['n'=>1];
        if(str_contains($sql,'percentage'))return ['percentage'=>'25.00'];
        throw new RuntimeException('Unexpected test query.');
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array {
        $this->record($sql,$types,$params);
        if(str_contains($sql,'late_tasks'))return [['id'=>12,'name'=>'Dự án <script>trễ</script>','late_tasks'=>2,'oldest_due'=>'2026-10-01']];
        return [['id'=>23,'name'=>'Task <script>thử</script>','project_id'=>12,'due_date'=>'2026-10-10','project_name'=>'Dự án thử']];
    }
    private function record(string $sql,string $types,array $params): void {
        if(strlen($types)!==count($params))throw new RuntimeException('Placeholder types mismatch.');
        $this->calls[]=[$sql,$types,$params];
        if($this->fail)throw new RuntimeException('SECRET_SQL_PASSWORD');
    }
}
class DashboardFixtureAccess {
    public function __construct(private string $role,private array $permissions) {}
    public function hasRole(string $role): bool {return $role===$this->role;}
    public function hasPermission(string $permission): bool {return in_array($permission,$this->permissions,true);}
}
function checkDashboard(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
$costs=['summary'=>['missing_rate_entries'=>2],'total_pages'=>2,'projects'=>[
 ['id'=>12,'name'=>'Dự án thử','budget'=>'400.00','lifetime'=>['cost'=>'100.00','currency'=>'VND','missing_rate_entries'=>0]],
 ['id'=>13,'name'=>'Chưa ngân sách','budget'=>'0.00','lifetime'=>['cost'=>'900.00','currency'=>'VND','missing_rate_entries'=>0]],
 ['id'=>14,'name'=>'Thiếu đơn giá','budget'=>'400.00','lifetime'=>['cost'=>'100.00','currency'=>'VND','missing_rate_entries'=>1]],
 ['id'=>15,'name'=>'Tiền tệ khác','budget'=>'400.00','lifetime'=>['cost'=>'100.00','currency'=>'USD','missing_rate_entries'=>0]],
 ['id'=>16,'name'=>'Tiền tệ hỗn hợp','budget'=>'400.00','lifetime'=>['cost'=>null,'currency'=>null,'missing_rate_entries'=>0]]
]];
$allocation=['rows'=>[
 ['user_id'=>8,'user_name'=>'Người thử','work_date'=>'2026-10-09','warnings'=>['Vượt ngưỡng ngày']],
 ['user_id'=>8,'user_name'=>'Người thử','work_date'=>'2026-10-09','warnings'=>['Vượt ngưỡng ngày']]
], 'truncated'=>false,'task_estimate_warnings'=>[
 ['task_name'=>'Task thử','actual_hours'=>'5.00','estimated_hours'=>'4.00','excess_hours'=>'1.00','historical'=>1]
]];
$all=['pm.dashboard.view','pm.timesheets.own','pm.projects.view','pm.tasks.view','pm.costs.view','pm.allocations.view'];
$today=new DateTimeImmutable('2026-10-09',new DateTimeZone('Asia/Ho_Chi_Minh'));
$costCalls=0;$allocationCalls=0;$weeks=[];
$costProvider=function()use(&$costCalls,$costs){$costCalls++;return $costs;};
$allocationProvider=function($week)use(&$allocationCalls,&$weeks,$allocation){$allocationCalls++;$weeks[]=$week;return $allocation;};
foreach(['PM','ADMIN'] as $role) {
 $db=new DashboardRecordingDb();$access=new DashboardFixtureAccess($role,$all);$dao=new PmDashboard($db,7);
 $out=PmDashboardService::compose($dao,$access,41,$today,$costProvider,$allocationProvider);
 checkDashboard($out['today_hours']==='0.00'&&$out['week_hours']==='8.25'&&$out['needs_timesheet'],'Own hours/zero/reminder.');
 checkDashboard(count($out['management']['allocation_alerts'])===1,'Duplicate allocation warning.');
 checkDashboard(count($out['management']['task_estimate_warnings'])===1,'Separate task warning.');
 checkDashboard($out['management']['budget_projects'][0]['percentage']==='25.00','Budget value changed.');
 foreach(array_slice($out['management']['budget_projects'],1) as $p)checkDashboard($p['percentage']===null&&$p['reason']!==null,'Unsafe budget comparison.');
 checkDashboard($out['management']['budget_more']&&$out['management']['missing_rates']===2,'Cost truncation/missing rates.');
 foreach($db->calls as [$sql,$types,$params]) {
  if(str_contains($sql,'percentage')){checkDashboard($params===['100.00','400.00']&&str_contains($sql,'DECIMAL')&&str_contains($sql,'NULLIF'),'Decimal budget contract.');continue;}
  if(str_contains($sql,'today_hours'))checkDashboard($params===['2026-10-09','2026-10-09',7,41,'2026-10-05','2026-10-09'],'Own date/tenant contract.');
  else checkDashboard($params[0]===7,'Tenant missing.');
  if(str_contains($sql,'late_tasks'))checkDashboard(str_contains($sql,'p.manager_id=?')===($role==='PM')&&($role!=='PM'||end($params)===41),'PM ownership contract.');
  if(str_contains($sql,'EXISTS'))checkDashboard(!str_contains($sql,'JOIN dc_pm_project_members'),'Membership multiplication.');
 }
 if($role==='PM'){$managerOverview=$out;$managerAccess=$access;}
}
checkDashboard($weeks===['2026-10-05','2026-10-05'],'Vietnam week start.');
foreach(['EMPLOYEE','HR','NONE'] as $role) {
 $db=new DashboardRecordingDb();$permissions=$role==='EMPLOYEE'?['pm.timesheets.own','pm.projects.view','pm.tasks.view']:[];
 $before=[$costCalls,$allocationCalls];
 $out=PmDashboardService::compose(new PmDashboard($db,7),new DashboardFixtureAccess($role,$permissions),41,$today,$costProvider,$allocationProvider);
 checkDashboard($out['management']===null&&[$costCalls,$allocationCalls]===$before,'Manager provider called for unauthorized role.');
 if($role!=='EMPLOYEE')checkDashboard(!$db->calls&&$out['today_hours']===null,'No-role query leak.');
}
$db=new DashboardRecordingDb();
$out=PmDashboardService::compose(new PmDashboard($db,7),new DashboardFixtureAccess('PM',[]),41,$today,
 function(){throw new RuntimeException('Permission bypass');},function(){throw new RuntimeException('Permission bypass');});
checkDashboard(!$db->calls&&$out['management']['budget_projects']===null&&$out['management']['allocation_alerts']===null,'PM permission gates.');
$db=new DashboardRecordingDb();
$out=PmDashboardService::compose(new PmDashboard($db,7),new DashboardFixtureAccess('PM',['pm.projects.view']),41,$today,
 function(){throw new RuntimeException('Cost permission bypass');},function(){throw new RuntimeException('Allocation permission bypass');});
checkDashboard(!$db->calls&&$out['task_count']===null&&$out['management']['late_projects']===null,'Task permission missing gate.');
$db=new DashboardRecordingDb();
$out=PmDashboardService::compose(new PmDashboard($db,7),new DashboardFixtureAccess('PM',$all),41,$today,
 function(){throw new RuntimeException('SECRET_COST_SQL');},function(){throw new RuntimeException('SECRET_ALLOCATION_SQL');});
checkDashboard(count($out['errors'])===2&&$out['management']['budget_projects']===null&&$out['management']['allocation_alerts']===null&&!str_contains(json_encode($out),'SECRET'),'Provider failure not isolated.');
$db=new DashboardRecordingDb();$db->fail=true;
$out=PmDashboardService::compose(new PmDashboard($db,7),new DashboardFixtureAccess('EMPLOYEE',['pm.timesheets.own','pm.projects.view','pm.tasks.view']),41,$today,$costProvider,$allocationProvider);
checkDashboard(count($out['errors'])===2&&$out['task_count']===null&&$out['today_hours']===null&&!str_contains(json_encode($out),'SECRET'),'Unavailable data misrepresented/leaked.');
$db=new DashboardRecordingDb();$dao=new PmDashboard($db,7);
checkDashboard($dao->budgetPercent('bad','1.00')===null&&!$db->calls,'Invalid decimal queried.');
$dao->budgetPercent('12345678901234567890.01','12345678901234567890.04');
checkDashboard($db->calls[0][2]===['12345678901234567890.01','12345678901234567890.04'],'Large decimal rounded.');
class DashboardFixtureUser {
 public function getUsername(){return 'demo';}public function getFullName(){return 'Người thử';}
}
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates/admin');$s->setCompileDir(sys_get_temp_dir());
$s->assign(['pageTitle'=>'Tổng quan — dữ liệu giả','section'=>'dashboard','notice'=>'','error'=>'','csrfToken'=>'synthetic',
 'profileUser'=>new DashboardFixtureUser(),'teamMembers'=>[],'teamCount'=>0,'workOverview'=>$managerOverview,
 'pmNavigation'=>PmUiService::navigation($managerAccess,'pm','dashboard')]);
$html=$s->fetch('pm.tpl.html');
checkDashboard(str_contains($html,'25.00% ngân sách')&&str_contains($html,'&lt;script&gt;trễ')&&!str_contains($html,'<script>trễ'),'Manager render/escape.');
checkDashboard(str_contains($html,'(Lịch sử)')&&str_contains($html,'task đang được phân bổ trong tuần này'),'Task warning scope/history.');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/workspace-dashboard-managed.html',str_replace('<head>','<head><base href="/">',$html));
echo "PASS: dashboard fixture orchestration Admin/PM/Employee/HR/no-role, provider gates, SQL tenant/ownership/date/EXISTS/DECIMAL contracts, safe budget decisions, allocation dedup/separate task warnings, graceful errors and Smarty escaping. No DB connected; SQL execution/EXPLAIN not tested.\n";
