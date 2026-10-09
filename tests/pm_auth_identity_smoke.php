<?php
// A connection-local temporary table shadows dc_users; real accounts are untouched.
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/mysql.class.php';
require ROOT_PATH.'classes/security/pmauth.class.php';
if (($config['db_name'] ?? '') !== 'derasoft_pm_local'
    || !in_array($config['db_server'] ?? '', ['localhost', '127.0.0.1'], true)) {
    fwrite(STDERR, "Refusing non-local auth tests.\n");
    exit(1);
}
foreach (['DEBUG', 'QUERY_ERROR', 'QUERY_DEBUG'] as $constant) {
    if (!defined($constant)) define($constant, false);
}
$db = new DB();
$pmDb = new PmDb($db);
$db->connection->set_charset('utf8mb4');
$persistentUsers=$pmDb->fetchAll('SELECT * FROM dc_users ORDER BY id');
$ddl=$pmDb->fetchOne('SHOW CREATE TABLE dc_users')['Create Table'];
$pmDb->execute(preg_replace('/^CREATE TABLE `dc_users`/','CREATE TEMPORARY TABLE `dc_users`',$ddl));
$oldPassword = 'Old-shared-password';
$newPassword = 'New-shared-password';
$pmDb->execute('INSERT INTO dc_users(id,store_id,username,email,password,password_hash,status,last_login) VALUES (1,1,?,?,?,NULL,1,NULL)', 'sss',
    ['shared-user', 'shared@example.test', md5($oldPassword)]);
$auth = new PmAuth($db);
$checks = 0;
function checkAuth($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
foreach (['shared-user', 'shared@example.test'] as $identity) {
    checkAuth($auth->findUserIdByIdentity(1, $identity) === 1, 'Identifier must resolve to same account');
    checkAuth($auth->authenticate(1, $identity, $oldPassword) === 1, 'Legacy upgrade must share credentials');
    $upgraded=$pmDb->fetchOne('SELECT password_hash,last_login FROM dc_users WHERE store_id=? AND id=?','ii',[1,1]);
    checkAuth(password_verify($oldPassword,(string)$upgraded['password_hash'])&&password_get_info($upgraded['password_hash'])['algo']!==null,'Modern credential not persisted after MD5 login');
    checkAuth($upgraded['last_login']!==null,'Successful legacy login timestamp not persisted');
    checkAuth($auth->authenticate(1, $identity, 'wrong') === 0, 'Wrong password accepted');
}
checkAuth($auth->changePassword(1, 1, $newPassword), 'Password change failed');
foreach (['shared-user', 'shared@example.test'] as $identity) {
    checkAuth($auth->authenticate(1, $identity, $newPassword) === 1, 'New password must work for both identities');
    checkAuth($auth->authenticate(1, $identity, $oldPassword) === 0, 'Old MD5 must not bypass new password');
    checkAuth($auth->authenticate(2, $identity, $newPassword) === 0, 'Cross-tenant login accepted');
}
$pmDb->execute('UPDATE dc_users SET status=0 WHERE id=1');
foreach (['shared-user', 'shared@example.test'] as $identity) {
    checkAuth($auth->authenticate(1, $identity, $newPassword) === -1, 'Disabled account accepted');
}
$pmDb->execute('UPDATE dc_users SET status=1 WHERE id=1');
$pmDb->execute("INSERT INTO dc_users(id,store_id,username,email,password,password_hash,status,last_login) VALUES (2,1,?,?,'',?,1,NULL)", 'sss',
    ['shared@example.test', 'other@example.test', password_hash($oldPassword, PASSWORD_DEFAULT)]);
checkAuth($auth->findUserIdByIdentity(1, 'shared@example.test') === 0, 'Collision must fail closed');
foreach ([$oldPassword, $newPassword] as $password) {
    checkAuth($auth->authenticate(1, 'shared@example.test', $password) === 0, 'Collision authenticated wrong account');
}
checkAuth($auth->authenticate(1, 'shared-user', $newPassword) === 1, 'Unambiguous username regressed');
$legacy = file_get_contents(ROOT_PATH.'modules/admin/profile/password.module.php');
checkAuth(str_contains($legacy, 'changePasswordSecure($userId, $new_password)')
    && !str_contains($legacy, "array('password' =>"), 'Legacy password route writes stale credential');
$verify=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$verify->set_charset('utf8mb4');try{checkAuth((new PmDb((object)['connection'=>$verify]))->fetchAll('SELECT * FROM dc_users ORDER BY id')===$persistentUsers,'Persistent accounts changed');}finally{$verify->close();}
echo "PASS 2.2: MD5 login persisted modern hash and last_login; exact-schema users mirror; persistent accounts unchanged.\n";
echo "PASS: $checks shared identity/password checks; temporary table only, no real accounts changed.\n";
