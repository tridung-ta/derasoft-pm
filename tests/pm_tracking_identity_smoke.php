<?php
/** Executes actual auth modules with recording collaborators; no database writes. */
if(!isset($argv[1])){
    foreach(['pm-login','legacy-login','pm-lock','legacy-lock','pm-logout','legacy-logout','pm-fail-new','legacy-fail-new','pm-fail-existing','legacy-fail-existing'] as $case){
        $process=proc_open([PHP_BINARY,__FILE__,$case],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process))throw new RuntimeException('Cannot start isolated module fixture.');
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);
        fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        if($exit!==0||$err!==''||trim($out)!=='PASS')throw new RuntimeException('Auth tracking module fixture failed: '.$case.' '.$err);
    }
    echo "PASS: ten actual login/lock/logout/failure module fixtures; PM account ID only, no new raw IP, historical IP retained, legacy identity preserved; no database access.\n";exit;
}
$case=$argv[1];$pm=str_starts_with($case,'pm-');$locked=str_ends_with($case,'-lock');$logout=str_ends_with($case,'-logout');
$failed=str_contains($case,'-fail-');$existing=str_ends_with($case,'-existing');
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);define('PM_HIDE_LEGACY',$pm);
define('MAX_FAIL_TIMES',3);define('MAX_GRACE_TIME',5);define('ADMIN_SCRIPT','admin.php');
class Users {
    public function __construct($store){}
    public function findUserIdByIdentity($identity){return 42;}
    public function authenticateUser($identity,$password){global $failed;return $failed?0:42;}
}
class CheckLogin {
    public static array $writes=[];
    public function getFailLoginInfo($id){global $locked,$existing;return ($locked||$existing)?['id'=>1,'fail_times'=>$locked?3:1,'last_try'=>date('Y-m-d H:i:s')]:false;}
    public function addData($fields){self::$writes[]=$fields;}
    public function updateData($fields,$id){self::$writes[]=$fields;}
}
class Trackings {
    public static array $rows=[];
    public function __construct($store){}
    public function addData($fields){self::$rows[]=$fields;return 1;}
}
class TrackingFixtureRequest {
    public function element($key){return ['username'=>'fixture@example.test','password'=>'fixture-only-password','csrf_token'=>'fixture-csrf','site'=>''][$key]??'';}
}
class TrackingFixtureTemplate {public function assign($key,$value){}}
class TrackingFixtureUser {public function getId(){return 42;}public function getUsername(){return 'fixture-user';}}
session_start();$_SESSION=['userId'=>42,'pm_login_csrf'=>'fixture-csrf','pm_csrf_token'=>'fixture-csrf'];
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['REMOTE_ADDR']='192.0.2.1';$_POST=['fixture'=>1];
$storeId=7;$userTemplate='admin';$customDomain=false;$request=new TrackingFixtureRequest();$template=new TrackingFixtureTemplate();
$trackings=new Trackings($storeId);$userInfo=new TrackingFixtureUser();
$amessages=['tracking'=>['login_ok'=>'Login','logout_ok'=>'Logout','lock_too_many_fail_logins'=>'Locked'],'your_account_has_been_blocked'=>'Blocked','invalid_user_password'=>'Invalid','fail_login_times'=>'Failures: %d'];
register_shutdown_function(function()use($pm,$logout,$failed,$existing){
    if($failed){
        $row=CheckLogin::$writes[0]??[];
        $valid=count(CheckLogin::$writes)===1&&count(Trackings::$rows)===0&&($row['uid']??0)===42;
        $valid=$valid&&($pm&&$existing?!array_key_exists('last_ip',$row):($row['last_ip']??null)===($pm?'':'192.0.2.1'));
        if(!$valid){fwrite(STDERR,"Failed-login identity policy failed.\n");exit(1);}
        echo "PASS\n";return;
    }
    $expected=$pm?'42':($logout?'fixture-user':'fixture@example.test');
    if(count(Trackings::$rows)!==1||Trackings::$rows[0]['username']!==$expected||Trackings::$rows[0]['ip']!==($pm?'':'192.0.2.1')){
        fwrite(STDERR,"Tracking identity policy failed.\n");exit(1);
    }
    echo "PASS\n";
});
$source=file_get_contents(ROOT_PATH.'modules/admin/'.($logout?'logout':'login').'.module.php');
if(!$logout){
    foreach(["include_once(ROOT_PATH.'classes/dao/users.class.php');","include_once(ROOT_PATH.'classes/security/checklogin.class.php');"] as $include){
        $source=str_replace($include,'',$source,$count);if($count!==1)throw new RuntimeException('Module fixture dependency no longer matches.');
    }
}
// Trusted repository module executes unchanged except DB collaborator includes.
eval('?>'.$source);
