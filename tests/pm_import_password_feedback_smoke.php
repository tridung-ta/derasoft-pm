<?php
// Actual controller with an isolated service stub: no DB, permissions integration NOT tested.
$repoRoot=dirname(__DIR__).'/';
$fixtureRoot=$repoRoot.'.local/import-feedback-'.bin2hex(random_bytes(6)).'/';
mkdir($fixtureRoot.'classes/services',0700,true);
file_put_contents($fixtureRoot.'classes/services/pmimportservice.class.php', <<<'PHP'
<?php
class PmAccess { public function __construct(...$args) {} }
function requirePermission($permission) {}
class PmImportService {
 public static array $calls=[];public static bool $fail=false;public static bool $partial=false;
 public function __construct(...$args) {}
 public function history(): array {return [];}
 public function apply(int $id,bool $resume): array {return ['id'=>$id,'counts'=>['applied'=>2,'skipped_duplicate'=>1,'failed'=>self::$partial?1:0,'pending'=>self::$partial?1:0]];}
 public function provisionPassword(int $id,int $row,string $password): array {
  self::$calls[]=[$id,$row,$password];
  if(self::$fail)throw new DomainException('Tài khoản đã kích hoạt.');
  return ['id'=>$id,'results'=>[['source_row'=>$row,'user_id'=>115+$row]]];
 }
}
PHP
);
define('ROOT_PATH',$fixtureRoot);
require $repoRoot.'classes/http/request.class.php';
$db=null;$storeId=1;$userInfo=new class {function getId(){return 111;}};
$request=new Request();
$template=new class {public array $assigned=[];public function assign(array $values){$this->assigned=$values;}};
$_SESSION=['pm_csrf_token'=>'fixture-csrf'];$_SERVER['REQUEST_METHOD']='POST';
$password='FixtureOnly12345';
foreach([2,3] as $testRow) {
 $_POST=['csrf_token'=>'fixture-csrf','action'=>'provision_password','import_id'=>'1','source_row'=>(string)$testRow,'password'=>$password];
 require $repoRoot.'modules/admin/pmimports.module.php';
 if($template->assigned['error']!=='' || $template->assigned['passwordProvisionedRow']!==$testRow
  || !str_contains($template->assigned['notice'],'User ID '.(115+$testRow))
  || str_contains($template->assigned['notice'],$password))throw new RuntimeException('Missing or sensitive success feedback.');
}
if(count(PmImportService::$calls)!==2 || PmImportService::$calls[0][2]!==PmImportService::$calls[1][2])throw new RuntimeException('Two distinct rows did not reach service with same test password.');
PmImportService::$fail=true;
require $repoRoot.'modules/admin/pmimports.module.php';
if($template->assigned['notice']!=='' || $template->assigned['passwordProvisionedRow']!==null || $template->assigned['error']==='')throw new RuntimeException('Failure falsely confirmed.');
$count=count(PmImportService::$calls);$_POST['csrf_token']='wrong';
require $repoRoot.'modules/admin/pmimports.module.php';
if(count(PmImportService::$calls)!==$count || $template->assigned['notice']!=='')throw new RuntimeException('Invalid CSRF reached service.');
PmImportService::$fail=false;
foreach([false,true] as $partial){
 PmImportService::$partial=$partial;$_POST=['csrf_token'=>'fixture-csrf','action'=>'apply','import_id'=>'1'];
 require $repoRoot.'modules/admin/pmimports.module.php';
 if($template->assigned['importPanel']!=='history'||($partial?($template->assigned['error']===''||$template->assigned['notice']!==''):($template->assigned['notice']===''||$template->assigned['error']!=='')))throw new RuntimeException('Apply completion/partial feedback');
}
echo "PASS: actual controller feedback for two rows using same synthetic password, failure/CSRF/no secret echo; stub service, no DB connected. Real password persistence NOT RUN.\n";
