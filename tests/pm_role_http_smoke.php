<?php
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/pmdb.class.php';
if (($config['db_name'] ?? '') !== 'derasoft_pm_local' || !in_array($config['db_server'], ['localhost','127.0.0.1'], true)) throw new RuntimeException('Non-local refused.');
$c = new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);
$q = new PmDb((object)['connection'=>$c]);
$actor = $q->fetchOne('SELECT id,store_id FROM dc_users WHERE status=1 ORDER BY id LIMIT 1');
if (!$actor) throw new RuntimeException('No active local session actor.');
function rolePersistentState(PmDb $q): string {
    return hash('sha256',serialize([
        $q->fetchAll('SELECT * FROM dc_pm_user_roles ORDER BY id'),
        $q->fetchAll('SELECT * FROM dc_pm_projects ORDER BY id'),
        $q->fetchOne("SELECT engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_users'")
    ]));
}
$before=rolePersistentState($q);
$dir = ROOT_PATH.'.local/role-http-sessions';
if (!is_dir($dir)) mkdir($dir,0700,true);
ini_set('session.save_path',$dir); ini_set('session.use_cookies','0');
session_cache_limiter('');
session_id(bin2hex(random_bytes(20))); session_start();
$_SESSION=['userId'=>(int)$actor['id'],'storeId'=>(int)$actor['store_id'],'pm_csrf_token'=>'role-http-csrf'];
$sid=session_id(); session_write_close();
$token=bin2hex(random_bytes(24));
$socket=@fsockopen('127.0.0.1',18770,$errno,$error,.1);
if ($socket) { fclose($socket); throw new RuntimeException('Fixture port already occupied.'); }
putenv('PM_ROLE_HTTP_TOKEN='.$token); putenv('PM_ROLE_HTTP_ACTOR='.$actor['id']); putenv('PM_ROLE_HTTP_STORE='.$actor['store_id']);
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:18770',ROOT_PATH.'tests/pm_role_http_router.php'],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/role-http.log','a'],2=>['file',ROOT_PATH.'.local/role-http.log','a']],$pipes,ROOT_PATH);
if (!is_resource($server)) throw new RuntimeException('Cannot start HTTP fixture.');
fclose($pipes[0]);
$cases=0;
function roleHttp(string $role,string $query,int $expected,?array $post=null): string {
    global $sid,$token,$cases;
    $headers="Cookie: PHPSESSID=$sid\r\nX-PM-Test-Token: $token\r\nX-PM-Test-Role: $role\r\n";
    if ($post!==null) $headers.="Content-Type: application/x-www-form-urlencoded\r\n";
    $ctx=stream_context_create(['http'=>['method'=>$post===null?'GET':'POST','header'=>$headers,'content'=>$post===null?'':http_build_query($post),'ignore_errors'=>true,'timeout'=>15]]);
    $body=file_get_contents('http://127.0.0.1:18770/admin.php?'.$query,false,$ctx);
    preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$m);
    if ((int)($m[1]??0)!==$expected) throw new RuntimeException("$role/$query expected $expected got ".($m[1]??'none'));
    if (!in_array('Cache-Control: no-store',$http_response_header,true)) throw new RuntimeException('Missing no-store.');
    if (str_contains($body,'Fatal error:')||str_contains($body,'mysqli_sql_exception')) throw new RuntimeException('HTTP runtime error.');
    $cases++; return $body;
}
try {
    $ready=false;
    for($i=0;$i<50;$i++) { $socket=@fsockopen('127.0.0.1',18770,$errno,$error,.1); if($socket){fclose($socket);$ready=true;break;} usleep(100000); }
    if (!$ready) throw new RuntimeException('HTTP fixture did not start.');
    foreach(['PM','HR'] as $role) {
        foreach(['pm','pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'] as $route) {
            $denied=$route==='pmimports'||($role==='HR'&&in_array($route,['pmprojects','pmcosts','pmallocations'],true));
            $html=roleHttp($role,'op='.$route,$denied?403:200);
            if (!$denied&&!str_contains($html,'id="pm-main"')) throw new RuntimeException('Missing actual page landmark '.$role.'/'.$route.': '.substr(strip_tags($html),0,500));
        }
        roleHttp($role,'op=pmreports&mode=costs',$role==='PM'?200:403);
        roleHttp($role,'op=pmreports&user_id=999999999',$role==='HR'?403:200);
        roleHttp($role,'op=pmreports&project_id=900000002',403);
        roleHttp($role,'op=pmreports',400,['op'=>'pmreports','action'=>'export','csrf_token'=>'wrong']);
        $bytes=roleHttp($role,'op=pmreports',200,['op'=>'pmreports','action'=>'export','csrf_token'=>'role-http-csrf','mode'=>'hours','from'=>'2000-01-01','to'=>'2000-01-01']);
        if (!str_starts_with($bytes,'PK')) throw new RuntimeException('Missing XLSX export.');
    }
    $own=roleHttp('PM','op=pmprojects&project_id=900000001',200);
    if (!str_contains($own,'HTTP own project')) throw new RuntimeException('Own project not rendered.');
    $foreign=roleHttp('PM','op=pmprojects&project_id=900000002',403);
    if (str_contains($foreign,'HTTP foreign project')) throw new RuntimeException('Foreign project leaked.');
} finally {
    proc_terminate($server); proc_close($server);
    putenv('PM_ROLE_HTTP_TOKEN'); putenv('PM_ROLE_HTTP_ACTOR'); putenv('PM_ROLE_HTTP_STORE');
    session_id($sid); session_start(); session_destroy();
    if (rolePersistentState($q)!==$before) throw new RuntimeException('Persistent role/project state or user engine changed.');
}
echo "PASS: $cases PM/HR HTTP controller cases with connection-local role/project fixtures; page gates, project ownership, report scope, CSRF and XLSX. No persistent business writes. Not real-account login UAT.\n";
