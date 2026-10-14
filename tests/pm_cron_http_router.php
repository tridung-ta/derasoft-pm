<?php
if(PHP_SAPI!=='cli-server'||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'||!getenv('PM_CRON_TEST_TOKEN')||!hash_equals(getenv('PM_CRON_TEST_TOKEN'),$_SERVER['HTTP_X_PM_TEST_TOKEN']??'')){http_response_code(404);exit;}
if(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)!=='/admin.php'||($_GET['op']??'')!=='pmnotifications'){http_response_code(404);exit;}
define('ROOT_PATH',dirname(__DIR__).'/');require ROOT_PATH.'tests/pm_cron_db_fixture.php';
$f=pmCronLoadFixture();$actor=(int)($_GET['fixture_actor']??$f['users'][0]['id']);$user=null;foreach($f['users'] as $candidate)if($candidate['id']===$actor)$user=$candidate;if(!$user){http_response_code(404);exit;}
$c=pmCronLocalConnection();pmCronMirrorUsers($c,$f);$db=(object)['connection'=>$c];$storeId=$user['store'];
require ROOT_PATH.'classes/security/pmaccess.class.php';require ROOT_PATH.'classes/http/request.class.php';require ROOT_PATH.'classes/template/smarty.class.php';require ROOT_PATH.'classes/services/pmuiservice.class.php';
define('PM_REMINDERS_ENABLED',true);session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax']);
$_SESSION['userId']=$actor;$_SESSION['storeId']=$storeId;$_SESSION['pm_csrf_token']=hash('sha256',$f['token']);
$userInfo=new class($actor){public function __construct(private int $id){}public function getId(){return $this->id;}};
$request=new Request();$template=new Smarty();$template->setTemplateDir(ROOT_PATH.'templates/admin');$template->setCompileDir(ROOT_PATH.'.local/smarty');
require ROOT_PATH.'modules/admin/pmnotifications.module.php';
$template->assign('pmNavigation',PmUiService::navigation($pmAccess,'pmnotifications'));
header('Content-Type: text/html; charset=utf-8');$template->display($templateFile);
