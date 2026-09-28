<?php
/*************************************************************************
Editing article module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Last updated: 01/05/2012
Coder: Mai Minh
Checked by: Mai Minh (19/05/2012)
**************************************************************************/
$userInfo->checkPermission('weblink','edit');
$templateFile = 'manageweblink.tpl.html';
include_once(ROOT_PATH.'classes/dao/weblinks.class.php');
include_once(ROOT_PATH.'classes/dao/fields.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
$weblinks = new Weblinks();
$fields = new Fields($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_weblink'] => '/'.ADMIN_SCRIPT.'?op=manage&act=weblink',
				$amessages['edit_weblink'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=weblink';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['edit_weblink'] => '#',
				$amessages['add_weblink'] => $tabLink.'&mod=add',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',2);

$result_code = $request->element('rcode'); 
if($result_code) $template->assign('result_code',$result_code);
$id = $request->element('id');
if($id) $template->assign('id',$id);
$weblinkInfo = $weblinks->getObject($id);
if(!$weblinkInfo) {
	$template->assign('validItem',0);
} else{
	$template->assign('validItem',1);

	# Allow some javascript
	$template->assign('ckEditor',1);
	if($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Get list of custom fields
		$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='weblink'",array('position' => 'ASC'));
		if($fieldList) $template->assign('fieldList',$fieldList);
		
		# Validate the data input
		$validate = validateData($request);
		if($validate['invalid']) {	# data input is not in valid form
			$template->assign('error',$validate);
			$weblinkInfo = $weblinks->getObject($id);
			$template->assign('itemInfo',$weblinkInfo);
		} else { # Valid data input
	
			# Everything is ok. Update data to DB
			if(!$validate['invalid']) {
				$weblinkInfo = $weblinks->getObject($id);
				if($weblinkInfo) {			
					$properties = $weblinkInfo->getProperties();
					# Custom fields
					foreach($fieldList as $field) {
						$properties[$field->getName()] = $request->element($field->getName());
					}
			
					$data = array('name' => Filter($request->element('name')),
						  'url' => $request->element('url'),
						  'status' => $request->element('status'),
						  'properties' => serialize($properties),
						 );
					$weblinks->updateData($data,$id);
					
					# Operation tracking
					$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['edit_weblink'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
					
					# Redirect to editing page
					header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=weblink&mod=edit&lang=$lang&id=$id&rcode=7");
				}
			}
		}
	} else { # Load article category information to edit
		$template->assign('item',$weblinkInfo);
		
		# Get list of custom fields
		$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='weblink'",array('position' => 'ASC'));
		if($fieldList) $template->assign('fieldList',$fieldList);
	}
}
# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['url'] = $validate->validString($request->element('url'),$amessages['url']);
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	# Paste value of custom fields
	global $fieldList;
	foreach($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}
		
	if($error['INPUT']['name']['error']||$error['INPUT']['url']['error'] ) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>