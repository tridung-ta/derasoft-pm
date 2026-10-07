<?php
// Read-only audit of existing accounts; never fix, lock or remove users.
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/mysql.class.php';
require ROOT_PATH.'classes/services/pmuseridentityservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server']??'', ['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused');
foreach(['DEBUG','QUERY_ERROR','QUERY_DEBUG'] as $key)if(!defined($key))define($key,false);
$database=new DB();$q=new PmDb($database);$identity=new PmUserIdentityService($database);
$before=$q->fetchAll('SELECT * FROM dc_users ORDER BY id');
$pairs=$identity->scanPairs();
echo 'READ_ONLY identity collision scan: users='.count($before).' pairs='.count($pairs).PHP_EOL;
foreach($pairs as $pair)echo json_encode($pair,JSON_THROW_ON_ERROR).PHP_EOL;
if($q->fetchAll('SELECT * FROM dc_users ORDER BY id')!==$before)throw new RuntimeException('Users changed during scan.');
echo "PASS: scan executed for all stores/statuses using DB collation and same-store pairs; user rows unchanged. Finding count is reported separately, not a clean-data assertion.\n";
