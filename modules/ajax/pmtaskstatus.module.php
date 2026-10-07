<?php
// Only the authenticated, CSRF-checked PM entry point may execute this writer.
if(!defined('PM_TASK_STATUS_AUTHORIZED')||PM_TASK_STATUS_AUTHORIZED!==true||($_SERVER['REQUEST_METHOD']??'')!=='POST'){
    http_response_code(404);header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>'Unknown PM endpoint']);exit;
}
include_once(ROOT_PATH.'classes/services/pmprojectservice.class.php');
try{
    $ids=[];
    foreach(['project_id','task_id'] as $key){
        $value=(string)($_POST[$key]??'');
        if(!preg_match('/^[1-9]\d{0,17}$/D',$value)||filter_var($value,FILTER_VALIDATE_INT)===false)throw new InvalidArgumentException('Công việc không hợp lệ.');
        $ids[$key]=(int)$value;
    }
    $result=(new PmProjectService($db,$storeId,$actorId))->changeTaskStatus($ids['project_id'],$ids['task_id'],(string)($_POST['status']??''));
    echo json_encode(['task'=>$result],JSON_THROW_ON_ERROR);
}catch(InvalidArgumentException $e){http_response_code(422);echo json_encode(['error'=>'Dữ liệu trạng thái không hợp lệ.']);}
catch(DomainException $e){http_response_code(403);echo json_encode(['error'=>'Bạn không có quyền đổi trạng thái công việc này.']);}
catch(OutOfBoundsException $e){http_response_code(404);echo json_encode(['error'=>'Không tìm thấy công việc trong phạm vi truy cập.']);}
