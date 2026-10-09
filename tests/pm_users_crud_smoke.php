<?php
// Actual DAO writes against a connection-local mirror, never real personnel.
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/mysql.class.php';
require ROOT_PATH.'classes/dao/pmusers.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server']??'', ['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused');
foreach(['DEBUG','QUERY_ERROR','QUERY_DEBUG'] as $key)if(!defined($key))define($key,false);
$database=new DB();$q=new PmDb($database);
$persistentUsers=$q->fetchAll('SELECT * FROM dc_users ORDER BY id');
$schema=$q->fetchOne('SHOW CREATE TABLE dc_users')['Create Table'];
$q->execute(preg_replace('/^CREATE TABLE `dc_users`/','CREATE TEMPORARY TABLE `dc_users`',$schema));
$q->execute('ALTER TABLE dc_users AUTO_INCREMENT=1000000000');
$users=new PmUsers($database);
$data=['username'=>'crud-local','password'=>'Temporary-password-only','email'=>'crud@example.test',
 'fullname'=>'CRUD Sample','tel'=>'0900000000','department_id'=>null,'weekly_limit_hours'=>'40.00'];
$id=$users->create(1,$data);
function checkPersonnel($condition,string $message){if(!$condition)throw new RuntimeException($message);}
checkPersonnel($id>=1000000000,'Mirror ID allocation');
$row=$users->get(1,$id);
checkPersonnel($row&&$row['fullname']==='CRUD Sample'&&(int)$row['status']===1,'Create not persisted');
$credential=$q->fetchOne('SELECT password_hash FROM dc_users WHERE id=?','i',[$id]);
checkPersonnel(password_verify($data['password'],$credential['password_hash']),'New password not hashed');
checkPersonnel($users->usernameExists(1,$data['username'])&&$users->emailExists(1,$data['email']),'Duplicate checks failed');
checkPersonnel(!$users->emailExists(2,$data['email'])&&!$users->get(2,$id),'Tenant read boundary');
checkPersonnel($users->count(1,['q'=>'CRUD'])===1&&count($users->list(1,['q'=>'CRUD'],1,20))===1,'Search/count/page');
$update=['fullname'=>'CRUD Updated','email'=>'updated@example.test','tel'=>'0911111111','department_id'=>null,'weekly_limit_hours'=>'32.00'];
checkPersonnel(!$users->updateSafe(2,$id,$update),'Cross-tenant update reported success');
checkPersonnel($users->get(1,$id)['fullname']==='CRUD Sample','Cross-tenant update changed data');
checkPersonnel($users->updateSafe(1,$id,$update),'Update failed');
checkPersonnel($users->updateSafe(1,$id,$update),'Unchanged valid form rejected');
checkPersonnel(!$users->updateSafe(1,999999999,$update),'Missing target reported success');
$row=$users->get(1,$id);checkPersonnel($row['tel']==='0911111111'&&$row['fullname']==='CRUD Updated','Contact edit not persisted');
checkPersonnel($users->count(1,['q'=>'updated@example.test'])===1&&!$users->emailExists(1,$data['email']),'Email/search not updated');
checkPersonnel($users->setStatus(1,$id,0)&&(int)$users->get(1,$id)['status']===0,'Lock failed');
checkPersonnel($users->setStatus(1,$id,1)&&(int)$users->get(1,$id)['status']===1,'Unlock failed');
checkPersonnel(!$users->setStatus(2,$id,2),'Cross-tenant status update accepted');
checkPersonnel($users->setStatus(1,$id,2)&&$users->get(1,$id)===null&&$users->count(1)===0,'Soft delete still listed');
checkPersonnel(!$users->updateSafe(1,$id,$update),'Hidden target reported success');
checkPersonnel((int)$q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total']===1,'Soft delete removed data');
$referenceSnapshots=[];
foreach(['dc_pm_departments','dc_pm_roles','dc_pm_user_roles'] as $table){$referenceSnapshots[$table]=$q->fetchAll('SELECT * FROM '.$table);$ddl=$q->fetchOne('SHOW CREATE TABLE '.$table)['Create Table'];$ddl=preg_replace('/,\n\s*CONSTRAINT[^\n]+/','',$ddl);$q->execute(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));}
foreach([[900000121,'FILTER-A'],[900000122,'FILTER-B']] as [$department,$code])$q->execute('INSERT INTO dc_pm_departments(id,store_id,code,name,status) VALUES(?,1,?,?,1)','iss',[$department,$code,$code]);
foreach([[900000111,'FILTER-A'],[900000112,'FILTER-B']] as [$role,$code])$q->execute('INSERT INTO dc_pm_roles(id,store_id,code,name,status) VALUES(?,1,?,?,1)','iss',[$role,$code,$code]);
$base=['password'=>'Temporary-password-only','tel'=>'','weekly_limit_hours'=>'40.00'];$created=[];
foreach([['a','Alpha Filter',900000121],['b','Beta Filter',900000121],['c','Gamma Filter',900000122]] as [$suffix,$fullname,$department])$created[$suffix]=$users->create(1,$base+['username'=>'filter-'.$suffix,'email'=>'filter-'.$suffix.'@example.test','fullname'=>$fullname,'department_id'=>$department]);
$hidden=$users->create(1,$base+['username'=>'filter-hidden','email'=>'filter-hidden@example.test','fullname'=>'Hidden Filter','department_id'=>900000121]);$users->setStatus(1,$hidden,2);
$foreign=$users->create(2,$base+['username'=>'filter-foreign','email'=>'filter-foreign@example.test','fullname'=>'Foreign Filter','department_id'=>null]);
foreach([[1,$created['a'],900000111],[1,$created['b'],900000112],[1,$created['c'],900000111],[1,$created['c'],900000112],[1,$hidden,900000111],[2,$created['a'],900000112]] as [$tenant,$user,$role])$q->execute('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) VALUES(?,?,?,0)','iii',[$tenant,$user,$role]);
$filterCases=[
 [['q'=>'Alpha Filter'],[$created['a']]],
 [['q'=>'filter-b@example.test'],[$created['b']]],
 [['q'=>'filter-c'],[$created['c']]],
 [['department_id'=>900000121],[$created['a'],$created['b']]],
 [['department_id'=>900000122],[$created['c']]],
 [['role_id'=>900000111],[$created['a'],$created['c']]],
 [['role_id'=>900000112],[$created['b'],$created['c']]],
 [['department_id'=>900000121,'role_id'=>900000111],[$created['a']]],
 [['q'=>'Filter','department_id'=>900000122,'role_id'=>900000112],[$created['c']]],
 [['q'=>'Filter'],array_values($created)],
 [['q'=>'Hidden Filter'],[]],
 [['q'=>'Foreign Filter'],[]]
];
foreach($filterCases as $index=>[$filters,$expected]){$actual=array_map('intval',array_column($users->list(1,$filters,1,20),'id'));checkPersonnel($actual===$expected&&$users->count(1,$filters)===count($expected),'3.10 filter/list/count case '.($index+1));echo 'PASS 3.10 filter case '.($index+1).': list/count exact IDs; hidden/foreign rows excluded.'.PHP_EOL;}
foreach([1=>$created['a'],2=>$created['b'],3=>$created['c']] as $page=>$expected)checkPersonnel(array_map('intval',array_column($users->list(1,['q'=>'Filter'],$page,1),'id'))===[$expected],'3.10 paginated ID mismatch');checkPersonnel($users->list(1,['q'=>'Filter'],4,1)===[],'3.10 final page not empty');
echo 'PASS 3.10: name/email/username/department/role/combined filters and 4 pagination pages; no duplicate IDs.'.PHP_EOL;
$verify=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$verify->set_charset('utf8mb4');$vq=new PmDb((object)['connection'=>$verify]);
try{checkPersonnel($vq->fetchAll('SELECT * FROM dc_users ORDER BY id')===$persistentUsers,'Persistent users changed');foreach($referenceSnapshots as $table=>$beforeRows)checkPersonnel($vq->fetchAll('SELECT * FROM '.$table)===$beforeRows,'Persistent reference table changed: '.$table);checkPersonnel($vq->fetchOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_users'")['ENGINE']==='MyISAM','Core engine changed');}finally{$verify->close();}
echo "PASS: personnel DAO create/hash/search/page/contact edit/duplicate/lock/unlock/soft-delete and tenant boundaries; exact-schema temporary MyISAM mirror, persistent users/engine unchanged.\n";
