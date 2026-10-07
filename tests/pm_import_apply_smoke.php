<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/services/pmimportservice.class.php';require ROOT_PATH.'classes/dao/pmauditlogs.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
// Isolated temporary transactional mirror: never create test personnel in MyISAM dc_users.
class ApplyFixtureConnection extends mysqli {
    public bool $failInsert=false;public bool $missingUnique=false;public bool $failRole=false;
    public function prepare(string $sql): mysqli_stmt|false {
        if($this->failRole&&str_starts_with($sql,'INSERT IGNORE INTO dc_pm_user_roles')){$this->failRole=false;throw new RuntimeException('Injected role journal failure.');}
        if($this->missingUnique&&str_contains($sql,'information_schema.statistics'))$sql.=' LIMIT 0';
        if(str_starts_with($sql,'SELECT r.source_row,r.status'))$sql=str_replace('dc_users','pm_import_test_users',$sql);
        if(str_starts_with($sql,'INSERT INTO dc_users')||str_starts_with($sql,'SELECT id,properties FROM dc_users')||str_starts_with($sql,'SELECT id FROM dc_users WHERE store_id=? AND username=? AND id<>?')||str_starts_with($sql,'UPDATE dc_users SET password=NULL')||str_starts_with($sql,'SELECT u.id,u.username,u.properties,u.status,r.provenance_token')){
            if($this->failInsert&&str_starts_with($sql,'INSERT')){$this->failInsert=false;throw new mysqli_sql_exception('Injected insert failure',1205);}
            $sql=str_replace('dc_users','pm_import_test_users',$sql);
        }
        return parent::prepare($sql);
    }
    public function beginFixture(): bool{return parent::begin_transaction();}public function endFixture(): bool{return parent::rollback();}
    public function begin_transaction(int $flags=0,?string $name=null): bool{return $this->query('SAVEPOINT apply_step');}public function commit(int $flags=0,?string $name=null): bool{return $this->query('RELEASE SAVEPOINT apply_step');}public function rollback(int $flags=0,?string $name=null): bool{$this->query('ROLLBACK TO SAVEPOINT apply_step');return $this->query('RELEASE SAVEPOINT apply_step');}
}
class InterruptedImportService extends PmImportService {
    public bool $interrupt=true;
    protected function afterUserInsert(): void {if($this->interrupt){$this->interrupt=false;throw new RuntimeException('Injected interruption after insert.');}}
}
function applyCheck(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function applyDenied(callable $fn): void {try{$fn();}catch(InvalidArgumentException|DomainException|OverflowException $e){return;}throw new RuntimeException('Expected denial.');}
$c=new ApplyFixtureConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$db=(object)['connection'=>$c];$q=new PmDb($db);
$c->query('CREATE TEMPORARY TABLE pm_import_test_users (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,store_id BIGINT UNSIGNED NOT NULL,username VARCHAR(50),password VARCHAR(255) NULL,password_hash VARCHAR(255),email VARCHAR(50),fullname VARCHAR(100),tel VARCHAR(50),department_id BIGINT NULL,weekly_limit_hours DECIMAL(5,2),type INT,date_created DATETIME,status INT,properties TEXT,UNIQUE KEY email_unique(store_id,email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=900000000');
$admin=$q->fetchOne("SELECT u.id,u.store_id FROM dc_users u JOIN dc_pm_user_roles ur ON ur.store_id=u.store_id AND ur.user_id=u.id JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.code='ADMIN' LIMIT 1");$store=(int)$admin['store_id'];$actor=(int)$admin['id'];$s=new PmImportService($db,$store,$actor);$dao=new PmImports($db);$count=$q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total'];
function applyBatch(PmDb $q,int $store,int $actor,array $rows): int {
    $q->execute("INSERT INTO dc_pm_import_logs(store_id,actor_id,status,row_count) VALUES(?,?,'staged',?)",'iii',[$store,$actor,count($rows)]);$id=(int)$q->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
    foreach($rows as $i=>$row)$q->execute('INSERT INTO dc_pm_import_staging(store_id,import_id,source_row,payload) VALUES(?,?,?,?)','iiis',[$store,$id,$i+2,json_encode($row,JSON_THROW_ON_ERROR)]);return $id;
}
$r=['username'=>'apply_'.bin2hex(random_bytes(4)),'fullname'=>'Nhân viên thử','email'=>'apply_'.bin2hex(random_bytes(4)).'@example.test','department_code'=>'','department_id'=>null,'weekly_limit_hours'=>'40.25','role_code'=>'EMPLOYEE'];
$c->beginFixture();
try{
    $id=applyBatch($q,$store,$actor,[$r]);$result=$s->apply($id);applyCheck($result['status']==='completed'&&$result['counts']['applied']===1,'Apply failed.');
    $userId=(int)$result['results'][0]['user_id'];$u=$q->fetchOne('SELECT status,password,password_hash,weekly_limit_hours FROM pm_import_test_users WHERE id=?','i',[$userId]);applyCheck((int)$u['status']===0&&$u['password']===null&&password_get_info($u['password_hash'])['algo']!==null&&$u['weekly_limit_hours']==='40.25','Unsafe account or decimal.');
    applyCheck($s->apply($id)['results'][0]['attempt_count']===1,'Completed replay re-applied.');
    $password='Test only '.bin2hex(random_bytes(12));$s->provisionPassword($id,2,$password);$hash=$q->fetchOne('SELECT password_hash,status FROM pm_import_test_users WHERE id=?','i',[$userId]);applyCheck(password_verify($password,$hash['password_hash'])&&(int)$hash['status']===0,'Credential provisioning failed.');
    applyDenied(fn()=>$s->provisionPassword($id,3,$password));applyDenied(fn()=>$s->provisionPassword($id,2,'short'));
    $audit=$q->fetchOne("SELECT new_values FROM dc_pm_audit_logs WHERE store_id=? AND entity_type='import' AND entity_id=? AND action='provision_password' ORDER BY id DESC LIMIT 1",'ii',[$store,$id]);applyCheck(!str_contains($audit['new_values'],$password)&&!str_contains($audit['new_values'],$hash['password_hash']),'Credential leaked in audit.');
    applyCheck((new PmAuditLogs($db,$store,$actor))->list(['entity_type'=>'import','action'=>'apply'])['total']>=1&&(new PmAuditLogs($db,$store,$actor))->list(['entity_type'=>'import','action'=>'provision_password'])['total']>=1,'Apply audit filters failed.');
    $id2=applyBatch($q,$store,$actor,[$r]);$before=$q->fetchOne('SELECT COUNT(*) total FROM dc_pm_user_roles')['total'];applyCheck($s->apply($id2)['counts']['skipped_duplicate']===1,'Duplicate not skipped.');applyCheck($q->fetchOne('SELECT COUNT(*) total FROM dc_pm_user_roles')['total']===$before,'Duplicate granted roles.');
    applyDenied(fn()=>$s->provisionPassword($id2,2,$password));
    $r2=$r;$r2['username'].='_2';$r2['email']='second_'.$r['email'];$id3=applyBatch($q,$store,$actor,[$r2]);$crash=new InterruptedImportService($db,$store,$actor);
    try{$crash->apply($id3);throw new LogicException('No interruption.');}catch(RuntimeException $e){applyCheck($e->getMessage()==='Injected interruption after insert.','Wrong interruption.');}
    applyCheck($s->detail($id3)['status']==='in_progress','Lost progress flag.');applyDenied(fn()=>$s->apply($id3));$res=$s->apply($id3,true);applyCheck($res['counts']['applied']===1&&$res['results'][0]['reason_code']==='recovered_inactive'&&(int)$res['results'][0]['attempt_count']===2,'Recovery failed.');
    $r3=$r2;$r3['username'].='_3';$r3['email']='third_'.$r['email'];$id4=applyBatch($q,$store,$actor,[$r3]);$c->failInsert=true;applyCheck($s->apply($id4)['counts']['failed']===1,'Failure not journaled.');applyCheck($s->apply($id4,true)['counts']['applied']===1,'Failed retry failed.');
    $r4=$r3;$r4['username'].='_4';$r4['email']='fourth_'.$r['email'];$id7=applyBatch($q,$store,$actor,[$r4]);$c->failRole=true;
    try{$s->apply($id7);throw new LogicException('No role failure.');}catch(RuntimeException $e){applyCheck($e->getMessage()==='Injected role journal failure.','Wrong role failure.');}
    applyCheck($s->detail($id7)['counts']['pending']===1&&$s->apply($id7,true)['results'][0]['reason_code']==='recovered_inactive','Role transaction crash recovery failed.');
    $r5=$r4;$r5['username'].='_5';$r5['email']='fifth_'.$r['email'];$id8=applyBatch($q,$store,$actor,[$r5,$r]);$c->failInsert=true;$partial=$s->apply($id8);applyCheck($partial['counts']['failed']===1&&$partial['counts']['skipped_duplicate']===1,'Row failure stopped independent rows.');
    $q->execute("UPDATE dc_pm_import_staging SET payload=JSON_SET(payload,'$.fullname','Changed') WHERE store_id=? AND import_id=? AND source_row=2",'ii',[$store,$id8]);
    try{$s->apply($id8,true);throw new LogicException('Changed payload accepted.');}catch(RuntimeException $e){applyCheck($e->getMessage()==='Immutable import payload changed.','Wrong immutability failure.');}
    $bad=$r3;$bad['role_code']='ADMIN';$id5=applyBatch($q,$store,$actor,[$bad]);applyCheck($s->apply($id5)['results'][0]['reason_code']==='invalid_row_or_reference','Elevated role accepted.');
    $collision=$r;$collision['email']='collision_'.$r['email'];$id6=applyBatch($q,$store,$actor,[$collision]);applyCheck($s->apply($id6)['results'][0]['reason_code']==='username_collision_inactive','Username collision not protected.');
    applyDenied(fn()=>(new PmImportService($db,$store+99999,$actor))->apply($id));applyDenied(fn()=>$s->detail(999999999));
    applyDenied(fn()=>(new PmImportService($db,$store+99999,$actor))->provisionPassword($id,2,$password));
    $q->execute('UPDATE pm_import_test_users SET status=1 WHERE id=?','i',[$userId]);applyDenied(fn()=>$s->provisionPassword($id,2,$password));
    $other=$q->fetchOne("SELECT u.id FROM dc_users u WHERE u.store_id=? AND u.status=1 AND NOT EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.code='ADMIN' AND r.status=1) LIMIT 1",'i',[$store]);applyDenied(fn()=>(new PmImportService($db,$store,(int)$other['id']))->apply($id));
    $c->missingUnique=true;try{$s->apply($id);throw new LogicException('Missing UNIQUE accepted.');}catch(RuntimeException $e){applyCheck($e->getMessage()==='Apply requires full DB email UNIQUE.','Wrong constraint guard.');}$c->missingUnique=false;
    $second=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$second->set_charset('utf8mb4');$otherDao=new PmImports((object)['connection'=>$second]);$name=$dao->lockName($store,$id);applyCheck($otherDao->acquire($name),'Second worker lock failed.');applyDenied(fn()=>$s->apply($id));$second->close();applyCheck($dao->acquire($name),'Connection close did not release lock.');$dao->release($name);
    applyCheck($q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total']===$count,'Core users changed.');
    echo "PASS: Apply mirror INSERT/1062, inactive accounts, DECIMAL, provenance crash recovery, retry, role/tenant/IDOR/UNIQUE gates, username collision, two real connections and abandoned lock release; all fixtures rolled back, core dc_users unchanged.\n";
}finally{$c->endFixture();$c->close();}
