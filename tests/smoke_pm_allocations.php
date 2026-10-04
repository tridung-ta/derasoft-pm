<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$row=['id'=>1,'user_id'=>1,'task_id'=>1,'project_id'=>1,'work_date'=>'2026-12-31','hours'=>'9.00','start_time'=>null,'end_time'=>null,'project_name'=>'<script>project</script>','task_name'=>'Task','user_name'=>'Nhân viên thử','historical'=>1,'warnings'=>['<script>warning</script>','Vượt ngưỡng phân bổ ngày.'],'day_total'=>'9.00','week_total'=>'41.00','daily_limit'=>'8.00','weekly_limit'=>'40.00'];
$data=['from'=>'2026-12-28','to'=>'2027-01-03','rows'=>[$row],'project_id'=>null,'user_id'=>null,'projects'=>[['id'=>1,'name'=>'Dự án thử']],'tasks'=>[['id'=>1,'name'=>'Task thử','assignee_id'=>1,'user_name'=>'Nhân viên thử']],'users'=>[['id'=>1,'fullname'=>'Nhân viên thử']],'capacities'=>[],'can_manage'=>true,'is_admin'=>true,'truncated'=>false];
$s->assign(['pageTitle'=>'Phân bổ','allocationData'=>$data,'editAllocation'=>null,'csrfToken'=>'test-token','notice'=>'','error'=>'','allocationWarnings'=>[]]);$html=$s->fetch('admin/pm-allocations.tpl.html');
foreach(['&lt;script&gt;','name="csrf_token"','allocation-editor','capacity','(lịch sử)','allocation-suggestions'] as $needle)if(!str_contains($html,$needle))throw new RuntimeException('Missing view: '.$needle);
if(str_contains($html,'<script>project')||str_contains($html,'<script>warning'))throw new RuntimeException('Escaping failed.');
if(in_array('--preview',$argv,true)){file_put_contents(ROOT_PATH.'.local/phase7-preview.html',str_replace('<head>','<head><base href="/">',$html));file_put_contents(ROOT_PATH.'.local/phase7-preview.json',json_encode($data,JSON_UNESCAPED_UNICODE));}
$data['can_manage']=false;$data['is_admin']=false;$data['tasks']=[];$data['projects']=[];$data['users']=[];$data['capacities']=[];
$s->assign('allocationData',$data);$html=$s->fetch('admin/pm-allocations.tpl.html');if(str_contains($html,'allocation-editor')||str_contains($html,'name="csrf_token"'))throw new RuntimeException('Employee can mutate from view.');
$s->assign(['allocationData'=>null,'error'=>'<script>error</script>']);$html=$s->fetch('admin/pm-allocations.tpl.html');if(str_contains($html,'<script>error')||str_contains($html,'id="allocation-rows"'))throw new RuntimeException('Error view failed.');
echo "PASS group 5: Smarty allocation forms, CSRF fields, history, escaping, Employee read-only and error state.\n";
