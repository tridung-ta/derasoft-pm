<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$query=new PmDb((object)['connection'=>$connection]);
$admin=$query->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");
if(!$admin)throw new RuntimeException('No local Admin.');
$other=$query->fetchOne("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code IN ('ADMIN','PM') AND r.status=1) LIMIT 1",'i',[(int)$admin['store_id']]);
if(!$other)throw new RuntimeException('No local non-cost-role user.');
$sessionDir=ROOT_PATH.'.local/phase6sessions';if(!is_dir($sessionDir))mkdir($sessionDir,0700,true);
ini_set('session.save_path',$sessionDir);ini_set('session.use_cookies','0');
function fixtureSession(int $user,int $store): string {session_id(bin2hex(random_bytes(20)));session_start();$_SESSION=['userId'=>$user,'storeId'=>$store];$id=session_id();session_write_close();return $id;}
$adminSession=fixtureSession((int)$admin['id'],(int)$admin['store_id']);$otherSession=fixtureSession((int)$other['id'],(int)$admin['store_id']);$wrongStore=fixtureSession((int)$admin['id'],(int)$admin['store_id']+99999);
$port=18766;$base='http://127.0.0.1:'.$port;
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$sessionDir,'-S','127.0.0.1:'.$port,'-t',ROOT_PATH],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/phase6-http.log','a'],2=>['file',ROOT_PATH.'.local/phase6-http.log','a']],$pipes,ROOT_PATH);
if(!is_resource($server))throw new RuntimeException('Test server did not start.');fclose($pipes[0]);
function costRequest(string $url,?string $session=null,string $method='GET'): array {
    $context=stream_context_create(['http'=>['method'=>$method,'header'=>$session?'Cookie: PHPSESSID='.$session."\r\n":'','ignore_errors'=>true,'timeout'=>15]]);
    $body=file_get_contents($url,false,$context);$headers=$http_response_header??[];
    preg_match('/\s(\d{3})\s/',$headers[0]??'',$match);
    return [(int)($match[1]??0),json_decode($body,true),$headers,$body];
}
try{
    for($i=0;$i<50;$i++){$socket=@fsockopen('127.0.0.1',$port,$errno,$error,0.1);if($socket){fclose($socket);break;}usleep(100000);}
    foreach([
        ['?op=pmcosts',null,401],['?op=pmcosts',$adminSession,200],['?op=pmcosts&project_id=999999999',$adminSession,403],
        ['?op=pmcosts&from=2026-02-30',$adminSession,400],['?op=pmcosts',$otherSession,403],['?op=pmcosts&project_id=999999999',$otherSession,403],
        ['?op=pmcosts',$wrongStore,401],['?op=../../admin',$adminSession,404],['?op=pmcosts&project_id[]=1',$adminSession,400]
    ] as [$suffix,$session,$expected]){
        [$code,$body,$headers]=costRequest($base.'/pm_ajax.php'.$suffix,$session);
        if($code!==$expected||!is_array($body))throw new RuntimeException('HTTP scope/filter failure: expected '.$expected.', got '.$code);
        if($code!==200&&isset($body['summary']))throw new RuntimeException('Error leaked cost data.');
        if($code===200&&(!is_int($body['summary']['missing_rate_entries']??null)||!is_bool($body['summary']['cost_complete']??null)))throw new RuntimeException('Polling missing actual-cost completeness metadata.');
        if(!in_array('Cache-Control: no-store',$headers,true))throw new RuntimeException('Missing cost cache control.');
    }
    if(costRequest($base.'/pm_ajax.php?op=pmcosts',$adminSession,'POST')[0]!==405)throw new RuntimeException('Read endpoint accepted mutation method.');
    [$code,,,$html]=costRequest($base.'/admin.php?op=pmcosts',$adminSession);
    if($code!==200||!str_contains($html,'id="cost-data"'))throw new RuntimeException('Admin cost page route failed: '.$code);
    if(costRequest($base.'/admin.php?op=pmcosts',$otherSession)[0]!==403)throw new RuntimeException('Non-cost role opened Admin cost page.');
    foreach([$adminSession,$otherSession] as $session){
        [$code,,,$html]=costRequest($base.'/admin.php?op=pmtimesheets',$session);
        if($code!==200||!str_contains($html,'Lịch sử chấm công'))throw new RuntimeException('Timesheet page permission initialization failed: '.$code);
        if(costRequest($base.'/admin.php?op=pmtimesheets&id=999999999',$session)[0]!==403)throw new RuntimeException('Unavailable timesheet did not fail closed.');
    }
    [$code,,,$html]=costRequest($base.'/admin.php?op=pmaudit',$adminSession);
    if($code!==200||!str_contains($html,'Nhật ký kiểm toán'))throw new RuntimeException('Admin audit page route failed: '.$code);
    if(costRequest($base.'/admin.php?op=pmaudit&entity_type=invalid',$adminSession)[0]!==400)throw new RuntimeException('Audit page accepted invalid filter.');
}finally{proc_terminate($server);proc_close($server);}
echo "PASS group 3 HTTP: authenticated polling, list/detail permission and tenant rejection, bad filters, allowlist, no-store, no error data leaks, GET-only endpoint, timesheet page/invalid ID and Admin audit page/filter.\n";
