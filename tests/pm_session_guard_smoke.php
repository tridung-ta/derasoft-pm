<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/security/pmsessionguard.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
class GuardFixtureConnection extends mysqli {
    public function prepare(string $sql): mysqli_stmt|false {return parent::prepare(str_replace('dc_users','pm_guard_users',$sql));}
}
$c=new GuardFixtureConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$db=(object)['connection'=>$c];
$c->query('CREATE TEMPORARY TABLE pm_guard_users(id INT,store_id INT,status INT) ENGINE=InnoDB');$c->query('INSERT INTO pm_guard_users VALUES(1,1,1),(2,1,0),(3,1,2),(4,2,1)');
foreach([[1,1,1,true],[1,2,1,false],[1,3,1,false],[1,4,1,false],[1,1,2,false],[1,0,1,false],[1,999,1,false]] as [$store,$actor,$sessionStore,$expected])if(PmSessionGuard::valid($db,$store,$actor,$sessionStore)!==$expected)throw new RuntimeException('Session guard fixture failed.');
$c->query('UPDATE pm_guard_users SET status=0 WHERE id=1');if(PmSessionGuard::valid($db,1,1,1))throw new RuntimeException('Revoked actor remained valid.');
$c->close();echo "PASS: session guard active/inactive/deleted/missing/cross-tenant and revoked actor using temporary mirror; core users untouched.\n";
