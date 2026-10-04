<?php
/** Allowlisted PM read endpoint; legacy ajax.php remains unchanged. */
define('ROOT_PATH',__DIR__.'/');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);header('Allow: GET');echo json_encode(['error'=>'Method not allowed']);exit;}
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','cookie_secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||($_SERVER['SERVER_PORT']??null)==443,'use_strict_mode'=>true]);
$actorId=(int)($_SESSION['userId']??0);$storeId=(int)($_SESSION['storeId']??0);
session_write_close();
header('Cache-Control: no-store');
if(!$actorId){http_response_code(401);echo json_encode(['error'=>'Authentication required']);exit;}
$pmEndpoint=$_GET['op']??'';
if(!is_string($pmEndpoint)||!in_array($pmEndpoint,['pmcosts','pmallocations'],true)){http_response_code(404);echo json_encode(['error'=>'Unknown PM endpoint']);exit;}
try{
    include_once(ROOT_PATH.'includes/config.inc.php');
    include_once(ROOT_PATH.'includes/constant.inc.php');
    include_once(ROOT_PATH.'classes/database/mysql.class.php');
    include_once(ROOT_PATH.'classes/database/pmdb.class.php');
    $db=new DB();
    if(!(new PmDb($db))->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$storeId,$actorId])){http_response_code(401);echo json_encode(['error'=>'Authentication required']);exit;}
    include(ROOT_PATH.'modules/ajax/'.$pmEndpoint.'.module.php');
}catch(Throwable $e){http_response_code(500);error_log('PM read endpoint failed.');echo json_encode(['error'=>'Không thể tải dữ liệu.']);}
