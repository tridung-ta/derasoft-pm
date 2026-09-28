<?php
include_once(ROOT_PATH."classes/dao/provinces.class.php");
include_once(ROOT_PATH.'classes/dao/emails.class.php');
include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH.'classes/dao/emails.class.php');
include_once(ROOT_PATH."classes/data/validate.class.php");

$templateFile = "register.tpl.html";
$customers= new Customers();
//$emails = new Emails($sId);
$plus = $request->element('plus');

if ($plus == 'apply'){ 
	$pageTitle = $messages['active_profile'];
	$templateFile = "login.tpl.html";
	$cId = $request->element('id');
	$customerInfo = $customers->getObject($cId,'id');
	if ($customerInfo){
		$customers->changeStatus($cId ,1);
		$template->assign("active_user_succes",$messages['active_user_succes']);
	}
	
}else{
if($_POST){	
 $validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);	
		$template->assign("infoClass","error");
		$template->assign('note',$messages["register_error"]);
	} else { # Valid data input
			# Password
			$pass = $request->element('password','');
			$password = md5($pass);
			#Property 
			$properties = array('');
			$properties = array('cell' => $request->element('cell'));
			# End property
			$data = array('store_id'=>1,
						  'area_id'  => $request->element('district'),
						  'username' => $request->element('cell'),
						  'password' => $password,
						  'fullname' => Filter($request->element('fullname')),
						  'address' => Filter($request->element('address')),
						  'email' => Filter($request->element('email')),
						  'tel' => Filter($request->element('tel')),
						  'properties' => serialize($properties),
						  'date_created' => date("Y-m-d H:i:s"),
						  'status' => '');
			$newId = $customers->addData($data);
			$emails= new Emails();
			$emailItem = $emails->getObject('REGISTRATION_EMAIL','name',"status = 1");
			if($emailItem){
				$tokens = explode(',',$emailItem->getTokens());
				$bodyEmail = $emailItem->getContent();
				$subjectEmail = $emailItem->getTitle();
				foreach($tokens as $token){
					$subjectEmail = str_replace("{".$token."}",'%s',$subjectEmail);
					$bodyEmail = str_replace("{".$token."}",'%s',$bodyEmail); 
				}
			}else{
				$subjectEmail = $messages["register_email_title"];
				$bodyEmail = $messages["register_email_body"];
			}
			$To = $request->element('email');
			$url = DOMAIN."/register/apply-".$newId.".html";
			$Subject     =sprintf($subjectEmail,DOMAIN);
			//$from=$estore->getEmail();
			$from="thai.nguyen@derasoft.com";
			$Body = sprintf($bodyEmail,$request->element('fullname'),DOMAIN,DOMAIN,$request->element('username'),$pass,$url,$url,DOMAIN,DOMAIN,DOMAIN,DOMAIN);
			$header = "Content-type: text/html; charset=utf-8\r\nFrom: $from\r\nReply-to: $from";
			$ok = mail($To,$Subject,$Body,$header);
			unset($_SESSION['rand_code']);
		//	header('location:/index.php?op=estore&act=register&rcode=1');
		$template->assign("register_success",$messages['register_success']);
		}
}
}
function validateData($request) {
	global $messages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['cell'] = $validate->validNumber($request->element('cell'),$messages['cell']);
$code = strtolower($request->element('captcha'));
	$error['INPUT']['code'] = $validate->validCode($code,$messages['security']);
	if($error['INPUT']['cell']['error']||$error['INPUT']['code']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}

?>