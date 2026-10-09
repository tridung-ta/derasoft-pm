<?php
/** Local-only collecting tenant matrix. Real entry points/controllers are unchanged. */
$path=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
$role=$_SERVER['HTTP_X_PM_TEST_ROLE']??'';$nonce=$_SERVER['HTTP_X_PM_TEST_NONCE']??'';
$adminOps=['login','pm','pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports'];
$ajaxOps=['pmcosts','pmallocations','pmtaskstatus'];
$actions=[
 'pmprojects'=>['project_save','project_delete','member_save','task_save','task_status','task_delete'],
 'pmusers'=>['save','status','roles','rate','rate_save','rate_deactivate','department_save','department_status','department_delete','department_restore'],
 'pmtimesheets'=>['save','delete','settings'], 'pmallocations'=>['save','delete','capacity','capacity_delete'],
 'pmreports'=>['export'], 'pmimports'=>['detail','apply','resume','provision_password','template','preview','stage']
];
if(PHP_SAPI!=='cli-server'||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'||!getenv('PM_ROLE_HTTP_TOKEN')
 ||!hash_equals(getenv('PM_ROLE_HTTP_TOKEN'),$_SERVER['HTTP_X_PM_TEST_TOKEN']??'')
 ||!in_array($role,['ADMIN','PM','NONE'],true)||!preg_match('/^[a-f0-9]{24}$/D',$nonce)
 ||!in_array($_SERVER['REQUEST_METHOD']??'',['GET','POST'],true)
 ||!(($path==='/admin.php'&&in_array($_GET['op']??'',$adminOps,true))||($path==='/pm_ajax.php'&&in_array($_GET['op']??'',$ajaxOps,true)))) {http_response_code(404);exit;}
