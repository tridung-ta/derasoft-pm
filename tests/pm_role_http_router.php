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
    || !in_array($_GET['op'] ?? '', ['pm','pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'], true)
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && !(
        (($_POST['op'] ?? '') === 'pmreports' && ($_POST['action'] ?? '') === 'export')
        || (($_POST['op'] ?? '') === 'pmprojects' && in_array($_POST['action'] ?? '', ['project_save','project_delete','task_save','task_delete'], true))
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
        foreach (['dc_pm_user_roles','dc_pm_projects','dc_pm_project_members','dc_pm_tasks','dc_pm_audit_logs','dc_trackings'] as $table) {
            $ddl=$c->query('SHOW CREATE TABLE '.$table)->fetch_row()[1];
            $ddl=preg_replace('/,\n\s*CONSTRAINT[^\n]+/', '', $ddl);
            $c->query(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $ddl));
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
        return $result;
    }
}
$entry = file_get_contents(ROOT_PATH.'admin.php');
$entry = str_replace("include_once(ROOT_PATH.'classes/database/mysql.class.php');", '', $entry, $count);
if ($count !== 1) throw new RuntimeException('Admin fixture entry no longer matches.');
eval('?>'.$entry);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['op'] ?? '') === 'pmprojects') {
    // Observe request-local committed state before DB teardown; never a production endpoint.
    $fixtureState=[];
    foreach (['dc_pm_projects','dc_pm_tasks','dc_pm_audit_logs'] as $table) {
        $fixtureState[$table]=$db->connection->query('SELECT * FROM '.$table.' ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    }
    echo '\n<!-- PM_FIXTURE_STATE '.base64_encode(json_encode($fixtureState,JSON_THROW_ON_ERROR)).' -->';
}
