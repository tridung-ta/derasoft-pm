<?php
include_once(ROOT_PATH.'classes/services/pmprojectservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());
requirePermission('pm.projects.view');
$service=new PmProjectService($db,(int)$storeId,(int)$userInfo->getId());
$templateFile='pm-projects.tpl.html';$notice='';$error='';
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
$projectId=max(0,(int)$request->element('project_id'));
if($_SERVER['REQUEST_METHOD']==='GET'&&$projectId===0&&isset($_SESSION['pm_project_result'])){
    if($_SESSION['pm_project_result']==='invalid_dates')$error=PmProjectDateRangeException::MESSAGE;
    else $notice=['saved'=>'Đã lưu dự án.','hidden'=>'Đã ẩn dự án.'][$_SESSION['pm_project_result']]??'';
    unset($_SESSION['pm_project_result']);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($_SESSION['pm_csrf_token'],(string)$request->element('csrf_token'))){$error='Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';}
    else try{
        $action=(string)$request->element('action');
        if($action==='project_save'){
            $data=[];foreach(['code','name','description','manager_id','start_date','end_date','budget','status','client_name'] as $key)$data[$key]=$request->element($key);
            $projectId=$service->saveProject($data,$projectId?:null);$notice='Đã lưu dự án.';
        }elseif($action==='project_delete'){$service->deleteProject($projectId);$projectId=0;$notice='Đã ẩn dự án.';}
        elseif($action==='member_save'){$service->setMember($projectId,(int)$request->element('member_id'),(string)$request->element('member_active')==='1',$request->element('project_role')!==''?(string)$request->element('project_role'):null);$notice='Đã cập nhật thành viên.';}
        elseif($action==='task_save'){$data=[];foreach(['name','description','assignee_id','status','priority','estimated_hours','due_date','start_date'] as $key)$data[$key]=$request->element($key);$service->saveTask($projectId,$data,((int)$request->element('task_id'))?:null);$notice='Đã lưu công việc.';}
        elseif($action==='task_status'){
            foreach(['project_id','task_id'] as $key){$value=(string)$request->element($key);if(!preg_match('/^[1-9]\d{0,17}$/D',$value)||filter_var($value,FILTER_VALIDATE_INT)===false)throw new InvalidArgumentException('Công việc không hợp lệ.');}
            $service->changeTaskStatus($projectId,(int)$request->element('task_id'),(string)$request->element('status'));$notice='Đã cập nhật trạng thái công việc.';
        }
        elseif($action==='task_delete'){$service->deleteTask($projectId,(int)$request->element('task_id'));$notice='Đã ẩn công việc.';}
        else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        try{$trackings->addData(['store_id'=>$storeId,'username'=>(string)(int)$userInfo->getId(),'action'=>'PM projects: '.$action.' project #'.$projectId,'date_created'=>date('Y-m-d H:i:s'),'ip'=>'']);}catch(Throwable $logError){error_log('PM project tracking failed.');}
        if(in_array($action,['project_save','project_delete'],true)){
            $_SESSION['pm_project_result']=$action==='project_save'?'saved':'hidden';
            header('Location: '.ADMIN_SCRIPT.'?op=pmprojects',true,303);exit;
        }
    }catch(PmProjectDateRangeException $e){
        $_SESSION['pm_project_result']='invalid_dates';header('Location: '.ADMIN_SCRIPT.'?op=pmprojects',true,303);exit;
    }catch(InvalidArgumentException|DomainException|OutOfBoundsException $e){$error=$e->getMessage();}
    catch(Throwable $e){error_log('PM project operation failed.');$error='Không thể hoàn tất thao tác. Vui lòng thử lại.';}
}
$page=max(1,(int)$request->element('page',1));$q=trim((string)$request->element('q'));
$project=null;$members=[];$tasks=[];$canManage=false;
if($projectId){try{$project=$service->getProject($projectId);$members=$service->members($projectId);if($pmAccess->hasPermission('pm.tasks.view'))$tasks=$service->tasks($projectId);$canManage=$service->canManage($project);}catch(DomainException|OutOfBoundsException $e){http_response_code(403);$error=$e->getMessage();$projectId=0;}}
$result=$service->listProjects($q,$page);
$canCreate=$pmAccess->hasPermission('pm.projects.manage')&&($pmAccess->hasRole('ADMIN')||$pmAccess->hasRole('PM'));
$template->assign(['pageTitle'=>'Dự án & Công việc — DeraSoft PM','notice'=>$notice,'error'=>$error,'csrfToken'=>$_SESSION['pm_csrf_token'],'projects'=>$result['rows'],'page'=>$page,'totalPages'=>max(1,(int)ceil($result['total']/20)),'q'=>$q,'project'=>$project,'members'=>$members,'tasks'=>$tasks,'allUsers'=>$canManage&&$pmAccess->hasPermission('pm.project_members.manage')?$service->availableUsers():[],'managers'=>$canCreate?$service->availableUsers(true):[],'canCreate'=>$canCreate,'canEditProject'=>$canManage&&$pmAccess->hasPermission('pm.projects.manage'),'canEditMembers'=>$canManage&&$pmAccess->hasPermission('pm.project_members.manage'),'canEditTasks'=>$canManage&&$pmAccess->hasPermission('pm.tasks.manage'),'isAdmin'=>$pmAccess->hasRole('ADMIN'),'actorId'=>(int)$userInfo->getId(),'projectStatuses'=>['planned'=>'Dự kiến','active'=>'Đang thực hiện','paused'=>'Tạm dừng','completed'=>'Hoàn thành'],'taskStatuses'=>['todo'=>'Cần làm','in_progress'=>'Đang làm','review'=>'Chờ kiểm tra','done'=>'Hoàn thành']]);
