<?php
/** Collect all tenant failures; cleanup/persistent verification always runs at the end. */
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$q=new PmDb((object)['connection'=>$c]);
$actor=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' ORDER BY u.id LIMIT 1");if(!$actor)throw new RuntimeException('No actor.');
$tables=['dc_users','dc_trackings'];foreach($q->fetchAll("SELECT table_name name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name LIKE 'dc_pm_%' ORDER BY table_name") as $r){if(preg_match('/^dc_pm_[a-z_]+$/D',$r['name']))$tables[]=$r['name'];}
function tenantState(PmDb $q,array $tables): array {$hashes=[];foreach($tables as $t){$rows=$q->fetchAll('SELECT * FROM '.$t);usort($rows,fn($a,$b)=>strcmp(serialize($a),serialize($b)));$hashes[$t]=hash('sha256',serialize($rows));}return $hashes;}
$group=$argv[1]??'all';$groupOps=['a'=>['pmprojects','pmtaskstatus'],'b'=>['pmusers'],'c'=>['pmtimesheets','pmallocations','pmreports'],'all'=>['pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports','pmtaskstatus']];if(!isset($groupOps[$group]))throw new InvalidArgumentException('Unknown matrix group.');
$before=tenantState($q,$tables);$usersBefore=$q->fetchAll('SELECT * FROM dc_users ORDER BY id');$trackingBefore=$q->fetchOne('SELECT COALESCE(MAX(id),0) max_id,COUNT(*) n FROM dc_trackings');$startedAt=date('c');$port=18776;$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($socket){fclose($socket);throw new RuntimeException('Fixture port occupied. No process killed.');}
$dir=ROOT_PATH.'.local/tenant-matrix-sessions';$stateDir=ROOT_PATH.'.local/tenant-matrix-state';foreach([$dir,$stateDir] as $d)if(!is_dir($d))mkdir($d,0700,true);
ini_set('session.save_path',$dir);ini_set('session.use_cookies','0');session_cache_limiter('');session_id(bin2hex(random_bytes(20)));session_start();$_SESSION=['userId'=>(int)$actor['id'],'storeId'=>(int)$actor['store_id'],'pm_csrf_token'=>'matrix-csrf'];$sid=session_id();session_write_close();
$token=bin2hex(random_bytes(20));putenv('PM_ROLE_HTTP_TOKEN='.$token);putenv('PM_ROLE_HTTP_ACTOR='.$actor['id']);putenv('PM_ROLE_HTTP_STORE='.$actor['store_id']);
$today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
$foreign=['project'=>900000090,'task'=>900000091,'user'=>900000092,'department'=>900000093,'hidden_department'=>900000089,'role'=>900000094,'rate'=>900000095,'timesheet'=>900000096,'allocation'=>900000097,'capacity'=>900000098,'import'=>900000099];
$cases=[];
function matrixCase(string $op,string $key,string $type,array $base=[],bool $post=false,string $role='ADMIN',string $path='/admin.php',array $get=[],string $variant=''): void {global $cases;$cases[]=['label'=>$op.($post?' POST '.($base['action']??'status'):' GET '.($base['action']??$base['mode']??'read')).' / '.$key.($variant!==''?' ['.$variant.']':''),'op'=>$op,'key'=>$key,'type'=>$type,'base'=>$base,'post'=>$post,'role'=>$role,'path'=>$path,'get'=>$get];}
$project=['code'=>'MATRIX-EDIT','name'=>'Attempt update','description'=>'fixture','manager_id'=>(int)$actor['id'],'start_date'=>'2026-01-01','end_date'=>'2026-12-31','budget'=>'100','status'=>'active','client_name'=>''];
$task=['project_id'=>900000001,'task_id'=>900000011,'name'=>'Attempt update','description'=>'fixture','assignee_id'=>'','estimated_hours'=>'1','status'=>'todo','priority'=>'normal','start_date'=>'','due_date'=>''];
matrixCase('pmprojects','project_id','project',[],false,'PM');
foreach(['project_save'=>$project,'project_delete'=>[],'member_save'=>['member_id'=>900000041,'member_active'=>'1','project_role'=>'developer'],'task_save'=>$task,'task_status'=>['task_id'=>900000011,'status'=>'done'],'task_delete'=>['task_id'=>900000011]] as $action=>$data)matrixCase('pmprojects','project_id','project',['action'=>$action]+$data,true,'PM');
foreach(['task_save'=>$task,'task_status'=>['project_id'=>900000001,'status'=>'done'],'task_delete'=>['project_id'=>900000001]] as $action=>$data)matrixCase('pmprojects','task_id','task',['action'=>$action]+$data,true,'PM');
matrixCase('pmprojects','member_id','user',['action'=>'member_save','project_id'=>900000001,'member_active'=>'1','project_role'=>'developer'],true,'PM');
matrixCase('pmprojects','assignee_id','user',['action'=>'task_save']+$task,true,'PM');
matrixCase('pmprojects','manager_id','user',['action'=>'project_save','project_id'=>900000001]+$project,true,'ADMIN');
foreach(['project_id'=>'project','task_id'=>'task'] as $key=>$type)matrixCase('pmtaskstatus',$key,$type,['project_id'=>900000001,'task_id'=>900000011,'status'=>'done'],true,'PM','/pm_ajax.php');
$sheet=['id'=>900000021,'task_id'=>900000011,'work_date'=>$today,'shift_label'=>'Fixture shift','hours'=>'1','description'=>'fixture'];
matrixCase('pmtimesheets','id','timesheet');
matrixCase('pmtimesheets','id','timesheet',['action'=>'save']+$sheet,true);
matrixCase('pmtimesheets','id','timesheet',['action'=>'delete'],true);
matrixCase('pmtimesheets','task_id','task',['action'=>'save']+$sheet,true);
$allocation=['task_id'=>900000011,'user_id'=>(int)$actor['id'],'work_date'=>$today,'hours'=>'1','start_time'=>'11:00','end_time'=>'12:00'];
matrixCase('pmallocations','id','allocation');
foreach(['save'=>$allocation,'delete'=>[]] as $action=>$data)matrixCase('pmallocations','id','allocation',['action'=>$action]+$data,true);
foreach(['task_id'=>'task','user_id'=>'user'] as $key=>$type)matrixCase('pmallocations',$key,$type,['action'=>'save']+$allocation,true);
$capacity=['user_id'=>900000041,'daily_limit_hours'=>'8','weekly_limit_hours'=>'40','effective_from'=>'2000-01-01','effective_to'=>''];
matrixCase('pmallocations','id','capacity',['action'=>'capacity']+$capacity,true);
matrixCase('pmallocations','user_id','user',['action'=>'capacity']+$capacity,true);
matrixCase('pmallocations','id','capacity',['action'=>'capacity_delete'],true);
foreach(['/admin.php','/pm_ajax.php'] as $path){matrixCase('pmallocations','project_id','project',['week'=>$today],false,'PM',$path);matrixCase('pmallocations','user_id','user',['week'=>$today],false,'ADMIN',$path);}
matrixCase('pmallocations','project_id','project',['action'=>'suggestions','work_date'=>$today,'hours'=>'1'],false,'PM','/pm_ajax.php');
$person=['fullname'=>'Fixture changed','email'=>'fixture-changed@example.test','tel'=>'0900000000','department_id'=>'','weekly_limit_hours'=>'40'];
matrixCase('pmusers','id','user',['action'=>'save']+$person,true);
matrixCase('pmusers','id','user',['action'=>'status','status'=>'0'],true);
matrixCase('pmusers','id','user',['action'=>'roles','role_ids'=>[],'primary_role_id'=>'0'],true);
$employeeRole=$q->fetchOne("SELECT id FROM dc_pm_roles WHERE store_id=? AND code='EMPLOYEE' AND status=1",'i',[(int)$actor['store_id']]);if(!$employeeRole)throw new RuntimeException('Missing EMPLOYEE role.');
matrixCase('pmusers','role_ids','role',['action'=>'roles','id'=>900000041,'primary_role_id'=>0],true);
matrixCase('pmusers','department_id','department',['action'=>'save','id'=>900000041]+$person,true);
foreach(['department_save'=>['code'=>'MATRIX-EDIT','name'=>'Fixture changed department'],'department_status'=>['status'=>'0'],'department_delete'=>[],'department_restore'=>[]] as $action=>$data)matrixCase('pmusers','id',$action==='department_restore'?'hidden_department':'department',['action'=>$action]+$data,true);
$rate=['rate'=>'123','effective_from'=>'2001-01-01','effective_to'=>'2001-01-02'];
matrixCase('pmusers','id','user',['action'=>'rate']+$rate,true);
matrixCase('pmusers','rate_user_id','user',['action'=>'rate_save','rate_role_id'=>'']+$rate,true);
matrixCase('pmusers','rate_role_id','role',['action'=>'rate_save','rate_user_id'=>'']+$rate,true);
matrixCase('pmusers','rate_id','rate',['action'=>'rate_save','rate_user_id'=>900000041,'rate_role_id'=>'']+$rate,true);
matrixCase('pmusers','id','rate',['action'=>'rate_deactivate'],true);
foreach(['department_id'=>'department','role_id'=>'role'] as $key=>$type)matrixCase('pmusers',$key,$type);
foreach(['/admin.php','/pm_ajax.php'] as $path)matrixCase('pmcosts','project_id','project',[],false,'ADMIN',$path);
foreach(['hours','tasks','costs','users','projects'] as $mode){$key=$mode==='users'?'user_id':'project_id';$type=$mode==='users'?'user':'project';matrixCase('pmreports',$key,$type,['mode'=>$mode],false);matrixCase('pmreports',$key,$type,['action'=>'export','mode'=>$mode],true);if(in_array($mode,['hours','tasks','costs'],true)){matrixCase('pmreports','user_id','user',['mode'=>$mode],false);matrixCase('pmreports','user_id','user',['action'=>'export','mode'=>$mode],true);}}
foreach(['detail','apply','resume','provision_password'] as $action)matrixCase('pmimports','import_id','import',['action'=>$action,'source_row'=>2,'password'=>'Fixture password 123'],true);

