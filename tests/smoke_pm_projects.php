<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$s->assign(['pageTitle'=>'Dự án','notice'=>'','error'=>'','csrfToken'=>'test','projects'=>[],'page'=>1,'totalPages'=>1,'q'=>'','project'=>null,'members'=>[],'tasks'=>[],'allUsers'=>[],'managers'=>[['id'=>1,'fullname'=>'Manager']],'canCreate'=>true,'canEditProject'=>true,'canEditMembers'=>true,'canEditTasks'=>true,'isAdmin'=>true,'actorId'=>1,'projectStatuses'=>['planned'=>'Dự kiến','active'=>'Đang làm','paused'=>'Tạm dừng','completed'=>'Hoàn thành'],'taskStatuses'=>['todo'=>'Cần làm','in_progress'=>'Đang làm','review'=>'Kiểm tra','done'=>'Hoàn thành']]);
if(strpos($s->fetch('admin/pm-projects.tpl.html'),'value="project_save"')===false)throw new RuntimeException('Project create form missing.');
$cards=[];
foreach(['planned','active','paused','completed','active'] as $i=>$state){
    $total=[0,2,3,4,3][$i];$done=[0,1,0,4,1][$i];
    $cards[]=['id'=>$i+1,'name'=>$i===1?'<script>Nhóm thử</script> - Dự án triển khai hệ thống quản lý nội bộ':'Dự án thử '.($i+1),'code'=>'DEMO_'.($i+1),'status'=>$state,'manager_name'=>'Người phụ trách thử','start_date'=>'2026-10-05','end_date'=>null,'budget'=>'123456789.50','task_total'=>$total,'task_done'=>$done,'progress_percent'=>$total?round(100*$done/$total,1):null];
}
$cards[]=['id'=>6,'name'=>'Dự án giới hạn quyền','code'=>'LIMITED','status'=>'planned','manager_name'=>'Người thử','task_total'=>null,'task_done'=>null,'progress_percent'=>null];
$s->assign(['projects'=>$cards,'totalPages'=>2,'q'=>'<script>filter</script>']);$list=$s->fetch('admin/pm-projects.tpl.html');
foreach(['pm-project-grid','value="50"','value="0"','value="100"','value="33.3"','Chưa có dữ liệu','Tiến độ chưa khả dụng','page=2','&lt;script&gt;'] as $needle)if(!str_contains($list,$needle))throw new RuntimeException('Project card missing '.$needle);
if(str_contains($list,'<script>filter')||str_contains($list,'<script>Nhóm')||str_contains($list,'class="card pm-project-create" open'))throw new RuntimeException('List escaping/collapse failed.');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9b-project-list.html',str_replace('<head>','<head><base href="/">',$list));
$s->assign(['error'=>'Thông tin chưa hợp lệ']);$errorList=$s->fetch('admin/pm-projects.tpl.html');if(!str_contains($errorList,'class="card pm-project-create" open'))throw new RuntimeException('Error hides create form.');
$s->assign(['error'=>'','canCreate'=>false]);$readonlyList=$s->fetch('admin/pm-projects.tpl.html');if(str_contains($readonlyList,'value="project_save"'))throw new RuntimeException('Readonly list exposes create form.');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9b-project-list-readonly.html',str_replace('<head>','<head><base href="/">',$readonlyList));
$s->assign(['projects'=>[]]);$emptyList=$s->fetch('admin/pm-projects.tpl.html');if(!str_contains($emptyList,'Chưa có dự án trong phạm vi'))throw new RuntimeException('Empty project message missing.');
$s->assign(['canCreate'=>true]);
$s->assign(['project'=>['id'=>1,'name'=>'Project','code'=>'DEMO','description'=>'<script>alert(1)</script>','manager_id'=>1,'status'=>'active','budget'=>'1000','start_date'=>null,'end_date'=>null],'members'=>[['user_id'=>1,'fullname'=>'Manager','user_status'=>1]],'tasks'=>[['id'=>1,'name'=>'Task','description'=>'<script>bad</script>','assignee_id'=>1,'assignee_name'=>'Manager','status'=>'todo','priority'=>'normal','estimated_hours'=>'4','due_date'=>null]]]);
$html=$s->fetch('admin/pm-projects.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-projects-1.html',str_replace('<head>','<head><base href="/">',$html));if(strpos($html,'<script>')!==false||strpos($html,'value="task_save"')===false)throw new RuntimeException('Task render or escaping failed.');
$s->assign(['canCreate'=>false,'canEditProject'=>false,'canEditMembers'=>false,'canEditTasks'=>false]);
$html=$s->fetch('admin/pm-projects.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-projects-2.html',str_replace('<head>','<head><base href="/">',$html));foreach(['value="task_save"','value="project_delete"','value="member_save"'] as $needle)if(strpos($html,$needle)!==false)throw new RuntimeException('Unauthorized mutation form visible.');
echo "PASS: project/task Smarty rendering, escaping and read-only forms.\n";
