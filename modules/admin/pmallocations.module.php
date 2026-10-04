<?php
include_once(ROOT_PATH.'classes/services/pmallocationservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());
requirePermission('pm.allocations.view');
$service=new PmAllocationService($db,(int)$storeId,(int)$userInfo->getId());
$templateFile='pm-allocations.tpl.html';$error='';$notice='';$warnings=[];$edit=null;
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
$filters=[];foreach(['week','project_id','user_id'] as $key){$v=$_GET[$key]??'';if($v!==null&&$v!=='')$filters[$key]=$v;}
try{
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(!is_string($request->element('csrf_token'))||!hash_equals($_SESSION['pm_csrf_token'],$request->element('csrf_token')))throw new InvalidArgumentException('Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
        $rawId=$request->element('id');if($rawId!==null&&$rawId!==''&&(!is_scalar($rawId)||!preg_match('/^[1-9]\d{0,9}$/D',(string)$rawId)))throw new InvalidArgumentException('ID không hợp lệ.');$id=$rawId?(int)$rawId:null;
        $action=$request->element('action');
        if($action==='save'){$data=[];foreach(['task_id','user_id','work_date','hours','start_time','end_time'] as $key){$data[$key]=$request->element($key);if($data[$key]!==null&&!is_scalar($data[$key]))throw new InvalidArgumentException('Input không hợp lệ.');}$saved=$service->save($data,$id);$warnings=$saved['warnings'];$notice='Đã lưu phân bổ.';}
        elseif($action==='delete'&&$id){$service->delete($id);$notice='Đã ẩn phân bổ; giữ lịch sử audit.';}
        elseif($action==='capacity'){$data=[];foreach(['user_id','daily_limit_hours','weekly_limit_hours','effective_from','effective_to'] as $key){$data[$key]=$request->element($key);if($data[$key]!==null&&!is_scalar($data[$key]))throw new InvalidArgumentException('Input không hợp lệ.');}$service->saveCapacity($data,$id);$notice='Đã lưu capacity.';}
        elseif($action==='capacity_delete'&&$id){$service->deleteCapacity($id);$notice='Đã ẩn capacity.';}
        else throw new InvalidArgumentException('Thao tác không hợp lệ.');
    }elseif($request->element('id')){
        $v=$request->element('id');if(!is_scalar($v)||!preg_match('/^[1-9]\d{0,9}$/D',(string)$v))throw new InvalidArgumentException('ID không hợp lệ.');$edit=$service->get((int)$v);
        foreach(['start_time','end_time'] as $key)if($edit[$key]!==null)$edit[$key]=substr($edit[$key],0,5);
    }
}catch(InvalidArgumentException $e){http_response_code(400);$error=$e->getMessage();}
catch(DomainException|OutOfBoundsException $e){http_response_code(403);$error=$e->getMessage();}
catch(Throwable $e){http_response_code(500);error_log('PM allocation operation failed.');$error='Không thể hoàn tất phân bổ.';}
try{$allocationData=$service->dashboard($filters);}catch(InvalidArgumentException $e){http_response_code(400);$error=$e->getMessage();$allocationData=null;}
catch(DomainException $e){http_response_code(403);$error=$e->getMessage();$allocationData=null;}
catch(Throwable $e){http_response_code(500);error_log('PM allocation read failed.');$error='Không thể tải lịch phân bổ.';$allocationData=null;}
$template->assign(['pageTitle'=>'Phân bổ nguồn lực — DeraSoft PM','allocationData'=>$allocationData,'editAllocation'=>$edit,'csrfToken'=>$_SESSION['pm_csrf_token'],'notice'=>$notice,'error'=>$error,'allocationWarnings'=>$warnings]);
