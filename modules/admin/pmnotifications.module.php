<?php
include_once(ROOT_PATH.'includes/pm_reminders.inc.php');
if(!PM_REMINDERS_ENABLED){http_response_code(404);exit('Không tìm thấy chức năng được yêu cầu.');}
include_once(ROOT_PATH.'classes/services/pmnotificationservice.class.php');
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());
$templateFile='pm-notifications.tpl.html';$error='';$notice='';$result=['rows'=>[],'total'=>0,'unread'=>0,'page'=>1];
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
try {
    $service=new PmNotificationService(new PmNotifications(new PmDb($db),(int)$storeId),(int)$userInfo->getId());
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $token=$request->element('csrf_token');
        if(!is_string($token)||!hash_equals($_SESSION['pm_csrf_token'],$token)){http_response_code(403);$error='Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';}
        elseif($request->element('action')!=='read'){http_response_code(400);$error='Thao tác không hợp lệ.';}
        else {
            $id=$request->element('id');
            if(!is_scalar($id)||!preg_match('/^[1-9][0-9]{0,17}$/D',(string)$id)||filter_var($id,FILTER_VALIDATE_INT)===false){http_response_code(404);$error='Không tìm thấy thông báo.';}
            elseif(!$service->markRead((int)$id)){http_response_code(404);$error='Không tìm thấy thông báo.';}
            else{header('Location: admin.php?op=pmnotifications',true,303);exit;}
        }
    }
    $result=$service->listing(max(1,(int)$request->element('page',1)));
}catch(Throwable $e){error_log('PM notifications unavailable.');$error='Chưa thể tải thông báo. Vui lòng thử lại sau.';}
$template->assign(['pageTitle'=>'Thông báo — DeraSoft PM','notificationRows'=>$result['rows'],'page'=>$result['page'],'totalPages'=>max(1,(int)ceil($result['total']/20)),'error'=>$error,'notice'=>$notice,'csrfToken'=>$_SESSION['pm_csrf_token']]);
