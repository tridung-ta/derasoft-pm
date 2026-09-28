<?php
/*************************************************************************
question listing module
----------------------------------------------------------------
Derasoft CMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 28/06/2012
Coder: Thai Nguyen
**************************************************************************/
$userInfo->checkPermission('poll','view');
$templateFile = 'managepoll.tpl.html';
include_once(ROOT_PATH.'classes/dao/answers.class.php');
include_once(ROOT_PATH.'classes/dao/questions.class.php');
$answer = new Answers();
$question = new Questions();
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_poll'] => '/'.ADMIN_SCRIPT.'?op=manage&act=poll',
				$amessages['list_question'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=poll';
$listTabs = array($amessages['list_answer'] => $tabLink.'&mod=list',
				$amessages['list_question'] => $tabLink.'&mod=listquestion',
				$amessages['add_answer'] => $tabLink.'&mod=addanswer',
				$amessages['list_question'] => $tabLink.'&mod=listquestion',
				$amessages['add_question'] => $tabLink.'&mod=addquestion',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

# Get parameters
$items_per_page = $request->element('ipp')?$request->element('ipp'):DEFAULT_ADMIN_ROWS_PER_PAGE;
if($items_per_page) $template->assign('ipp',$items_per_page);
$page = $request->element('pg')?$request->element('pg'):1;
if($page) $template->assign('pg',$page);
$sort_key = $request->element('sk')?$request->element('sk'):'id';
if($sort_key) $template->assign('sk',$sort_key);
$sort_direction = $request->element('sd')?$request->element('sd'):'DESC';
if($sort_direction) $template->assign('sd',$sort_direction);
$do = $request->element('doo')?$request->element('doo'):'';
if($do) $template->assign('do',$do);
$kw = $request->element('kw')?$request->element('kw'):'';
if($kw) $template->assign('kw',$kw);
$qId = $request->element('qId','-1');
if($qId>=0) {
	//$gfId = $question->getParentIdFromId($qId);
	$template->assign('qId',$qId);
	//$template->assign('gfId',$gfId);
	$topNav[$amessages['list_item']] = '/'.ADMIN_SCRIPT.'?op=manage&act=poll&mod=list';
	//$topNav[$articleCategories->getNameFromId($pId)] = '';
}

# Build WHERE condition
$condition = $qId>0?"`qid` = '$qId'":"1>0";
if($kw) $condition = "(`id`='$kw' OR `slug` LIKE '%$kw%' OR `title` LIKE '%$kw%')";
$pages_condition = $condition;
$sort = array($sort_key => $sort_direction);

# Page navigation
$rowsPages = $question->getNumItems('id', $pages_condition,$items_per_page);
$template->assign('rowsPages',$rowsPages);
if($page < 1) $page = 1;
if($page > $rowsPages['pages']) $page = $rowsPages['pages'];
$start_num = ($page-1)*$items_per_page+1;
$template->assign('startNum',$start_num);
$url = '/'.ADMIN_SCRIPT."?op=manage&act=poll&mod=listquestion&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&qId=$qId&pg=%d";
$pager = Url::genPager($url,$rowsPages['pages'],$page);
$template->assign('pager',$pager);

# Get objects
 $listItems = $question->getObjects($page,$condition,$sort,$items_per_page);
if($listItems) $template->assign('listItems',$listItems);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
$error_code = $request->element('ecode');
if($error_code) $template->assign('error_code',$error_code);

# Link
$link = '/'.ADMIN_SCRIPT."?op=manage&act=poll&mod=listquestion&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&qId=$qId&pg=$page";
$template->assign('link',$link);

# Show URL Popup
$template->assign('urlPopup',1);

#bottom Action Combo
$questionCombo = $question->createComboBox();
if($questionCombo) $template->assign('questionCombo',$questionCombo);

if($_POST) {
	switch($do) {
		
		case 'enable':
			$userInfo->checkPermission('poll','edit');
			$id = $request->element('id');
			if($id) {
				$question->changeStatus($id,S_ENABLED);
				$result_code = 1;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_question'],$question->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {		
				$ids = $request->element('ids');
				if($ids) {
					$listQuestion = '';
					foreach ($ids as $id) {
						$question->changeStatus($id,S_ENABLED);
						$listQuestion .= ($listQuestion?',&nbsp;':'').$question->getTitleFromId($id);
					}
					$result_code = 1;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['enable_question'],$listQuestion),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'disable':
			$userInfo->checkPermission('poll','edit');
			$id = $request->element('id');
			if($id) {
				$question->changeStatus($id,S_DISABLED);
				$result_code = 2;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_question'],$question->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listQuestion = '';
					foreach ($ids as $id) {
						$question->changeStatus($id,S_DISABLED);
						$listQuestion .= ($listQuestion?',&nbsp;':'').$question->getTitleFromId($id);
					}
					$result_code = 2;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['disable_question'],$listQuestion),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
		case 'delete':
			$userInfo->checkPermission('poll','delete');
			$id = $request->element('id');
			if($id) {
				$question->changeStatus($id,S_DELETED);
				$result_code = 3;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_question'],$question->getTitleFromId($id)),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else {
				$ids = $request->element('ids');
				if($ids) {
					$listQuestion = '';
					foreach ($ids as $id) {
						$question->changeStatus($id,S_DELETED);
						$listQuestion .= ($listQuestion?',&nbsp;':'').$question->getTitleFromId($id);
					}
					$result_code = 3;
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['delete_question'],$listQuestion),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
				} else $error_code = 5;
			}
			break;
	
		case 'changeposition':
			$userInfo->checkPermission('article','edit');
			$positions = $request->element('positions');
			if($positions) {
				foreach ($positions as $key=>$value) {
					$question->changePosition($key,$value);
				}
				$result_code = 4;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['change_question_position'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			} else $error_code = 5;
			break;
		case 'cleantrash':
			$userInfo->checkPermission('poll','clean',0);
			$cleanQuestion = $request->element('question'); 
			$cleanItems = $request->element('items');
			if($cleanQuestion == 1) { 
				$question->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_question'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			}
			if($cleanItems == 1) {
				$question->cleanTrash();
				$result_code = 5;
				# Operation tracking
				$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>$amessages['tracking']['clean_trash_question'],'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			}
			break;		
		case 'cancel':		
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=poll&mod=list&lang=$lang&ecode=7&qId=$qId");
			exit;
			break;
	}
	header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=poll&mod=listquestion&doo=$do&kw=$kw&lang=$lang&ipp=$items_per_page&sk=$sort_key&sd=$sort_direction&pg=$page&ecode=$error_code&rcode=$result_code&qId=$qId");
} else {

}
?>