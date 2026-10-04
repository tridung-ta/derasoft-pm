<?php
include_once(ROOT_PATH.'classes/services/pmimportservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());requirePermission('pm.imports.manage');
$service=new PmImportService($db,(int)$storeId,(int)$userInfo->getId());$templateFile='pm-imports.tpl.html';$result=null;$batch=null;$error='';$history=[];$path=null;
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
            }else $batch=$action==='detail'?$service->detail((int)$id):$service->apply((int)$id,$action==='resume');
            $history=$service->history();
        }else{
        if($action==='template'){$bytes=$service->template();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="pm-personnel-template.xlsx"');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');echo $bytes;exit;}
        if(!in_array($action,['preview','stage'],true))throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $file=$_FILES['workbook']??null;if(!is_array($file)||!isset($file['error'],$file['tmp_name'],$file['name'],$file['size'])||$file['error']!==UPLOAD_ERR_OK||!is_string($file['tmp_name'])||!is_string($file['name'])||$file['size']>2097152||!is_uploaded_file($file['tmp_name']))throw new InvalidArgumentException('Upload XLSX tối đa 2MB không hợp lệ.');
        $path=tempnam(sys_get_temp_dir(),'pm-import-');if($path===false||!move_uploaded_file($file['tmp_name'],$path))throw new RuntimeException('Không lưu được file tạm.');
        $result=$action==='stage'?$service->stage($path,$file['name']):$service->preview($path,$file['name']);$history=$service->history();
        }
    }
}catch(InvalidArgumentException $e){http_response_code(400);$error=$e->getMessage();}
catch(DomainException $e){http_response_code(403);$error=$e->getMessage();}
catch(OverflowException $e){http_response_code(409);$error=$e->getMessage();}
catch(Throwable $e){http_response_code(500);error_log('PM import operation failed.');$error='Không thể kiểm tra import.';}
finally{if(is_string($path)&&is_file($path))unlink($path);}
header('Cache-Control: no-store');$template->assign(['pageTitle'=>'Import nhân sự — DeraSoft PM','csrfToken'=>$_SESSION['pm_csrf_token'],'importResult'=>$result,'importBatch'=>$batch,'importHistory'=>$history,'error'=>$error]);
