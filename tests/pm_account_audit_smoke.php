<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/services/pmaccountauditservice.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$q=new PmDb((object)['connection'=>$c]);
$before=$q->fetchAll('SELECT * FROM dc_pm_audit_logs ORDER BY id');$users=$q->fetchAll('SELECT * FROM dc_users ORDER BY id');
try{
    $ddl=$c->query('SHOW CREATE TABLE dc_pm_audit_logs')->fetch_row()[1];$ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n/m','',$ddl);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$c->query(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));
    $s=new PmAccountAuditService((object)['connection'=>$c],1,42);$s->profileChanged([]);if($q->fetchAll('SELECT * FROM dc_pm_audit_logs'))throw new RuntimeException('No-op wrote audit.');
    $s->profileChanged(['tel','email','email']);$s->passwordChanged();
    foreach([['email=value@example.test'],['password'],['ip'],[42]] as $fields){try{$s->profileChanged($fields);throw new LogicException('Invalid metadata accepted.');}catch(InvalidArgumentException $expected){}}
    $rows=$q->fetchAll('SELECT * FROM dc_pm_audit_logs ORDER BY id');if(count($rows)!==2||(int)$rows[0]['actor_id']!==42||$rows[0]['action']!=='profile_update'||json_decode($rows[0]['new_values'],true)!==['changed_fields'=>['email','tel']]||$rows[0]['old_values']!==null||$rows[1]['new_values']!==null||$rows[1]['old_values']!==null||$rows[1]['action']!=='password_change')throw new RuntimeException('Minimal metadata mismatch.');
    echo "PASS: account profile/password exact minimal metadata, field allowlist, duplicate/sorted fields, no-op and rejected metadata.\n";
}finally{$c->close();$v=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$v->set_charset('utf8mb4');$vq=new PmDb((object)['connection'=>$v]);if($vq->fetchAll('SELECT * FROM dc_pm_audit_logs ORDER BY id')!==$before||$vq->fetchAll('SELECT * FROM dc_users ORDER BY id')!==$users)throw new RuntimeException('Persistent rows changed.');$v->close();}
echo "PASS: temporary exact-schema audit mirror destroyed; persistent audit/users unchanged.\n";
