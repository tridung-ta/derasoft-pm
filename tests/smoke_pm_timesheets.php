<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$days=[];foreach(['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','Chủ nhật'] as $i=>$label)$days[]=['date'=>'2026-06-0'.($i+1),'short_date'=>'0'.($i+1).'/06','label'=>$label,'hours'=>$i===0?'9.00':'0.00','regular_hours'=>$i===0?'8.00':'0.00','ot_hours'=>$i===0?'1.00':'0.00','has_ot'=>$i===0,'has_entries'=>$i===0,'is_today'=>$i===0];
$s->assign(['weeklySummary'=>['start'=>'01/06/2026','end'=>'07/06/2026','user_id'=>111,'days'=>$days],'weeklyError'=>'']);
$s->assign(['pageTitle'=>'Chấm công','notice'=>'','error'=>'','csrfToken'=>'test','timesheets'=>[],'assignedTasks'=>[['id'=>1,'name'=>'<script>bad</script>','project_name'=>'QA']],'formData'=>['task_id'=>1,'work_date'=>'2026-06-01','shift_label'=>'Morning','hours'=>'6','description'=>'<script>bad</script>'],'editId'=>0,'canEdit'=>true,'page'=>1,'totalPages'=>1,'isAdmin'=>true,'settings'=>['standard_hours_per_day'=>'8.00','ot_multiplier'=>'1.00']]);
$html=$s->fetch('admin/pm-timesheets.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-timesheets-1.html',str_replace('<head>','<head><base href="/">',$html));
if(str_contains($html,'<script>')||!str_contains($html,'value="save"')||!str_contains($html,'value="settings"'))throw new RuntimeException('Forms or escaping failed.');
if(substr_count($html,'<time datetime=')!==7||!str_contains($html,'pm-time-ot')||!str_contains($html,'<details><summary>'))throw new RuntimeException('Ledger/settings collapse failed.');
$s->assign(['isAdmin'=>false,'canEdit'=>false,'timesheets'=>[['id'=>1,'work_date'=>'2026-06-01','shift_label'=>'Morning','project_name'=>'QA','task_name'=>'Task','description'=>'<script>bad</script>','hours'=>'6','regular_hours'=>'6','ot_hours'=>'0','ot_multiplier_snapshot'=>'1','rate_snapshot'=>'0','currency'=>'VND','cost'=>'0','rate_warning'=>null,'is_locked'=>true,'can_edit'=>false,'user_id'=>1]]]);
$html=$s->fetch('admin/pm-timesheets.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-timesheets-2.html',str_replace('<head>','<head><base href="/">',$html));
foreach(['<script>','value="save"','value="delete"','value="settings"'] as $needle)if(str_contains($html,$needle))throw new RuntimeException('Read-only render failed: '.$needle);
$s->assign(['weeklySummary'=>null,'weeklyError'=>'<script>unavailable</script>']);$html=$s->fetch('admin/pm-timesheets.tpl.html');
if(str_contains($html,'<script>')||str_contains($html,'<time datetime=')||!str_contains($html,'&lt;script&gt;unavailable'))throw new RuntimeException('Unavailable summary escaping failed.');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9b-timesheets-unavailable.html',str_replace('<head>','<head><base href="/">',$html));
echo "PASS: timesheet Smarty forms, escaping, locked read-only and Admin settings visibility.\n";
