<?php
/** Test-only DB decorator: real HTTP controllers, connection-local role/project fixtures. */
if (PHP_SAPI !== 'cli-server' || ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1'
    || !getenv('PM_ROLE_HTTP_TOKEN')
    || !hash_equals(getenv('PM_ROLE_HTTP_TOKEN'), $_SERVER['HTTP_X_PM_TEST_TOKEN'] ?? '')
    || parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/admin.php') {
    http_response_code(404); exit;
}
$fixtureRole = $_SERVER['HTTP_X_PM_TEST_ROLE'] ?? '';
if (!in_array($fixtureRole, ['PM', 'HR'], true)
    || !in_array($_SERVER['REQUEST_METHOD']??'', ['GET','POST'], true)
    || !in_array($_GET['op'] ?? '', ['pm','pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'], true)
    || (($_SERVER['REQUEST_METHOD']??'')==='POST' && ($_POST['op']??'')!==($_GET['op']??''))
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && !(
        (($_POST['op'] ?? '') === 'pmreports' && ($_POST['action'] ?? '') === 'export')
        || (($_POST['op'] ?? '') === 'pmprojects' && in_array($_POST['action'] ?? '', ['project_save','project_delete','task_save','task_delete'], true))
        || (($_POST['op'] ?? '') === 'pmtimesheets' && in_array($_POST['action'] ?? '', ['save','delete','settings'], true))
        || (($_POST['op'] ?? '') === 'pmallocations' && in_array($_POST['action'] ?? '', ['save','delete','capacity'], true))
        || (($_POST['op'] ?? '') === 'pmusers' && in_array($_POST['action'] ?? '', ['save','status'], true))
    ))) {
    http_response_code(400); exit;
}
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
if (($config['db_name'] ?? '') !== 'derasoft_pm_local'
    || !in_array($config['db_server'], ['localhost', '127.0.0.1'], true)) {
    http_response_code(503); exit;
}
// Rename only the trusted repository class declaration, retaining every DB method.
// No request text is evaluated and no production file is modified.
$source = file_get_contents(ROOT_PATH.'classes/database/mysql.class.php');
$source = preg_replace('/\bclass DB\s*\{/', 'class PmRoleHttpBaseDb {', $source, 1, $count);
if ($count !== 1) throw new RuntimeException('DB fixture decorator no longer matches.');
eval('?>'.$source);
// The in-memory entry point below omits the original DB declaration only.
// Controllers, middleware, DAO and Smarty rendering still execute unchanged.
class DB extends PmRoleHttpBaseDb {
    public function initialize($persistent = 0, $server = '', $user = '', $password = '', $name = '') {
        $result = parent::initialize($persistent, $server, $user, $password, $name);
        $c = $this->connection;
        $actor = (int)getenv('PM_ROLE_HTTP_ACTOR');
        $store = (int)getenv('PM_ROLE_HTTP_STORE');
        $role = $_SERVER['HTTP_X_PM_TEST_ROLE'];
        // Shadow every table that the allowed project mutations can write.
        // MySQL temporary tables cannot carry FK constraints; retain columns/indexes.
        $tables=['dc_pm_user_roles','dc_pm_projects','dc_pm_project_members','dc_pm_tasks','dc_pm_audit_logs','dc_trackings'];
        if (($_GET['op']??'')==='pmtimesheets') $tables=[...$tables,'dc_pm_timesheets','dc_pm_timesheet_days','dc_pm_system_settings','dc_pm_hourly_rates'];
        if (($_GET['op']??'')==='pmallocations') $tables=[...$tables,'dc_pm_allocations','dc_pm_allocation_locks','dc_pm_capacity_overrides'];
        $actorRow=null;
        if (($_GET['op']??'')==='pmusers') {
            $stmt=$c->prepare('SELECT * FROM dc_users WHERE store_id=? AND id=?');
            $stmt->bind_param('ii',$store,$actor); $stmt->execute();
            $actorRow=$stmt->get_result()->fetch_assoc();
            if (!$actorRow) throw new RuntimeException('Missing local actor row.');
            $tables[]='dc_users';
        }
        foreach ($tables as $table) {
            $ddl=$c->query('SHOW CREATE TABLE '.$table)->fetch_row()[1];
            $ddl=preg_replace('/,\n\s*CONSTRAINT[^\n]+/', '', $ddl);
            $c->query(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $ddl));
        }
        if ($actorRow) {
            // Keep the real actor only in memory; never expose copied credentials.
            $columns=array_keys($actorRow);
            $stmt=$c->prepare('INSERT INTO dc_users (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');
            foreach ([$actorRow,
                array_replace($actorRow,['id'=>900000041,'username'=>'http-member','email'=>'http-member@example.test','fullname'=>'HTTP Member','code'=>'HTTP41','status'=>1,'tel'=>'0900000000','weekly_limit_hours'=>'40.00','department_id'=>null]),
                array_replace($actorRow,['id'=>900000042,'username'=>'http-locked','email'=>'http-locked@example.test','fullname'=>'HTTP Locked','code'=>'HTTP42','status'=>0]),
                array_replace($actorRow,['id'=>900000043,'store_id'=>$store+1,'username'=>'http-other','email'=>'http-other@example.test','fullname'=>'HTTP Other Tenant','code'=>'HTTP43','status'=>1]),
                array_replace($actorRow,['id'=>900000044,'username'=>'http-hidden','email'=>'http-hidden@example.test','fullname'=>'HTTP Hidden','code'=>'HTTP44','status'=>2])
            ] as $row) {
                $values=array_values($row); $stmt->bind_param(str_repeat('s',count($values)),...$values); $stmt->execute();
            }
        }
        $stmt = $c->prepare('INSERT INTO dc_pm_user_roles (store_id,user_id,role_id,is_primary) SELECT ?,?,id,1 FROM dc_pm_roles WHERE store_id=? AND code=? AND status=1');
        $stmt->bind_param('iiis', $store, $actor, $store, $role); $stmt->execute();
        if ($stmt->affected_rows !== 1) throw new RuntimeException('Missing unique fixture role.');
        $stmt = $c->prepare("INSERT INTO dc_pm_projects (id,store_id,code,name,manager_id,created_by) VALUES (900000001,?,'HTTP-OWN','HTTP own project',?,?), (900000002,?,'HTTP-FOREIGN','HTTP foreign project',999999999,?)");
        $stmt->bind_param('iiiii', $store, $actor, $actor, $store, $actor); $stmt->execute();
        $stmt=$c->prepare('INSERT INTO dc_pm_project_members (store_id,project_id,user_id) VALUES (?,900000001,?)');
        $stmt->bind_param('ii',$store,$actor); $stmt->execute();
        $stmt=$c->prepare("INSERT INTO dc_pm_tasks (id,store_id,project_id,name,status,created_by,completed_at) VALUES (900000011,?,900000001,'HTTP todo task','todo',?,NULL), (900000012,?,900000001,'HTTP done task','done',?,'2026-01-01 09:00:00'), (900000013,?,900000002,'HTTP foreign task','todo',?,NULL)");
        $stmt->bind_param('iiiiii',$store,$actor,$store,$actor,$store,$actor); $stmt->execute();
        $stmt=$c->prepare('UPDATE dc_pm_tasks SET assignee_id=? WHERE id=900000011');
        $stmt->bind_param('i',$actor); $stmt->execute();
        if (($_GET['op']??'')==='pmallocations') {
            $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
            $stmt=$c->prepare("INSERT INTO dc_pm_allocations (id,store_id,project_id,task_id,user_id,work_date,hours,start_time,end_time,created_by,updated_by) VALUES (900000031,?,900000001,900000011,?,?,'7.00','08:00','15:00',?,?)");
            $stmt->bind_param('iisii',$store,$actor,$today,$actor,$actor); $stmt->execute();
            $stmt=$c->prepare("INSERT INTO dc_pm_capacity_overrides (store_id,user_id,daily_limit_hours,weekly_limit_hours,effective_from,created_by,updated_by) VALUES (?,?,'8.00','8.00','2000-01-02',?,?)");
            $stmt->bind_param('iiii',$store,$actor,$actor,$actor); $stmt->execute();
        }
        if (($_GET['op']??'')!=='pmtimesheets') return $result;
        $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        $stmt=$c->prepare("INSERT INTO dc_pm_system_settings (store_id,setting_key,setting_value) VALUES (?,'standard_hours_per_day','8.00'), (?,'ot_multiplier','1.50')");
        $stmt->bind_param('ii',$store,$store); $stmt->execute();
        $stmt=$c->prepare("INSERT INTO dc_pm_hourly_rates (store_id,user_id,rate,currency,effective_from,status,created_by) VALUES (?,?,'100.00','VND','2000-01-01',1,?)");
        $stmt->bind_param('iii',$store,$actor,$actor); $stmt->execute();
        $stmt=$c->prepare("INSERT INTO dc_pm_timesheet_days (store_id,user_id,work_date,standard_hours,ot_multiplier) VALUES (?, ?, ?, '8.00', '1.50')");
        $stmt->bind_param('iis',$store,$actor,$today); $stmt->execute();
        $stmt=$c->prepare("INSERT INTO dc_pm_timesheets (id,store_id,user_id,project_id,task_id,work_date,shift_label,hours,rate_snapshot,rate_source,currency,regular_hours,ot_hours,ot_multiplier_snapshot,cost) VALUES (900000021,?,?,900000001,900000011,?,'HTTP first',4,'100.00','user','VND',4,0,1.50,400), (900000022,?,?,900000001,900000011,?,'HTTP second',9,'100.00','user','VND',4,5,1.50,1150), (900000023,?,999999999,900000002,900000013,?,'HTTP other user',1,'100.00','user','VND',1,0,1.50,100)");
        $stmt->bind_param('iisiisis',$store,$actor,$today,$store,$actor,$today,$store,$today); $stmt->execute();
        return $result;
    }
}
$entry = file_get_contents(ROOT_PATH.'admin.php');
$entry = str_replace("include_once(ROOT_PATH.'classes/database/mysql.class.php');", '', $entry, $count);
if ($count !== 1) throw new RuntimeException('Admin fixture entry no longer matches.');
eval('?>'.$entry);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($_POST['op'] ?? '',['pmprojects','pmtimesheets','pmallocations','pmusers'],true)) {
    // Observe request-local committed state before DB teardown; never a production endpoint.
    $fixtureState=[];
    $tables=['dc_pm_projects','dc_pm_tasks','dc_pm_audit_logs'];
    if (($_POST['op']??'')==='pmtimesheets') $tables=[...$tables,'dc_pm_timesheets','dc_pm_timesheet_days','dc_pm_system_settings'];
    if (($_POST['op']??'')==='pmallocations') $tables=[...$tables,'dc_pm_allocations','dc_pm_capacity_overrides'];
    foreach ($tables as $table) {
        $fixtureState[$table]=$db->connection->query('SELECT * FROM '.$table.' ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    }
    if (($_POST['op']??'')==='pmallocations') $fixtureState['warnings']=$warnings??[];
    if (($_POST['op']??'')==='pmusers') {
        $fixtureState['users']=$db->connection->query('SELECT id,store_id,fullname,email,tel,status,weekly_limit_hours FROM dc_users ORDER BY id')->fetch_all(MYSQLI_ASSOC);
        $fixtureState['tracking_count']=(int)$db->connection->query('SELECT COUNT(*) FROM dc_trackings')->fetch_row()[0];
    }
    echo '\n<!-- PM_FIXTURE_STATE '.base64_encode(json_encode($fixtureState,JSON_THROW_ON_ERROR)).' -->';
}
