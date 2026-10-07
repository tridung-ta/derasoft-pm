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
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['op'] ?? '') !== 'pmreports' || ($_POST['action'] ?? '') !== 'export'))) {
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
        $ddl=$c->query('SHOW CREATE TABLE dc_pm_user_roles')->fetch_row()[1];
        // MySQL temporary tables cannot carry FK constraints; retain columns/indexes.
        $ddl=preg_replace('/,\n\s*CONSTRAINT[^\n]+/', '', $ddl);
        $c->query(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $ddl));
        $stmt = $c->prepare('INSERT INTO dc_pm_user_roles (store_id,user_id,role_id,is_primary) SELECT ?,?,id,1 FROM dc_pm_roles WHERE store_id=? AND code=? AND status=1');
        $stmt->bind_param('iiis', $store, $actor, $store, $role); $stmt->execute();
        if ($stmt->affected_rows !== 1) throw new RuntimeException('Missing unique fixture role.');
        $ddl=$c->query('SHOW CREATE TABLE dc_pm_projects')->fetch_row()[1];
        $c->query(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $ddl));
        $stmt = $c->prepare("INSERT INTO dc_pm_projects (id,store_id,code,name,manager_id,created_by) VALUES (900000001,?,'HTTP-OWN','HTTP own project',?,?), (900000002,?,'HTTP-FOREIGN','HTTP foreign project',999999999,?)");
        $stmt->bind_param('iiiii', $store, $actor, $actor, $store, $actor); $stmt->execute();
        return $result;
    }
}
$entry = file_get_contents(ROOT_PATH.'admin.php');
$entry = str_replace("include_once(ROOT_PATH.'classes/database/mysql.class.php');", '', $entry, $count);
if ($count !== 1) throw new RuntimeException('Admin fixture entry no longer matches.');
eval('?>'.$entry);
