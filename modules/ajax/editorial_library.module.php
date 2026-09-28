<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors',0); error_reporting(0);
if(session_status()===PHP_SESSION_NONE)session_start();
include_once(ROOT_PATH.'classes/dao/editorialuserlibrary.class.php');
function libraryResponse($success,$data=array(),$status=200){http_response_code($status);echo json_encode(array_merge(array('success'=>(bool)$success),$data));exit;}
$customerId=(int)($_SESSION['store_customerId']??0);
if(!$customerId)libraryResponse(false,array('message'=>'Bạn cần đăng nhập.'),401);
$action=trim((string)($_POST['action']??$_GET['action']??'list'));
$library=new EditorialUserLibrary(1);
if($action==='list'){ $type=trim((string)($_GET['type']??'')); $key=trim((string)($_GET['key']??'')); if($key!=='')libraryResponse(true,array('item'=>$library->getItem($customerId,$type,$key))); libraryResponse(true,array('items'=>$library->getItems($customerId,$type))); }
if($_SERVER['REQUEST_METHOD']!=='POST')libraryResponse(false,array('message'=>'Yêu cầu không hợp lệ.'),405);
if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],(string)($_POST['csrf_token']??'')))libraryResponse(false,array('message'=>'Phiên làm việc đã hết hạn.'),403);
$type=trim((string)($_POST['item_type']??'')); $key=trim((string)($_POST['item_key']??''));
if($action==='remove'){ libraryResponse((bool)$library->removeItem($customerId,$type,$key),array('message'=>'Đã xóa khỏi Không gian đọc.')); }
if($action!=='save')libraryResponse(false,array('message'=>'Thao tác không hợp lệ.'),400);
$payloadRaw=(string)($_POST['payload']??'{}'); $decoded=json_decode($payloadRaw,true); if(!is_array($decoded))$decoded=array();
$payload=json_encode(array('title'=>mb_substr(strip_tags((string)($decoded['title']??'')),0,300),'url'=>mb_substr((string)($decoded['url']??''),0,500),'image'=>mb_substr((string)($decoded['image']??''),0,500),'lang'=>in_array(($decoded['lang']??'vn'),array('vn','en','zh'),true)?$decoded['lang']:'vn'),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$ok=$library->saveItem($customerId,$type,$key,$payload,(int)($_POST['progress']??0)); libraryResponse((bool)$ok,array('message'=>$ok?'Đã đồng bộ Không gian đọc.':'Chưa thể lưu nội dung.'));
