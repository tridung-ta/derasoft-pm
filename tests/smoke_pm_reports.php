<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$r=['mode'=>'hours','filters'=>['from'=>'2026-10-01','to'=>'2026-10-02','project_id'=>null,'user_id'=>null],'data'=>['summary'=>['hours'=>'2.00','regular_hours'=>'2.00','ot_hours'=>'0.00'],'rows'=>[['work_date'=>'2026-10-01','shift_label'=>'Day','user_name'=>'<script>bad</script>','project_name'=>'Project','task_name'=>'Task','hours'=>'2.00','regular_hours'=>'2.00','ot_hours'=>'0.00','description'=>'<img src=x>','historical'=>1]],'by_user'=>[],'page'=>1,'total_pages'=>2]];
$r['filters']['mode']='hours';$r['data']['by_user']=[['user_name'=>'Sample','hours'=>'2.00','regular_hours'=>'2.00','ot_hours'=>'0.00']];
$s->assign(['pageTitle'=>'Reports','report'=>$r,'error'=>'','csrfToken'=>'test','canExportReports'=>true,'canReportCosts'=>false]);$html=$s->fetch('admin/pm-reports.tpl.html');
foreach(['&lt;script&gt;','value="export"','name="op" value="pmreports"','page=2','(lịch sử)'] as $n)if(!str_contains($html,$n))throw new RuntimeException('Missing report view: '.$n);
if(str_contains($html,'<script>bad')||str_contains($html,'<img src=x>'))throw new RuntimeException('Report escaping failed.');
if(!preg_match('/<header class="pm-report-header">.*?value="export".*?<\/header>/s',$html)||!str_contains($html,'class="pm-report-summary"'))throw new RuntimeException('Export header/summary missing.');
$preview=in_array('--preview',$argv,true);if($preview)file_put_contents(ROOT_PATH.'.local/phase9b-reports-hours.html',str_replace('<head>','<head><base href="/">',$html));
$s->assign('canExportReports',false);$html=$s->fetch('admin/pm-reports.tpl.html');if(str_contains($html,'value="export"'))throw new RuntimeException('Export denied but rendered.');if($preview)file_put_contents(ROOT_PATH.'.local/phase9b-reports-restricted.html',str_replace('<head>','<head><base href="/">',$html));
$r['mode']='costs';$r['filters']['mode']='costs';$r['data']=['valuation_date'=>'2026-10-06','warnings'=>['<script>mixed currency</script>'],'projects'=>[['name'=>'Sample','budget'=>'1234567890123.45','actual'=>['cost'=>null,'currency'=>null],'lifetime'=>['cost'=>'100.00','currency'=>'VND'],'estimate'=>['cost'=>null,'currency'=>null]]],'page'=>1,'total_pages'=>1];$s->assign(['report'=>$r,'canReportCosts'=>true,'canExportReports'=>true]);$html=$s->fetch('admin/pm-reports.tpl.html');
if(str_contains($html,'<script>mixed')||!str_contains($html,'1234567890123.45')||str_contains($html,'class="pm-report-summary"'))throw new RuntimeException('Cost report exact/empty/escaping failed.');if($preview)file_put_contents(ROOT_PATH.'.local/phase9b-reports-costs.html',str_replace('<head>','<head><base href="/">',$html));
$s->assign(['report'=>null,'error'=>'<script>bad</script>']);$html=$s->fetch('admin/pm-reports.tpl.html');if(str_contains($html,'<script>bad'))throw new RuntimeException('Report error escaping failed.');
if(str_contains($html,'value="export"'))throw new RuntimeException('Missing report exposes export.');if($preview)file_put_contents(ROOT_PATH.'.local/phase9b-reports-error.html',str_replace('<head>','<head><base href="/">',$html));
$s->assign(['error'=>'','canExportReports'=>true,'canReportUsers'=>true,'canReportProjects'=>true]);
$r['mode']='tasks';$r['filters']['mode']='tasks';$r['filters']['role_code']='EMPLOYEE';
$r['data']=['as_of'=>'2026-10-06','summary'=>['completed'=>1,'overdue'=>1,'completed_late'=>1,'completion_unknown'=>1],
    'rows'=>[['project_name'=>'Project','name'=>'<script>task</script>','user_name'=>'Sample','status'=>'done','start_date'=>'2026-10-01','due_date'=>'2026-10-02','completed_at'=>'2026-10-03 12:00:00','overdue'=>false,'completed_late'=>true,'late_days'=>1]],
    'by_project_user'=>[],'by_week'=>[['week_start'=>'2026-09-28','user_name'=>'Sample','completed'=>1]],'page'=>1,'total_pages'=>1];
$s->assign('report',$r);$html=$s->fetch('admin/pm-reports.tpl.html');
foreach(['&lt;script&gt;task&lt;/script&gt;','Task hoàn thành theo tuần','Hoàn thành trễ','2026-09-28'] as $needle)if(!str_contains($html,$needle))throw new RuntimeException('Task report rendering missing: '.$needle);
if(str_contains($html,'<script>task</script>'))throw new RuntimeException('Task report XSS');
if($preview)file_put_contents(ROOT_PATH.'.local/phase11-tasks.html',str_replace('<head>','<head><base href="/">',$html));
foreach(['users','projects'] as $mode){
    $r['mode']=$mode;$r['filters']['mode']=$mode;$r['data']=['fields'=>['name'=>'Tên'],'rows'=>[['name'=>'<img src=x>']],'page'=>1,'total_pages'=>1];
    $s->assign('report',$r);$html=$s->fetch('admin/pm-reports.tpl.html');
    if(!str_contains($html,'&lt;img src=x&gt;')||str_contains($html,'<img src=x>')||!str_contains($html,'Danh sách xuất Excel'))throw new RuntimeException('Directory report escaping/structure missing');
    if($preview)file_put_contents(ROOT_PATH.'.local/phase11-'.$mode.'.html',str_replace('<head>','<head><base href="/">',$html));
}
echo "PASS: reports hours/costs/tasks/directories Smarty, escaping, filters/pagination, export route and CSRF fields.\n";
