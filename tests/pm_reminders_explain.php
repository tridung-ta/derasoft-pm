<?php
// Operator-only read-only EXPLAIN. No fixture creation, DDL or business writes.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$appRoot=getenv('DERASOFT_PM_APP_ROOT')?:dirname(__DIR__);$resolved=realpath($appRoot);
if(!$resolved||!is_file($resolved.'/classes/dao/pmnotifications.class.php')){fwrite(STDERR,"Invalid application root.\n");exit(2);}
define('ROOT_PATH',$resolved.'/');
if(count($argv)!==2||!preg_match('/^--store-id=([1-9][0-9]{0,17})$/D',$argv[1],$m)||filter_var($m[1],FILTER_VALIDATE_INT)===false){fwrite(STDERR,"Use --store-id=N; read-only schema/EXPLAIN only.\n");exit(2);}
require ROOT_PATH.'classes/dao/pmnotifications.class.php';
class ReminderExplainRecorder extends PmDb {
    public array $queries=[];
    public function __construct(){}
    public function fetchAll(string $sql,string $types='',array $params=[]): array{$this->queries[]=[$sql,$types,$params];return [];}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array{$this->queries[]=[$sql,$types,$params];return null;}
}
$db=null;
try{
    require ROOT_PATH.'includes/constant.inc.php';require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/mysql.class.php';
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new DB();$q=new PmDb($db);$store=(int)$m[1];(new PmNotifications($q,$store))->preflight();
    $record=new ReminderExplainRecorder();$dao=new PmNotifications($record,$store);$date=(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    $actor=(int)($q->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND status=1 ORDER BY id LIMIT 1','i',[$store])['id']??0);
    if(!$actor)throw new RuntimeException('No active recipient for representative plan.');
    $dao->candidates('timesheet',$date);$dao->candidates('task',$date);$dao->list($actor,$date);$dao->markRead($actor,0,$date);
    foreach($record->queries as $i=>[$sql,$types,$params]){$plan=$q->fetchAll('EXPLAIN '.$sql,$types,$params);echo json_encode(['query'=>$i+1,'plan'=>$plan],JSON_THROW_ON_ERROR)."\n";}
    echo 'PASS: '.count($record->queries)." reminder SELECT shapes EXPLAIN; read-only, no index added. Small/empty results are not a production load benchmark.\n";$code=0;
}catch(Throwable $e){fwrite(STDERR,"Reminder schema/EXPLAIN failed. No writes requested.\n");$code=1;}
finally{if($db&&$db->connection instanceof mysqli)$db->close();}
exit($code);
