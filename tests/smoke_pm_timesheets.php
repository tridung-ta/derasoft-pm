<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$s->assign(['pageTitle'=>'Chấm công','notice'=>'','error'=>'','csrfToken'=>'test','timesheets'=>[],'assignedTasks'=>[['id'=>1,'name'=>'<script>bad</script>','project_name'=>'QA']],'formData'=>['task_id'=>1,'work_date'=>'2026-06-01','shift_label'=>'Morning','hours'=>'6','description'=>'<script>bad</script>'],'editId'=>0,'canEdit'=>true,'page'=>1,'totalPages'=>1,'isAdmin'=>true,'settings'=>['standard_hours_per_day'=>'8.00','ot_multiplier'=>'1.00']]);
$html=$s->fetch('admin/pm-timesheets.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-timesheets-1.html',str_replace('<head>','<head><base href="/">',$html));
if(str_contains($html,'<script>')||!str_contains($html,'value="save"')||!str_contains($html,'value="settings"'))throw new RuntimeException('Forms or escaping failed.');
$s->assign(['isAdmin'=>false,'canEdit'=>false,'timesheets'=>[['id'=>1,'work_date'=>'2026-06-01','shift_label'=>'Morning','project_name'=>'QA','task_name'=>'Task','description'=>'<script>bad</script>','hours'=>'6','regular_hours'=>'6','ot_hours'=>'0','ot_multiplier_snapshot'=>'1','rate_snapshot'=>'0','currency'=>'VND','cost'=>'0','rate_warning'=>null,'is_locked'=>true,'can_edit'=>false,'user_id'=>1]]]);
$html=$s->fetch('admin/pm-timesheets.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-timesheets-2.html',str_replace('<head>','<head><base href="/">',$html));
foreach(['<script>','value="save"','value="delete"','value="settings"'] as $needle)if(str_contains($html,$needle))throw new RuntimeException('Read-only render failed: '.$needle);
echo "PASS: timesheet Smarty forms, escaping, locked read-only and Admin settings visibility.\n";
