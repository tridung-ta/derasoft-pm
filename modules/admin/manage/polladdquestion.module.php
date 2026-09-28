<?php
/*************************************************************************
Adding question module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Email: info@derasoft.com
Last updated: 28/06/2012
Coder: Thai Nguyen
**************************************************************************/
$userInfo->checkPermission('poll','add');
$templateFile = 'managearticle.tpl.html';
include_once(ROOT_PATH.'classes/dao/answers.class.php');
include_once(ROOT_PATH.'classes/dao/questions.class.php');
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$question = new Questions();
$answer = new Answers();
$fields = new Fields($storeId);
$gallery_root = ROOT_PATH."upload/$storeId/";
$gallery_path = $gallery_root."articles/";
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_poll'] => '/'.ADMIN_SCRIPT.'?op=manage&act=poll',
				$amessages['add_question'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=poll';
$listTabs = array($amessages['list_answer'] => $tabLink.'&mod=list',
				$amessages['add_answer'] => $tabLink.'&mod=addanswer',
				$amessages['list_question'] => $tabLink.'&mod=listquestion',
				$amessages['add_question'] => $tabLink.'&mod=addquestion',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',4);

$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Category combo box
$questionCombo = $question->createComboBox();
if($questionCombo) $template->assign('questionCombo',$questionCombo);

# Get list of custom fields for question
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='question'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);

if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	# Validate the data input
	$validate = validateData($request);
	if($validate['invalid']) {	# data input is not in valid form
		$template->assign('error',$validate);	
		# Category combo box
		$questionCombo = $question->createComboBox();
if($questionCombo) $template->assign('questionCombo',$questionCombo);

	} else { # Valid data input
		# check duplicate article name
		if($estore->getProperty('check_duplicate_article_title')) {
			if($answer->checkDuplicate($request->element('title'),'title',"qid = '".$request->element('qId')."'")) {
				$validate['INPUT']['title']['message'] = $amessages['title_duplicated'];
				$validate['INPUT']['title']['error'] = 1;
				$validate['invalid'] = 1;
				$template->assign('error',$validate);
			}
		}
		# Check if duplicate slug
		$slug = TextFilter::urlize($request->element('title'),false,'-');
		$i = 0;
		$dup = 1;
		while($dup) {
			$dup = $question->checkDuplicate($slug.($i?'-'.$i:''),'slug',"id = '".$request->element('id')."'");
			if($dup) $i++;
		}
		$slug .= $i?'-'.$i:'';
		
		# Everything is ok. Add data to DB
		if(!$validate['invalid']) {
			$properties = array('');
			# Custom fields
			foreach($fieldList as $field) {
				$properties[$field->getName()] = $request->element($field->getName());
			}
			
			# End File upload
			$data = array('slug' => $slug,
						  'title' => Filter($request->element('title')),
						  'position' => $request->element('position'),
						  'status' => $request->element('status'),
						  'properties' => serialize($properties),
						 );
			$newId = $question->addData($data);
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_question'],$request->element('title')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=poll&mod=listquestion&qId=".$request->element('qId')."&rcode=6");
		}
	}
}

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['title'] = $validate->validString($request->element('title'),$amessages['title']);
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	$error['INPUT']['answer1'] = $validate->pasteString($request->element('answer1'));
	$error['INPUT']['answer2'] = $validate->pasteString($request->element('answer2'));
	$error['INPUT']['answer3'] = $validate->pasteString($request->element('answer3'));
	
	# Paste value of custom fields
	global $fieldList;
	foreach($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}
	if($error['INPUT']['title']['error'] ) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>