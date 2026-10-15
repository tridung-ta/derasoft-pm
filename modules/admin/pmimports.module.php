<?php
include_once(ROOT_PATH.'classes/services/pmimportservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());requirePermission('pm.imports.manage');
$service=new PmImportService($db,(int)$storeId,(int)$userInfo->getId());$templateFile='pm-imports.tpl.html';$result=null;$batch=null;$error='';$history=[];$path=null;
$notice='';$passwordProvisionedRow=null;$importPanel='template';
$requestedAction=$request->element('action');
if(in_array($requestedAction,['preview','stage'],true))$importPanel='upload';
elseif(in_array($requestedAction,['apply','resume','detail','provision_password'],true))$importPanel='history';
if(empty($_SESSION['pm_csrf_token']))$_SESSION['pm_csrf_token']=bin2hex(random_bytes(32));
try{
    $history=$service->history();
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(!is_string($request->element('csrf_token'))||!hash_equals($_SESSION['pm_csrf_token'],$request->element('csrf_token')))throw new InvalidArgumentException('Phiên đã hết hạn.');
        $action=$request->element('action');
        if(in_array($action,['apply','resume','detail','provision_password'],true)){
            $id=$request->element('import_id');if(!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<=0)throw new InvalidArgumentException('Batch không hợp lệ.');
            if($action==='provision_password'){
                $row=$request->element('source_row');$password=$request->element('password');if(!is_scalar($row)||!ctype_digit((string)$row)||!is_string($password))throw new InvalidArgumentException('Dòng/mật khẩu không hợp lệ.');
                $batch=$service->provisionPassword((int)$id,(int)$row,$password);
                $passwordProvisionedRow=(int)$row;
                $provisionedUserId=0;
                foreach($batch['results'] as $provisionedResult)if((int)$provisionedResult['source_row']===$passwordProvisionedRow)$provisionedUserId=(int)$provisionedResult['user_id'];
                $notice='Đã cấp mật khẩu cho User ID '.$provisionedUserId.' (dòng Excel '.$passwordProvisionedRow.'). Tài khoản vẫn chưa kích hoạt.';
            }else $batch=$action==='detail'?$service->detail((int)$id):$service->apply((int)$id,$action==='resume');
            if(in_array($action,['apply','resume'],true)){
                $c=$batch['counts'];
                $notice='Apply #'.(int)$batch['id'].': đã tạo '.(int)$c['applied'].'; bỏ qua trùng '.(int)$c['skipped_duplicate'].'; lỗi '.(int)$c['failed'].'; chưa hoàn tất '.(int)$c['pending'].'.';
                if($c['failed']>0||$c['pending']>0){$error=$notice.' Kiểm tra kết quả từng dòng và dùng Tiếp tục khi cần.';$notice='';}
            }
            $history=$service->history();
        }else{
        if($action==='template'){$bytes=$service->template();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="pm-personnel-template.xlsx"');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');echo $bytes;exit;}
        if(!in_array($action,['preview','stage'],true))throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $file=$_FILES['workbook']??null;if(!is_array($file)||!isset($file['error'],$file['tmp_name'],$file['name'],$file['size'])||$file['error']!==UPLOAD_ERR_OK||!is_string($file['tmp_name'])||!is_string($file['name'])||$file['size']>2097152||!is_uploaded_file($file['tmp_name']))throw new InvalidArgumentException('Tệp tải lên không hợp lệ. Vui lòng chọn tệp Excel XLSX có dung lượng tối đa 2 MB.');
        $path=tempnam(sys_get_temp_dir(),'pm-import-');if($path===false||!move_uploaded_file($file['tmp_name'],$path))throw new RuntimeException('Không lưu được file tạm.');
        $result=$action==='stage'?$service->stage($path,$file['name']):$service->preview($path,$file['name']);$history=$service->history();
        }
    }
}catch(InvalidArgumentException $e){http_response_code(400);$error=$e->getMessage();}
catch(PmScopeDeniedException $e){http_response_code(403);$error=PmScopeDeniedException::MESSAGE;}
catch(DomainException $e){http_response_code(403);$error=$e->getMessage();}
catch(OverflowException $e){http_response_code(409);$error=$e->getMessage();}
catch(Throwable $e){http_response_code(500);error_log('PM import operation failed.');$error='Không thể hoàn tất thao tác nhập nhân sự. Vui lòng thử lại.';}
finally{if(is_string($path)&&is_file($path))unlink($path);}
header('Cache-Control: no-store');$template->assign(['importPanel'=>$importPanel,'pageTitle'=>'Import nhân sự — DeraSoft PM','csrfToken'=>$_SESSION['pm_csrf_token'],'importResult'=>$result,'importBatch'=>$batch,'importHistory'=>$history,'error'=>$error,'notice'=>$notice,'passwordProvisionedRow'=>$passwordProvisionedRow]);