// Additional independent reference positions/creation paths, not just primary IDs.
matrixCase('pmprojects','project_id','project',[],false,'ADMIN','/admin.php',[],'Admin read');
matrixCase('pmprojects','member_id','user',['action'=>'member_save','project_id'=>900000001,'member_active'=>'0','project_role'=>'developer'],true,'PM','/admin.php',[],'remove member');
matrixCase('pmprojects','manager_id','user',['action'=>'project_save']+$project,true,'ADMIN','/admin.php',[],'create project');
matrixCase('pmprojects','assignee_id','user',['action'=>'task_save']+array_replace($task,['task_id'=>'']),true,'PM','/admin.php',[],'create task');
matrixCase('pmtimesheets','task_id','task',['action'=>'save']+array_replace($sheet,['id'=>'']),true,'ADMIN','/admin.php',[],'create timesheet');
foreach(['task_id'=>'task','user_id'=>'user'] as $key=>$type)matrixCase('pmallocations',$key,$type,['action'=>'save','id'=>900000031]+$allocation,true,'ADMIN','/admin.php',[],'edit allocation');
matrixCase('pmallocations','user_id','user',['action'=>'capacity','id'=>900000081]+$capacity,true,'ADMIN','/admin.php',[],'edit capacity');
matrixCase('pmusers','primary_role_id','role',['action'=>'roles','id'=>900000041,'role_ids'=>[(int)$employeeRole['id']]],true);
matrixCase('pmusers','department_id','department',['action'=>'save','username'=>'matrix-new-person','password'=>'Fixture password 123']+$person,true,'ADMIN','/admin.php',[],'create personnel');