if($_SERVER['REQUEST_METHOD']==='POST'&&($_GET['op']??'')!=='login'&&!(($path==='/admin.php'&&($_POST['op']??'')===($_GET['op']??'')&&in_array($_POST['action']??'',$actions[$_GET['op']]??[],true))||($path==='/pm_ajax.php'&&($_GET['op']??'')==='pmtaskstatus'))) {http_response_code(400);exit;}
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'includes/config.inc.php';
if(($config['db_name']??'')!=='derasoft_pm_local'||!in_array($config['db_server'],['localhost','127.0.0.1'],true)) {http_response_code(503);exit;}
$source=file_get_contents(ROOT_PATH.'classes/database/mysql.class.php');
$source=preg_replace('/\bclass DB\s*\{/','class TenantMatrixBaseDb {',$source,1,$count);
if($count!==1)throw new RuntimeException('Fixture DB declaration mismatch.');eval('?>'.$source);
class TenantMatrixConnection extends mysqli {
 public function prepare(string $query): mysqli_stmt|false {try{return parent::prepare($query);}catch(mysqli_sql_exception $e){$GLOBALS['tenant_sql_error_code']=$e->getCode();if($e->getCode()===1137&&preg_match("/'([a-z_]+)'/",$e->getMessage(),$m))$GLOBALS['tenant_sql_error_alias']=$m[1];throw $e;}}
}
class DB extends TenantMatrixBaseDb {
 public function initialize($persistent=0,$server='',$user='',$password='',$name='') {
  $result=parent::initialize($persistent,$server,$user,$password,$name);global $config;$this->connection->close();$this->connection=new TenantMatrixConnection($config['db_server'],$config['db_user'],$config['db_pwd'],$config['db_name']);$this->connection->set_charset('utf8mb4');$c=$this->connection;
  $actor=(int)getenv('PM_ROLE_HTTP_ACTOR');$store=(int)getenv('PM_ROLE_HTTP_STORE');$other=$store+1;
  $s=$c->prepare('SELECT * FROM dc_users WHERE store_id=? AND id=?');$s->bind_param('ii',$store,$actor);$s->execute();$actorRow=$s->get_result()->fetch_assoc();if(!$actorRow)throw new RuntimeException('Missing actor.');
  $roleRows=$c->query('SELECT * FROM dc_pm_roles ORDER BY id')->fetch_all(MYSQLI_ASSOC);
  $tables=['dc_users','dc_pm_roles','dc_pm_user_roles','dc_pm_departments','dc_pm_hourly_rates','dc_pm_projects','dc_pm_project_members','dc_pm_tasks','dc_pm_timesheets','dc_pm_timesheet_days','dc_pm_system_settings','dc_pm_audit_logs','dc_trackings','dc_pm_allocations','dc_pm_allocation_locks','dc_pm_capacity_overrides','dc_pm_import_logs','dc_pm_import_staging','dc_pm_import_results'];
  $authFixture=($_SERVER['HTTP_X_PM_TEST_AUTH']??'')==='1';if($authFixture)$tables[]='dc_login_times';
  $costRead=$_SERVER['HTTP_X_PM_TEST_ROLE']==='ADMIN'&&(($_GET['op']??'')==='pmcosts'||(($_GET['op']??'')==='pmreports'&&($_POST['mode']??$_GET['mode']??'')==='costs'));
  // These read-only handlers never write rates. Native temporary tables cannot be
  // reopened by the three aliases in the existing cost estimate query.
  if($costRead){if($_SERVER['HTTP_X_PM_TEST_ROLE']!=='ADMIN')throw new RuntimeException('Cost read fixture requires real local Admin.');$s=$c->prepare("SELECT ur.user_id FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=? AND ur.user_id=? AND r.code='ADMIN' AND r.status=1");$s->bind_param('ii',$store,$actor);$s->execute();if(!$s->get_result()->fetch_assoc())throw new RuntimeException('No real Admin for read-only cost control.');$tables=array_values(array_diff($tables,['dc_pm_hourly_rates','dc_pm_user_roles']));}
  foreach($tables as $table){$ddl=$c->query('SHOW CREATE TABLE '.$table)->fetch_row()[1];$ddl=preg_replace('/,\n\s*CONSTRAINT[^\n]+/','',$ddl);$c->query(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));}
  $insert=function(string $table,array $row)use($c){$cols=array_map(fn($k)=>chr(96).$k.chr(96),array_keys($row));$s=$c->prepare('INSERT INTO '.$table.' ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')');$values=array_values($row);$s->bind_param(str_repeat('s',count($values)),...$values);$s->execute();};
  // Exact-schema users mirror; credentials never serialized into observations.
  $actorRow['fullname']='Fixture actor';if($authFixture){$actorRow['username']='matrix-auth';$actorRow['email']='matrix-auth@example.test';$actorRow['password_hash']=password_hash('Fixture password 123',PASSWORD_DEFAULT);}$actorRow['department_id']=null;$insert('dc_users',$actorRow);
  $insert('dc_users',array_replace($actorRow,['id'=>900000041,'username'=>'matrix-member','email'=>'matrix-member@example.test','fullname'=>'Fixture member','code'=>'MATRIX41','status'=>1]));
  $insert('dc_users',array_replace($actorRow,['id'=>900000092,'store_id'=>$other,'username'=>'foreign-identity-sentinel','email'=>'foreign-sentinel@example.test','fullname'=>'Foreign tenant user sentinel','code'=>'MATRIX92','status'=>1]));
  foreach($roleRows as $row)$insert('dc_pm_roles',$row);
  $insert('dc_pm_roles',['id'=>900000064,'store_id'=>$store,'code'=>'MATRIX-INACTIVE','name'=>'Fixture inactive role','status'=>0]);
  $insert('dc_pm_roles',['id'=>900000094,'store_id'=>$other,'code'=>'MATRIX-FOREIGN','name'=>'Foreign tenant role sentinel','status'=>1]);
  if(!$costRead&&$_SERVER['HTTP_X_PM_TEST_ROLE']!=='NONE'){$s=$c->prepare('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) SELECT ?,?,id,1 FROM dc_pm_roles WHERE store_id=? AND code=? AND status=1');$role=$_SERVER['HTTP_X_PM_TEST_ROLE'];$s->bind_param('iiis',$store,$actor,$store,$role);$s->execute();if($s->affected_rows!==1)throw new RuntimeException('Missing fixture role.');}
  $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
  $insert('dc_users',array_replace($actorRow,['id'=>900000042,'username'=>'matrix-inactive','email'=>'matrix-inactive@example.test','fullname'=>'Fixture inactive','code'=>'MATRIX42','status'=>0]));
  $insert('dc_pm_projects',['id'=>900000002,'store_id'=>$store,'code'=>'MATRIX-RESTRICTED','name'=>'Local restricted project','manager_id'=>900000041,'created_by'=>$actor]);
  $insert('dc_pm_project_members',['store_id'=>$store,'project_id'=>900000002,'user_id'=>900000041,'status'=>1]);
  $insert('dc_pm_tasks',['id'=>900000012,'store_id'=>$store,'project_id'=>900000002,'name'=>'Local restricted task','assignee_id'=>900000041,'estimated_hours'=>'4.00','status'=>'todo','created_by'=>$actor]);
  foreach([[900000001,$store,'MATRIX-OWN','Fixture own project',$actor],[900000090,$other,'MATRIX-FOREIGN','Foreign tenant project sentinel',900000092]] as [$id,$tenant,$code,$title,$manager])$insert('dc_pm_projects',['id'=>$id,'store_id'=>$tenant,'code'=>$code,'name'=>$title,'manager_id'=>$manager,'created_by'=>$actor]);
  foreach([[$store,900000001,$actor],[$store,900000001,900000041],[$other,900000090,900000092]] as [$tenant,$project,$user])$insert('dc_pm_project_members',['store_id'=>$tenant,'project_id'=>$project,'user_id'=>$user,'status'=>1]);
  foreach([[900000011,$store,900000001,'Fixture own task',$actor],[900000091,$other,900000090,'Foreign tenant task sentinel',900000092]] as [$id,$tenant,$project,$title,$user])$insert('dc_pm_tasks',['id'=>$id,'store_id'=>$tenant,'project_id'=>$project,'name'=>$title,'assignee_id'=>$user,'estimated_hours'=>'4.00','status'=>'todo','created_by'=>$actor]);
  foreach([[900000061,$store,'MATRIX-OWN','Fixture own department',null],[900000093,$other,'MATRIX-FOREIGN','Foreign tenant department sentinel',null],[900000089,$other,'MATRIX-HIDDEN','Foreign hidden department sentinel','2026-01-01 00:00:00']] as [$id,$tenant,$code,$title,$deleted])$insert('dc_pm_departments',['id'=>$id,'store_id'=>$tenant,'code'=>$code,'name'=>$title,'status'=>1,'deleted_at'=>$deleted]);
  if(!$costRead)foreach([[900000071,$store,900000041],[900000095,$other,900000092]] as [$id,$tenant,$user])$insert('dc_pm_hourly_rates',['id'=>$id,'store_id'=>$tenant,'user_id'=>$user,'rate'=>'100.00','currency'=>'VND','effective_from'=>'2000-01-01','effective_to'=>'2000-01-02','status'=>1,'created_by'=>$actor]);
  foreach([[$store,$actor],[$other,900000092]] as [$tenant,$user])$insert('dc_pm_timesheet_days',['store_id'=>$tenant,'user_id'=>$user,'work_date'=>$today,'standard_hours'=>'8.00','ot_multiplier'=>'1.50']);
  foreach([[900000021,$store,$actor,900000001,900000011],[900000096,$other,900000092,900000090,900000091]] as [$id,$tenant,$user,$project,$task])$insert('dc_pm_timesheets',['id'=>$id,'store_id'=>$tenant,'user_id'=>$user,'project_id'=>$project,'task_id'=>$task,'work_date'=>$today,'shift_label'=>'Fixture shift','hours'=>'1.00','regular_hours'=>'1.00','ot_hours'=>'0.00','rate_snapshot'=>'100.00','rate_source'=>'user','currency'=>'VND','ot_multiplier_snapshot'=>'1.50','cost'=>'100.00']);
  foreach([[900000031,$store,$actor,900000001,900000011],[900000097,$other,900000092,900000090,900000091]] as [$id,$tenant,$user,$project,$task])$insert('dc_pm_allocations',['id'=>$id,'store_id'=>$tenant,'user_id'=>$user,'project_id'=>$project,'task_id'=>$task,'work_date'=>$today,'hours'=>'1.00','start_time'=>'09:00','end_time'=>'10:00','created_by'=>$actor,'updated_by'=>$actor]);
  foreach([[900000081,$store,900000041],[900000098,$other,900000092]] as [$id,$tenant,$user])$insert('dc_pm_capacity_overrides',['id'=>$id,'store_id'=>$tenant,'user_id'=>$user,'daily_limit_hours'=>'8.00','weekly_limit_hours'=>'40.00','effective_from'=>'2000-01-01','created_by'=>$actor,'updated_by'=>$actor]);
  foreach([[900000085,$store],[900000099,$other]] as [$id,$tenant]){$insert('dc_pm_import_logs',['id'=>$id,'store_id'=>$tenant,'actor_id'=>$actor,'status'=>'staged','row_count'=>1]);$insert('dc_pm_import_staging',['store_id'=>$tenant,'import_id'=>$id,'source_row'=>2,'payload'=>json_encode(['username'=>'foreign-import-sentinel','fullname'=>'Foreign import sentinel','email'=>'foreign-import@example.test','department_code'=>'','department_id'=>null,'role_code'=>'EMPLOYEE','weekly_limit_hours'=>'40.00'])]);}
  if(($_GET['op']??'')==='pmaudit'){foreach([[$store,$actor,900000011,'Own audit fixture'],[$other,900000092,900000091,'Foreign audit sentinel']] as [$tenant,$user,$task,$title])$insert('dc_pm_audit_logs',['store_id'=>$tenant,'actor_id'=>$user,'entity_type'=>'task','entity_id'=>$task,'action'=>'update','new_values'=>json_encode(['name'=>$title])]);}
  $fingerprint=function()use($c,$tables){$rows=[];foreach($tables as $t){$rows[$t]=$c->query('SELECT * FROM '.$t)->fetch_all(MYSQLI_ASSOC);usort($rows[$t],fn($a,$b)=>strcmp(serialize($a),serialize($b)));}return hash('sha256',serialize($rows));};
  $before=$fingerprint();$serviceTrace=null;
if(($_GET['op']??'')==='pmprojects'&&($_POST['action']??'')==='task_save'&&(int)($_POST['project_id']??0)===900000001&&(int)($_POST['task_id']??0)===900000091){
include_once(ROOT_PATH.'classes/services/pmprojectservice.class.php');
try{(new PmProjectService($this,$store,$actor))->saveTask(900000001,['name'=>'Attempt update','description'=>'fixture','assignee_id'=>'','status'=>'todo','priority'=>'normal','estimated_hours'=>'1','start_date'=>'','due_date'=>''],900000091);$serviceTrace=['exception'=>null];}
catch(Throwable $e){$serviceTrace=['exception'=>get_class($e),'source'=>str_replace(chr(92),'/',substr($e->getFile(),strlen(ROOT_PATH))),'line'=>$e->getLine()];}
$serviceTrace['unchanged']=$fingerprint()===$before;}
$inventory=[];$missingInventory=[];$reopenProbe=null;
  if(!$costRead){try{$c->query('SELECT a.id FROM dc_pm_hourly_rates a LEFT JOIN dc_pm_hourly_rates b ON b.id=a.id');}catch(mysqli_sql_exception $e){$reopenProbe=$e->getCode();}}

  foreach(['project'=>['dc_pm_projects',900000090],'task'=>['dc_pm_tasks',900000091],'user'=>['dc_users',900000092],'department'=>['dc_pm_departments',900000093],'hidden_department'=>['dc_pm_departments',900000089],'role'=>['dc_pm_roles',900000094],'rate'=>['dc_pm_hourly_rates',900000095],'timesheet'=>['dc_pm_timesheets',900000096],'allocation'=>['dc_pm_allocations',900000097],'capacity'=>['dc_pm_capacity_overrides',900000098],'import'=>['dc_pm_import_logs',900000099]] as $key=>[$table,$id]){$s=$c->prepare('SELECT COUNT(*) n FROM '.$table.' WHERE store_id=? AND id=?');$s->bind_param('ii',$other,$id);$s->execute();$inventory[$key]=(int)$s->get_result()->fetch_assoc()['n']===1;$missingInventory[$key]=(int)$c->query('SELECT COUNT(*) FROM '.$table.' WHERE id=999000000')->fetch_row()[0]===0;}
  register_shutdown_function(function()use($c,$before,$fingerprint,$inventory,$missingInventory,$reopenProbe,$serviceTrace){global $error,$tenant_sql_error_code,$tenant_sql_error_alias;try{$data=['service_trace'=>$serviceTrace,'fixture_verified'=>$inventory,'missing_verified'=>$missingInventory,'temporary_reopen_probe_code'=>$reopenProbe,'controller_sql_error_code'=>$tenant_sql_error_code??null,'controller_sql_error_alias'=>$tenant_sql_error_alias??null,'unchanged'=>$fingerprint()===$before,'audit_count'=>(int)$c->query('SELECT COUNT(*) FROM dc_pm_audit_logs')->fetch_row()[0],'tracking_count'=>(int)$c->query('SELECT COUNT(*) FROM dc_trackings')->fetch_row()[0],'message'=>is_string($error??null)?$error:null];file_put_contents(ROOT_PATH.'.local/tenant-matrix-state/'.$_SERVER['HTTP_X_PM_TEST_NONCE'].'.json',json_encode($data,JSON_THROW_ON_ERROR));}catch(Throwable $e){file_put_contents(ROOT_PATH.'.local/tenant-matrix-state/'.$_SERVER['HTTP_X_PM_TEST_NONCE'].'.json',json_encode(['observer_error'=>get_class($e)]));}});
  return $result;
 }
}
$entry=file_get_contents(ROOT_PATH.($path==='/admin.php'?'admin.php':'pm_ajax.php'));
if($path==='/pm_ajax.php'){$entry=str_replace("define('ROOT_PATH',__DIR__.'/');",'',$entry,$n);if($n!==1)throw new RuntimeException('Ajax root mismatch.');}
$entry=str_replace("include_once(ROOT_PATH.'classes/database/mysql.class.php');",'',$entry,$n);if($n!==1)throw new RuntimeException('Entry DB mismatch.');
eval('?>'.$entry);
