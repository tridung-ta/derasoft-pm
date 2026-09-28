<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors',0); ini_set('display_startup_errors',0); error_reporting(0);
if(session_status()===PHP_SESSION_NONE)session_start();
include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH.'classes/dao/carts.class.php');
include_once(ROOT_PATH.'includes/editorial_mail.inc.php');
function editorialLoginResponse($success,$message,$field='form',$extra=array()){echo json_encode(array_merge(array('success'=>(bool)$success,'field'=>$field,'message'=>$message),$extra),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'||($_POST['op']??'')!=='login')editorialLoginResponse(false,'Yêu cầu không hợp lệ.');
if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],(string)($_POST['csrf_token']??'')))editorialLoginResponse(false,'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.');
$username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');
if($username===''||$password==='')editorialLoginResponse(false,'Vui lòng nhập đầy đủ tài khoản và mật khẩu.');
$customers=new Customers(1);$user=$customers->getObject($username,'username');if(!$user)$user=$customers->getObject(strtolower($username),'email');
if(!$user||!password_verify($password,$user->getPassword()))editorialLoginResponse(false,'Tài khoản hoặc mật khẩu không đúng.','password');
if(editorialEmailVerificationRequired()&&$user->getStatus()==0)editorialLoginResponse(false,'Tài khoản chưa xác thực email.','not_verified',array('email'=>$user->getEmail()));
session_regenerate_id(true);
$carts=new Carts(1);$carts->mergeCart($user->getId());
$customers->updateData(array('last_login'=>date('Y-m-d H:i:s')),$user->getId());
$_SESSION['store_customerId']=$user->getId();$_SESSION['username']=$user->getUsername();
$redirect=isset($_POST['next'])&&is_scalar($_POST['next'])?trim((string)$_POST['next']):'/';
if($redirect===''||$redirect[0]!=='/'||strpos($redirect,'//')===0||preg_match('#^[a-z][a-z0-9+.-]*:#i',$redirect))$redirect='/';
$redirectPath=parse_url($redirect,PHP_URL_PATH);if(in_array($redirectPath,array('/404.html','/dang-nhap','/dang-ky','/en/login','/en/register','/zh/login','/zh/register','/ajax.php'),true))$redirect='/';
session_write_close();
editorialLoginResponse(true,'Đăng nhập thành công.','form',array('redirect'=>$redirect));