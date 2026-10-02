<?php
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
define('DB_PREFIX', 'dc_');
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/mysql.class.php';
require ROOT_PATH.'classes/security/pmauth.class.php';
require ROOT_PATH.'classes/security/pmaccess.class.php';

if (($config['db_name'] ?? '') !== 'derasoft_pm_local') {
    fwrite(STDERR, "Refusing to test against non-local database.\n");
    exit(1);
}

if (!defined('DEBUG')) define('DEBUG', false);
if (!defined('QUERY_ERROR')) define('QUERY_ERROR', false);
if (!defined('QUERY_DEBUG')) define('QUERY_DEBUG', false);
$db = new DB();

$failures = [];
$password = 'Phase2-test-password';
$hash = password_hash($password, PASSWORD_DEFAULT);
if (!password_verify($password, $hash) || password_verify('wrong', $hash)) {
    $failures[] = 'password_hash/password_verify behavior failed';
}

$pmDb = new PmDb($db);
$stores = $pmDb->fetchAll('SELECT DISTINCT store_id FROM dc_pm_roles ORDER BY store_id');
foreach ($stores as $store) {
    $storeId = (int) $store['store_id'];
    $roles = $pmDb->fetchAll(
        'SELECT code FROM dc_pm_roles WHERE store_id = ? AND status = 1 ORDER BY code',
        'i',
        [$storeId]
    );
    if (array_column($roles, 'code') !== ['ADMIN', 'EMPLOYEE', 'HR', 'PM']) {
        $failures[] = 'role set invalid for store '.$storeId;
    }
}

$adminMissing = $pmDb->fetchOne(
    "SELECT COUNT(*) AS total FROM dc_pm_permissions p
     WHERE NOT EXISTS (
       SELECT 1 FROM dc_pm_roles r
       JOIN dc_pm_role_permissions rp ON rp.role_id = r.id
       WHERE r.code = 'ADMIN' AND rp.permission_id = p.id
     )"
);
if ((int) ($adminMissing['total'] ?? -1) !== 0) {
    $failures[] = 'ADMIN does not contain every permission';
}

$employeeForbidden = $pmDb->fetchOne(
    "SELECT COUNT(*) AS total FROM dc_pm_roles r
     JOIN dc_pm_role_permissions rp ON rp.role_id = r.id
     JOIN dc_pm_permissions p ON p.id = rp.permission_id
     WHERE r.code = 'EMPLOYEE' AND p.code IN ('pm.rbac.manage', 'pm.users.manage', 'pm.timesheets.approve')"
);
if ((int) ($employeeForbidden['total'] ?? -1) !== 0) {
    $failures[] = 'EMPLOYEE received privileged permissions';
}

$unassigned = $pmDb->fetchOne(
    'SELECT COUNT(*) AS total FROM dc_users u
     WHERE u.status = 1 AND NOT EXISTS (
       SELECT 1 FROM dc_pm_user_roles ur WHERE ur.store_id = u.store_id AND ur.user_id = u.id
     )'
);
if ((int) ($unassigned['total'] ?? -1) !== 0) {
    $failures[] = 'an active legacy user has no compatibility role';
}

$adminUser = $pmDb->fetchOne(
    "SELECT ur.store_id, ur.user_id FROM dc_pm_user_roles ur
     JOIN dc_pm_roles r ON r.id = ur.role_id
     WHERE r.code = 'ADMIN' LIMIT 1"
);
if ($adminUser) {
    $adminAccess = new PmAccess($db, (int) $adminUser['store_id'], (int) $adminUser['user_id']);
    if (!$adminAccess->hasPermission('pm.rbac.manage') || $adminAccess->hasProjectAccess(999999)) {
        $failures[] = 'ADMIN permission or nonexistent project boundary failed';
    }
}

$employeeUser = $pmDb->fetchOne(
    "SELECT ur.store_id, ur.user_id FROM dc_pm_user_roles ur
     JOIN dc_pm_roles r ON r.id = ur.role_id
     WHERE r.code = 'EMPLOYEE' LIMIT 1"
);
if ($employeeUser) {
    $employeeAccess = new PmAccess($db, (int) $employeeUser['store_id'], (int) $employeeUser['user_id']);
    if (!$employeeAccess->hasPermission('pm.dashboard.view')
        || $employeeAccess->hasPermission('pm.team.view')
        || $employeeAccess->hasProjectAccess(999999)) {
        $failures[] = 'EMPLOYEE permission or project boundary failed';
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL: '.$failure.PHP_EOL);
    }
    exit(1);
}
echo "PASS: password primitives, four roles, permission boundaries and legacy mappings verified.\n";
