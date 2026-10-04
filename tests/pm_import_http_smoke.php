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
require ROOT_PATH.'classes/services/pmimportservice.class.php';
function importUpload(string $url,string $session,string $bytes,string $name='sample.xlsx',string $token='allocation-test-token',string $action='preview'): array {
    $b='pm'.bin2hex(random_bytes(8));$body='';foreach(['op'=>'pmimports','csrf_token'=>$token,'action'=>$action] as $k=>$v)$body.='--'.$b."\r\nContent-Disposition: form-data; name=\"".$k."\"\r\n\r\n".$v."\r\n";
    $body.='--'.$b."\r\nContent-Disposition: form-data; name=\"workbook\"; filename=\"".$name."\"\r\nContent-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\r\n\r\n".$bytes."\r\n--".$b."--\r\n";
    $context=stream_context_create(['http'=>['method'=>'POST','header'=>'Cookie: PHPSESSID='.$session."\r\nContent-Type: multipart/form-data; boundary=".$b."\r\n",'content'=>$body,'ignore_errors'=>true,'timeout'=>15]]);$html=file_get_contents($url,false,$context);preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$m);return [(int)($m[1]??0),$html];
}
try{
    for($i=0;$i<50;$i++){$socket=@fsockopen('127.0.0.1',18768,$errno,$error,0.1);if($socket){fclose($socket);break;}usleep(100000);}
    $url=$base.'/admin.php?op=pmimports';[$code,$html,$headers]=allocationHttp($url,$session);if($code!==200||!str_contains($html,'Preview/staging chưa áp dụng')||!in_array('Cache-Control: no-store',$headers,true))throw new RuntimeException('Import page failed.');
    foreach(['apply','resume','detail'] as $action){
        $post=['op'=>'pmimports','action'=>$action,'import_id'=>'999999999','csrf_token'=>'allocation-test-token'];
        if(allocationHttp($url,$session,$post)[0]!==403)throw new RuntimeException('Unknown batch not denied.');
        $post['csrf_token']='wrong';if(allocationHttp($url,$session,$post)[0]!==400)throw new RuntimeException('Apply CSRF not denied.');
    }
    $post=['op'=>'pmimports','action'=>'provision_password','import_id'=>'999999999','source_row'=>'2','password'=>'Synthetic password 2026','csrf_token'=>'allocation-test-token'];
    if(allocationHttp($url,$session,$post)[0]!==403)throw new RuntimeException('Foreign credential target not denied.');
    $post['csrf_token']='wrong';if(allocationHttp($url,$session,$post)[0]!==400)throw new RuntimeException('Credential CSRF not denied.');
    $service=new PmImportService($db,$store,(int)$admin['id']);$bytes=$service->template();
    [$code,$html]=importUpload($url,$session,$bytes);if($code!==200||!str_contains($html,'File không có dữ liệu'))throw new RuntimeException('Real multipart preview failed.');
    if(importUpload($url,$session,$bytes,'sample.xlsx','wrong')[0]!==400||importUpload($url,$session,$bytes,'sample.xls')[0]!==400)throw new RuntimeException('CSRF/extension not rejected.');
    $before=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_logs')['total'];[$code,$html]=importUpload($url,$session,$bytes,'sample.xlsx','allocation-test-token','stage');if($code!==200||$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_logs')['total']!==$before)throw new RuntimeException('Invalid workbook was staged.');
    [$code,$file,$headers]=allocationHttp($url,$session,['op'=>'pmimports','action'=>'template','csrf_token'=>'allocation-test-token']);if($code!==200||!str_starts_with($file,'PK'))throw new RuntimeException('Template download failed.');
    foreach($q->fetchAll('SELECT id FROM dc_users WHERE store_id=? AND status=1','i',[$store]) as $u){$access=new PmAccess($db,$store,(int)$u['id']);if($access->hasRole('ADMIN'))continue;$cookie=allocationSession((int)$u['id'],$store);if(allocationHttp($url,$cookie)[0]!==403||importUpload($url,$cookie,$bytes)[0]!==403)throw new RuntimeException('Non-Admin imported.');
        foreach(['apply','resume','detail','provision_password'] as $action)if(allocationHttp($url,$cookie,['op'=>'pmimports','action'=>$action,'import_id'=>'999999999','source_row'=>'2','password'=>'Synthetic password 2026','csrf_token'=>'allocation-test-token'])[0]!==403)throw new RuntimeException('Non-Admin used Apply endpoint.');
    }
    if(allocationHttp($url,$wrong,['op'=>'pmimports','action'=>'apply','import_id'=>'999999999','csrf_token'=>'allocation-test-token'])[0]!==403)throw new RuntimeException('Wrong-tenant Apply not denied.');
    echo "PASS: import HTTP Admin/non-Admin, real multipart preview, CSRF/extension, template download/no-store and no staging of invalid files.\n";
}finally{proc_terminate($server);proc_close($server);}
