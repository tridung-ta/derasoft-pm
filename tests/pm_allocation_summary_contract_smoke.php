<?php
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/services/pmallocationservice.class.php';
class AllocationSummaryDb extends PmDb {
 public array $calls=[];public array $transactions=[];
 public function __construct(){}
 public function fetchOne(string $sql,string $types='',array $params=[]): ?array {$this->calls[]=[$sql,$types,$params];return ['id'=>41];}
 public function fetchAll(string $sql,string $types='',array $params=[]): array {$this->calls[]=[$sql,$types,$params];return [];}
 public function beginTransaction(): void {$this->transactions[]='begin';}
 public function commit(): void {$this->transactions[]='commit';}
 public function rollBack(): void {$this->transactions[]='rollback';}
}
class AllocationSummaryAccess extends PmAccess {
 public function __construct(private string $fixtureRole){}
 public function hasRole(string $role): bool {return $role===$this->fixtureRole;}
 public function hasPermission(string $permission): bool {return $this->fixtureRole!=='NONE';}
}
function allocationSummaryFixture(string $role): array {
 $db=new AllocationSummaryDb();
 $dao=(new ReflectionClass(PmAllocations::class))->newInstanceWithoutConstructor();$dao->db=$db;
 (new ReflectionProperty(PmAllocations::class,'storeId'))->setValue($dao,7);
 $service=(new ReflectionClass(PmAllocationService::class))->newInstanceWithoutConstructor();
 foreach(['dao'=>$dao,'db'=>$db,'access'=>new AllocationSummaryAccess($role),'storeId'=>7,'actorId'=>41] as $name=>$value)(new ReflectionProperty(PmAllocationService::class,$name))->setValue($service,$value);
 return [$service,$db];
}
foreach(['ADMIN','PM','EMPLOYEE'] as $role) {
 [$service,$db]=allocationSummaryFixture($role);$result=$service->dashboardSummary('2026-10-09');
 if($db->transactions!==['begin','commit']||$result['projects']||$result['tasks']||$result['users']||$result['capacities'])throw new RuntimeException('Summary loads editor data or wrong transaction.');
 $rows=array_values(array_filter($db->calls,fn($c)=>str_contains($c[0],'SELECT a.*')));[$sql,$types,$params]=$rows[0];
 if(!str_contains($sql,'LIMIT 101')||$params[0]!==7||$params[1]!=='2026-10-05'||$params[2]!=='2026-10-11'||strlen($types)!==count($params))throw new RuntimeException('Summary limit/tenant/week contract.');
 if($role==='PM'&&(!str_contains($sql,'p.manager_id=?')||end($params)!==41))throw new RuntimeException('PM scope.');
 if($role==='EMPLOYEE'&&(!str_contains($sql,'a.user_id=?')||isset($result['task_estimate_warnings'])))throw new RuntimeException('Employee scope/whole-task leak.');
}
[$service,$db]=allocationSummaryFixture('ADMIN');$service->dashboard(['week'=>'2026-10-09']);
if(!array_filter($db->calls,fn($c)=>str_contains($c[0],'LIMIT 1001'))||!array_filter($db->calls,fn($c)=>str_contains($c[0],'SELECT t.id,t.name')))throw new RuntimeException('Original dashboard choices/limit changed.');
[$service,$db]=allocationSummaryFixture('NONE');
try{$service->dashboardSummary('2026-10-09');throw new RuntimeException('No-role accepted.');}catch(PmScopeDeniedException $e){}
if($db->transactions!==['begin','rollback']||array_filter($db->calls,fn($c)=>str_contains($c[0],'SELECT a.*')))throw new RuntimeException('No-role data query.');
echo "PASS: allocation summary real service with no-DB adapter: bounded101/100, skips editor choices, Admin/PM/Employee/no-role scope/transaction, original dashboard1001/choices preserved. SQL not executed.\n";
