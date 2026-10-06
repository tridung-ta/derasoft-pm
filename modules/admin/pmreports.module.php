<?php
include_once(ROOT_PATH.'classes/services/pmreportservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());requirePermission('pm.reports.view');
$service=new PmReportService($db,(int)$storeId,(int)$userInfo->getId());$templateFile='pm-reports.tpl.html';$error='';$report=null;
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
$input=[];foreach(['from','to','project_id','user_id','page','mode','role_code'] as $key){$v=$request->element($key);if($v!==''&&$v!==null)$input[$key]=$v;}
try{
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(!is_string($request->element('csrf_token'))||!hash_equals($_SESSION['pm_csrf_token'],$request->element('csrf_token')))throw new InvalidArgumentException('Phiên đã hết hạn. Vui lòng tải lại trang.');
        if($request->element('action')!=='export')throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $bytes=$service->xlsx($input);header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="derasoft-pm-report.xlsx"');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Content-Length: '.strlen($bytes));echo $bytes;exit;
    }
    $report=$service->report($input);
}catch(InvalidArgumentException $e){http_response_code(400);$error=$e->getMessage();}
catch(DomainException $e){http_response_code(403);$error=$e->getMessage();}
catch(Throwable $e){http_response_code(500);error_log('PM report operation failed.');$error='Không thể tạo báo cáo.';}
header('Cache-Control: no-store');
$template->assign(['pageTitle'=>'Báo cáo — DeraSoft PM','report'=>$report,'error'=>$error,'csrfToken'=>$_SESSION['pm_csrf_token'],'canExportReports'=>$pmAccess->hasPermission('pm.reports.export'),'canReportCosts'=>$pmAccess->hasPermission('pm.costs.view')&&($pmAccess->hasRole('ADMIN')||$pmAccess->hasRole('PM'))]);
