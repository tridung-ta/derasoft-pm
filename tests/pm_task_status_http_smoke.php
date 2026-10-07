<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$q=new PmDb((object)['connection'=>$c]);
$actor=$q->fetchOne('SELECT id,store_id FROM dc_users WHERE status=1 ORDER BY id LIMIT 1');if(!$actor)throw new RuntimeException('No local fixture actor.');
function taskPersistent(PmDb $q): string {
    $state=[];foreach(['dc_users','dc_pm_user_roles','dc_pm_projects','dc_pm_tasks','dc_pm_audit_logs','dc_trackings'] as $table)$state[]=$q->fetchAll('SELECT * FROM '.$table.' ORDER BY id');
    $state[]=$q->fetchOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_users'");return hash('sha256',serialize($state));
}
$before=taskPersistent($q);$dir=ROOT_PATH.'.local/task-status-sessions';$states=ROOT_PATH.'.local/task-status-state';
foreach([$dir,$states] as $path)if(!is_dir($path))mkdir($path,0700,true);
ini_set('session.save_path',$dir);ini_set('session.use_cookies','0');session_cache_limiter('');
function taskSession(int $id,int $store): string {session_id(bin2hex(random_bytes(20)));session_start();$_SESSION=['userId'=>$id,'storeId'=>$store,'pm_csrf_token'=>'task-http-csrf'];$sid=session_id();session_write_close();return $sid;}
$sid=taskSession((int)$actor['id'],(int)$actor['store_id']);$wrongSid=taskSession((int)$actor['id'],(int)$actor['store_id']+1);
$port=18771;$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($socket){fclose($socket);throw new RuntimeException('Fixture port occupied.');}
$token=bin2hex(random_bytes(24));putenv('PM_TASK_HTTP_TOKEN='.$token);putenv('PM_TASK_HTTP_ACTOR='.$actor['id']);putenv('PM_TASK_HTTP_STORE='.$actor['store_id']);
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:'.$port,ROOT_PATH.'tests/pm_task_status_http_router.php'],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/task-status-http.log','a'],2=>['file',ROOT_PATH.'.local/task-status-http.log','a']],$pipes,ROOT_PATH);
if(!is_resource($server))throw new RuntimeException('Cannot start fixture server.');fclose($pipes[0]);$cases=0;
function taskRequest(array $changes=[],string $role='PM',int $expected=200,string $method='POST',string $op='pmtaskstatus',?string $session='default'): array {
    global $sid,$token,$cases,$states;
    $nonce=bin2hex(random_bytes(12));$session=$session==='default'?$sid:$session;
    $post=array_replace(['project_id'=>'900000001','task_id'=>'900000011','status'=>'done','csrf_token'=>'task-http-csrf'],$changes);
    $headers="X-PM-Test-Token: $token\r\nX-PM-Test-Role: $role\r\nX-PM-Test-Nonce: $nonce\r\nContent-Type: application/x-www-form-urlencoded\r\n";
    if($session!==null)$headers.="Cookie: PHPSESSID=$session\r\n";
    $ctx=stream_context_create(['http'=>['method'=>$method,'header'=>$headers,'content'=>$method==='POST'?http_build_query($post):'','ignore_errors'=>true,'timeout'=>15]]);
    $body=file_get_contents('http://127.0.0.1:18771/pm_ajax.php?op='.$op,false,$ctx);preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$match);
    if((int)($match[1]??0)!==$expected)throw new RuntimeException("$role $method $op expected $expected got ".($match[1]??'none'));
    if(!in_array('Cache-Control: no-store',$http_response_header,true)||!in_array('Content-Type: application/json; charset=utf-8',$http_response_header,true))throw new RuntimeException('Missing endpoint headers.');
    $result=json_decode($body,true,512,JSON_THROW_ON_ERROR);$stateFile=$states.'/'.$nonce.'.json';$state=is_file($stateFile)?json_decode(file_get_contents($stateFile),true,512,JSON_THROW_ON_ERROR):null;
    if($expected!==200){
        if(isset($result['task'])||!isset($result['error']))throw new RuntimeException('Error leaked task.');
        if($state&&($state['tasks']!==$state['before']||$state['audit']))throw new RuntimeException('Rejected endpoint changed fixture.');
    }
    $cases++;return [$result,$state];
}
try{
    $ready=false;for($i=0;$i<50;$i++){$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($socket){fclose($socket);$ready=true;break;}usleep(100000);}if(!$ready)throw new RuntimeException('Fixture server unavailable.');
    foreach(['ADMIN','PM'] as $role){
        [$result,$state]=taskRequest([], $role);$old=$state['before'][0];$new=$state['tasks'][0];
        if($result['task']['status']!=='done'||!$result['task']['completed_at']||count($state['audit'])!==1||(int)$state['audit'][0]['actor_id']!==(int)$actor['id'])throw new RuntimeException('Status success/audit failed.');
        foreach(['name','description','priority','estimated_hours','assignee_id','start_date','due_date'] as $key)if($old[$key]!==$new[$key])throw new RuntimeException('Task field overwritten.');
        if(json_decode($state['audit'][0]['old_values'],true)['status']!=='todo'||json_decode($state['audit'][0]['new_values'],true)['status']!=='done')throw new RuntimeException('Audit snapshots wrong.');
    }
    [$result,$state]=taskRequest(['task_id'=>'900000012']);if($result['task']['completed_at']!=='2026-01-01 09:00:00'||$state['audit']||$state['tasks']!==$state['before'])throw new RuntimeException('No-op changed data.');
    [$result,$state]=taskRequest(['task_id'=>'900000012','status'=>'review']);if($result['task']['completed_at']!==null||count($state['audit'])!==1)throw new RuntimeException('Reopen completion failed.');
    [$result,$state]=taskRequest(['actor_id'=>'999999999','store_id'=>'999999999']);if((int)$state['audit'][0]['actor_id']!==(int)$actor['id']||(int)$state['audit'][0]['store_id']!==(int)$actor['store_id'])throw new RuntimeException('Client-controlled actor/tenant accepted.');
    foreach(['HR','EMPLOYEE','NONE'] as $role)taskRequest([],$role,403);
    taskRequest([],'INACTIVE',401);taskRequest([],'PM',401,'POST','pmtaskstatus',null);taskRequest([],'PM',401,'POST','pmtaskstatus',$wrongSid);
    taskRequest(['csrf_token'=>'wrong'],'PM',403);taskRequest(['csrf_token'=>''],'PM',403);
    foreach([['task_id'=>['900000011']],['status'=>['done']],['csrf_token'=>['task-http-csrf']]] as $bad)taskRequest($bad,'PM',400);
    foreach([['task_id'=>'1abc'],['task_id'=>'0'],['task_id'=>'-1'],['status'=>'invalid'],['project_id'=>'999999999999999999999999']] as $bad)taskRequest($bad,'PM',422);
    foreach([['task_id'=>'900000013'],['task_id'=>'900000014'],['task_id'=>'900000015'],['task_id'=>'999999999'],['project_id'=>'900000004']] as $bad)taskRequest($bad,'PM',404);
    taskRequest(['project_id'=>'900000002','task_id'=>'900000013'],'PM',403);
    taskRequest(['project_id'=>'900000003','task_id'=>'900000015'],'ADMIN',404);
    taskRequest([],'PM',405,'GET');taskRequest([],'PM',405,'POST','pmcosts');taskRequest([],'PM',405,'POST','pmallocations');taskRequest([],'PM',404,'POST','unknown');
    $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"X-PM-Test-Token: $token\r\n",'ignore_errors'=>true,'timeout'=>10]]);
    $direct=file_get_contents('http://127.0.0.1:18771/modules/ajax/pmtaskstatus.module.php',false,$ctx);
    if(!str_contains($http_response_header[0]??'','404')||!isset(json_decode($direct,true)['error']))throw new RuntimeException('Direct module execution accepted.');$cases++;
}finally{
    proc_terminate($server);proc_close($server);foreach(['PM_TASK_HTTP_TOKEN','PM_TASK_HTTP_ACTOR','PM_TASK_HTTP_STORE'] as $key)putenv($key);
    foreach([$sid,$wrongSid] as $session){session_id($session);session_start();session_destroy();}
    if(taskPersistent($q)!==$before)throw new RuntimeException('Persistent endpoint data/engine changed.');
}
echo "PASS: $cases task-status HTTP cases, ADMIN/PM, CSRF/method/scalar/role/tenant/ownership/hidden guards, audit/no-op/completion; persistent users/projects/tasks/audit/tracking unchanged.\n";
