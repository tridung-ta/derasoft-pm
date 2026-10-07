<?php
include_once(ROOT_PATH.'classes/services/pmallocationservice.class.php');
try{
    $service=new PmAllocationService($db,(int)$storeId,(int)$actorId);
    if(($_GET['action']??'')==='suggestions'){
        foreach(['project_id','work_date','hours'] as $key)if(!isset($_GET[$key])||!is_scalar($_GET[$key]))throw new InvalidArgumentException('Thiếu bộ lọc gợi ý.');
        if(!preg_match('/^[1-9]\d{0,9}$/D',(string)$_GET['project_id']))throw new InvalidArgumentException('ID không hợp lệ.');
        $result=['suggestions'=>$service->suggestions((int)$_GET['project_id'],(string)$_GET['work_date'],$_GET['hours'])];
    }else{
        if(isset($_GET['action'])&&$_GET['action']!=='')throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $input=[];foreach(['week','project_id','user_id'] as $key)if(isset($_GET[$key]))$input[$key]=$_GET[$key];$result=$service->dashboard($input);
    }
    echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
}catch(InvalidArgumentException $e){http_response_code(400);echo json_encode(['error'=>$e->getMessage()]);}
catch(DomainException|OutOfBoundsException $e){http_response_code(403);echo json_encode(['error'=>$e->getMessage()]);}
