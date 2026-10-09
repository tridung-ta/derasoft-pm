<?php
/** Verify aggregate requests ignore caller store_id; real controllers, temporary schema mirrors. */
define('PM_TENANT_FIXTURE_BOOTSTRAP_ONLY',true);
require __DIR__.'/pm_tenant_matrix_smoke.php';
$server=null;$checks=[];$runError=null;$cleanup=false;
try {
 $server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:'.$port,ROOT_PATH.'tests/pm_tenant_matrix_router.php'],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/aggregate-store-http.log','a'],2=>['file',ROOT_PATH.'.local/aggregate-store-http.log','a']],$pipes,ROOT_PATH);
 if(!is_resource($server))throw new RuntimeException('Server start failed.');fclose($pipes[0]);
 $ready=false;$deadline=microtime(true)+5;do{$sock=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($sock){fclose($sock);$ready=true;break;}usleep(10000);}while(microtime(true)<$deadline);if(!$ready)throw new RuntimeException('Server not ready.');
 $definitions=[['pmcosts','/admin.php',[],false],['pmcosts','/pm_ajax.php',[],false]];
 foreach(['hours','tasks','costs','users','projects'] as $mode){$definitions[]=['pmreports','/admin.php',['mode'=>$mode],false];$definitions[]=['pmreports','/admin.php',['mode'=>$mode,'action'=>'export'],true];}
 foreach($definitions as [$op,$path,$base,$post]){
  $case=['op'=>$op,'key'=>'page','type'=>'project','base'=>$base,'post'=>$post,'role'=>'ADMIN','path'=>$path,'get'=>[],'control'=>true];
  $reference=safeMatrixRequest($case,1);
  foreach([(int)$actor['store_id']+1,999000000,'not-a-store'] as $fake){
   $forged=$case;$forged['get']['store_id']=$fake;if($post)$forged['base']['store_id']=$fake;
   $response=safeMatrixRequest($forged,1);
   $ok=$reference['status']===200&&$response['status']===200&&($reference['body_sha256']??null)===($response['body_sha256']??false)
    &&$reference['unchanged']&&$response['unchanged']&&$reference['audit_count']===0&&$response['audit_count']===0&&$reference['tracking_count']===0&&$response['tracking_count']===0
    &&!$reference['observer_error']&&!$response['observer_error']&&!$reference['leak']&&!$response['leak'];
   $label=$op.' '.($post?'POST export':'GET').' '.($base['mode']??'dashboard').' '.$path.' store_id='.$fake;
   $checks[]=['label'=>$label,'pass'=>$ok,'reference'=>$reference,'response'=>$response];echo ($ok?'PASS':'FAIL').' '.$label.PHP_EOL;
  }
 }
}catch(Throwable $e){$runError=get_class($e).': '.$e->getMessage();echo 'FAIL harness '.$runError.PHP_EOL;}
finally{
 if(is_resource($server)){proc_terminate($server);proc_close($server);}
 try{$after=tenantState($q,$tables);$cleanup=$before===$after;}catch(Throwable $e){$after=[];$cleanup=false;}
 file_put_contents(ROOT_PATH.'.local/aggregate-store-input-20261009.json',json_encode(['source_commit'=>trim(shell_exec('git -c safe.directory=D:/derasoft-pm rev-parse HEAD')??''),'checks'=>$checks,'run_error'=>$runError,'cleanup'=>$cleanup,'persistent_before'=>$before,'persistent_after'=>$after],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
 echo ($cleanup?'PASS':'FAIL').' cleanup: '.count($tables).' persistent tables unchanged; owned server stopped; temporary fixtures destroyed.'.PHP_EOL;
}
$failed=count(array_filter($checks,fn($check)=>!$check['pass']));echo 'RESULT 1.4: '.(count($checks)-$failed).' PASS / '.$failed.' FAIL; '.count($checks).' comparisons.'.PHP_EOL;
exit($failed||!$cleanup||$runError||count($checks)!==36?1:0);
