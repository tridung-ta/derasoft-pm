<?php
define('ROOT_PATH',dirname(__DIR__).'/');require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/services/pmtimesheetservice.class.php';require ROOT_PATH.'classes/services/pmprojectservice.class.php';require ROOT_PATH.'classes/PhpSpreadSheet/PhpOffice/autoload.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local source refused.');
$f=json_decode(file_get_contents(ROOT_PATH.'.local/phase10/fixture.json'),true,512,JSON_THROW_ON_ERROR);if($f['database']!=='derasoft_pm_phase10_20261004')throw new RuntimeException('Wrong isolated database.');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$f['database']);$c->set_charset('utf8mb4');$db=(object)['connection'=>$c];$q=new PmDb($db);
$rows=$q->fetchAll('SELECT * FROM dc_pm_timesheets WHERE store_id=1 AND deleted_at IS NULL');
if(count($rows)!==1||$rows[0]['hours']!=='9.00'||$rows[0]['regular_hours']!=='8.00'||$rows[0]['ot_hours']!=='1.00')throw new RuntimeException('Browser actual/OT data mismatch.');
$book=PhpOffice\PhpSpreadsheet\IOFactory::load(ROOT_PATH.'.local/phase10-authenticated.xlsx');if($book->getActiveSheet()->getHighestRow()<2)throw new RuntimeException('Empty authenticated workbook.');$book->disconnectWorksheets();
ini_set('session.save_path',ROOT_PATH.'.local/phase10');ini_set('session.use_cookies','0');session_id(bin2hex(random_bytes(20)));session_start();$_SESSION=['userId'=>$f['ids']['PM'],'storeId'=>1,'pm_csrf_token'=>'phase10-negative-fixture'];$sid=session_id();session_write_close();
function phase10Http(string $query,string $sid): int {
    $ctx=stream_context_create(['http'=>['header'=>'Cookie: PHPSESSID='.$sid,'ignore_errors'=>true,'follow_location'=>0,'timeout'=>10]]);file_get_contents('http://127.0.0.1:18770/admin.php?'.$query,false,$ctx);preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$m);return (int)($m[1]??0);
}
if(phase10Http('op=pmcosts',$sid)!==200)throw new RuntimeException('PM not initially allowed.');
$q->execute("UPDATE dc_pm_roles SET status=0 WHERE store_id=1 AND code='PM'");
try{if(phase10Http('op=pmcosts',$sid)!==403)throw new RuntimeException('Old session retained revoked role.');}finally{$q->execute("UPDATE dc_pm_roles SET status=1 WHERE store_id=1 AND code='PM'");}
$q->execute('UPDATE dc_users SET status=0 WHERE id=?','i',[(int)$f['ids']['PM']]);
try{if(phase10Http('op=pm',$sid)!==401)throw new RuntimeException('Old session retained inactive actor.');}finally{$q->execute('UPDATE dc_users SET status=1 WHERE id=?','i',[(int)$f['ids']['PM']]);}
$times=new PmTimesheetService($db,1,(int)$f['ids']['ADMIN']);foreach($rows as $r)$times->delete((int)$r['id']);
$projects=new PmProjectService($db,1,(int)$f['ids']['ADMIN']);foreach($q->fetchAll('SELECT id FROM dc_pm_projects WHERE store_id=1 AND deleted_at IS NULL') as $p){foreach($projects->tasks((int)$p['id']) as $t)$projects->deleteTask((int)$p['id'],(int)$t['id']);$projects->deleteProject((int)$p['id']);}
// Keep schema/journal/history; only disable synthetic accounts in the isolated DB.
$q->execute('UPDATE dc_users SET status=2 WHERE store_id IN (1,2)');
foreach(['dc_pm_projects','dc_pm_tasks','dc_pm_timesheets'] as $table)if((int)$q->fetchOne("SELECT COUNT(*) total FROM $table WHERE deleted_at IS NULL")['total']!==0)throw new RuntimeException('Active business fixture remains.');
if((int)$q->fetchOne('SELECT COUNT(*) total FROM dc_users WHERE status<>2')['total']!==0)throw new RuntimeException('Active fixture account remains.');
$source=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);if((int)$source->query('SELECT COUNT(*) total FROM dc_users')->fetch_assoc()['total']!==(int)$f['source_user_count'])throw new RuntimeException('Current users changed.');
echo "PASS: browser stored hours/OT + XLSX, old-session role revocation/inactive actor HTTP, isolated soft-delete cleanup, no active synthetic accounts/business rows; source personnel count unchanged. Schema and history retained.\n";
