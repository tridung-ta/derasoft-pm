<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/dao/pmusers.class.php';require ROOT_PATH.'classes/services/pmuiservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
class RoleCountConnection extends mysqli {
    public int $roleQueries=0;
    public function prepare(string $query): mysqli_stmt|false {if(str_starts_with($query,'SELECT role_id,is_primary FROM dc_pm_user_roles')||str_starts_with($query,'SELECT user_id,role_id,is_primary FROM dc_pm_user_roles'))$this->roleQueries++;return parent::prepare($query);}
}
function batchCheck(bool $ok,string $m): void {if(!$ok)throw new RuntimeException($m);}
$c=new RoleCountConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$db=(object)['connection'=>$c];$q=new PmDb($db);$dao=new PmUsers($db);
$r=$q->fetchAll("SELECT store_id,id FROM dc_pm_roles WHERE store_id=(SELECT MIN(store_id) FROM dc_pm_roles) AND status=1 ORDER BY id LIMIT 3");batchCheck(count($r)===3,'Three active fixture roles required.');$store=(int)$r[0]['store_id'];$roles=array_map(fn($row)=>(int)$row['id'],$r);$base=900000000+random_int(1,1000000);$ids=range($base,$base+19);
$c->begin_transaction();try{
    foreach($ids as $i=>$id){$count=$i%2===0?3:2;$chosen=$i%$count;foreach(array_reverse(array_slice($roles,0,$count)) as $roleId)$q->execute('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) VALUES(?,?,?,?)','iiii',[$store,$id,$roleId,$roleId===$roles[$chosen]?1:0]);}
    $q->execute('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) VALUES(?,?,?,1)','iii',[$store+99999,$base+25,$roles[0]]);
    $c->roleQueries=0;$old=[];foreach($ids as $id){$old[$id]=$dao->roles($store,$id);usort($old[$id],fn($a,$b)=>$a['role_id']<=>$b['role_id']);}batchCheck($c->roleQueries===20,'Baseline N queries not measured.');
    $c->roleQueries=0;$batch=$dao->rolesForUsers($store,$ids);batchCheck($c->roleQueries===1&&$batch===$old,'Batch roles differ per user from old queries.');
    $oldState=PmUiService::roleState($old);$newState=PmUiService::roleState($batch);batchCheck($newState===$oldState,'Primary role differs from old N queries.');
    foreach($ids as $i=>$id){$count=$i%2===0?3:2;batchCheck($newState['primary'][$id]===$roles[$i%$count],'Wrong exact primary user '.$id);}
    $c->roleQueries=0;$dao->rolesForUsers($store,[$ids[0]]);batchCheck($c->roleQueries===1,'One-user batch query count.');
    $c->roleQueries=0;batchCheck($dao->rolesForUsers($store,[])===[]&&$c->roleQueries===0,'Empty batch queried.');batchCheck($dao->rolesForUsers($store,[$base+25])===[$base+25=>[]],'Cross-tenant roles leaked.');
    $q->execute('UPDATE dc_pm_user_roles SET is_primary=1 WHERE store_id=? AND user_id=?','ii',[$store,$base]);$state=PmUiService::roleState($dao->rolesForUsers($store,[$base]));batchCheck(!isset($state['primary'][$base])&&$state['inconsistent']===[$base],'Multiple primary silently inferred.');
    echo "PASS: exact role rows/primary for 20 users with 2-3 roles, varied order; old 20 queries -> batch 1, 1 user -> 1, empty -> 0; tenant and multiple-primary fail closed. PM role fixtures rolled back, no core users created.\n";
}finally{$c->rollback();}
