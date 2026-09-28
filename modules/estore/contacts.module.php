<?php
include_once(ROOT_PATH."classes/data/validate.class.php");
include_once(ROOT_PATH."includes/functions.php");
$templateFile = 'contacts.tpl.html';

include_once(ROOT_PATH.'classes/dao/products.class.php');
$products = new Products($sId);
$template->assign('products',$products);
$template->assign('sId',$sId);
	if($_POST){
	$fullname	= $request->element('fullname','');
	$email		= $request->element('email','');
	$tel		=$request->element('tel','');
	$address	=$request->element('address','');
	$details	=$request->element('details','');
	$sId		=$request->element('sId');
	$created	= date('Y-m-d H:i');
	#validate
	$listerror = validateData($request);
	$checkerror = checkError($listerror);
	if($checkerror == 0){
		$emailstore = "kque.tran@gmail.com";
		$To          = strip_tags($emailstore);
		$from   = $email;
		$Subject     =$messages["contact_email_title"];
		$body = sprintf($messages["contact_email_body"],$fullname,$address,$email,$tel,$details);
		$header = "Content-type: text/html; charset=utf-8\r\nFrom: $from\r\nReply-to: $from";
		$ok = mail($To,$Subject,$body,$header);
		$infoText = $messages['contact_success'];
		unset($_SESSION['rand_code']);
		$infoClass = "infoText";
		$template->assign("infoText",$infoText);
		$template->assign("infoClass",$infoClass);
	}else{
		$template->assign("infoText",$messages['contact_error']);
		$template->assign("infoClass","infoClass");				
		$template->assign('fullname',$fullname);
		$template->assign('email',$email);
		$template->assign('tel',$tel);
		$template->assign('address',$address);
		$template->assign('details',$details);
		$infoClass = "error";
		$template->assign('infoClass',$infoClass);
		$template->assign('listerror',$listerror);
	}
}
# Generate the navigation bar
$navigationItems[] = array('name' => $estore->getName(), 'url' => '/', 'current' => '0');
$navigationItems[] = array('name' => $messages['contacts'], 'url' => Url::genUrl('contacts',''), 'current' => '1');
$template->assign('navigationItems',$navigationItems);
# support
include_once(ROOT_PATH.'classes/dao/support.class.php');
$support = new Support();
$supportItems = $support->getObjects(1,"status=1", array('position'=>'ASC'));
$template->assign("supportItems",$supportItems);

function validateData($request) {
		global $messages;
		$error = array();
		$validate = new Validate();		
		$error['fullname'] = $validate->validString($messages['fullname'],$request->element('fullname'));
		$error['email'] = $validate->validEmail($messages['email'],$request->element('email'));
		$error['details'] = $validate->validString($messages['details'],$request->element('details'));
		if(strtolower($request->element('code'))!= strtolower($_SESSION['rand_code'])) $error['code'] = $messages['invalid_security_code'];
		else $error['code'] = NULL;
		return $error;
		}
?>