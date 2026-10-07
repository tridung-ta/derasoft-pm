<?php
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/pmdb.class.php';
if (($config['db_name'] ?? '') !== 'derasoft_pm_local' || !in_array($config['db_server'], ['localhost','127.0.0.1'], true)) throw new RuntimeException('Non-local refused.');
$c = new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);
$q = new PmDb((object)['connection'=>$c]);
$actor = $q->fetchOne('SELECT id,store_id FROM dc_users WHERE status=1 ORDER BY id LIMIT 1');
if (!$actor) throw new RuntimeException('No active local session actor.');
function rolePersistentState(PmDb $q): string {
    return hash('sha256',serialize([
        $q->fetchAll('SELECT * FROM dc_pm_user_roles ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_projects ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_project_members ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_tasks ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_audit_logs ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_trackings ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_timesheets ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_timesheet_days ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_system_settings ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_hourly_rates ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_allocations ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_allocation_locks ORDER BY store_id,user_id,work_date'),
        $q->fetchAll('SELECT * FROM dc_pm_capacity_overrides ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_users ORDER BY id'),
        $q->fetchOne("SELECT engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_users'")
    ]));
}
$before=rolePersistentState($q);
$dir = ROOT_PATH.'.local/role-http-sessions';
if (!is_dir($dir)) mkdir($dir,0700,true);
ini_set('session.save_path',$dir); ini_set('session.use_cookies','0');
session_cache_limiter('');
session_id(bin2hex(random_bytes(20))); session_start();
$_SESSION=['userId'=>(int)$actor['id'],'storeId'=>(int)$actor['store_id'],'pm_csrf_token'=>'role-http-csrf'];
$sid=session_id(); session_write_close();
$token=bin2hex(random_bytes(24));
$socket=@fsockopen('127.0.0.1',18770,$errno,$error,.1);
if ($socket) { fclose($socket); throw new RuntimeException('Fixture port already occupied.'); }
putenv('PM_ROLE_HTTP_TOKEN='.$token); putenv('PM_ROLE_HTTP_ACTOR='.$actor['id']); putenv('PM_ROLE_HTTP_STORE='.$actor['store_id']);
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:18770',ROOT_PATH.'tests/pm_role_http_router.php'],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/role-http.log','a'],2=>['file',ROOT_PATH.'.local/role-http.log','a']],$pipes,ROOT_PATH);
if (!is_resource($server)) throw new RuntimeException('Cannot start HTTP fixture.');
fclose($pipes[0]);
$cases=0;
function roleHttp(string $role,string $query,int $expected,?array $post=null,bool $controllerHeaders=true): string {
    global $sid,$token,$cases;
    $headers="Cookie: PHPSESSID=$sid\r\nX-PM-Test-Token: $token\r\nX-PM-Test-Role: $role\r\n";
    if ($post!==null) $headers.="Content-Type: application/x-www-form-urlencoded\r\n";
    $ctx=stream_context_create(['http'=>['method'=>$post===null?'GET':'POST','header'=>$headers,'content'=>$post===null?'':http_build_query($post),'ignore_errors'=>true,'follow_location'=>0,'timeout'=>15]]);
    $body=file_get_contents('http://127.0.0.1:18770/admin.php?'.$query,false,$ctx);
    preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$m);
    if ((int)($m[1]??0)!==$expected) throw new RuntimeException("$role/$query expected $expected got ".($m[1]??'none'));
    if ($controllerHeaders&&!in_array('Cache-Control: no-store',$http_response_header,true)) throw new RuntimeException('Missing no-store.');
    if($expected===303&&!in_array('Location: admin.php?op=pmprojects',$http_response_header,true))throw new RuntimeException('Project success redirected to wrong page.');
    if (str_contains($body,'Fatal error:')||str_contains($body,'mysqli_sql_exception')) throw new RuntimeException('HTTP runtime error.');
    $cases++; return $body;
}
function roleTrackingIdentity(array $state): void {
    global $actor;
    roleAssert(isset($state['tracking'])&&is_array($state['tracking']), 'Missing tracking fixture observation.');
    foreach($state['tracking'] as $row){
        roleAssert($row['username']===(string)$actor['id']&&$row['ip']==='', 'New PM tracking contains raw identity/IP.');
    }
}
function roleWrite(array $changes, string $role='PM', ?int $expected=null): ?array {
    $post=array_replace(['op'=>'pmprojects','project_id'=>'900000001','action'=>'task_save','csrf_token'=>'role-http-csrf','name'=>'HTTP created task','description'=>'HTTP fixture','status'=>'todo','priority'=>'normal','estimated_hours'=>'2','start_date'=>'2026-01-01','due_date'=>'2026-12-31','assignee_id'=>''],$changes);
    $expected??=in_array($post['action'],['project_save','project_delete'],true)?303:200;
    $html=roleHttp($role,'op=pmprojects',$expected,$post);
    if (!in_array($expected,[200,303],true)) return null;
    if (!preg_match('/<!-- PM_FIXTURE_STATE ([A-Za-z0-9+\/=]+) -->/',$html,$m)) throw new RuntimeException('Missing request-local write state.');
    $state=json_decode(base64_decode($m[1],true),true,512,JSON_THROW_ON_ERROR);
    roleTrackingIdentity($state);return $state;
}
function roleAssert(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); }
function roleTask(array $state,int $id): array {
    foreach($state['dc_pm_tasks'] as $task) if ((int)$task['id']===$id) return $task;
    throw new RuntimeException('Missing fixture task.');
}
function roleTimeWrite(array $changes,string $role='PM',int $expected=200): array {
    $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    $post=array_replace(['op'=>'pmtimesheets','action'=>'save','csrf_token'=>'role-http-csrf','task_id'=>'900000011','work_date'=>$today,'shift_label'=>'HTTP new','hours'=>'2','description'=>'HTTP fixture'],$changes);
    $html=roleHttp($role,'op=pmtimesheets',$expected,$post);
    if (!preg_match('/<!-- PM_FIXTURE_STATE ([A-Za-z0-9+\/=]+) -->/',$html,$m)) throw new RuntimeException('Missing timesheet write state.');
    return json_decode(base64_decode($m[1],true),true,512,JSON_THROW_ON_ERROR);
}
function roleTime(array $state,int $id): array {
    foreach($state['dc_pm_timesheets'] as $row) if ((int)$row['id']===$id) return $row;
    throw new RuntimeException('Missing fixture timesheet.');
}
function roleTimeUnchanged(array $state): void {
    roleAssert(count($state['dc_pm_timesheets'])===3&&!$state['dc_pm_audit_logs'],'Rejected timesheet action inserted data/audit.');
    roleAssert(roleTime($state,900000021)['hours']==='4.00'&&roleTime($state,900000022)['cost']==='1150.00','Rejected action altered timesheet values.');
}
function roleAllocationWrite(array $changes,int $expected=200): array {
    global $actor;
    $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    $post=array_replace(['op'=>'pmallocations','action'=>'save','csrf_token'=>'role-http-csrf','task_id'=>'900000011','user_id'=>(string)$actor['id'],'work_date'=>$today,'hours'=>'2','start_time'=>'14:00','end_time'=>'16:00'],$changes);
    $html=roleHttp('PM','op=pmallocations',$expected,$post);
    if (!preg_match('/<!-- PM_FIXTURE_STATE ([A-Za-z0-9+\/=]+) -->/',$html,$m)) throw new RuntimeException('Missing allocation write state.');
    return json_decode(base64_decode($m[1],true),true,512,JSON_THROW_ON_ERROR);
}
function roleUserWrite(array $changes,string $role='HR',int $expected=200): ?array {
    $post=array_replace(['op'=>'pmusers','action'=>'save','id'=>'900000041','csrf_token'=>'role-http-csrf','fullname'=>'HTTP Updated','email'=>'http-updated@example.test','tel'=>'0900000000','weekly_limit_hours'=>'32','department_id'=>''],$changes);
    $html=roleHttp($role,'op=pmusers',$expected,$post);
    if ($expected!==200) return null;
    if (!preg_match('/<!-- PM_FIXTURE_STATE ([A-Za-z0-9+\/=]+) -->/',$html,$m)) throw new RuntimeException('Missing personnel write state.');
    $state=json_decode(base64_decode($m[1],true),true,512,JSON_THROW_ON_ERROR);
    roleTrackingIdentity($state);$state['_html']=$html; return $state;
}
function roleUser(array $state,int $id): array {
    foreach($state['users'] as $row) if ((int)$row['id']===$id) return $row;
    throw new RuntimeException('Missing fixture personnel.');
}
try {
    $ready=false;
    for($i=0;$i<50;$i++) { $socket=@fsockopen('127.0.0.1',18770,$errno,$error,.1); if($socket){fclose($socket);$ready=true;break;} usleep(100000); }
    if (!$ready) throw new RuntimeException('HTTP fixture did not start.');
    // Routing and temporary-table selection must agree before any controller runs.
    roleHttp('PM','op=pmprojects',400,['op'=>'pmtimesheets','action'=>'save'],false);
    foreach(['PM','HR'] as $role) {
        foreach(['pm','pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'] as $route) {
            $denied=$route==='pmimports'||($role==='HR'&&in_array($route,['pmprojects','pmcosts','pmallocations'],true));
            $html=roleHttp($role,'op='.$route,$denied?403:200);
            if (!$denied&&!str_contains($html,'id="pm-main"')) throw new RuntimeException('Missing actual page landmark '.$role.'/'.$route.': '.substr(strip_tags($html),0,500));
        }
        roleHttp($role,'op=pmreports&mode=costs',$role==='PM'?200:403);
        roleHttp($role,'op=pmreports&user_id=999999999',$role==='HR'?403:200);
        roleHttp($role,'op=pmreports&project_id=900000002',403);
        roleHttp($role,'op=pmreports',400,['op'=>'pmreports','action'=>'export','csrf_token'=>'wrong']);
        $bytes=roleHttp($role,'op=pmreports',200,['op'=>'pmreports','action'=>'export','csrf_token'=>'role-http-csrf','mode'=>'hours','from'=>'2000-01-01','to'=>'2000-01-01']);
        if (!str_starts_with($bytes,'PK')) throw new RuntimeException('Missing XLSX export.');
    }
    $own=roleHttp('PM','op=pmprojects&project_id=900000001',200);
    if (!str_contains($own,'HTTP own project')) throw new RuntimeException('Own project not rendered.');
    $foreign=roleHttp('PM','op=pmprojects&project_id=900000002',403);
    if (str_contains($foreign,'HTTP foreign project')) throw new RuntimeException('Foreign project leaked.');
    $state=roleWrite(['status'=>'done']);
    $created=array_values(array_filter($state['dc_pm_tasks'],fn($t)=>$t['name']==='HTTP created task'));
    roleAssert(count($created)===1&&$created[0]['completed_at']!==null,'HTTP task create/completion failed.');
    roleAssert(count($state['dc_pm_audit_logs'])===1&&$state['dc_pm_audit_logs'][0]['action']==='create','Create audit missing.');
    $state=roleWrite(['task_id'=>'900000012','status'=>'done','name'=>'Edited completed task']);
    roleAssert(roleTask($state,900000012)['completed_at']==='2026-01-01 09:00:00','Done edit replaced completion date.');
    $audit=$state['dc_pm_audit_logs'][0]??[];
    roleAssert(($audit['action']??'')==='update'&&(int)($audit['actor_id']??0)===(int)$actor['id'],'Update audit actor/action incorrect.');
    roleAssert(json_decode($audit['old_values'],true)['name']==='HTTP done task'&&json_decode($audit['new_values'],true)['name']==='Edited completed task','Audit snapshots incorrect.');
    $state=roleWrite(['task_id'=>'900000012','status'=>'todo']);
    roleAssert(roleTask($state,900000012)['completed_at']===null,'HTTP reopen did not clear completion date.');
    $state=roleWrite(['action'=>'task_delete','task_id'=>'900000011']);
    roleAssert(roleTask($state,900000011)['deleted_at']!==null&&($state['dc_pm_audit_logs'][0]['action']??'')==='soft_delete','HTTP soft delete/audit failed.');
    $state=roleWrite(['task_id'=>'900000011','csrf_token'=>'wrong']);
    roleAssert(roleTask($state,900000011)['name']==='HTTP todo task'&&!$state['dc_pm_audit_logs'],'Bad CSRF wrote task/audit.');
    $state=roleWrite(['task_id'=>'900000011','start_date'=>'2026-12-31','due_date'=>'2026-01-01']);
    roleAssert(roleTask($state,900000011)['name']==='HTTP todo task'&&!$state['dc_pm_audit_logs'],'Invalid dates wrote task/audit.');
    // Controller returns 403 after reloading the inaccessible project; observe denial state as well.
    $html=roleHttp('PM','op=pmprojects',403,['op'=>'pmprojects','project_id'=>'900000002','action'=>'task_save','task_id'=>'900000013','name'=>'Unauthorized task','status'=>'todo','priority'=>'normal','estimated_hours'=>'1','csrf_token'=>'role-http-csrf']);
    roleAssert(preg_match('/<!-- PM_FIXTURE_STATE ([A-Za-z0-9+\/=]+) -->/',$html,$m)===1,'Missing denial state.');
    $state=json_decode(base64_decode($m[1],true),true,512,JSON_THROW_ON_ERROR);
    roleAssert(roleTask($state,900000013)['name']==='HTTP foreign task'&&!$state['dc_pm_audit_logs'],'Foreign project mutation succeeded.');
    roleWrite([], 'HR',403);
    $state=roleWrite(['action'=>'project_save','code'=>'HTTP-EDIT','name'=>'HTTP edited project','client_name'=>'HTTP client','budget'=>'100','status'=>'active','start_date'=>'2026-01-01','end_date'=>'2026-12-31']);
    roleAssert($state['dc_pm_projects'][0]['client_name']==='HTTP client'&&$state['dc_pm_projects'][0]['name']==='HTTP edited project','HTTP project metadata update failed.');
    $landing=roleHttp('PM','op=pmprojects',200);
    roleAssert(str_contains($landing,'Đã lưu dự án.')&&str_contains($landing,'pm-project-list')&&!str_contains($landing,'data-pm-kanban'),'Project save did not land on list with notice.');
    roleAssert(!str_contains(roleHttp('PM','op=pmprojects',200),'Đã lưu dự án.'),'Save notice repeated on refresh.');
    $state=roleWrite(['action'=>'project_save','project_id'=>'0','code'=>'HTTP-NEW','name'=>'HTTP new project','status'=>'active','budget'=>'0','start_date'=>'2026-10-02','end_date'=>'2026-10-03']);
    roleAssert(count($state['dc_pm_projects'])===3&&$state['dc_pm_projects'][2]['name']==='HTTP new project','Project create/redirect failed.');
    roleAssert(str_contains(roleHttp('PM','op=pmprojects',200),'Đã lưu dự án.'),'Create notice lost across redirect.');
    $state=roleWrite(['action'=>'project_delete']);
    roleAssert($state['dc_pm_projects'][0]['deleted_at']!==null,'HTTP project soft delete failed.');
    $landing=roleHttp('PM','op=pmprojects',200);
    roleAssert(str_contains($landing,'Đã ẩn dự án.')&&str_contains($landing,'pm-project-list')&&!str_contains($landing,'data-pm-kanban'),'Hidden project did not redirect to list.');
    roleAssert(!str_contains(roleHttp('PM','op=pmprojects',200),'Đã ẩn dự án.'),'Hide notice repeated on refresh.');
    foreach([['action'=>'project_save','name'=>''],['action'=>'project_save','csrf_token'=>'wrong'],['action'=>'project_delete','csrf_token'=>'wrong']] as $bad){
        $state=roleWrite($bad,'PM',200);
        roleAssert($state['dc_pm_projects'][0]['name']==='HTTP own project'&&$state['dc_pm_projects'][0]['deleted_at']===null&&!$state['tracking'],'Failed project action changed data/redirected.');
    }
    foreach(['0','900000001'] as $dateProject)foreach(['2026-10-02','2026-10-01'] as $dateEnd){
        $state=roleWrite(['action'=>'project_save','project_id'=>$dateProject,'code'=>'HTTP-DATE','name'=>'Invalid project dates','status'=>'active','budget'=>'0','start_date'=>'2026-10-02','end_date'=>$dateEnd]);
        roleAssert(count($state['dc_pm_projects'])===2&&$state['dc_pm_projects'][0]['name']==='HTTP own project'&&!$state['tracking'],'Invalid project dates created/changed data.');
        $landing=roleHttp('PM','op=pmprojects',200);
        roleAssert(str_contains($landing,'Ngày kết thúc dự án phải sau ngày bắt đầu, không được trùng ngày.')&&str_contains($landing,'pm-project-list')&&!str_contains($landing,'data-pm-kanban'),'Invalid date error/landing missing.');
        roleAssert(!str_contains(roleHttp('PM','op=pmprojects',200),'không được trùng ngày'),'Invalid date error repeated.');
    }
    $state=roleWrite(['task_id'=>'900000011','start_date'=>'2026-10-02','due_date'=>'2026-10-02']);
    roleAssert(roleTask($state,900000011)['start_date']==='2026-10-02'&&roleTask($state,900000011)['due_date']==='2026-10-02'&&count($state['dc_pm_audit_logs'])===1,'Same-day task save rejected.');
    $state=roleTimeWrite([]);
    $created=array_values(array_filter($state['dc_pm_timesheets'],fn($s)=>$s['shift_label']==='HTTP new'));
    roleAssert(count($created)===1&&$created[0]['regular_hours']==='0.00'&&$created[0]['ot_hours']==='2.00'&&$created[0]['cost']==='300.00'&&$created[0]['rate_snapshot']==='100.00','HTTP timesheet create OT/rate/cost failed.');
    roleAssert(($state['dc_pm_audit_logs'][0]['action']??'')==='create','Timesheet create audit missing.');
    $state=roleTimeWrite(['id'=>'900000021']);
    roleAssert(roleTime($state,900000021)['hours']==='2.00'&&roleTime($state,900000022)['regular_hours']==='6.00'&&roleTime($state,900000022)['ot_hours']==='3.00'&&roleTime($state,900000022)['cost']==='1050.00','HTTP edit did not recalculate other daily record.');
    roleAssert(array_column($state['dc_pm_audit_logs'],'action')===['recalculate','update'],'Timesheet edit/recalculate audit missing.');
    $yesterday=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->modify('-1 day')->format('Y-m-d');
    $state=roleTimeWrite(['id'=>'900000021','work_date'=>$yesterday]);
    roleAssert(roleTime($state,900000021)['cost']==='200.00'&&roleTime($state,900000022)['regular_hours']==='8.00'&&roleTime($state,900000022)['ot_hours']==='1.00'&&roleTime($state,900000022)['cost']==='950.00','HTTP date move did not recalculate both days.');
    $state=roleTimeWrite(['id'=>'900000021','action'=>'delete']);
    roleAssert(roleTime($state,900000021)['deleted_at']!==null&&roleTime($state,900000022)['cost']==='950.00','HTTP timesheet hide did not recalculate day.');
    foreach ([['csrf_token'=>'wrong'],['hours'=>'24'],['task_id'=>'900000013'],['work_date'=>'2099-01-01']] as $bad) roleTimeUnchanged(roleTimeWrite($bad));
    roleTimeUnchanged(roleTimeWrite(['id'=>'900000023'], 'PM',403));
    foreach (['PM','HR'] as $role) {
        $state=roleTimeWrite(['action'=>'settings','standard_hours_per_day'=>'7','ot_multiplier'=>'2'],$role);
        roleTimeUnchanged($state);
        roleAssert(array_column($state['dc_pm_system_settings'],'setting_value','setting_key')===['standard_hours_per_day'=>'8.00','ot_multiplier'=>'1.50'],'Non-Admin changed OT settings.');
    }
    $state=roleAllocationWrite([]);
    roleAssert(count($state['dc_pm_allocations'])===2&&($state['dc_pm_audit_logs'][0]['action']??'')==='create','HTTP allocation create/audit failed.');
    roleAssert(count($state['warnings'])===3,'Missing daily/weekly/overlap warnings.');
    foreach(['ngày','tuần','Trùng'] as $word) roleAssert(str_contains(implode(' ',$state['warnings']),$word),'Missing allocation warning '.$word);
    $state=roleAllocationWrite(['id'=>'900000031','start_time'=>'08:00','end_time'=>'10:00']);
    roleAssert($state['dc_pm_allocations'][0]['hours']==='2.00'&&($state['dc_pm_audit_logs'][0]['action']??'')==='update'&&!$state['warnings'],'HTTP allocation edit/audit failed.');
    $state=roleAllocationWrite(['id'=>'900000031','action'=>'delete']);
    roleAssert($state['dc_pm_allocations'][0]['deleted_at']!==null&&($state['dc_pm_audit_logs'][0]['action']??'')==='soft_delete','HTTP allocation hide/audit failed.');
    foreach ([[['end_time'=>'17:00'],400],[['csrf_token'=>'wrong'],400],[['task_id'=>'900000013'],403],[['action'=>'capacity','daily_limit_hours'=>'7','weekly_limit_hours'=>'30','effective_from'=>'2000-01-02'],403]] as [$bad,$expected]) {
        $state=roleAllocationWrite($bad,$expected);
        roleAssert(count($state['dc_pm_allocations'])===1&&$state['dc_pm_allocations'][0]['hours']==='7.00'&&!$state['dc_pm_audit_logs'],'Rejected allocation action wrote data/audit.');
        roleAssert($state['dc_pm_capacity_overrides'][0]['daily_limit_hours']==='8.00'&&$state['dc_pm_capacity_overrides'][0]['weekly_limit_hours']==='8.00','PM changed capacity override.');
    }
    roleHttp('HR','op=pmallocations',403,['op'=>'pmallocations','action'=>'save','csrf_token'=>'role-http-csrf']);
    $state=roleWrite(['action'=>'task_status','task_id'=>'900000011','status'=>'done']);
    roleAssert(roleTask($state,900000011)['status']==='done'&&roleTask($state,900000011)['completed_at']!==null&&count($state['dc_pm_audit_logs'])===1&&count($state['tracking'])===1,'Status POST fallback/audit/tracking failed.');
    roleAssert(roleTask($state,900000011)['name']==='HTTP todo task','Status POST overwrote task name.');
    foreach([['csrf_token'=>'wrong'],['task_id'=>'900000013'],['task_id'=>'999999999'],['status'=>'invalid'],['task_id'=>'900000011junk'],['project_id'=>'900000001junk']] as $bad){
        $state=roleWrite(array_replace(['action'=>'task_status','task_id'=>'900000011','status'=>'done'],$bad));
        roleAssert(roleTask($state,900000011)['status']==='todo'&&!$state['dc_pm_audit_logs']&&!$state['tracking'],'Rejected fallback wrote status/audit/tracking.');
    }
    roleWrite(['action'=>'task_status','task_id'=>'900000011','status'=>'done'],'HR',403);
    $state=roleUserWrite([]);
    roleAssert(roleUser($state,900000041)['fullname']==='HTTP Updated'&&roleUser($state,900000041)['tel']==='0900000000'&&$state['tracking_count']===1,'HTTP personnel edit/tracking failed.');
    $state=roleUserWrite(['fullname'=>'HTTP Member','email'=>'http-member@example.test','weekly_limit_hours'=>'40']);
    roleAssert(str_contains($state['_html'],'Đã lưu thông tin nhân sự.')&&$state['tracking_count']===1,'Unchanged valid personnel form rejected.');
    $personnelCount=count($state['users']);
    $newPerson=['id'=>'0','username'=>'http-new-person','password'=>'password123','email'=>'http-new@example.test'];
    foreach(['','0901234567'] as $phone){
        $state=roleUserWrite(array_replace($newPerson,['tel'=>$phone]));
        roleAssert(count($state['users'])===$personnelCount+1&&$state['tracking_count']===1,'Valid create/optional phone rejected.');
    }
    foreach([['password'=>''],['password'=>'1234567'],['password'=>'        '],['email'=>'invalid'],['tel'=>'090abc1234'],['tel'=>'09012345678'],['tel'=>'+84901234567'],['tel'=>'090 123456'],['tel'=>"0901234567\n"],['csrf_token'=>'wrong']] as $bad){
        $state=roleUserWrite(array_replace($newPerson,$bad));
        roleAssert(count($state['users'])===$personnelCount&&$state['tracking_count']===0&&!str_contains($state['_html'],'Đã lưu thông tin nhân sự.'),'Rejected create wrote personnel/tracking or reported success.');
    }
    roleUserWrite(array_replace($newPerson,['tel'=>['0901234567']]),'HR',400);
    $state=roleUserWrite(['action'=>'status','status'=>'0']);
    roleAssert((int)roleUser($state,900000041)['status']===0&&$state['tracking_count']===1,'HR lock failed.');
    $state=roleUserWrite(['action'=>'status','status'=>'1','id'=>'900000042']);
    roleAssert((int)roleUser($state,900000042)['status']===1,'HR unlock failed.');
    $state=roleUserWrite(['action'=>'status','status'=>'2']);
    roleAssert((int)roleUser($state,900000041)['status']===2,'HR soft delete failed.');
    foreach([['csrf_token'=>'wrong'],['email'=>'http-locked@example.test'],['email'=>'invalid'],['action'=>'status','status'=>'0','id'=>(string)$actor['id']]] as $bad) {
        $state=roleUserWrite($bad);
        roleAssert(roleUser($state,900000041)['fullname']==='HTTP Member'&&$state['tracking_count']===0,'Rejected personnel write changed data/tracking.');
        roleAssert((int)roleUser($state,(int)$actor['id'])['status']===1,'Actor locked itself.');
    }
    roleUserWrite(['action'=>'status','status'=>'0'],'PM',403);
    foreach(['900000043','900000044','999999999'] as $invalidTarget) {
        $state=roleUserWrite(['id'=>$invalidTarget]);
        roleAssert(!str_contains($state['_html'],'Đã lưu thông tin nhân sự.')&&$state['tracking_count']===0,'Invalid personnel target reported successful save.');
    }
} finally {
    proc_terminate($server); proc_close($server);
    putenv('PM_ROLE_HTTP_TOKEN'); putenv('PM_ROLE_HTTP_ACTOR'); putenv('PM_ROLE_HTTP_STORE');
    session_id($sid); session_start(); session_destroy();
    if (rolePersistentState($q)!==$before) throw new RuntimeException('Persistent role/project state or user engine changed.');
}
echo "PASS: $cases PM/HR HTTP controller cases with temporary fixtures; pages/reports/XLSX, project/task/timesheet/allocation writes/audit, daily OT/cost, overbooking/overlap warnings and rejection boundaries. Persistent business tables/settings/rates/locks and user engine unchanged. Not real-account login/browser UAT.\n";
