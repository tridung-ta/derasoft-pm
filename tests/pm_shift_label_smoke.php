<?php
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/services/pmtimesheetservice.class.php';
$labels=PmShiftLabelService::choices();$count=0;
foreach(array_keys($labels) as $label){if(PmShiftLabelService::validate($label)!==$label)throw new RuntimeException('Canonical rejected');$count++;}
foreach(['', 'adfawdf',str_repeat('x',31),"Ca sáng\n",[],123,'Ca OT'] as $invalid){
 try{PmShiftLabelService::validate($invalid);throw new RuntimeException('Invalid accepted');}catch(InvalidArgumentException $e){if(!str_contains($e->getMessage(),'chọn nhãn ca'))throw $e;$count++;}
}
foreach(['adfawdf','<script>x</script>','  Nhãn cũ  '] as $legacy){
 if(PmShiftLabelService::validate($legacy,$legacy)!==$legacy)throw new RuntimeException('Legacy changed');
 if(PmShiftLabelService::validate('Ca sáng',$legacy)!=='Ca sáng')throw new RuntimeException('Cannot change legacy');
 try{PmShiftLabelService::validate('another',$legacy);throw new RuntimeException('Arbitrary replacement accepted');}catch(InvalidArgumentException $e){}
 $count+=3;
}
try{PmShiftLabelService::validate('adfawdf','Ca sáng');throw new RuntimeException('Stale legacy reintroduced');}catch(InvalidArgumentException $e){$count++;}
class ShiftAccess extends PmAccess {
 public function __construct(){}
 public function hasPermission(string $p):bool{return true;}
 public function hasRole(string $r):bool{return $r==='ADMIN';}
}
class ShiftNoDb extends PmDb {
 public int $writes=0;
 public function __construct(){}
 public function execute(string $s,string $t='',array $p=[]):int{$this->writes++;throw new RuntimeException('Unexpected SQL');}
 public function beginTransaction():void{$this->writes++;throw new RuntimeException('Unexpected transaction');}
}
$reflect=new ReflectionClass(PmTimesheetService::class);$service=$reflect->newInstanceWithoutConstructor();$db=new ShiftNoDb();
foreach(['db'=>$db,'access'=>new ShiftAccess(),'storeId'=>1,'actorId'=>1] as $field=>$value)$reflect->getProperty($field)->setValue($service,$value);
foreach(['','adfawdf',str_repeat('x',31)] as $invalid){
 try{$service->save(['task_id'=>1,'work_date'=>'2026-10-13','hours'=>'1','shift_label'=>$invalid]);throw new RuntimeException('Service accepted invalid');}
 catch(InvalidArgumentException $e){if(!str_contains($e->getMessage(),'chọn nhãn ca'))throw $e;$count++;}
}
if($db->writes)throw new RuntimeException('Invalid label wrote DB');
$source=file_get_contents(ROOT_PATH.'classes/services/pmtimesheetservice.class.php');
if(substr_count($source,'PmShiftLabelService::validate($shiftInput')!==2)throw new RuntimeException('Current-row revalidation missing');
echo "PASS: $count label cases; real service invalid-create rejection before transaction; canonical/legacy preservation/change/stale/controls/type; no DB connection. Authenticated save/concurrency NOT RUN.\n";
