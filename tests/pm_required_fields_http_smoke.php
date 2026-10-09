<?php
/** Collect 0.3/0.4 actual controller evidence; do not weaken field-error expectations. */
define('PM_TENANT_FIXTURE_BOOTSTRAP_ONLY',true);
require __DIR__.'/pm_tenant_matrix_smoke.php';
$server=null;$checks=[];$runError=null;$cleanup=false;
try {
 $server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:'.$port,ROOT_PATH.'tests/pm_tenant_matrix_router.php'],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/aggregate-store-http.log','a'],2=>['file',ROOT_PATH.'.local/aggregate-store-http.log','a']],$pipes,ROOT_PATH);
 if(!is_resource($server))throw new RuntimeException('Server start failed.');fclose($pipes[0]);
 $ready=false;$deadline=microtime(true)+5;do{$sock=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($sock){fclose($sock);$ready=true;break;}usleep(10000);}while(microtime(true)<$deadline);if(!$ready)throw new RuntimeException('Server not ready.');
 $personBase=['action'=>'save','id'=>'','username'=>'matrix-random-test','password'=>'Fixture password 123','fullname'=>'akjshdkjasdkj','email'=>'123123123@gmail.vn','tel'=>'','department_id'=>'','weekly_limit_hours'=>'40'];
 $case=['op'=>'pmusers','key'=>'id','type'=>'user','base'=>$personBase,'post'=>true,'role'=>'ADMIN','path'=>'/admin.php','get'=>[]];$r=safeMatrixRequest($case,'');$ok=$r['status']===200&&$r['fixture_named_user_matches']&&$r['unchanged']===false&&$r['audit_count']===0&&$r['tracking_count']===1&&!$r['observer_error'];$checks[]=['label'=>'0.3 accepted random-looking fullname and syntactically valid email','pass'=>$ok,'response'=>$r];echo ($ok?'PASS':'FAIL').' 0.3 accepted literal fixture name/email'.PHP_EOL;
 $inputs=[];
 $personMessages=['username'=>'/(?:tên đăng nhập|username)/iu','password'=>'/mật khẩu/iu','fullname'=>'/họ(?: và)? tên/iu','email'=>'/Email/iu','weekly_limit_hours'=>'/Giới hạn giờ\/tuần/u'];
 foreach(['username','password','fullname','email','weekly_limit_hours'] as $field)$inputs[]=['pmusers',$field,$personBase,$personMessages[$field]];
 foreach(['fullname','email','weekly_limit_hours'] as $field)$inputs[]=['pmusers',$field,array_replace($personBase,['id'=>900000041]),$personMessages[$field]];
 $projectMessages=['code'=>'/mã dự án/iu','name'=>'/tên dự án/iu','manager_id'=>'/người (?:phụ trách|quản lý)|quản lý dự án/iu','budget'=>'/ngân sách/iu'];
 foreach([900000001,''] as $id)foreach($projectMessages as $field=>$message)$inputs[]=['pmprojects',$field,['action'=>'project_save','project_id'=>$id]+$project,$message];
 foreach([900000011,''] as $id)foreach(['name'=>'/tên (?:công việc|task)/iu','estimated_hours'=>'/giờ (?:ước tính|dự kiến)|ước tính giờ/iu'] as $field=>$message)$inputs[]=['pmprojects',$field,array_replace(['action'=>'task_save']+$task,['task_id'=>$id]),$message];
 foreach($inputs as [$op,$field,$data,$expected]){$case=['op'=>$op,'key'=>$field,'type'=>'user','base'=>$data,'post'=>true,'role'=>'ADMIN','path'=>'/admin.php','get'=>[]];$r=safeMatrixRequest($case,'');$ok=$r['status']===200&&$r['unchanged']&&$r['audit_count']===0&&$r['tracking_count']===0&&!$r['observer_error']&&$r['message_visible']&&preg_match($expected,$r['message']??'')===1;
 if($op==='pmusers'&&$field==='username')$ok=$ok&&!preg_match('/mật khẩu|trùng/iu',$r['message']??'');
 if($op==='pmusers'&&$field==='email')$ok=$ok&&!preg_match('/họ(?: và)? tên/iu',$r['message']??'');
 if($op==='pmusers'&&$field==='fullname')$ok=$ok&&!preg_match('/email/iu',$r['message']??'');
 if($op==='pmprojects')$ok=$ok&&!preg_match($field==='code'?'/tên|mô tả/iu':'/mã|mô tả/iu',$r['message']??'');
 $formId=$op==='pmusers'?($data['id']??''):(($data['action']??'')==='task_save'?($data['task_id']??''):($data['project_id']??''));$label='0.4 '.$op.' '.($data['action']??'').($formId===''?' create':' edit #'.$formId).' empty '.$field;$checks[]=['label'=>$label,'pass'=>$ok,'expected_field_message'=>$expected,'response'=>$r];echo ($ok?'PASS':'FAIL').' '.$label.' message='.($r['message']??'NULL').PHP_EOL;}
 $controls=[
 ['all required user fields','pmusers','page',1,array_replace($personBase,['username'=>'','password'=>'','fullname'=>'','email'=>'']),200,true,['username','password','fullname','email']],
 ['whitespace username','pmusers','username','   ',$personBase,200,true,['username']],
 ['whitespace project name','pmprojects','name','   ',['action'=>'project_save','project_id'=>900000001]+$project,200,true,[]],
 ['whitespace estimate','pmprojects','estimated_hours','   ',['action'=>'task_save']+$task,200,true,[]],
 ['zero budget','pmprojects','budget','0',['action'=>'project_save','project_id'=>900000001]+$project,303,false,[]],
 ['zero estimate','pmprojects','estimated_hours','0',['action'=>'task_save']+$task,200,false,[]],
 ['negative budget still rejected','pmprojects','budget','-1',['action'=>'project_save','project_id'=>900000001]+$project,200,true,[]],
 ['precision estimate still rejected','pmprojects','estimated_hours','1.234',['action'=>'task_save']+$task,200,true,[]],
 ['password and phone errors retained','pmusers','password','x',array_replace($personBase,['tel'=>'abc']),200,true,['password','tel']]
 ];
 foreach($controls as [$label,$op,$key,$value,$data,$status,$unchanged,$errorKeys]){$case=['op'=>$op,'key'=>$key,'type'=>'user','base'=>$data,'post'=>true,'role'=>'ADMIN','path'=>'/admin.php','get'=>[]];$r=safeMatrixRequest($case,$value);$ok=$r['status']===$status&&$r['unchanged']===$unchanged&&!$r['observer_error'];
  if($unchanged)$ok=$ok&&$r['audit_count']===0&&$r['tracking_count']===0&&$r['message_visible']&&!empty($r['message']);
  else{$ok=$ok&&$r['tracking_count']===1&&$r['audit_count']===($label==='zero estimate'?1:0);if($label==='zero estimate')$ok=$ok&&$r['fixture_task_estimate_zero'];if($status===303)$ok=$ok&&$r['location']===['Location: admin.php?op=pmprojects'];}
  foreach($errorKeys as $errorKey)$ok=$ok&&in_array($errorKey,$r['field_error_keys'],true);
  $checks[]=['label'=>'0.4 control '.$label,'pass'=>$ok,'response'=>$r];echo ($ok?'PASS':'FAIL').' control '.$label.PHP_EOL;
 }
}catch(Throwable $e){$runError=get_class($e).': '.$e->getMessage();echo 'FAIL harness '.$runError.PHP_EOL;}
finally{
 if(is_resource($server)){proc_terminate($server);proc_close($server);}
 try{$after=tenantState($q,$tables);$cleanup=$before===$after;}catch(Throwable $e){$after=[];$cleanup=false;}
 file_put_contents(ROOT_PATH.'.local/required-fields-http-20261009.json',json_encode(['source_commit'=>trim(shell_exec('git -c safe.directory=D:/derasoft-pm rev-parse HEAD')??''),'checks'=>$checks,'run_error'=>$runError,'cleanup'=>$cleanup,'persistent_before'=>$before,'persistent_after'=>$after],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
 echo ($cleanup?'PASS':'FAIL').' cleanup: '.count($tables).' persistent tables unchanged; owned server stopped; temporary fixtures destroyed.'.PHP_EOL;
}
$failed=count(array_filter($checks,fn($check)=>!$check['pass']));echo 'RESULT 0.3/0.4: '.(count($checks)-$failed).' PASS / '.$failed.' FAIL; '.count($checks).' comparisons.'.PHP_EOL;
exit($failed||!$cleanup||$runError||count($checks)!==30?1:0);
