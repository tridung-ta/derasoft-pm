<?php
/** Explicit local isolation only. No writes to the current application's database. */
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local source.');
$target='derasoft_pm_phase10_20261004';
$source=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$source->set_charset('utf8mb4');
$before=$source->query('SELECT COUNT(*) total FROM dc_users')->fetch_assoc()['total'];
// Guard a fresh, explicitly named schema. Never replace or clear an existing fixture.
$exists=$source->query("SELECT SCHEMA_NAME FROM information_schema.schemata WHERE SCHEMA_NAME='$target'")->num_rows;
if(!$exists)$source->query("CREATE DATABASE `$target` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$target);$c->set_charset('utf8mb4');$c->query('SET FOREIGN_KEY_CHECKS=0');
if($c->query('SHOW TABLES')->num_rows)throw new RuntimeException('Fixture DB is not empty; never reset or overwrite it.');
foreach($source->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetch_all() as [$table,$kind]){
    if(!preg_match('/^dc_[a-z0-9_]+$/D',$table))throw new RuntimeException('Unsafe table name');
    $ddl=$source->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];$c->query($ddl);
}
$c->query('SET FOREIGN_KEY_CHECKS=1');
$q=new PmDb((object)['connection'=>$c]);$sq=new PmDb((object)['connection'=>$source]);
$store=(int)$sq->fetchOne("SELECT store_id FROM dc_pm_roles WHERE code='ADMIN' AND status=1 LIMIT 1")['store_id'];
function fixtureInsert(mysqli $c,string $table,array $row): void {
    $columns=implode(',',array_map(fn($k)=>'`'.$k.'`',array_keys($row)));$s=$c->prepare("INSERT INTO `$table` ($columns) VALUES (".implode(',',array_fill(0,count($row),'?')).')');
    $values=array_values($row);$s->bind_param(str_repeat('s',count($values)),...$values);$s->execute();$s->close();
}
// Copy only schema/permission definitions, never real personnel/business data.
foreach(['dc_pm_roles','dc_pm_permissions','dc_pm_role_permissions'] as $table){
    foreach($sq->fetchAll("SELECT * FROM `$table` WHERE store_id=?",'i',[$store]) as $r){$r['store_id']=1;fixtureInsert($c,$table,$r);}
}
$storeRow=[];
foreach($c->query('SHOW COLUMNS FROM dc_estores')->fetch_all(MYSQLI_ASSOC) as $col){
    if($col['Default']!==null||$col['Null']==='YES'||str_contains($col['Extra'],'auto_increment'))continue;
    $storeRow[$col['Field']]=preg_match('/int|decimal|float|double/',$col['Type'])?0:(preg_match('/date|time/',$col['Type'])?date('Y-m-d H:i:s'):'');
}
$storeRow=array_merge($storeRow,['id'=>1,'subdomain'=>'estore','domain'=>'127.0.0.1','properties'=>serialize([])]);fixtureInsert($c,'dc_estores',$storeRow);
$ids=[];$password=bin2hex(random_bytes(18));
foreach(['ADMIN','PM','HR','EMPLOYEE'] as $i=>$role){
    $id=900000001+$i;$ids[$role]=$id;
    $q->execute('INSERT INTO dc_users(id,store_id,username,email,fullname,password_hash,type,status,properties,weekly_limit_hours,date_created) VALUES(?,1,?,?,?,?,1,1,?,40,NOW())','isssss',[$id,'phase10_'.strtolower($role),strtolower($role).'@example.test','Fixture '.$role,password_hash($password,PASSWORD_DEFAULT),serialize([])]);
    $r=$q->fetchOne('SELECT id FROM dc_pm_roles WHERE store_id=1 AND code=?','s',[$role]);$q->execute('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) VALUES(1,?,?,1)','ii',[$id,(int)$r['id']]);
}
$q->execute('INSERT INTO dc_users(id,store_id,username,email,fullname,password_hash,type,status,properties,weekly_limit_hours,date_created) VALUES(900000010,2,?,?,?, ?,1,1,?,40,NOW())','sssss',['phase10_foreign','foreign@example.test','Foreign fixture',password_hash($password,PASSWORD_DEFAULT),serialize([])]);
if($source->query('SELECT COUNT(*) total FROM dc_users')->fetch_assoc()['total']!==$before)throw new RuntimeException('Source users changed.');
$dir=ROOT_PATH.'.local/phase10';if(!is_dir($dir))mkdir($dir,0700,true);
file_put_contents($dir.'/fixture.json',json_encode(['database'=>$target,'ids'=>$ids,'password'=>$password,'source_user_count'=>$before],JSON_THROW_ON_ERROR));
echo "PASS: isolated schema prepared, four synthetic roles, MyISAM/UNIQUE preserved; current dc_users unchanged. Credentials only in ignored .local.\n";
