<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$r=['mode'=>'hours','filters'=>['from'=>'2026-10-01','to'=>'2026-10-02','project_id'=>null,'user_id'=>null],'data'=>['summary'=>['hours'=>'2.00','regular_hours'=>'2.00','ot_hours'=>'0.00'],'rows'=>[['work_date'=>'2026-10-01','shift_label'=>'Day','user_name'=>'<script>bad</script>','project_name'=>'Project','task_name'=>'Task','hours'=>'2.00','regular_hours'=>'2.00','ot_hours'=>'0.00','description'=>'<img src=x>','historical'=>1]],'by_user'=>[],'page'=>1,'total_pages'=>2]];
$s->assign(['pageTitle'=>'Reports','report'=>$r,'error'=>'','csrfToken'=>'test','canExportReports'=>true,'canReportCosts'=>false]);$html=$s->fetch('admin/pm-reports.tpl.html');
foreach(['&lt;script&gt;','value="export"','name="op" value="pmreports"','page=2','(lịch sử)'] as $n)if(!str_contains($html,$n))throw new RuntimeException('Missing report view: '.$n);
if(str_contains($html,'<script>bad')||str_contains($html,'<img src=x>'))throw new RuntimeException('Report escaping failed.');
$s->assign(['report'=>null,'error'=>'<script>bad</script>']);$html=$s->fetch('admin/pm-reports.tpl.html');if(str_contains($html,'<script>bad'))throw new RuntimeException('Report error escaping failed.');
echo "PASS: reports Smarty, escaping, filters/pagination, export route and CSRF fields.\n";
