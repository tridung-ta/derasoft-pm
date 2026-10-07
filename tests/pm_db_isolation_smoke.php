<?php
require dirname(__DIR__).'/classes/database/pmdb.class.php';
// Driver-response fixture: no DB writes, not native MariaDB verification.
class IsolationFixtureDb extends PmDb {
    public array $queries=[];
    public function __construct(private int $firstError=0,private bool $fallbackFails=false) {}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array {
        $this->queries[]=$sql;
        if(count($this->queries)===1 && $this->firstError)throw new mysqli_sql_exception('fixture',$this->firstError);
        if(count($this->queries)===2 && $this->fallbackFails)throw new mysqli_sql_exception('fixture',1193);
        return ['isolation_level'=>'REPEATABLE-READ'];
    }
}
$native=new IsolationFixtureDb();
if($native->transactionIsolation()!=='REPEATABLE-READ'||count($native->queries)!==1)throw new RuntimeException('Native lookup failed');
$old=new IsolationFixtureDb(1193);
if($old->transactionIsolation()!=='REPEATABLE-READ'||$old->queries!==['SELECT @@transaction_isolation isolation_level','SELECT @@tx_isolation isolation_level'])throw new RuntimeException('Old MariaDB fallback failed');
foreach([[1044,false],[1193,true]] as [$code,$fails]){
    $db=new IsolationFixtureDb($code,$fails);$caught=false;
    try{$db->transactionIsolation();}catch(mysqli_sql_exception $e){$caught=true;}
    if(!$caught||count($db->queries)!==($code===1193?2:1))throw new RuntimeException('Unexpected SQL error suppressed');
}
echo "PASS: native isolation, 1193-only legacy fallback, permission/fallback errors propagate. Driver fixture only.\n";
