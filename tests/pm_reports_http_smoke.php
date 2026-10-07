<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/security/pmaccess.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$db=(object)['connection'=>$connection];$q=new PmDb($db);
$admin=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");if(!$admin)throw new RuntimeException('No Admin.');$store=(int)$admin['store_id'];
$dir=ROOT_PATH.'.local/phase7sessions';if(!is_dir($dir))mkdir($dir,0700,true);ini_set('session.save_path',$dir);ini_set('session.use_cookies','0');
function allocationSession(int $user,int $store): string {session_id(bin2hex(random_bytes(20)));session_start();$_SESSION=['userId'=>$user,'storeId'=>$store,'pm_csrf_token'=>'allocation-test-token'];$id=session_id();session_write_close();return $id;}
$session=allocationSession((int)$admin['id'],$store);$wrong=allocationSession((int)$admin['id'],$store+99999);$base='http://127.0.0.1:18768';
function allocationHttp(string $url,?string $session=null,?array $post=null): array {
    $headers=$session?'Cookie: PHPSESSID='.$session."\r\n":'';if($post!==null)$headers.="Content-Type: application/x-www-form-urlencoded\r\n";
    $context=stream_context_create(['http'=>['method'=>$post===null?'GET':'POST','header'=>$headers,'content'=>$post===null?'':http_build_query($post),'ignore_errors'=>true,'timeout'=>15]]);$body=file_get_contents($url,false,$context);$h=$http_response_header??[];preg_match('/\s(\d{3})\s/',$h[0]??'',$m);return [(int)($m[1]??0),$body,$h];
}
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:18768','-t',ROOT_PATH],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/phase7-http.log','a'],2=>['file',ROOT_PATH.'.local/phase7-http.log','a']],$pipes,ROOT_PATH);if(!is_resource($server))throw new RuntimeException('No server.');fclose($pipes[0]);
try{
    for($i=0;$i<50;$i++){$socket=@fsockopen('127.0.0.1',18768,$errno,$error,0.1);if($socket){fclose($socket);break;}usleep(100000);}
    foreach([['',200],['&mode=costs',200],['&mode=tasks',200],['&mode=users',200],['&mode=projects',200],['&from=2026-02-30',400],['&project_id=999999999',403],['&user_id[]=x',400],['&role_code[]=ADMIN',400]] as [$suffix,$expected]){
        [$code,$body]=allocationHttp($base.'/admin.php?op=pmreports'.$suffix,$session);if($code!==$expected)throw new RuntimeException('Report page expected '.$expected.', got '.$code);
        if($expected===200&&!str_contains($body,'Báo cáo & Excel'))throw new RuntimeException('Missing report page.');
    }
    if(allocationHttp($base.'/admin.php?op=pmreports',$session,['op'=>'pmreports','action'=>'export','csrf_token'=>'wrong'])[0]!==400)throw new RuntimeException('Report export bypassed CSRF.');
    [$code,$bytes,$headers]=allocationHttp($base.'/admin.php?op=pmreports',$session,['op'=>'pmreports','action'=>'export','csrf_token'=>'allocation-test-token','mode'=>'hours','from'=>'2000-01-01','to'=>'2000-01-01']);
    if($code!==200||!str_starts_with($bytes,'PK')||!in_array('Cache-Control: no-store',$headers,true))throw new RuntimeException('Report XLSX download failed.');
    if(!in_array('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',$headers,true))throw new RuntimeException('Wrong download MIME.');
    foreach($q->fetchAll('SELECT id FROM dc_users WHERE store_id=? AND status=1','i',[$store]) as $u){
        $access=new PmAccess($db,$store,(int)$u['id']);if($access->hasRole('ADMIN')||$access->hasRole('PM'))continue;$cookie=allocationSession((int)$u['id'],$store);
        if(allocationHttp($base.'/admin.php?op=pmreports',$cookie)[0]!==200)throw new RuntimeException('Personal report route failed.');
        if(allocationHttp($base.'/admin.php?op=pmreports&mode=costs',$cookie)[0]!==403)throw new RuntimeException('Personal role read costs.');
        if(allocationHttp($base.'/admin.php?op=pmreports&user_id='.$admin['id'],$cookie)[0]!==403)throw new RuntimeException('Personal report user IDOR.');
    }
    echo "PASS: report HTTP routes/filters, own scope, cost denial, CSRF and XLSX MIME/no-store download. No business writes.\n";
}finally{proc_terminate($server);proc_close($server);}
