<?php
/** Read-only local preflight; reports counts and schema only, never email values. */
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Refusing non-local database.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$q=new PmDb((object)['connection'=>$connection]);
$schema=$q->fetchOne("SELECT t.engine AS engine,c.column_type AS column_type,c.is_nullable AS nullable,c.collation_name AS collation_name FROM information_schema.tables t JOIN information_schema.columns c ON c.table_schema=t.table_schema AND c.table_name=t.table_name WHERE t.table_schema=DATABASE() AND t.table_name='dc_users' AND c.column_name='email'");
$indexes=$q->fetchAll("SELECT index_name AS index_name,non_unique AS non_unique,seq_in_index AS seq_in_index,column_name AS column_name,sub_part AS sub_part FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='dc_users' ORDER BY index_name,seq_in_index");
$grouped=[];foreach($indexes as $i)$grouped[$i['index_name']][]=$i;$global=false;$tenant=false;
foreach($grouped as $parts){if((int)$parts[0]['non_unique']!==0||array_filter($parts,fn($p)=>$p['sub_part']!==null))continue;$columns=array_column($parts,'column_name');if($columns===['email'])$global=true;if(count($columns)===2&&in_array('store_id',$columns,true)&&in_array('email',$columns,true))$tenant=true;}
$counts=$q->fetchOne("SELECT COUNT(*) rows_total,SUM(email IS NULL) null_emails,SUM(email='') empty_emails FROM dc_users");
$counts['duplicate_email_groups_global']=(int)$q->fetchOne('SELECT COUNT(*) total FROM (SELECT email FROM dc_users WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*)>1) d')['total'];
$counts['duplicate_email_groups_tenant']=(int)$q->fetchOne('SELECT COUNT(*) total FROM (SELECT store_id,email FROM dc_users WHERE email IS NOT NULL GROUP BY store_id,email HAVING COUNT(*)>1) d')['total'];
echo json_encode(['schema'=>$schema,'indexes'=>$indexes,'global_email_unique'=>$global,'tenant_email_unique'=>$tenant,'counts'=>$counts],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
