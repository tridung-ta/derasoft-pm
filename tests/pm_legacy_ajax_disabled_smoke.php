<?php
// Exercise real ajax.php under the repository default PM mode. No DB module is reached.
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/pm_mode.inc.php';
if(PM_HIDE_LEGACY!==true)throw new RuntimeException('Expected default PM_HIDE_LEGACY=true.');
$port=18775;$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($socket){fclose($socket);throw new RuntimeException('Fixture port occupied.');}
$dir=ROOT_PATH.'.local/legacy-ajax-sessions';if(!is_dir($dir))mkdir($dir,0700,true);
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir,'-S','127.0.0.1:'.$port,'-t',ROOT_PATH],[0=>['pipe','r'],1=>['file',ROOT_PATH.'.local/legacy-ajax-http.log','a'],2=>['file',ROOT_PATH.'.local/legacy-ajax-http.log','a']],$pipes,ROOT_PATH);if(!is_resource($server))throw new RuntimeException('Server failed.');fclose($pipes[0]);
try{
    $deadline=microtime(true)+5;do{$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if($socket){fclose($socket);break;}usleep(10000);}while(microtime(true)<$deadline);if(!$socket)throw new RuntimeException('Server not ready.');
    $cases=0;foreach(['GET','POST'] as $method)foreach(['count','COUNT'] as $op){
        $ctx=stream_context_create(['http'=>['method'=>$method,'ignore_errors'=>true,'follow_location'=>0,'timeout'=>15,'header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$method==='POST'?'op='.$op.'&action=fixture':'']]);
        $body=file_get_contents('http://127.0.0.1:'.$port.'/ajax.php?op='.$op,false,$ctx);preg_match('/\s(\d{3})\s/',$http_response_header[0]??'',$m);
        if(($m[1]??'')!=='404'||json_decode($body,true)!==['error'=>'Legacy endpoint disabled'])throw new RuntimeException('Legacy count exposed in PM mode.');$cases++;
    }
    echo "PASS: default PM_HIDE_LEGACY=true; $cases real GET/POST legacy ajax count requests rejected 404 before config/database/module loading.\n";
}finally{proc_terminate($server);proc_close($server);}
