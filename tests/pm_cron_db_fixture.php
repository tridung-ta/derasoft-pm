<?php
// Shared local fixture support. Never writes dc_users; every connection mirrors it.
require_once ROOT_PATH.'classes/database/pmdb.class.php';
function pmCronLocalConnection(): mysqli {
    static $connectionConfig=null;
    if($connectionConfig===null){require ROOT_PATH.'includes/config.inc.php';$connectionConfig=$config;}
    $config=$connectionConfig;
    if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server']??'',['localhost','127.0.0.1'],true))throw new RuntimeException('Local database guard refused.');
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');return $c;
}
function pmCronFingerprint(mysqli $c): array {
    $out=[];$tables=$c->query('SHOW TABLES')->fetch_all(MYSQLI_NUM);
    foreach($tables as [$table])if($table==='dc_users'||str_starts_with($table,'dc_pm_')){
        if(!preg_match('/^[a-z0-9_]+$/D',$table))throw new RuntimeException('Unsafe table.');
        $keys=$c->query("SHOW KEYS FROM `".$table."` WHERE Key_name='PRIMARY'")->fetch_all(MYSQLI_ASSOC);
        usort($keys,fn($a,$b)=>(int)$a['Seq_in_index']<=>(int)$b['Seq_in_index']);
        $order=array_column($keys,'Column_name');if(!$order)$order=array_column($c->query('SHOW COLUMNS FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC),'Field');
        foreach($order as $column)if(!preg_match('/^[a-z0-9_]+$/iD',$column))throw new RuntimeException('Unsafe column.');
        $rows=$c->query('SELECT * FROM `'.$table.'` ORDER BY `'.implode('`,`',$order).'`')->fetch_all(MYSQLI_ASSOC);
        $out[$table]=['rows'=>count($rows),'sha256'=>hash('sha256',json_encode($rows,JSON_THROW_ON_ERROR))];
    }ksort($out);return $out;
}
function pmCronInsert(mysqli $c,string $table,array $values): int {
    if(!preg_match('/^dc_(?:pm_[a-z_]+|users)$/D',$table))throw new RuntimeException('Invalid fixture table.');
    $sql='INSERT INTO '.$table.' (`'.implode('`,`',array_keys($values)).'`) VALUES('.implode(',',array_fill(0,count($values),'?')).')';
    $s=$c->prepare($sql);$params=array_values($values);$s->bind_param(str_repeat('s',count($params)),...$params);$s->execute();$id=(int)$c->insert_id;$s->close();return $id;
}
function pmCronMirrorUsers(mysqli $c,array $fixture): void {
    $columns=$c->query('SHOW COLUMNS FROM dc_users')->fetch_all(MYSQLI_ASSOC);
    $ddl=$c->query('SHOW CREATE TABLE dc_users')->fetch_row()[1];
    $c->query(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));
    foreach($fixture['users'] as $user){
        $row=[];foreach($columns as $col){$field=$col['Field'];$type=$col['Type'];
            if($col['Default']!==null&&!str_contains(strtolower((string)$col['Default']),'current_timestamp'))$row[$field]=$col['Default'];
            elseif($col['Null']==='YES')$row[$field]=null;
            elseif(preg_match('/int|decimal|float|double/',$type))$row[$field]=0;
            elseif(str_starts_with($type,'datetime')||str_starts_with($type,'timestamp'))$row[$field]='2026-01-01 00:00:00';
            elseif(str_starts_with($type,'date'))$row[$field]='2026-01-01';
            else $row[$field]='';
        }
        $row=array_replace($row,['id'=>$user['id'],'store_id'=>$user['store'],'username'=>'cron-'.$user['id'],'fullname'=>'Cron fixture','email'=>'cron-'.$user['id'].'@example.test','password'=>'','password_hash'=>password_hash('fixture-unused',PASSWORD_DEFAULT),'status'=>$user['status']]);
        pmCronInsert($c,'dc_users',$row);
    }
}
function pmCronLoadFixture(): array {
    $file=ROOT_PATH.'.local/cron-db-fixture.json';$f=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
    $token=getenv('PM_CRON_TEST_TOKEN');if(!$token||!hash_equals($f['token'],$token))throw new RuntimeException('Fixture token guard.');return $f;
}
