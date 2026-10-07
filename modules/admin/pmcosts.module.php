<?php
include_once(ROOT_PATH.'classes/services/pmcostservice.class.php');
$pmAccess=new PmAccess($db,(int)$storeId,(int)$userInfo->getId());
requirePermission('pm.costs.view');
$service=new PmCostService($db,(int)$storeId,(int)$userInfo->getId());
$templateFile='pm-costs.tpl.html';$error='';$costData=null;$projectChoices=[];
$filters=[];foreach(['from','to','project_id','page'] as $key){$value=$request->element($key);if($value!==null&&$value!=='')$filters[$key]=$value;}
try{$projectChoices=$service->projectChoices();$costData=$service->dashboard($filters);}
catch(InvalidArgumentException $e){http_response_code(400);$error=$e->getMessage();}
catch(DomainException $e){http_response_code(403);$error=$e->getMessage();}
catch(Throwable $e){http_response_code(500);error_log('PM costs read failed.');$error='Không thể tải chi phí. Vui lòng thử lại.';}
$template->assign(['pageTitle'=>'Chi phí & Biểu đồ — DeraSoft PM','projectChoices'=>$projectChoices,'costData'=>$costData,'costJson'=>json_encode($costData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR),'error'=>$error,'filters'=>$filters]);
