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
$verify=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$verify->set_charset('utf8mb4');$vq=new PmDb((object)['connection'=>$verify]);
try{checkPersonnel($vq->fetchAll('SELECT * FROM dc_users ORDER BY id')===$persistentUsers,'Persistent users changed');checkPersonnel($vq->fetchOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_users'")['ENGINE']==='MyISAM','Core engine changed');}finally{$verify->close();}
echo "PASS: personnel DAO create/hash/search/page/contact edit/duplicate/lock/unlock/soft-delete and tenant boundaries; exact-schema temporary MyISAM mirror, persistent users/engine unchanged.\n";
