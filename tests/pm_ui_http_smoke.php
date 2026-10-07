<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/security/pmaccess.class.php';require ROOT_PATH.'classes/services/pmuiservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$db=(object)['connection'=>$c];$q=new PmDb($db);
$dir=ROOT_PATH.'.local/phase9sessions';if(!is_dir($dir))mkdir($dir,0700,true);ini_set('session.save_path',$dir);ini_set('session.use_cookies','0');
function uiSession(int $user,int $store): string {session_id(bin2hex(random_bytes(20)));session_start();$_SESSION=['userId'=>$user,'storeId'=>$store,'pm_csrf_token'=>'ui-fixture-token'];$id=session_id();session_write_close();return $id;}
function uiHttp(string $query,string $sid,?array $post=null): array {
    $ctx=stream_context_create(['http'=>['method'=>$post===null?'GET':'POST','header'=>'Cookie: PHPSESSID='.$sid."\r\n".($post!==null?"Content-Type: application/x-www-form-urlencoded\r\n":''),'content'=>$post===null?'':http_build_query($post),'ignore_errors'=>true,'timeout'=>15]]);$body=file_get_contents('http://127.0.0.1:18769/admin.php?'.$query,false,$ctx);$headers=$http_response_header??[];preg_match('/\s(\d{3})\s/',$headers[0]??'',$m);return [(int)($m[1]??0),$body,$headers];
}
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:18769','-t',ROOT_PATH],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/phase9-http.log','a'],2=>['file',ROOT_PATH.'.local/phase9-http.log','a']],$pipes,ROOT_PATH);if(!is_resource($server))throw new RuntimeException('No HTTP server.');fclose($pipes[0]);
try{
    for($i=0;$i<50;$i++){$socket=@fsockopen('127.0.0.1',18769,$errno,$error,.1);if($socket){fclose($socket);break;}usleep(100000);}
    $routes=['pm','pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'];$cases=0;$actors=[];$missing=[];
    foreach(['ADMIN','PM','HR','EMPLOYEE'] as $role){
        $a=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code=? LIMIT 1",'s',[$role]);if(!$a){$missing[]=$role;continue;}
        $actors[$role]=$a;$sid=uiSession((int)$a['id'],(int)$a['store_id']);$access=new PmAccess($db,(int)$a['store_id'],(int)$a['id']);$nav=PmUiService::navigation($access,'pm');$allowed=array_column($nav,'url');
        foreach($routes as $route){[$code,$html,$headers]=uiHttp('op='.$route,$sid);$expected=in_array('?op='.$route,$allowed,true)?200:403;if($code!==$expected)throw new RuntimeException($role.'/'.$route.' expected '.$expected.' got '.$code);
            foreach(['Cache-Control: no-store','X-Content-Type-Options: nosniff','Referrer-Policy: same-origin','X-Frame-Options: SAMEORIGIN'] as $h)if(!in_array($h,$headers,true))throw new RuntimeException('Missing header '.$h);
            $csp=array_values(array_filter($headers,fn($h)=>str_starts_with($h,'Content-Security-Policy:')));if(count($csp)!==1||!str_contains($csp[0],"script-src 'self'")||str_contains($csp[0],'unsafe-inline'))throw new RuntimeException('CSP header missing/unsafe.');
            if($code===200&&(!str_contains($html,'id="pm-main"')||!str_contains($html,'aria-current="page"')))throw new RuntimeException('Missing active nav/landmark '.$route);
            $cases++;
        }
    }
    $a=$actors['ADMIN'];$sid=uiSession((int)$a['id'],(int)$a['store_id']);
    foreach(['op[]=pm','op=pmusers&q[]=x','op=pmprojects&project_id[]=1','op=pmtimesheets&id[]=1','op=pmaudit&action[]=create','op=pmcosts&from[]=x','op=pmallocations&week[]=x','op=pmreports&mode[]=hours','op=pmimports&import_id[]=1'] as $bad)if(uiHttp($bad,$sid)[0]!==400)throw new RuntimeException('Array input not rejected.');
    [$code,$html]=uiHttp('op=pmusers&q='.rawurlencode("' OR 1=1 -- <img src=x onerror=alert(1)>"),$sid);if($code!==200||str_contains($html,'<img src=x')||str_contains($html,'mysqli_sql_exception'))throw new RuntimeException('Search SQLi/XSS regression.');
    foreach([uiSession((int)$a['id'],(int)$a['store_id']+99999),uiSession(999999999,(int)$a['store_id'])] as $invalid)if(uiHttp('op=pm',$invalid)[0]!==401)throw new RuntimeException('Invalid session was not expired.');
    $inactive=$q->fetchOne('SELECT id,store_id FROM dc_users WHERE status<>1 LIMIT 1');if($inactive&&uiHttp('op=pm',uiSession((int)$inactive['id'],(int)$inactive['store_id']))[0]!==401)throw new RuntimeException('Inactive actor kept Admin session.');
    $session=uiSession((int)$a['id'],(int)$a['store_id']);uiHttp('op=pm',$session);session_id($session);session_start();if(empty($_SESSION['KCFINDER']['disabled']))throw new RuntimeException('Legacy file manager enabled in PM.');session_write_close();
    if(uiHttp('op=logout',$session)[0]!==405||uiHttp('op=logout',$session,['op'=>'logout','csrf_token'=>'bad'])[0]!==400||uiHttp('op=pm',$session)[0]!==200)throw new RuntimeException('Logout CSRF/session preservation failed.');
    // Reject login CSRF before invoking authentication/failed-login tracking.
    if(uiHttp('op=login',uiSession(0,(int)$a['store_id']),['op'=>'login','site'=>'admin','username'=>'no-user','password'=>'no-password','csrf_token'=>'bad'])[0]!==400)throw new RuntimeException('Login CSRF not rejected.');
    echo 'PASS: '.$cases.' authenticated role/route cases for '.implode(',',array_keys($actors)).'; menu/current page/landmarks, headers, malformed arrays, search SQLi/XSS, wrong/missing sessions and PM file-manager guard. No business writes.'.PHP_EOL;
    if($missing)echo 'NOT COVERED via authenticated HTTP (no active local actor): '.implode(',',$missing).'. Use rollback service/permission fixtures; no production/UAT claim.'.PHP_EOL;
}finally{proc_terminate($server);proc_close($server);}
