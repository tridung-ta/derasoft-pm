<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';require ROOT_PATH.'classes/database/pmdb.class.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true))throw new RuntimeException('Non-local refused.');
$connection=new mysqli($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$connection->set_charset('utf8mb4');
if(in_array('--apply',$argv,true)){$connection->multi_query(file_get_contents(ROOT_PATH.'database/migrations/008_create_pm_import_results.sql'));do{if($r=$connection->store_result())$r->free();if(!$connection->more_results())break;}while($connection->next_result());}
$q=new PmDb((object)['connection'=>$connection]);$table=$q->fetchOne("SELECT engine AS engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='dc_pm_import_results'");
$columns=$q->fetchAll("SELECT column_name AS name FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='dc_pm_import_results' AND index_name='uq_pm_import_result_row' AND non_unique=0 ORDER BY seq_in_index");
if(($table['engine']??'')!=='InnoDB'||array_column($columns,'name')!==['store_id','import_id','source_row'])throw new RuntimeException('Invalid import journal schema.');
echo "PASS: additive InnoDB import journal, tenant/batch/row UNIQUE.\n";
