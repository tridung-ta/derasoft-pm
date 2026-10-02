<?php
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');
include_once(ROOT_PATH.'classes/dao/pmusers.class.php');
include_once(ROOT_PATH.'classes/dao/pmdepartments.class.php');
include_once(ROOT_PATH.'classes/dao/pmroles.class.php');
include_once(ROOT_PATH.'classes/services/pmrateservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());
requirePermission('pm.team.view');
$templateFile='pm-users.tpl.html';$pmUsers=new PmUsers($db);$departments=new PmDepartments($db);$rolesDao=new PmRoles($db);
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
$notice='';$error='';$action=(string)$request->element('action');
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($_SESSION['pm_csrf_token'],(string)$request->element('csrf_token')))$error='Phiên làm việc đã hết hạn.';
    else try{
        if($action==='rate')requirePermission('pm.rates.manage');
        elseif($action==='roles')requirePermission('pm.rbac.manage');
        else requirePermission('pm.users.manage');
        $targetId=(int)$request->element('id');
        if($action==='status'){
            $status=(int)$request->element('status');if(!in_array($status,[0,1,2],true))throw new InvalidArgumentException('Trạng thái không hợp lệ.');
            if($targetId===(int)$userInfo->getId() && $status!==1)throw new DomainException('Không thể khóa hoặc xóa tài khoản đang đăng nhập.');
            if(!$pmUsers->setStatus((int)$storeId,$targetId,$status))throw new RuntimeException('Không thể cập nhật trạng thái.');
            $notice='Đã cập nhật trạng thái tài khoản.';
        }elseif($action==='roles'){
            $roleIds=array_map('intval',(array)$request->element('role_ids',[]));$primary=(int)$request->element('primary_role_id');
            $pmUsers->assignRoles((int)$storeId,$targetId,$roleIds,$primary);$notice='Đã cập nhật vai trò.';
        }elseif($action==='rate'){
            $rateService=new PmRateService($db);$rateService->saveRate((int)$storeId,['user_id'=>$targetId,'role_id'=>null,'rate'=>$request->element('rate'),'currency'=>'VND','effective_from'=>$request->element('effective_from'),'effective_to'=>$request->element('effective_to')],(int)$userInfo->getId());$notice='Đã thêm đơn giá.';
        }elseif($action==='save'){
            $email=trim((string)$request->element('email'));$fullname=trim((string)$request->element('fullname'));
            if($fullname===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Họ tên hoặc email không hợp lệ.');
            if($pmUsers->emailExists((int)$storeId,$email,$targetId?:null))throw new DomainException('Email đã được sử dụng.');
            $departmentId=(int)$request->element('department_id');$departmentId=$departmentId?:null;
            if($departmentId&&!$departments->exists((int)$storeId,$departmentId))throw new DomainException('Phòng ban không thuộc tenant hiện tại.');
            $data=['fullname'=>$fullname,'email'=>$email,'tel'=>trim((string)$request->element('tel')),'department_id'=>$departmentId,'weekly_limit_hours'=>(float)$request->element('weekly_limit_hours')];
            if($data['weekly_limit_hours']<=0||$data['weekly_limit_hours']>168)throw new InvalidArgumentException('Giới hạn giờ/tuần không hợp lệ.');
            if($targetId){$pmUsers->updateSafe((int)$storeId,$targetId,$data);}
            else{$username=trim((string)$request->element('username'));$password=(string)$request->element('password');if($username===''||strlen($password)<8||$pmUsers->usernameExists((int)$storeId,$username))throw new InvalidArgumentException('Username hoặc mật khẩu không hợp lệ/trùng.');$data+=['username'=>$username,'password'=>$password];$targetId=$pmUsers->create((int)$storeId,$data);}
            $notice='Đã lưu thông tin nhân sự.';
        }
        if($notice!=='')$trackings->addData(['store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>'PM users: '.$action.' user #'.$targetId,'date_created'=>date('Y-m-d H:i:s'),'ip'=>$_SERVER['REMOTE_ADDR']??'']);
    }catch(Throwable $e){$error=$e->getMessage();}
}
$page=max(1,(int)$request->element('page',1));$q=trim((string)$request->element('q'));
$rows=$pmUsers->list((int)$storeId,['q'=>$q,'department_id'=>(int)$request->element('department_id'),'role_id'=>(int)$request->element('role_id')],$page,20);
$userRoles=[];foreach($rows as $row){$userRoles[$row['id']]=$pmUsers->roles((int)$storeId,(int)$row['id']);}
$total=$pmUsers->count((int)$storeId,$q);
$template->assign('pageTitle','Nhân sự — DeraSoft PM');$template->assign('users',$rows);$template->assign('userRoles',$userRoles);$template->assign('departments',$departments->getActive((int)$storeId));$template->assign('roles',$rolesDao->getActive((int)$storeId));$template->assign('csrfToken',$_SESSION['pm_csrf_token']);$template->assign('notice',$notice);$template->assign('error',$error);$template->assign('q',$q);$template->assign('page',$page);$template->assign('totalPages',max(1,(int)ceil($total/20)));
