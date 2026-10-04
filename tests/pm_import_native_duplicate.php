<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/dao/pmimports.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
$c=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$c->set_charset('utf8mb4');$q=new PmDb((object)['connection'=>$c]);
(new PmImports((object)['connection'=>$c]))->requireEmailUnique();
$count=$q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total'];$engine=$q->fetchOne("SELECT engine AS engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_users'")['engine'];
$row=$q->fetchOne("SELECT id FROM dc_users WHERE email IS NOT NULL AND email<>'' LIMIT 1");if(!$row)throw new RuntimeException('No existing identity for duplicate rejection test.');
$columns=$q->fetchAll("SELECT column_name AS name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='dc_users' AND column_name<>'id' AND extra NOT LIKE '%GENERATED%' ORDER BY ordinal_position");
// Trusted schema identifiers only; no email/password/PII returned or printed.
$names=implode(',',array_map(fn($r)=>'`'.str_replace('`','``',$r['name']).'`',$columns));$rejected=false;
try{$q->execute('INSERT INTO dc_users('.$names.') SELECT '.$names.' FROM dc_users WHERE id=?','i',[(int)$row['id']]);}
catch(mysqli_sql_exception $e){if((int)$e->getCode()!==1062)throw $e;$rejected=true;}
if(!$rejected||$q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total']!==$count||$q->fetchOne("SELECT engine AS engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_users'")['engine']!==$engine)throw new RuntimeException('Native duplicate rejection failed.');
echo "PASS: actual dc_users rejected duplicate INSERT with native 1062; no personnel inserted and engine/count unchanged. Auto-increment gaps are possible.\n";
