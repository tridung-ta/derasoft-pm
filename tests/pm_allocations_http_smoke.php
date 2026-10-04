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
    foreach([['',null,401],['',$session,200],['',$wrong,401],['&week=2026-02-30',$session,400],['&week[]=x',$session,400],['&project_id=999999999',$session,403],['&action=suggestions&project_id=999999999&work_date=2026-10-02&hours=1',$session,403]] as [$suffix,$cookie,$expected]){
        [$code,$body,$headers]=allocationHttp($base.'/pm_ajax.php?op=pmallocations'.$suffix,$cookie);if($code!==$expected||!is_array(json_decode($body,true)))throw new RuntimeException('Allocation HTTP expected '.$expected.', got '.$code);
        if(!in_array('Cache-Control: no-store',$headers,true))throw new RuntimeException('Missing no-store.');
    }
    [$code,$body]=allocationHttp($base.'/admin.php?op=pmallocations',$session);if($code!==200||!str_contains($body,'id="allocation-rows"'))throw new RuntimeException('Allocation Admin page failed: '.$code);
    if(allocationHttp($base.'/admin.php?op=pmallocations&id=999999999',$session)[0]!==403)throw new RuntimeException('Invalid detail ID accepted.');
    $before=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_allocations')['total'];
    foreach([['op'=>'pmallocations','csrf_token'=>'wrong','action'=>'save'],['op'=>'pmallocations','csrf_token'=>'allocation-test-token','action'=>'delete','id'=>'999999999']] as $post){$expected=$post['action']==='save'?400:403;$actual=allocationHttp($base.'/admin.php?op=pmallocations',$session,$post)[0];if($actual!==$expected)throw new RuntimeException('CSRF/IDOR mutation rejection expected '.$expected.', got '.$actual);}
    if(allocationHttp($base.'/pm_ajax.php?op=pmallocations',$session,[])[0]!==405)throw new RuntimeException('Polling accepted POST.');
    if($q->fetchOne('SELECT COUNT(*) total FROM dc_pm_allocations')['total']!==$before)throw new RuntimeException('Rejected mutation changed data.');
    $users=$q->fetchAll('SELECT id FROM dc_users WHERE store_id=? AND status=1','i',[$store]);$tested=0;
    foreach($users as $u){$access=new PmAccess($db,$store,(int)$u['id']);if($access->hasRole('ADMIN')||$access->hasRole('PM'))continue;
        $cookie=allocationSession((int)$u['id'],$store);$expected=$access->hasRole('EMPLOYEE')&&$access->hasPermission('pm.allocations.view')?200:403;
        if(allocationHttp($base.'/pm_ajax.php?op=pmallocations',$cookie)[0]!==$expected||allocationHttp($base.'/admin.php?op=pmallocations',$cookie)[0]!==$expected)throw new RuntimeException('Non-manager role page/polling failed.');$tested++;
    }
    if(!$tested)throw new RuntimeException('No non-manager role session tested.');
    echo "PASS group 4 HTTP: Admin and non-manager role page/polling, session tenant, filters, detail/suggestions IDOR, CSRF mutation, no-store and GET-only. No business writes.\n";
}finally{proc_terminate($server);proc_close($server);}
