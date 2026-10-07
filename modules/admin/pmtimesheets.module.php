<?php
include_once(ROOT_PATH.'classes/services/pmtimesheetservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());
requirePermission('pm.timesheets.own');
$service=new PmTimesheetService($db,(int)$storeId,(int)$userInfo->getId());
$templateFile='pm-timesheets.tpl.html';$notice='';$error='';$edit=null;
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
$id=max(0,(int)$request->element('id'));
$data=['task_id'=>'','work_date'=>$service->today(),'shift_label'=>'','hours'=>'','description'=>''];
if($_SERVER['REQUEST_METHOD']==='POST'){
    foreach(array_keys($data) as $key)$data[$key]=$request->element($key);
    if(!hash_equals($_SESSION['pm_csrf_token'],(string)$request->element('csrf_token')))$error='Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
    else try{
        $action=(string)$request->element('action');
        if($action==='save'){$service->save($data,$id?:null);$notice='Đã lưu chấm công.';}
        elseif($action==='delete'){$service->delete($id);$notice='Đã ẩn chấm công và tính lại OT cả ngày.';}
        elseif($action==='settings'){$service->saveSettings(['standard_hours_per_day'=>$request->element('standard_hours_per_day'),'ot_multiplier'=>$request->element('ot_multiplier')]);$notice='Đã lưu quy tắc cho ngày chấm công mới.';}
        else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $id=0;$data=['task_id'=>'','work_date'=>$service->today(),'shift_label'=>'','hours'=>'','description'=>''];
    }catch(InvalidArgumentException|DomainException|OutOfBoundsException $e){$error=$e->getMessage();}
    catch(Throwable $e){error_log('PM timesheet operation failed.');$error='Không thể hoàn tất thao tác. Vui lòng thử lại.';}
}
if($id){try{$edit=$service->getTimesheet($id);if($_SERVER['REQUEST_METHOD']!=='POST')$data=$edit;}catch(OutOfBoundsException $e){http_response_code(403);$error=$e->getMessage();$id=0;}}
$page=max(1,(int)$request->element('page',1));$result=$service->listTimesheets($page);
foreach($result['rows'] as &$row){$row['is_locked']=$service->isLocked($row['work_date']);$row['can_edit']=$service->canEdit($row['work_date']);}unset($row);
$tasks=$service->assignedTasks($edit?(int)$edit['user_id']:null);
if($edit&&$pmAccess->hasRole('ADMIN')&&!in_array((int)$edit['task_id'],array_column($tasks,'id')))$tasks[]=['id'=>$edit['task_id'],'name'=>'Công việc #'.$edit['task_id'].' (lịch sử)','project_name'=>'Dự án #'.$edit['project_id']];
$weeklySummary=null;$weeklyError='';
try{$weeklySummary=$service->weeklySummary((string)$data['work_date'],$edit?(int)$edit['user_id']:null);}
catch(InvalidArgumentException $e){$weeklyError='Chọn ngày hợp lệ để xem tổng giờ trong tuần.';}
catch(Throwable $e){error_log('PM timesheet weekly summary failed.');$weeklyError='Không thể tải tổng giờ trong tuần. Vui lòng tải lại trang.';}
$template->assign(['weeklySummary'=>$weeklySummary,'weeklyError'=>$weeklyError]);
$template->assign(['pageTitle'=>'Chấm công — DeraSoft PM','notice'=>$notice,'error'=>$error,'csrfToken'=>$_SESSION['pm_csrf_token'],'timesheets'=>$result['rows'],'assignedTasks'=>$tasks,'formData'=>$data,'editId'=>$id,'canEdit'=>!$edit||$service->canEdit($edit['work_date']),'editingLocked'=>$edit&&$service->isLocked($edit['work_date']),'editingUserId'=>$edit?(int)$edit['user_id']:null,'page'=>$page,'totalPages'=>max(1,(int)ceil($result['total']/20)),'isAdmin'=>$pmAccess->hasRole('ADMIN'),'settings'=>$service->settings()]);
