<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');$q=new PmDb((object)['connection'=>$connection]);
$count=$q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total'];$engine=$q->fetchOne("SELECT engine AS engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_users'")['engine'];
if(in_array('--apply',$argv,true)){
    if((int)$q->fetchOne('SELECT COUNT(*) total FROM (SELECT store_id,email FROM dc_users WHERE email IS NOT NULL GROUP BY store_id,email HAVING COUNT(*)>1) d')['total']!==0)throw new RuntimeException('Duplicate groups exist; no data cleanup authorized.');
    $connection->multi_query(file_get_contents(ROOT_PATH.'database/migrations/007_add_pm_users_email_unique.sql'));do{if($r=$connection->store_result())$r->free();if(!$connection->more_results())break;}while($connection->next_result());
}
$index=$q->fetchAll("SELECT column_name AS column_name,non_unique AS non_unique,sub_part AS sub_part FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='dc_users' AND index_name='uq_pm_users_store_email' ORDER BY seq_in_index");
if(array_column($index,'column_name')!==['store_id','email']||array_filter($index,fn($r)=>(int)$r['non_unique']!==0||$r['sub_part']!==null))throw new RuntimeException('Full tenant email UNIQUE missing.');
if($q->fetchOne('SELECT COUNT(*) total FROM dc_users')['total']!==$count||$q->fetchOne("SELECT engine AS engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_users'")['engine']!==$engine)throw new RuntimeException('Migration changed count or engine.');
echo "PASS: tenant email UNIQUE installed; user row count and engine unchanged.\n";
