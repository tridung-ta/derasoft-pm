<?php
/** Local token-gated router; shadows every endpoint write target. */
if(PHP_SAPI!=='cli-server'||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'||!getenv('PM_TASK_HTTP_TOKEN')||!hash_equals(getenv('PM_TASK_HTTP_TOKEN'),$_SERVER['HTTP_X_PM_TEST_TOKEN']??'')||!in_array(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),['/pm_ajax.php','/modules/ajax/pmtaskstatus.module.php'],true)) {http_response_code(404);exit;}
if(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)==='/modules/ajax/pmtaskstatus.module.php'){include dirname(__DIR__).'/modules/ajax/pmtaskstatus.module.php';exit;}
$role=$_SERVER['HTTP_X_PM_TEST_ROLE']??'';$nonce=$_SERVER['HTTP_X_PM_TEST_NONCE']??'';
if(!in_array($role,['ADMIN','PM','HR','EMPLOYEE','NONE','INACTIVE'],true)||!preg_match('/^[a-f0-9]{24}$/D',$nonce)){http_response_code(400);exit;}
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true)){http_response_code(503);exit;}
$source=file_get_contents(ROOT_PATH.'classes/database/mysql.class.php');
$source=preg_replace('/\bclass DB\s*\{/', 'class TaskHttpBaseDb {',$source,1,$count);
if($count!==1)throw new RuntimeException('DB fixture mismatch.');eval('?>'.$source);
class DB extends TaskHttpBaseDb {
    public function initialize($persistent=0,$server='',$user='',$password='',$name=''){
        $result=parent::initialize($persistent,$server,$user,$password,$name);$c=$this->connection;
        $actor=(int)getenv('PM_TASK_HTTP_ACTOR');$store=(int)getenv('PM_TASK_HTTP_STORE');$role=$_SERVER['HTTP_X_PM_TEST_ROLE'];
        $stmt=$c->prepare('SELECT * FROM dc_users WHERE store_id=? AND id=?');$stmt->bind_param('ii',$store,$actor);$stmt->execute();$userRow=$stmt->get_result()->fetch_assoc();
        if(!$userRow)throw new RuntimeException('No fixture actor.');
        foreach(['dc_users','dc_pm_user_roles','dc_pm_projects','dc_pm_tasks','dc_pm_audit_logs'] as $table){
            $ddl=$c->query('SHOW CREATE TABLE '.$table)->fetch_row()[1];
            // MySQL temporary InnoDB tables cannot contain FK clauses.
            $ddl=preg_replace('/,\n\s*CONSTRAINT[^\n]+/','',$ddl);
            $c->query(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));
        }
        $userRow['status']=$role==='INACTIVE'?0:1;
        $columns=array_keys($userRow);$values=array_values($userRow);
        $stmt=$c->prepare('INSERT INTO dc_users (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');
        $stmt->bind_param(str_repeat('s',count($values)),...$values);$stmt->execute();
        if(!in_array($role,['NONE','INACTIVE'],true)){
            $stmt=$c->prepare('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,1 FROM dc_pm_roles WHERE store_id=? AND code=? AND status=1');$stmt->bind_param('iiis',$store,$actor,$store,$role);$stmt->execute();
            if($stmt->affected_rows!==1)throw new RuntimeException('Missing fixture role.');
        }
        $foreignStore=$store+1;
        $stmt=$c->prepare("INSERT INTO dc_pm_projects(id,store_id,code,name,manager_id,created_by,deleted_at) VALUES (900000001,?,'TASK-OWN','Own',?,?,NULL),(900000002,?,'TASK-OTHER','Other',999999999,?,NULL),(900000003,?,'TASK-TENANT','Tenant',?,?,NULL),(900000004,?,'TASK-HIDDEN','Hidden',?,?,NOW())");
        $stmt->bind_param('iiiiiiiiiii',$store,$actor,$actor,$store,$actor,$foreignStore,$actor,$actor,$store,$actor,$actor);$stmt->execute();
        $stmt=$c->prepare("INSERT INTO dc_pm_tasks(id,store_id,project_id,name,description,status,priority,estimated_hours,created_by,completed_at,deleted_at) VALUES (900000011,?,900000001,'Task <script>fixture</script>','Keep description','todo','high','4.50',?,NULL,NULL),(900000012,?,900000001,'Done task','Keep done','done','normal','2.00',?,'2026-01-01 09:00:00',NULL),(900000013,?,900000002,'Other task','','todo','normal','1',?,NULL,NULL),(900000014,?,900000001,'Hidden task','','todo','normal','1',?,NULL,NOW()),(900000015,?,900000003,'Tenant task','','todo','normal','1',?,NULL,NULL)");
        $stmt->bind_param('iiiiiiiiii',$store,$actor,$store,$actor,$store,$actor,$store,$actor,$foreignStore,$actor);$stmt->execute();
        $before=$c->query('SELECT * FROM dc_pm_tasks ORDER BY id')->fetch_all(MYSQLI_ASSOC);
        register_shutdown_function(function()use($c,$before){
            $state=['before'=>$before,'tasks'=>$c->query('SELECT * FROM dc_pm_tasks ORDER BY id')->fetch_all(MYSQLI_ASSOC),'audit'=>$c->query('SELECT * FROM dc_pm_audit_logs ORDER BY id')->fetch_all(MYSQLI_ASSOC)];
            file_put_contents(ROOT_PATH.'.local/task-status-state/'.$_SERVER['HTTP_X_PM_TEST_NONCE'].'.json',json_encode($state,JSON_THROW_ON_ERROR));
        });return $result;
    }
}
$entry=file_get_contents(ROOT_PATH.'pm_ajax.php');$entry=str_replace("define('ROOT_PATH',__DIR__.'/');",'',$entry,$count);
if($count!==1)throw new RuntimeException('Entry root mismatch.');
$entry=str_replace("include_once(ROOT_PATH.'classes/database/mysql.class.php');",'',$entry,$count);
if($count!==1)throw new RuntimeException('Entry DB mismatch.');eval('?>'.$entry);
