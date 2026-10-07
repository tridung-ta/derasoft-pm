<?php
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
if (($config['db_name'] ?? '') !== 'derasoft_pm_local') exit("Refusing non-local database.\n");
$db = new mysqli($config['db_server'], $config['db_user'], $config['db_pwd'], $config['db_name']);
$db->set_charset('utf8mb4');

function phase3Column(mysqli $db, string $table, string $column): bool {
    $s=$db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
    $s->bind_param('ss',$table,$column);$s->execute();$s->bind_result($n);$s->fetch();$s->close();return (int)$n===1;
}
function phase3Run(mysqli $db, string $file): void {
    $sql=file_get_contents($file);
    if(!$db->multi_query($sql)) throw new RuntimeException($db->error);
    do { if($r=$db->store_result())$r->free(); if(!$db->more_results())break; } while($db->next_result());
    if($db->errno) throw new RuntimeException($db->error);
}
if(in_array('--apply',$argv,true) && !phase3Column($db,'dc_users','department_id')) {
    phase3Run($db,ROOT_PATH.'database/migrations/002_create_pm_users_rates.sql');
}
$fail=[];
foreach(['dc_pm_roles','dc_pm_user_roles','dc_pm_permissions','dc_pm_role_permissions','dc_pm_departments','dc_pm_hourly_rates'] as $table){
    $s=$db->prepare('SELECT column_type,is_nullable FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name="store_id"');
    $s->bind_param('s',$table);$s->execute();$row=$s->get_result()->fetch_row();$s->close();
    if(!$row || strtolower((string)$row[0])!=='bigint unsigned' || $row[1]!=='NO')$fail[]=$table.'.store_id invalid';
}
foreach(['department_id','weekly_limit_hours'] as $column) if(!phase3Column($db,'dc_users',$column))$fail[]='dc_users.'.$column.' missing';
if($fail){foreach($fail as $f)fwrite(STDERR,'FAIL: '.$f.PHP_EOL);exit(1);}
echo "PASS: Phase 3 tenant schema verified.\n";
