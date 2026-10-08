<?php
// Local-only, token-gated GET fixture. Real polling entry/controller execute unchanged.
if(PHP_SAPI!=='cli-server'||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'||($_SERVER['REQUEST_METHOD']??'')!=='GET'
 ||parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)!=='/pm_ajax.php'||($_GET['op']??'')!=='pmallocations'
 ||!getenv('PM_D2_HTTP_TOKEN')||!hash_equals(getenv('PM_D2_HTTP_TOKEN'),$_SERVER['HTTP_X_PM_TEST_TOKEN']??'')) {http_response_code(404);exit;}
$role=$_SERVER['HTTP_X_PM_TEST_ROLE']??'';if(!in_array($role,['ADMIN','PM','EMPLOYEE','HR','NONE'],true)){http_response_code(400);exit;}
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true)){http_response_code(503);exit;}
$source=file_get_contents(ROOT_PATH.'classes/database/mysql.class.php');$source=preg_replace('/\bclass DB\s*\{/','class D2BaseDb {',$source,1,$count);if($count!==1)throw new RuntimeException('DB decorator mismatch.');eval('?>'.$source);
class DB extends D2BaseDb {
 public function initialize($persistent=0,$server='',$user='',$password='',$name='') {
  $result=parent::initialize($persistent,$server,$user,$password,$name);$c=$this->connection;
  $actor=(int)getenv('PM_D2_HTTP_ACTOR');$store=(int)getenv('PM_D2_HTTP_STORE');$role=$_SERVER['HTTP_X_PM_TEST_ROLE'];
  $stmt=$c->prepare('SELECT * FROM dc_users WHERE store_id=? AND id=?');$stmt->bind_param('ii',$store,$actor);$stmt->execute();$actorRow=$stmt->get_result()->fetch_assoc();if(!$actorRow)throw new RuntimeException('Missing actor.');
  $tables=['dc_users','dc_pm_roles','dc_pm_user_roles','dc_pm_projects','dc_pm_project_members','dc_pm_tasks','dc_pm_allocations','dc_pm_allocation_locks','dc_pm_capacity_overrides','dc_pm_timesheets'];
  $roleRows=$c->query('SELECT * FROM dc_pm_roles')->fetch_all(MYSQLI_ASSOC);
  foreach($tables as $table){$ddl=$c->query('SHOW CREATE TABLE '.$table)->fetch_row()[1];$ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n/m','',$ddl);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$c->query(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));}
  $insert=function(string $table,array $row)use($c){$cols=array_map(fn($k)=>chr(96).$k.chr(96),array_keys($row));$s=$c->prepare('INSERT INTO '.$table.' ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')');$values=array_values($row);$s->bind_param(str_repeat('s',count($values)),...$values);$s->execute();};
  $actorRow['fullname']='Fixture actor';$insert('dc_users',$actorRow);foreach($roleRows as $row){$row['status']=1;$insert('dc_pm_roles',$row);}
  if($role!=='NONE'){$stmt=$c->prepare('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,1 FROM dc_pm_roles WHERE store_id=? AND code=?');$stmt->bind_param('iiis',$store,$actor,$store,$role);$stmt->execute();if($stmt->affected_rows!==1)throw new RuntimeException('Missing fixture role.');}
  $stmt=$c->prepare("INSERT INTO dc_pm_projects(id,store_id,code,name,manager_id,created_by) VALUES(900000001,?,'D2-OWN','Own fixture project',?,?),(900000002,?,'D2-OTHER','Other PM secret project',999999999,?)");$stmt->bind_param('iiiii',$store,$actor,$actor,$store,$actor);$stmt->execute();
  $stmt=$c->prepare('INSERT INTO dc_pm_project_members(store_id,project_id,user_id) VALUES(?,900000001,?),(?,900000002,?)');$stmt->bind_param('iiii',$store,$actor,$store,$actor);$stmt->execute();
  $stmt=$c->prepare("INSERT INTO dc_pm_tasks(id,store_id,project_id,name,assignee_id,estimated_hours,created_by) VALUES(900000011,?,900000001,'Own overload task',?,4,?),(900000012,?,900000002,'Other PM secret task',?,1,?)");$stmt->bind_param('iiiiii',$store,$actor,$actor,$store,$actor,$actor);$stmt->execute();
  $stmt=$c->prepare("INSERT INTO dc_pm_allocations(store_id,user_id,project_id,task_id,work_date,hours,start_time,end_time,created_by,updated_by) VALUES(?,?,900000001,900000011,'2026-12-31',1,'09:00','10:00',?,?),(?,?,900000002,900000012,'2026-12-31',1,'11:00','12:00',?,?)");$stmt->bind_param('iiiiiiii',$store,$actor,$actor,$actor,$store,$actor,$actor,$actor);$stmt->execute();
  $stmt=$c->prepare("INSERT INTO dc_pm_timesheets(store_id,user_id,project_id,task_id,work_date,shift_label,hours,regular_hours,ot_hours,rate_snapshot,rate_source,currency,cost,ot_multiplier_snapshot) VALUES(?,?,900000001,900000011,'2025-01-01','fixture',3,1,2,0,'fallback','VND',0,1),(?,?,900000001,900000011,'2025-01-02','fixture',2,2,0,0,'fallback','VND',0,1),(?,?,900000002,900000012,'2025-01-01','fixture',10,8,2,0,'fallback','VND',0,1)");$other=$actor+1;$stmt->bind_param('iiiiii',$store,$actor,$store,$other,$store,$actor);$stmt->execute();
  return $result;
 }
}
$entry=file_get_contents(ROOT_PATH.'pm_ajax.php');$entry=str_replace("define('ROOT_PATH',__DIR__.'/');",'',$entry,$n);if($n!==1)throw new RuntimeException('Entry root mismatch.');$entry=str_replace("include_once(ROOT_PATH.'classes/database/mysql.class.php');",'',$entry,$n);if($n!==1)throw new RuntimeException('Entry DB mismatch.');eval('?>'.$entry);
