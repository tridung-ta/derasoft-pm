<?php
/**
 * Local-only Phase 2 migration runner and schema verifier.
 * Usage: php tests/pm_phase2_migration.php --apply
 */
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';

$databaseName = (string) ($config['db_name'] ?? '');
if ($databaseName !== 'derasoft_pm_local') {
    fwrite(STDERR, "Refusing to change non-local database.\n");
    exit(1);
}

$connection = new mysqli(
    $config['db_server'],
    $config['db_user'],
    $config['db_pwd'],
    $databaseName
);
if ($connection->connect_errno) {
    fwrite(STDERR, "Unable to connect to local test database.\n");
    exit(1);
}
$connection->set_charset('utf8mb4');

function hasColumn(mysqli $connection, string $table, string $column): bool {
    $statement = $connection->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
    );
    $statement->bind_param('ss', $table, $column);
    $statement->execute();
    $statement->bind_result($total);
    $statement->fetch();
    $statement->close();
    return (int) $total === 1;
}

function runSqlFile(mysqli $connection, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false || !$connection->multi_query($sql)) {
        throw new RuntimeException('Migration execution failed: '.$connection->error);
    }
    do {
        if ($result = $connection->store_result()) {
            $result->free();
        }
        if (!$connection->more_results()) {
            break;
        }
    } while ($connection->next_result());
    if ($connection->errno) {
        throw new RuntimeException('Migration execution failed: '.$connection->error);
    }
}

if (in_array('--apply', $argv, true)) {
    if (!hasColumn($connection, 'dc_users', 'password_hash')) {
        runSqlFile($connection, ROOT_PATH.'database/migrations/001_create_pm_auth_rbac.sql');
    }
    runSqlFile($connection, ROOT_PATH.'database/seeds/001_seed_pm_auth_rbac.sql');
}

$requiredTables = [
    'dc_pm_roles',
    'dc_pm_permissions',
    'dc_pm_user_roles',
    'dc_pm_role_permissions',
];
$failures = [];
if (!hasColumn($connection, 'dc_users', 'password_hash')) {
    $failures[] = 'dc_users.password_hash is missing';
}
foreach ($requiredTables as $table) {
    $statement = $connection->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ? AND engine = ?'
    );
    $engine = 'InnoDB';
    $statement->bind_param('ss', $table, $engine);
    $statement->execute();
    $statement->bind_result($total);
    $statement->fetch();
    $statement->close();
    if ((int) $total !== 1) {
        $failures[] = $table.' is missing or not InnoDB';
    }
}

$expectedRoles = ['ADMIN', 'EMPLOYEE', 'HR', 'PM'];
$result = $connection->query('SELECT DISTINCT code FROM dc_pm_roles ORDER BY code');
$actualRoles = array_column($result->fetch_all(MYSQLI_ASSOC), 'code');
if ($actualRoles !== $expectedRoles) {
    $failures[] = 'default role seed is incomplete';
}

$result = $connection->query('SELECT COUNT(*) AS total FROM dc_pm_permissions');
$permissionCount = (int) $result->fetch_assoc()['total'];
if ($permissionCount !== 18) {
    $failures[] = 'expected 18 permissions, found '.$permissionCount;
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL: '.$failure.PHP_EOL);
    }
    exit(1);
}

echo "PASS: Phase 2 schema and RBAC seed verified on local database.\n";
