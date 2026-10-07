<?php
include_once(ROOT_PATH.'classes/services/pmcostservice.class.php');
try{
    $input=[];foreach(['from','to','project_id','page'] as $key)if(isset($_GET[$key]))$input[$key]=$_GET[$key];
    echo json_encode((new PmCostService($db,(int)$storeId,(int)$actorId))->dashboard($input),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
}catch(InvalidArgumentException $e){http_response_code(400);echo json_encode(['error'=>$e->getMessage()]);}
catch(DomainException $e){http_response_code(403);echo json_encode(['error'=>$e->getMessage()]);}
