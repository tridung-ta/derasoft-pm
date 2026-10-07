<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';
require ROOT_PATH.'classes/database/mysql.class.php';
require ROOT_PATH.'classes/dao/pmusers.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server']??'', ['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused');
foreach(['DEBUG','QUERY_ERROR','QUERY_DEBUG'] as $key)if(!defined($key))define($key,false);
class IdentityFixtureConnection extends mysqli {
    public function prepare(string $sql): mysqli_stmt|false {
        // Test-only second alias avoids reopening one MySQL temporary table.
        if(str_contains($sql,'FROM dc_users a JOIN dc_users b'))$sql=str_replace('JOIN dc_users b','JOIN pm_identity_peer_users b',$sql);
        return parent::prepare($sql);
    }
}
$mirrorConnection=new IdentityFixtureConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$mirrorConnection->set_charset('utf8mb4');
$database=(object)['connection'=>$mirrorConnection];$q=new PmDb($database);$real=$q->fetchAll('SELECT * FROM dc_users ORDER BY id');
$schema=$q->fetchOne('SHOW CREATE TABLE dc_users')['Create Table'];
$q->execute(preg_replace('/^CREATE TABLE `dc_users`/','CREATE TEMPORARY TABLE `dc_users`',$schema));
$q->execute('ALTER TABLE dc_users AUTO_INCREMENT=1000000000');
$q->execute(preg_replace('/^CREATE TABLE `dc_users`/','CREATE TEMPORARY TABLE `pm_identity_peer_users`',$schema));
$users=new PmUsers($database);$identity=new PmUserIdentityService($database);$cases=0;
function identityCheck(bool $ok,string $message): void {global $cases;if(!$ok)throw new RuntimeException($message);$cases++;}
function identityDenied(callable $fn,string $class): void {global $cases;try{$fn();}catch(Throwable $e){if($e instanceof $class){$cases++;return;}throw $e;}throw new RuntimeException('Expected rejection '.$class);}
$base=['username'=>'alpha','fullname'=>'Valid user','email'=>'alpha@example.test','tel'=>'0901234567','password'=>'password123','department_id'=>null,'weekly_limit_hours'=>40];
$second=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$second->set_charset('utf8mb4');$other=new PmUserIdentityService((object)['connection'=>$second]);$held=null;
try{
    foreach([['username',30],['fullname',50],['email',50],['tel',15]] as [$field,$limit]){
        $v=['username'=>'valid','fullname'=>'Valid','email'=>'valid@example.test','tel'=>''];
        $v[$field]=$field==='email'?str_repeat('a',$limit-13).'@example.test':str_repeat('a',$limit);
        PmUserInputService::validateFields(...array_values($v));$cases++;
        $v[$field]=$field==='email'?str_repeat('a',$limit-12).'@example.test':str_repeat('a',$limit+1);
        identityDenied(fn()=>PmUserInputService::validateFields(...array_values($v)),PmUserInputException::class);
    }
    PmUserInputService::validateFields('valid',str_repeat('á',50),'a@example.test','');$cases++;
    identityDenied(fn()=>PmUserInputService::validateFields('valid',str_repeat('á',51),'a@example.test',''),PmUserInputException::class);
    foreach(['username','fullname','email','tel'] as $field){$v=['username'=>'valid','fullname'=>'Valid','email'=>'a@example.test','tel'=>''];$v[$field].="\x01";identityDenied(fn()=>PmUserInputService::validateFields(...array_values($v)),PmUserInputException::class);}
    identityDenied(fn()=>PmUserInputService::validateFields('valid',"\xff",'a@example.test',''),PmUserInputException::class);
    $a=$users->create(1,$base);
    identityCheck(!$identity->conflicts(1,$base['username'],$base['email'],$a),'Same user rejected');
    $same=$users->create(1,array_replace($base,['username'=>'same@example.test','email'=>'same@example.test']));
    identityCheck(!$identity->conflicts(1,'same@example.test','same@example.test',$same),'Own username/email equality rejected');
    identityDenied(fn()=>$users->create(1,array_replace($base,['username'=>'ALPHA','email'=>'different@example.test'])),DomainException::class);
    identityDenied(fn()=>$users->create(1,array_replace($base,['username'=>'alpha@example.test','email'=>'different@example.test'])),DomainException::class);
    $legacy=$users->create(1,array_replace($base,['username'=>'legacy@example.test','email'=>'legacy-address@example.test']));
    identityDenied(fn()=>$users->create(1,array_replace($base,['username'=>'different','email'=>'LEGACY@example.test'])),DomainException::class);
    identityDenied(fn()=>$users->updateSafe(1,$a,array_replace($base,['email'=>'legacy@example.test'])),DomainException::class);
    identityCheck($users->updateSafe(1,$a,$base),'Self edit rejected');
    $users->setStatus(1,$legacy,0);
    identityCheck($identity->conflicts(1,'different','legacy@example.test'),'Locked identity reusable');
    $users->setStatus(1,$legacy,2);
    identityCheck($identity->conflicts(1,'different','legacy@example.test'),'Hidden identity reusable');
    identityCheck(!$identity->conflicts(2,'alpha','different@example.test'),'Cross-tenant collision');
    identityCheck($identity->equivalent('café','CAFE'),'DB accent/case equality');
    identityCheck($identity->equivalent('alpha ','alpha'),'DB padding equality');
    identityCheck(!$identity->equivalent('alpha','beta'),'Different identity equality');
    $accent=$users->create(1,array_replace($base,['username'=>'café','email'=>'accent@example.test']));
    identityDenied(fn()=>$users->create(1,array_replace($base,['username'=>'CAFE','email'=>'different@example.test'])),DomainException::class);
    identityDenied(fn()=>$users->create(1,array_replace($base,['username'=>str_repeat('a',31)])),PmUserInputException::class);
    identityDenied(fn()=>$users->updateSafe(1,$a,array_replace($base,['fullname'=>str_repeat('a',51)])),PmUserInputException::class);
    identityCheck($identity->lockName(1)!==$identity->lockName(2),'Tenant lock names identical');
    $held=$other->acquire(1,0);
    identityDenied(fn()=>$identity->acquire(1,0),OverflowException::class);
    identityDenied(fn()=>$users->create(1,array_replace($base,['username'=>'blocked','email'=>'blocked@example.test'])),OverflowException::class);
    identityDenied(fn()=>$users->updateSafe(1,$a,$base),OverflowException::class);
    $b=$users->create(2,array_replace($base,['email'=>'tenant-two@example.test']));
    identityCheck($users->get(2,$b)!==null,'Tenant two blocked by tenant one');
    $other->release($held);$held=null;
    $l=$identity->acquire(1,0);$identity->release($l);$cases++;
    // Exception during an identity write must release the lock.
    identityDenied(fn()=>$users->create(1,$base),DomainException::class);
    $l=$other->acquire(1,0);$other->release($l);$cases++;
    $q->execute('UPDATE dc_users SET username=? WHERE id=?','si',['legacy-address@example.test',$a]);
    $q->execute('UPDATE dc_users SET username=? WHERE id=?','si',['alpha@example.test',$legacy]);
    $q->execute('INSERT INTO pm_identity_peer_users SELECT * FROM dc_users');
    $pairs=$identity->scanPairs();
    identityCheck(count($pairs)===1&&(int)$pairs[0]['user_a']===$a&&(int)$pairs[0]['user_b']===$legacy,'Read-only pair scan scope/count');
    identityCheck((int)$pairs[0]['username_email']===1&&(int)$pairs[0]['email_username']===1,'Read-only scan missed either cross direction or hidden user');
}finally{
    if($held!==null)$other->release($held);
    $verify=new PmDb((object)['connection'=>$second]);
    identityCheck($verify->fetchAll('SELECT * FROM dc_users ORDER BY id')===$real,'Persistent users changed');
    identityCheck($verify->fetchOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_users'")['ENGINE']==='MyISAM','Persistent engine changed');
    $second->close();
    $mirrorConnection->close();
}
echo "PASS: $cases schema/collation/cross-identity/tenant/locked-hidden/self-edit cases and two real connection lock checks; exact-schema mirror, persistent users/engine unchanged.\n";