function matrixRequest(array $case,$id): array {
 global $port,$sid,$token,$stateDir,$foreign;$nonce=bin2hex(random_bytes(12));$input=$case['base'];$input[$case['key']]=$case['key']==='role_ids'?[$id]:$id;if($case['key']==='role_ids')$input['primary_role_id']=$id;
 $query=['op'=>$case['op']]+$case['get'];$post=null;
 if($case['post']){$post=['op'=>$case['op'],'csrf_token'=>'matrix-csrf']+$input;}else{$query+=$input;}
 $headers="Cookie: PHPSESSID=$sid\r\nX-PM-Test-Token: $token\r\nX-PM-Test-Role: ".$case['role']."\r\nX-PM-Test-Nonce: $nonce\r\n";
 if($post!==null)$headers.="Content-Type: application/x-www-form-urlencoded\r\n";
 $ctx=stream_context_create(['http'=>['method'=>$post===null?'GET':'POST','header'=>$headers,'content'=>$post===null?'':http_build_query($post),'ignore_errors'=>true,'follow_location'=>0,'timeout'=>15]]);
 $start=microtime(true);$body=@file_get_contents('http://127.0.0.1:'.$port.$case['path'].'?'.http_build_query($query),false,$ctx);$elapsed=round((microtime(true)-$start)*1000,3);
 preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$m);$status=(int)($m[1]??0);$observation=$stateDir.'/'.$nonce.'.json';
 $deadline=microtime(true)+2;while(!is_file($observation)&&microtime(true)<$deadline)usleep(10000);
 $state=is_file($observation)?json_decode(file_get_contents($observation),true,512,JSON_THROW_ON_ERROR):['observer_error'=>'Missing observation'];
 $message=$state['message']??null;$json=json_decode($body===false?'':$body,true);if(is_array($json)&&isset($json['error']))$message=$json['error'];
 $text=$body===false?'':$body;
 if(str_starts_with($text,'PK')){$tmp=tempnam(ROOT_PATH.'.local','matrix-xlsx-');try{file_put_contents($tmp,$text);$zip=new ZipArchive();if($zip->open($tmp)!==true)throw new RuntimeException('Cannot inspect response XLSX.');$text='';for($i=0;$i<$zip->numFiles;$i++)if(str_ends_with($zip->getNameIndex($i),'.xml'))$text.=$zip->getFromIndex($i);$zip->close();}finally{unlink($tmp);}}
 $leak=false;foreach(['Foreign tenant','Foreign hidden','foreign-identity-sentinel','foreign-sentinel@example.test','Foreign import sentinel','foreign-import-sentinel','Foreign audit sentinel'] as $sentinel)if(str_contains(html_entity_decode($text,ENT_QUOTES|ENT_HTML5,'UTF-8'),$sentinel))$leak=true;
 if($case['control']??false)foreach($foreign as $foreignId)if(str_contains($text,(string)$foreignId))$leak=true;
 $visibleMessage=is_array($json)&&isset($json['error'])&&is_string($json['error'])&&$json['error']!=='';
 if(!$visibleMessage)$visibleMessage=$message!==null&&$message!==''&&str_contains(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($text),ENT_QUOTES|ENT_HTML5,'UTF-8')),trim(preg_replace('/\s+/u',' ',html_entity_decode($message,ENT_QUOTES|ENT_HTML5,'UTF-8'))));
 return ['location'=>array_values(array_filter($http_response_header??[],fn($h)=>str_starts_with($h,'Location: '))),'message_visible'=>$visibleMessage,'status'=>$status,'service_trace'=>$state['service_trace']??null,'message'=>$message===null?null:trim(preg_replace('/\s+/u',' ',html_entity_decode($message,ENT_QUOTES|ENT_HTML5,'UTF-8'))),'ms'=>$elapsed,'fixture'=>$state['fixture_verified'][$case['type']]??false,'missing_verified'=>$state['missing_verified'][$case['type']]??false,'temporary_reopen_probe_code'=>$state['temporary_reopen_probe_code']??null,'controller_sql_error_code'=>$state['controller_sql_error_code']??null,'controller_sql_error_alias'=>$state['controller_sql_error_alias']??null,'unchanged'=>$state['unchanged']??false,'audit_count'=>$state['audit_count']??null,'tracking_count'=>$state['tracking_count']??null,'leak'=>$leak,'observer_error'=>$state['observer_error']??null];
}
function safeMatrixRequest(array $case,$id): array {try{return matrixRequest($case,$id);}catch(Throwable $e){return ['status'=>0,'message'=>null,'fixture'=>false,'missing_verified'=>false,'unchanged'=>false,'audit_count'=>null,'tracking_count'=>null,'leak'=>false,'observer_error'=>get_class($e)];}}
$cases=array_values(array_filter($cases,fn($case)=>in_array($case['op'],$groupOps[$group],true)));
$server=null;$rows=[];$controls=[];$contracts=[];$cleanup=false;$runError=null;
try {
 $server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:'.$port,ROOT_PATH.'tests/pm_tenant_matrix_router.php'],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/tenant-matrix.log','a'],2=>['file',ROOT_PATH.'.local/tenant-matrix.log','a']],$pipes,ROOT_PATH);if(!is_resource($server))throw new RuntimeException('No server.');fclose($pipes[0]);
 $ready=false;$deadline=microtime(true)+5;do{$s=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($s){fclose($s);$ready=true;break;}usleep(10000);}while(microtime(true)<$deadline);if(!$ready)throw new RuntimeException('Server not ready.');
 // Positive scoped reads prove the real modules run, not a router-wide denial.
 foreach(['pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'] as $op){if(!in_array($op,$groupOps[$group],true))continue;$case=['op'=>$op,'key'=>'page','type'=>'project','base'=>[],'post'=>false,'role'=>'ADMIN','path'=>'/admin.php','get'=>[],'control'=>true];$r=safeMatrixRequest($case,1);$ok=$r['status']===200&&!$r['observer_error']&&!$r['leak']&&$r['unchanged'];$controls[]=['endpoint'=>$op,'pass'=>$ok,'response'=>$r];echo ($ok?'PASS':'FAIL').' control '.$op.' HTTP'.$r['status']."\n";}
 foreach(['pmcosts','pmallocations','pmtaskstatus'] as $op){if(!in_array($op,$groupOps[$group],true))continue;$case=['op'=>$op,'key'=>$op==='pmtaskstatus'?'project_id':'page','type'=>'project','base'=>$op==='pmtaskstatus'?['task_id'=>900000011,'status'=>'todo']:[],'post'=>$op==='pmtaskstatus','role'=>'ADMIN','path'=>'/pm_ajax.php','get'=>[]];$r=safeMatrixRequest($case,$op==='pmtaskstatus'?900000001:1);$ok=$r['status']===200&&!$r['observer_error']&&!$r['leak']&&$r['unchanged'];$controls[]=['endpoint'=>'/pm_ajax.php '.$op,'pass'=>$ok,'response'=>$r];echo ($ok?'PASS':'FAIL').' control /pm_ajax.php '.$op.' HTTP'.$r['status'].PHP_EOL;}
 foreach($cases as $i=>$case){$responses=[];foreach(['foreign'=>$foreign[$case['type']],'missing'=>999000000] as $kind=>$id){try{$responses[$kind]=matrixRequest($case,$id);}catch(Throwable $e){$responses[$kind]=['status'=>0,'message'=>null,'fixture'=>false,'unchanged'=>false,'audit_count'=>null,'tracking_count'=>null,'leak'=>false,'observer_error'=>get_class($e)];}}
 $f=$responses['foreign'];$n=$responses['missing'];$same=$f['status']===$n['status']&&$f['message']===$n['message'];$reasons=[];
 foreach($responses as $kind=>$r){if(!in_array($r['status'],[403,404],true))$reasons[]=$kind.': HTTP'.$r['status'];if(!$r['fixture'])$reasons[]=$kind.': fixture unverified';if(!($r['missing_verified']??false))$reasons[]=$kind.': missing ID unverified';if(in_array($r['status'],[403,404],true)&&(empty($r['message'])||!($r['message_visible']??false)))$reasons[]=$kind.': denial message absent';if($r['message']!=='Không thể truy cập dữ liệu.')$reasons[]=$kind.': non-generic denial';if(!$r['unchanged']||$r['audit_count']!==0||$r['tracking_count']!==0)$reasons[]=$kind.': state/audit/tracking changed';if($r['leak'])$reasons[]=$kind.': foreign sentinel leaked';if($r['observer_error'])$reasons[]=$kind.': observer '.$r['observer_error'];}
 if($case['op']==='pmprojects'&&($case['base']['action']??'')==='task_save'&&$case['key']==='task_id'&&(($f['service_trace']['exception']??null)!=='OutOfBoundsException'||!($f['service_trace']['unchanged']??false)))$reasons[]='service exception trace mismatch';
 if(!$same)$reasons[]='status/message parity failed';$ok=!$reasons;$rows[]=['endpoint'=>$case['path'],'label'=>$case['label'],'id_key'=>$case['key'],'resource'=>$case['type'],'role'=>$case['role'],'pass'=>$ok,'same_status_message'=>$same,'foreign'=>$f,'missing'=>$n,'reasons'=>$reasons];
 echo ($ok?'PASS':'FAIL').' | '.($i+1).' | '.$case['path'].' '.$case['label'].' | foreign='.$foreign[$case['type']].'/'.$f['status'].' | missing=999000000/'.$n['status'].' | same='.($same?'yes':'no').' | '.implode('; ',$reasons)."\n";
 }
 if(in_array($group,['a','all'],true)){
 $behavior=[
 ['local nonmember GET','project_id',900000002,[],false,'PM',403,true,0,0],
 ['local nonmanager task POST','project_id',900000002,['action'=>'task_status','task_id'=>900000012,'status'=>'done'],true,'PM',403,true,0,0],
 ['malformed project GET','project_id','abc',[],false,'ADMIN',200,true,0,0],
 ['malformed task ID','task_id','abc',['action'=>'task_save']+$task,true,'ADMIN',200,true,0,0],
 ['malformed manager ID','manager_id','abc',['action'=>'project_save','project_id'=>900000001]+$project,true,'ADMIN',200,true,0,0],
 ['invalid name','project_id',900000001,['action'=>'project_save']+array_replace($project,['name'=>'']),true,'ADMIN',200,true,0,0],
 ['duplicate code business','project_id',900000001,['action'=>'project_save']+array_replace($project,['code'=>'MATRIX-RESTRICTED']),true,'ADMIN',200,true,0,0],
 ['same-day project dates','project_id',900000001,['action'=>'project_save']+array_replace($project,['start_date'=>'2026-10-02','end_date'=>'2026-10-02']),true,'ADMIN',303,true,0,0],
 ['save redirects','project_id',900000001,['action'=>'project_save']+$project,true,'ADMIN',303,false,0,1],
 ['hide redirects','project_id',900000001,['action'=>'project_delete'],true,'ADMIN',303,false,0,1],
 ['same-day task allowed','task_id',900000011,['action'=>'task_save']+array_replace($task,['start_date'=>'2026-10-02','due_date'=>'2026-10-02']),true,'ADMIN',200,false,1,1]
 ];
 foreach($behavior as [$label,$key,$id,$base,$post,$role,$status,$unchanged,$audit,$tracking]){
 $case=['op'=>'pmprojects','key'=>$key,'type'=>'project','base'=>$base,'post'=>$post,'role'=>$role,'path'=>'/admin.php','get'=>[]];$r=safeMatrixRequest($case,$id);
 $ok=$r['status']===$status&&$r['unchanged']===$unchanged&&$r['audit_count']===$audit&&$r['tracking_count']===$tracking&&!$r['observer_error']&&!$r['leak'];
 if($status===403)$ok=$ok&&$r['message']==='Không thể truy cập dữ liệu.';
 if($status===303)$ok=$ok&&$r['location']===['Location: admin.php?op=pmprojects'];
 if($status===200&&$unchanged)$ok=$ok&&!empty($r['message'])&&$r['message_visible'];
 $contracts[]=['label'=>$label,'pass'=>$ok,'response'=>$r];echo ($ok?'PASS':'FAIL').' behavior '.$label.' HTTP'.$r['status'].PHP_EOL;
 }
 }
 if(in_array($group,['b','all'],true)){
 $pmRole=$q->fetchOne("SELECT id FROM dc_pm_roles WHERE store_id=? AND code='PM' AND status=1",'i',[(int)$actor['store_id']]);
 $behavior=[
 ['malformed user','id','abc',['action'=>'save']+$person,true,200],
 ['malformed department filter','department_id','abc',[],false,200],
 ['malformed role filter','role_id','abc',[],false,200],
 ['malformed department ref','department_id','abc',['action'=>'save','id'=>900000041]+$person,true,200],
 ['invalid password','id','',['action'=>'save','username'=>'matrix-new','password'=>'x']+$person,true,200],
 ['invalid create phone','id','',['action'=>'save','username'=>'matrix-new','password'=>'Fixture password 123']+array_replace($person,['tel'=>'abc']),true,200],
 ['inactive role','primary_role_id',900000064,['action'=>'roles','id'=>900000041,'role_ids'=>[900000064]],true,200],
 ['primary local not selected','primary_role_id',(int)$pmRole['id'],['action'=>'roles','id'=>900000041,'role_ids'=>[(int)$employeeRole['id']]],true,200],
 ['duplicate email business','id',900000041,['action'=>'save']+array_replace($person,['email'=>'matrix-inactive@example.test']),true,200]
 ];
 foreach($behavior as [$label,$key,$id,$base,$post,$status]){
 $case=['op'=>'pmusers','key'=>$key,'type'=>'user','base'=>$base,'post'=>$post,'role'=>'ADMIN','path'=>'/admin.php','get'=>[]];$r=safeMatrixRequest($case,$id);
 $ok=$r['status']===$status&&$r['unchanged']&&$r['audit_count']===0&&$r['tracking_count']===0&&!$r['observer_error']&&!$r['leak']&&!empty($r['message'])&&$r['message_visible'];
 $contracts[]=['label'=>$label,'pass'=>$ok,'response'=>$r];echo ($ok?'PASS':'FAIL').' behavior '.$label.' HTTP'.$r['status'].PHP_EOL;
 }
 }
} catch(Throwable $e){$runError=get_class($e).': '.$e->getMessage();echo 'FAIL harness: '.$runError."\n";}
finally {
 if(is_resource($server)){proc_terminate($server);proc_close($server);}
 try{$after=tenantState($q,$tables);$cleanup=$before===$after;$usersAfter=$q->fetchAll('SELECT * FROM dc_users ORDER BY id');$trackingAfter=$q->fetchOne('SELECT COALESCE(MAX(id),0) max_id,COUNT(*) n FROM dc_trackings');$changedUserFields=[];$oldById=array_column($usersBefore,null,'id');foreach($usersAfter as $u){$old=$oldById[$u['id']]??[];foreach($u as $field=>$value)if(!array_key_exists($field,$old)||$old[$field]!==$value)$changedUserFields[(string)$u['id']][]=$field;}foreach($oldById as $id=>$u)if(!in_array($id,array_column($usersAfter,'id')))$changedUserFields[(string)$id]=['row_missing'];}catch(Throwable $e){$after=[];$cleanup=false;}
 echo ($cleanup?'PASS':'FAIL').' cleanup: all '.count($tables).' persistent tables compared before/after; owned server stopped; temporary mirrors destroyed.' ."\n";
 $summary=['started_at'=>$startedAt,'finished_at'=>date('c'),'changed_user_fields'=>$changedUserFields??null,'tracking_before'=>$trackingBefore,'tracking_after'=>$trackingAfter??null,'date'=>'2026-10-08','source_commit'=>trim(shell_exec('git -c safe.directory=D:/derasoft-pm rev-parse HEAD')??''),'runtime_modified'=>trim(shell_exec('git -c safe.directory=D:/derasoft-pm diff --name-only HEAD -- classes modules includes templates css js admin.php pm_ajax.php')??'')!=='','command'=>'.tools/php83/php.exe tests/pm_tenant_matrix_smoke.php '.$group,'group'=>$group,'contracts'=>$contracts,'controls'=>$controls,'rows'=>$rows,'cleanup'=>$cleanup,'persistent_before'=>$before,'persistent_after'=>$after,'run_error'=>$runError];
 file_put_contents(ROOT_PATH.'.local/tenant-matrix-'.$group.'-20261008.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
}
$passed=count(array_filter($rows,fn($r)=>$r['pass']));$failed=count($rows)-$passed;$controlFailed=count(array_filter($controls,fn($r)=>!$r['pass']));$contractFailed=count(array_filter($contracts,fn($r)=>!$r['pass']));
echo "RESULT: $passed PASS / $failed FAIL endpoint-ID pairs; ".(count($rows)*2)." requests; $controlFailed control failures; cleanup=".($cleanup?'PASS':'FAIL').". Group1 gate remains closed if any FAIL.\n";
echo count($contracts)." behavioral controls; $contractFailed failures.\n";
exit(($failed||$controlFailed||$contractFailed||!$cleanup||$runError)?1:0);
