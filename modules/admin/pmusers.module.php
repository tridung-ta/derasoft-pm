<?php
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');
include_once(ROOT_PATH.'classes/dao/pmusers.class.php');
include_once(ROOT_PATH.'classes/dao/pmdepartments.class.php');
include_once(ROOT_PATH.'classes/dao/pmroles.class.php');
include_once(ROOT_PATH.'classes/services/pmrateservice.class.php');
include_once(ROOT_PATH.'classes/services/pmuiservice.class.php');
include_once(ROOT_PATH.'classes/services/pmuserinputservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());
requirePermission('pm.team.view');
$templateFile='pm-users-v2.tpl.html';$pmUsers=new PmUsers($db);$departments=new PmDepartments($db);$rolesDao=new PmRoles($db);
$canManageUsers=$pmAccess->hasPermission('pm.users.manage');$canManageRoles=$pmAccess->hasPermission('pm.rbac.manage');$canManageRates=$pmAccess->hasPermission('pm.rates.manage');
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
$notice='';$error='';$newPersonErrors=[];$action=(string)$request->element('action');
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($_SESSION['pm_csrf_token'],(string)$request->element('csrf_token')))$error='Phiên làm việc đã hết hạn.';
    else try{
        if(in_array($action,['rate','rate_save','rate_deactivate'],true))requirePermission('pm.rates.manage');
        elseif($action==='roles')requirePermission('pm.rbac.manage');
        elseif(in_array($action,['department_save','department_status','department_delete','department_restore','status','save'],true))requirePermission('pm.users.manage');
        else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $targetId=(int)$request->element('id');
        if($action==='department_save'){
            $code=strtoupper(trim((string)$request->element('code')));$name=trim((string)$request->element('name'));
            if(!preg_match('/^[A-Z0-9_-]{2,50}$/',$code)||$name===''||mb_strlen($name)>150)throw new InvalidArgumentException('Mã hoặc tên phòng ban không hợp lệ.');
            if($departments->hiddenCodeExists((int)$storeId,$code,$targetId?:null))throw new DomainException('Mã thuộc phòng ban đã ẩn. Hãy khôi phục trong mục Phòng ban đã ẩn.');
            if($departments->codeExists((int)$storeId,$code,$targetId?:null))throw new DomainException('Mã phòng ban đã tồn tại.');
            $targetId=$departments->save((int)$storeId,$targetId?:null,$code,$name);$notice='Đã lưu phòng ban.';
        }elseif($action==='department_restore'){
            if(!$departments->restore((int)$storeId,$targetId))throw new OutOfBoundsException('Không tìm thấy phòng ban đã ẩn trong tenant hiện tại.');
            $notice='Đã khôi phục phòng ban.';
        }elseif($action==='department_status'){
            $status=(int)$request->element('status');if(!in_array($status,[0,1],true))throw new InvalidArgumentException('Trạng thái phòng ban không hợp lệ.');
            if(!$departments->setStatus((int)$storeId,$targetId,$status))throw new RuntimeException('Không thể cập nhật phòng ban.');
            $notice='Đã cập nhật trạng thái phòng ban.';
        }elseif($action==='department_delete'){
            if(!$departments->softDelete((int)$storeId,$targetId))throw new RuntimeException('Không thể ẩn phòng ban.');
            $notice='Đã ẩn phòng ban.';
        }elseif($action==='status'){
            $status=(int)$request->element('status');if(!in_array($status,[0,1,2],true))throw new InvalidArgumentException('Trạng thái không hợp lệ.');
            if($targetId===(int)$userInfo->getId() && $status!==1)throw new DomainException('Không thể khóa hoặc xóa tài khoản đang đăng nhập.');
            if(!$pmUsers->setStatus((int)$storeId,$targetId,$status))throw new RuntimeException('Không thể cập nhật trạng thái.');
            $notice='Đã cập nhật trạng thái tài khoản.';
        }elseif($action==='roles'){
            $roleIds=array_map('intval',(array)$request->element('role_ids',[]));$primary=(int)$request->element('primary_role_id');
            $pmUsers->assignRoles((int)$storeId,$targetId,$roleIds,$primary);$notice='Đã cập nhật vai trò.';
        }elseif($action==='rate_deactivate'){
            (new PmRateService($db))->deactivateRate((int)$storeId,$targetId);$notice='Đã ngừng áp dụng đơn giá.';
        }elseif($action==='rate_save'){
            $rateId=(int)$request->element('rate_id');
            $targetId=(new PmRateService($db))->saveRate((int)$storeId,['user_id'=>$request->element('rate_user_id'),'role_id'=>$request->element('rate_role_id'),'rate'=>$request->element('rate'),'currency'=>'VND','effective_from'=>$request->element('effective_from'),'effective_to'=>$request->element('effective_to')],(int)$userInfo->getId(),$rateId?:null);
            $notice='Đã lưu đơn giá.';
        }elseif($action==='rate'){
            $rateService=new PmRateService($db);$rateService->saveRate((int)$storeId,['user_id'=>$targetId,'role_id'=>null,'rate'=>$request->element('rate'),'currency'=>'VND','effective_from'=>$request->element('effective_from'),'effective_to'=>$request->element('effective_to')],(int)$userInfo->getId());$notice='Đã thêm đơn giá.';
        }elseif($action==='save'){
            $email=trim((string)$request->element('email'));$fullname=trim((string)$request->element('fullname'));
            if(!$targetId){
                foreach(['email','password','tel'] as $field){
                    if(isset($_POST[$field])&&!is_string($_POST[$field]))throw new InvalidArgumentException('Dữ liệu thêm nhân sự không hợp lệ.');
                }
                PmUserInputService::validateCreate($email,$_POST['password']??'',$_POST['tel']??'');
            }
            if($fullname===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Họ tên hoặc email không hợp lệ.');
            if($pmUsers->emailExists((int)$storeId,$email,$targetId?:null))throw new DomainException('Email đã được sử dụng.');
            $departmentId=(int)$request->element('department_id');$departmentId=$departmentId?:null;
            if($departmentId&&!$departments->exists((int)$storeId,$departmentId))throw new DomainException('Phòng ban không thuộc tenant hiện tại.');
            $data=['fullname'=>$fullname,'email'=>$email,'tel'=>trim((string)$request->element('tel')),'department_id'=>$departmentId,'weekly_limit_hours'=>(float)$request->element('weekly_limit_hours')];
            if($data['weekly_limit_hours']<=0||$data['weekly_limit_hours']>168)throw new InvalidArgumentException('Giới hạn giờ/tuần không hợp lệ.');
            if($targetId){if(!$pmUsers->updateSafe((int)$storeId,$targetId,$data))throw new OutOfBoundsException('Không tìm thấy nhân sự trong đơn vị hiện tại.');}
            else{$username=trim((string)$request->element('username'));$password=(string)$request->element('password');if($username===''||strlen($password)<8||$pmUsers->usernameExists((int)$storeId,$username))throw new InvalidArgumentException('Username hoặc mật khẩu không hợp lệ/trùng.');$data+=['username'=>$username,'password'=>$password];$targetId=$pmUsers->create((int)$storeId,$data);}
            $notice='Đã lưu thông tin nhân sự.';
        }
        if($notice!=='')$trackings->addData(['store_id'=>$storeId,'username'=>(string)(int)$userInfo->getId(),'action'=>'PM users: '.$action.' user #'.$targetId,'date_created'=>date('Y-m-d H:i:s'),'ip'=>'']);
    }catch(PmUserInputException $e){$newPersonErrors=$e->getFieldErrors();$error=$e->getMessage();}
    catch(InvalidArgumentException|DomainException|OutOfBoundsException $e){$error=$e->getMessage();}
    catch(Throwable $e){error_log('PM user management operation failed.');$error='Không thể hoàn tất thao tác. Vui lòng kiểm tra dữ liệu và thử lại.';}
}
$page=max(1,(int)$request->element('page',1));$q=trim((string)$request->element('q'));$departmentFilter=(int)$request->element('department_id');$roleFilter=(int)$request->element('role_id');
$filters=['q'=>$q,'department_id'=>$departmentFilter,'role_id'=>$roleFilter];$rows=$pmUsers->list((int)$storeId,$filters,$page,20);
$userRoles=$pmUsers->rolesForUsers((int)$storeId,array_map(fn($row)=>(int)$row['id'],$rows));
$roleState=PmUiService::roleState($userRoles);$userRoleState=$roleState['state'];$userPrimaryRoles=$roleState['primary'];
if($roleState['inconsistent'])$error=trim($error.' Có nhân sự được gán nhiều vai trò chính; vui lòng chọn lại một vai trò chính.');
$total=$pmUsers->count((int)$storeId,$filters);
$canViewRates=$pmAccess->hasPermission('pm.rates.view')||$canManageRates;
$template->assign('canViewRates',$canViewRates);
$template->assign('hourlyRates',$canViewRates?(new PmRateService($db))->listRates((int)$storeId):[]);
$template->assign('pageTitle','Nhân sự — DeraSoft PM');$template->assign('users',$rows);$template->assign('userRoles',$userRoles);$template->assign('userRoleState',$userRoleState);$template->assign('userPrimaryRoles',$userPrimaryRoles);$template->assign('departments',$departments->getActive((int)$storeId));$template->assign('departmentRows',$departments->list((int)$storeId));$template->assign('roles',$rolesDao->getActive((int)$storeId));$template->assign('canManageUsers',$canManageUsers);$template->assign('canManageRoles',$canManageRoles);$template->assign('canManageRates',$canManageRates);$template->assign('csrfToken',$_SESSION['pm_csrf_token']);$template->assign('notice',$notice);$template->assign('error',$error);$template->assign('q',$q);$template->assign('departmentFilter',$departmentFilter);$template->assign('roleFilter',$roleFilter);$template->assign('page',$page);$template->assign('totalPages',max(1,(int)ceil($total/20)));

$template->assign('hiddenDepartments',$canManageUsers?$departments->listHidden((int)$storeId):[]);
$template->assign('newPersonErrors',$newPersonErrors);
