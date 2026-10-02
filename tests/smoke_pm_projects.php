<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$s->assign(['pageTitle'=>'Dự án','notice'=>'','error'=>'','csrfToken'=>'test','projects'=>[],'page'=>1,'totalPages'=>1,'q'=>'','project'=>null,'members'=>[],'tasks'=>[],'allUsers'=>[],'managers'=>[['id'=>1,'fullname'=>'Manager']],'canCreate'=>true,'canEditProject'=>true,'canEditMembers'=>true,'canEditTasks'=>true,'isAdmin'=>true,'actorId'=>1,'projectStatuses'=>['planned'=>'Dự kiến','active'=>'Đang làm','paused'=>'Tạm dừng','completed'=>'Hoàn thành'],'taskStatuses'=>['todo'=>'Cần làm','in_progress'=>'Đang làm','review'=>'Kiểm tra','done'=>'Hoàn thành']]);
if(strpos($s->fetch('admin/pm-projects.tpl.html'),'value="project_save"')===false)throw new RuntimeException('Project create form missing.');
$s->assign(['project'=>['id'=>1,'name'=>'Project','code'=>'DEMO','description'=>'<script>alert(1)</script>','manager_id'=>1,'status'=>'active','budget'=>'1000','start_date'=>null,'end_date'=>null],'members'=>[['user_id'=>1,'fullname'=>'Manager','user_status'=>1]],'tasks'=>[['id'=>1,'name'=>'Task','description'=>'<script>bad</script>','assignee_id'=>1,'assignee_name'=>'Manager','status'=>'todo','priority'=>'normal','estimated_hours'=>'4','due_date'=>null]]]);
$html=$s->fetch('admin/pm-projects.tpl.html');if(strpos($html,'<script>')!==false||strpos($html,'value="task_save"')===false)throw new RuntimeException('Task render or escaping failed.');
$s->assign(['canCreate'=>false,'canEditProject'=>false,'canEditMembers'=>false,'canEditTasks'=>false]);
$html=$s->fetch('admin/pm-projects.tpl.html');foreach(['value="task_save"','value="project_delete"','value="member_save"'] as $needle)if(strpos($html,$needle)!==false)throw new RuntimeException('Unauthorized mutation form visible.');
echo "PASS: project/task Smarty rendering, escaping and read-only forms.\n";
