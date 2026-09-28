<?php
/*************************************************************************
Upload Add module
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Last updated: 26/06/2025
**************************************************************************/
# Check permission
$userInfo->checkPermission('upload', 'add');

$templateFile = 'manageupload.tpl.html';
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
$fields = new Fields($storeId);
$uploadAlbums = new UploadAlbums($storeId);
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
	$amessages['add_new'] => $tabLink . '&mod=add',

);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Allow some javascript
$template->assign('ckEditor',0);
$template->assign('allowed_file_types',str_replace(['|','$'],[', ',''],ALLOW_FILE_TYPES));

$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";

$result_code = $request->element('result_code');
if($result_code) $template->assign('result_code', $result_code);

if ($_POST && $request->element('doo') == 'submit') {
	# Note for new file uploads:
	# Gallery root folder: ROOT_PATH.GALLERY_FOLDER."/".storeId."/".this_year
	# For example, for estore 1, all images in 2025 should be uploaded to folder:
	# /public_html/upload/1/2025
	# The system will check if album "this_year" exists. If not exists, it creates the album
	# The system also check if folder for this album is exists. If not exists, it also creates it
	# If the system cannot create folder, it returns permission error via template variable $albumCreateError
	# End note
	
	# Check if the image upload album for this year exists. If not exists, create it
	$thisYearAlbum = $uploadAlbums->getObject(date("Y"),"name");
	if(!$thisYearAlbum) { # Album for this year not exists, create it
		$newAlbumData = array('store_id' => $storeId,
							 'name' => date("Y"),
							 'status' => 1,
							 'folder' => date("Y"),
							 'date_created' => date("Y-m-d H:i:s"),
							 'properties' => serialize(array()));
		$thisYearAlbumId = $uploadAlbums->addData($newAlbumData);
		
		# Get album object that just created
		$thisYearAlbum = $uploadAlbums->getObject($thisYearAlbumId);
	} else { # Album for this year exists
		# Return error if cannot create the image upload folder
		if($thisYearAlbum->getProperty('create_album_error')) {
			$template->assign('albumCreateError',$amessages['cannot_create_folder']." ".$thisYearAlbum->getAbsoluteFolder());
		}
	}	
	
	# Check if album and folder exists now. If not, stop here and ask the staff to set permission for the folder
	if(!$thisYearAlbum->getProperty('create_album_error')) {
		# Files upload
		$files = isset($_FILES['files']) ? $_FILES['files'] : '';
		$newFileIds = [];
		if (is_array($files)) {
			for ($i = 0; $i < count($files['name']); $i++) {
				$newFileId = $uploads->uploadFile($thisYearAlbum,
												  $files['name'][$i],
												  $files['tmp_name'][$i],
												  $files['size'][$i],
												  'none',
												  $userInfo->getId(),
												  0);
				if($newFileId) array_push($newFileIds, $newFileId);
			} # End for ($i =0;
		} # End if (is_array($files)) {		
	} # End if(!$thisYearAlbum->getProperty('create_album_error')) {
	
	# Operation tracking
	$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['add_upload'], $newFileIds?implode(',',$newFileIds):''), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

	header('location:' .'/' . ADMIN_SCRIPT . "?op=manage&act=upload&mod=list&rcode=6");
} # End if ($_POST && $request->element('doo') == 'submit') {
?>