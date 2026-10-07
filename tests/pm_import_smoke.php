<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/template/smarty.class.php';require ROOT_PATH.'classes/services/pmimportservice.class.php';require ROOT_PATH.'classes/PhpSpreadSheet/PhpOffice/autoload.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
class ImportFixtureConnection extends mysqli {
    public int $failAfter=0;
    public function prepare(string $query): mysqli_stmt|false {if($this->failAfter>0&&str_contains($query,'INSERT INTO dc_pm_import_staging')&&--$this->failAfter===0)throw new RuntimeException('Injected staging failure.');return parent::prepare($query);}
    public function beginFixture(): bool{return parent::begin_transaction();}public function endFixture(): bool{return parent::rollback();}
    public function begin_transaction(int $flags=0,?string $name=null): bool{return $this->query('SAVEPOINT import_step');}public function commit(int $flags=0,?string $name=null): bool{return $this->query('RELEASE SAVEPOINT import_step');}public function rollback(int $flags=0,?string $name=null): bool{$this->query('ROLLBACK TO SAVEPOINT import_step');return $this->query('RELEASE SAVEPOINT import_step');}
}
$connection=new ImportFixtureConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$db=(object)['connection'=>$connection];$q=new PmDb($db);$a=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");$store=(int)$a['store_id'];$actor=(int)$a['id'];$service=new PmImportService($db,$store,$actor);
function importCheck(bool $ok,string $m): void{if(!$ok)throw new RuntimeException($m);}
function importDenied(callable $fn): void{try{$fn();}catch(InvalidArgumentException|DomainException $e){return;}throw new RuntimeException('Expected import denial.');}
function importWorkbook(string $path,array $rows,array $headers=PmImportService::HEADERS): void {
    $book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();$sheet=$book->getActiveSheet();foreach([$headers,...$rows] as $i=>$row)foreach($row as $j=>$v)$sheet->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j+1).($i+1),(string)$v,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);$book->disconnectWorksheets();
}
$path=tempnam(sys_get_temp_dir(),'pm-import-test-');$code='imp_'.bin2hex(random_bytes(5));$row=[$code,'Người thử <script>',$code.'@example.test','','40','EMPLOYEE'];$connection->beginFixture();
try{
    $users=$q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total'];$logs=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_logs')['total'];
    file_put_contents($path,$service->template());importCheck(!$service->preview($path,'sample.xlsx')['valid'],'Empty template considered valid.');
    importWorkbook($path,[$row]);$p=$service->preview($path,'sample.xlsx');importCheck($p['valid']&&!$p['applied']&&count($p['rows'])===1,'Valid preview failed.');
    $result=$service->stage($path,'sample.xlsx');importCheck(isset($result['import_id'])&&!$result['applied'],'Stage did not identify un-applied data.');
    importCheck((int)$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_staging WHERE store_id=? AND import_id=?','ii',[$store,$result['import_id']])['total']===1,'Staged row absent.');
    importCheck($q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total']===$users,'Staging wrote dc_users.');
    importDenied(fn()=>(new PmImportService($db,$store+99999,$actor))->preview($path,'sample.xlsx'));
    $other=$q->fetchOne("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) LIMIT 1",'i',[$store]);importDenied(fn()=>(new PmImportService($db,$store,(int)$other['id']))->history());
    importWorkbook($path,[$row,$row]);$bad=$service->stage($path,'sample.xlsx');importCheck(!$bad['valid']&&!isset($bad['import_id']),'Duplicate file staged.');
    foreach([['bad user','User','bademail','','0','ADMIN'],[$code,'User',$code.'@example.test','NO_SUCH_DEPT','40','EMPLOYEE'],[$code,'User',str_repeat('a',39).'@example.test','','40','EMPLOYEE']] as $badRow){importWorkbook($path,[$badRow]);importCheck(!$service->preview($path,'sample.xlsx')['valid'],'Invalid row accepted.');}
    $existing=$q->fetchOne('SELECT username,email FROM dc_users WHERE store_id=? AND id=?','ii',[$store,$actor]);importWorkbook($path,[[$existing['username'],'User',$existing['email'],'','40','EMPLOYEE']]);importCheck(!$service->preview($path,'sample.xlsx')['valid'],'Existing tenant identity accepted.');
    importWorkbook($path,[$row],['username','fullname','email','department_code','weekly_limit_hours','password']);importDenied(fn()=>$service->preview($path,'sample.xlsx'));
    importWorkbook($path,[$row]);$book=\PhpOffice\PhpSpreadsheet\IOFactory::load($path);$book->getActiveSheet()->setCellValue('B2','=1+1');(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);$book->disconnectWorksheets();importCheck(!$service->preview($path,'sample.xlsx')['valid'],'Formula accepted.');
    foreach(['xl/vbaProject.bin'=>'macro','xl/externalLinks/externalLink1.xml'=>'external','custom.xml'=>'<!DOCTYPE x><x/>','custom.rels'=>'<Relationship TargetMode="External"/>','large.xml'=>str_repeat('x',5000000)] as $name=>$contents){importWorkbook($path,[$row]);$z=new ZipArchive();$z->open($path);$z->addFromString($name,$contents);$z->close();importDenied(fn()=>$service->preview($path,'sample.xlsx'));}
    file_put_contents($path,str_repeat('x',2097153));importDenied(fn()=>$service->preview($path,'sample.xlsx'));file_put_contents($path,'not zip');importDenied(fn()=>$service->preview($path,'sample.xlsx'));
    importWorkbook($path,array_fill(0,501,$row));importDenied(fn()=>$service->preview($path,'sample.xlsx'));
    $row2=$row;$row2[0].='_2';$row2[2]='second_'.$row2[2];importWorkbook($path,[$row,$row2]);$before=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_logs')['total'];$stagingBefore=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_staging')['total'];$connection->failAfter=2;
    try{$service->stage($path,'sample.xlsx');throw new LogicException('Fault did not execute.');}catch(RuntimeException $e){importCheck($e->getMessage()==='Injected staging failure.','Unexpected staging error.');}
    importCheck($q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_logs')['total']===$before&&$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_import_staging')['total']===$stagingBefore,'Partial staging survived failure.');
    importCheck($q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total']===$users,'Import touched users.');
    echo "PASS: import preview/staging, Admin/tenant gates, headers/duplicates/identities/row errors, macro/external/DTD/formula/ZIP/size/row limits, injected atomic rollback; dc_users unchanged. Fixtures rolled back.\n";
}finally{if(is_file($path))unlink($path);$connection->endFixture();}
