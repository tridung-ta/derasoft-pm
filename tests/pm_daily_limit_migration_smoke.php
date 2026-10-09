<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['127.0.0.1','localhost'],true))throw new RuntimeException('Non-local refused');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$q=new PmDb((object)['connection'=>$c]);
$ddl=$q->fetchOne('SHOW CREATE TABLE dc_pm_timesheet_days')['Create Table'];$ddl=preg_replace('/,\n\s*CONSTRAINT[^\n]+/','',$ddl);$ddl=preg_replace('/^\s*`max_total_hours`[^\n]+\n/m','',$ddl);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$ddl=str_replace('CREATE TABLE `dc_pm_timesheet_days`','CREATE TEMPORARY TABLE `pm_daily_limit_pre_schema`',$ddl);$q->execute($ddl);
$q->execute("INSERT INTO pm_daily_limit_pre_schema(store_id,user_id,work_date,standard_hours,ot_multiplier) VALUES(1,1,'2020-01-01',8,1.5),(1,1,'2020-01-02',8,1)");
$before=$q->fetchAll('SELECT * FROM pm_daily_limit_pre_schema ORDER BY work_date');
$q->execute('ALTER TABLE pm_daily_limit_pre_schema ADD COLUMN max_total_hours DECIMAL(4,2) NOT NULL DEFAULT 24.00');
$after=$q->fetchAll('SELECT * FROM pm_daily_limit_pre_schema ORDER BY work_date');foreach($after as &$row){if($row['max_total_hours']!=='24.00')throw new RuntimeException('Legacy default not24');unset($row['max_total_hours']);}unset($row);if($after!==$before)throw new RuntimeException('Legacy fixture data changed');
$sql=file_get_contents(ROOT_PATH.'database/migrations/010_add_pm_daily_total_limit.sql');if(!str_contains($sql,'information_schema.columns')||preg_match('/\b(DROP|TRUNCATE|DELETE|RENAME|MODIFY)\b/i',preg_replace('/^--.*$/m','',$sql)))throw new RuntimeException('Migration guard/additive policy mismatch');
echo "PASS P3 migration: two pre-column ledger fixtures retain all old values, new max_total_hours24.00; additive guarded migration; temporary table only.\n";
