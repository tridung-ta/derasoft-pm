<?php
/*************************************************************************
Edit file upload module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 26/06/2025
**************************************************************************/
# Check permission
$userInfo->checkPermission('upload', 'edit');

$templateFile = 'manageupload.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
$fields = new Fields($storeId);
$uploads = new Uploads($storeId);

# Top navigation
$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['manage_website'] => '/' . ADMIN_SCRIPT . '?op=manage',
    $amessages['manage_gallery'] => ''
);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=upload';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
	$amessages['edit_upload'] => '#',
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash',

);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Allow some javascript
$template->assign('ckEditor', 0);
$template->assign('allowed_file_types',str_replace(['|','$'],[', ',''],ALLOW_FILE_TYPES));

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code', $result_code);

# Get UploadInfo object from ID
$id = (int)$request->element('id');	# Cast the ID to integer to prevent SQL injection
$template->assign('id', $id);
$uploadInfo = $uploads->getObject($id,'u.id');

if(!$uploadInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	
	# Submit form
	if ($_POST && $request->element('doo') == 'submit') {
		# Check if the name is updated
		if($request->element('name') <> $uploadInfo->getName()) { 
			# Path to the album folder
			$path = ROOT_PATH.$uploadInfo->getPath().'/';
			$textFilter = new TextFilter();
			
			# Initialize data array with updater information
			$data = array('name' => $request->element('name'),
						  'updater_id' => $userInfo->getId(),
						  'date_updated' => date("Y-m-d H:i:s"));
			
			# Normalize new name
			$new_file_name_normalized = strtolower(str_replace(' ', '-', strtolower($textFilter->cleanVietnamese($request->element('name')))))."_".rand(10000,99999);
			
			# Change original file name if exists
			if($uploadInfo->getUrlO()) {
				$data['url_o'] = changeFileName($path,$uploadInfo->getUrlO(),$new_file_name_normalized,"_o");
			}
			
			# Change large file name if exists
			if($uploadInfo->getUrlL()) {
				$data['url_l'] = changeFileName($path,$uploadInfo->getUrlL(),$new_file_name_normalized,"_l");
			}
			
			# Change medium file name if exists
			if($uploadInfo->getUrlM()) {
				$data['url_m'] = changeFileName($path,$uploadInfo->getUrlM(),$new_file_name_normalized,"_m");
			}
			
			# Change thumbnail file name if exists
			if($uploadInfo->getUrlT()) {
				$data['url_t'] = changeFileName($path,$uploadInfo->getUrlT(),$new_file_name_normalized,"_t");
			}
			
			# Change avatar file name if exists
			if($uploadInfo->getUrlA()) {
				$data['url_a'] = changeFileName($path,$uploadInfo->getUrlA(),$new_file_name_normalized,"_a");
			}
			
			# Update to database
			$uploads->updateData($data, $id);
		} # End if($request->element('name')
		header("Location: /admin.php?op=manage&act=upload&mod=edit&id=$id&rcode=7");
	} else { # Load upload object to edit
		$template->assign('item', $uploadInfo);
	} # End if ($_POST && $request->element('doo') == 'submit')
} # End if(!$uploadInfo) {

# Folder like /public_html/upload/1/2025/
# $old_file_name like filename_o.jpg
# $new_file_name_normalized like new_filename
# Suffix like "_o"
# Return new_filename_o.jpg
#---------------------------
function changeFileName($folder,$old_file_name,$new_file_name_normalized,$suffix='') {
	$last_dot_position = strrpos($old_file_name, '.');
	$extension = substr($old_file_name, $last_dot_position + 1);
	$new_file_name = $new_file_name_normalized.$suffix.'.'.$extension;
	rename($folder.$old_file_name,$folder.$new_file_name);
	return $new_file_name;	
}
?>