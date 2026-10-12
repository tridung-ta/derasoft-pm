<?php
/** Real login and permission refresh on one cookie. Every write uses temporary exact-schema mirrors. */
define('PM_TENANT_FIXTURE_BOOTSTRAP_ONLY',true);require __DIR__.'/pm_tenant_matrix_smoke.php';
$tables[]='dc_login_times';$before=tenantState($q,$tables);$checks=[];$server=null;$runError=null;$cleanup=false;
$extraHarnessHeaders="X-PM-Test-Auth: 1\r\n";
function authRequest(array $query,array $post=[]): array {
 global $sid,$token,$port;$headers="Cookie: PHPSESSID=$sid\r\nX-PM-Test-Token: $token\r\nX-PM-Test-Role: NONE\r\nX-PM-Test-Auth: 1\r\nX-PM-Test-Nonce: ".bin2hex(random_bytes(12))."\r\n";
 if($post)$headers.="Content-Type: application/x-www-form-urlencoded\r\n";
 $ctx=stream_context_create(['http'=>['method'=>$post?'POST':'GET','header'=>$headers,'content'=>$post?http_build_query($post):'','ignore_errors'=>true,'follow_location'=>0,'timeout'=>15]]);
 $body=@file_get_contents('http://127.0.0.1:'.$port.'/admin.php?'.http_build_query($query),false,$ctx);preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$m);
 $oldSid=$sid;$flags=[];foreach($http_response_header??[] as $h)if(preg_match('/^Set-Cookie: PHPSESSID=([^;]+);(.*)$/i',$h,$cookie)){$sid=$cookie[1];$flags[]=$cookie[2];}
 return ['status'=>(int)($m[1]??0),'body'=>$body?:'','sid_changed'=>$oldSid!==$sid,'cookie_flags'=>$flags];
}
function sessionRoleCheck(string $label,bool $ok,array $details=[]): void {global $checks;$checks[]=['label'=>$label,'pass'=>$ok,'details'=>$details];echo ($ok?'PASS':'FAIL').' '.$label.PHP_EOL;}
try {
 session_id($sid);session_start();$_SESSION=[];session_write_close();
 $server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:'.$port,ROOT_PATH.'tests/pm_tenant_matrix_router.php'],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/session-role-http.log','a'],2=>['file',ROOT_PATH.'.local/session-role-http.log','a']],$pipes,ROOT_PATH);if(!is_resource($server))throw new RuntimeException('Server not started.');fclose($pipes[0]);
 $ready=false;$deadline=microtime(true)+5;do{$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($socket){fclose($socket);$ready=true;break;}usleep(10000);}while(microtime(true)<$deadline);if(!$ready)throw new RuntimeException('Server not ready.');
 $form=authRequest(['op'=>'login']);if(!preg_match('/name="csrf_token" value="([^"]+)"/',$form['body'],$match))throw new RuntimeException('Login CSRF unavailable.');
 $login=authRequest(['op'=>'login'],['op'=>'login','site'=>'admin','username'=>'matrix-auth','password'=>'Fixture password 123','csrf_token'=>html_entity_decode($match[1],ENT_QUOTES)]);
 session_id($sid);session_start();$loggedActor=(int)($_SESSION['userId']??0);$matrixCsrf=$_SESSION['pm_csrf_token']??'';session_write_close();
 sessionRoleCheck('0.2 real HTTP no-role login',$login['status']===302&&$loggedActor===(int)$actor['id']&&$matrixCsrf!=='',['status'=>$login['status']]);
 sessionRoleCheck('2.6 partial login regenerates session ID',$login['sid_changed']);
 sessionRoleCheck('2.6 partial cookie HttpOnly SameSite=Lax Max-Age=43200',(bool)array_filter($login['cookie_flags'],fn($flags)=>stripos($flags,'httponly')!==false&&stripos($flags,'samesite=Lax')!==false&&stripos($flags,'Max-Age=43200')!==false));
 if($loggedActor!==(int)$actor['id']||$matrixCsrf==='')throw new RuntimeException('No authenticated fixture session.');
 foreach($cases as $case){$case['role']='NONE';$r=safeMatrixRequest($case,($case['key']==='role_ids'?900000094:($case['key']==='page'?1:999000000)));$ok=$r['status']===403&&($case['path']!=='/admin.php'||($r['body_sha256']??'')===hash('sha256','Bạn không có quyền truy cập chức năng này.'))&&$r['unchanged']&&$r['audit_count']===0&&$r['tracking_count']===0&&!$r['observer_error']&&!$r['leak'];sessionRoleCheck('0.2 '.$case['label'],$ok,$r);}
 foreach([['pmtimesheets','settings'],['pmimports','template'],['pmimports','preview'],['pmimports','stage']] as [$op,$action]){$case=['op'=>$op,'key'=>'page','type'=>'project','base'=>['action'=>$action],'post'=>true,'role'=>'NONE','path'=>'/admin.php','get'=>[]];$r=safeMatrixRequest($case,1);sessionRoleCheck('0.2 additional action '.$op.' '.$action,$r['status']===403&&($case['path']!=='/admin.php'||($r['body_sha256']??'')===hash('sha256','Bạn không có quyền truy cập chức năng này.'))&&$r['unchanged']&&$r['audit_count']===0&&$r['tracking_count']===0&&!$r['observer_error']&&!$r['leak'],$r);}
 foreach(['pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'] as $op){$case=['op'=>$op,'key'=>'page','type'=>'project','base'=>[],'post'=>false,'role'=>'NONE','path'=>'/admin.php','get'=>[]];$r=safeMatrixRequest($case,1);sessionRoleCheck('0.2 protected read '.$op,$r['status']===403&&($case['path']!=='/admin.php'||($r['body_sha256']??'')===hash('sha256','Bạn không có quyền truy cập chức năng này.'))&&$r['unchanged']&&!$r['observer_error']&&!$r['leak'],$r);}
 $case=['op'=>'pmusers','key'=>'page','type'=>'user','base'=>[],'post'=>false,'role'=>'ADMIN','path'=>'/admin.php','get'=>[]];$sameSid=$sid;$r=safeMatrixRequest($case,1);sessionRoleCheck('2.7 same cookie ADMIN read allowed',$r['status']===200&&$r['unchanged']&&!$r['observer_error'],$r);
 $case['role']='NONE';$r=safeMatrixRequest($case,1);sessionRoleCheck('2.7 same cookie after removing role denied',$r['status']===403&&($case['path']!=='/admin.php'||($r['body_sha256']??'')===hash('sha256','Bạn không có quyền truy cập chức năng này.'))&&$r['unchanged']&&!$r['observer_error']&&$sid===$sameSid,$r);
 $case['role']='ADMIN';$r=safeMatrixRequest($case,1);sessionRoleCheck('2.7 same cookie after restoring role allowed',$r['status']===200&&$r['unchanged']&&!$r['observer_error']&&$sid===$sameSid,$r);
}catch(Throwable $e){$runError=get_class($e).': '.$e->getMessage();echo 'FAIL harness '.$runError.PHP_EOL;}
finally {if(is_resource($server)){proc_terminate($server);proc_close($server);}try{$after=tenantState($q,$tables);$cleanup=$before===$after;}catch(Throwable $e){$after=[];}
 file_put_contents(ROOT_PATH.'.local/session-role-http-20261009.json',json_encode(['command'=>'.tools/php83/php.exe tests/pm_session_role_http_smoke.php','finished_at'=>date('c'),'checks'=>$checks,'run_error'=>$runError,'cleanup'=>$cleanup,'persistent_before'=>$before,'persistent_after'=>$after],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));echo ($cleanup?'PASS':'FAIL').' cleanup: '.count($tables).' persistent tables unchanged; temporary fixtures destroyed.'.PHP_EOL;}
$failed=count(array_filter($checks,fn($r)=>!$r['pass']));echo 'RESULT: '.(count($checks)-$failed).' PASS / '.$failed.' FAIL. Session expiry not tested; 2.6 remains incomplete.'.PHP_EOL;
exit($failed||!$cleanup||$runError||count($checks)!==98?1:0);
