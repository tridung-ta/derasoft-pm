<?php
/*************************************************************************
Addon listing module
----------------------------------------------------------------
Derasoft CMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 28/05/2012
Coder: Mai Minh
**************************************************************************/
$templateFile = 'systemaddon.tpl.html';
include_once(ROOT_PATH.'classes/dao/addons.class.php');
include_once(ROOT_PATH.'classes/dao/events.class.php');
$addons = new Addons($storeId);
$events = new Events($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
$amessages['system'] => '/'.ADMIN_SCRIPT.'?op=system',
$amessages['system_addon'] => '/'.ADMIN_SCRIPT.'?op=system&act=addon',
$amessages['list_item'] => '');

$tabLink = '/'.ADMIN_SCRIPT.'?op=system&act=addon';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
$amessages['add_new'] => $tabLink.'&mod=add',
$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');			
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',1);

$gallery_root = ROOT_PATH."addons";
if(!file_exists($gallery_root)) mkdir("$gallery_root");
$files = scandir($gallery_root);
$addonItem = 2;   #Get start position of first addon in $file array 
$count = count($files);
$countAddon = 0;
while($addonItem < $count){
    $addonFolder = $files[$addonItem];  #Get subfolder in the addons folder 
    $addonFile = $gallery_root.'/'.$addonFolder.'/addon.xml';   #Get file addon.xml in this subfolder
    $doc = new DOMDocument();
    $doc->load($addonFile);
    $plugin = $doc->getElementsByTagName( "plugin" );
    foreach( $plugin as $plug ){
        $name= $plug->getElementsByTagName( "addon" )->item(0)->nodeValue;
        $event= $plug->getElementsByTagName( "event" )->item(0)->nodeValue;
        $description= $plug->getElementsByTagName( "description" )->item(0)->nodeValue;

        $checkAddon = $addons->checkDuplicate($name);
        if($checkAddon == 0){
            $listAddon[$countAddon]['name'] = $name;
            $listAddon[$countAddon]['event'] = $event;
            $listAddon[$countAddon]['description'] = $description;
        }
        $countAddon++;
    }
    $addonItem++;
}
if(isset($_POST['doo'])){
    $validate = validateData($request);
    if($_POST['doo'] =='install'){
        $id = $request->element('id');
        if(isset($id)){
            $name = $_POST['name'][$id];
            $event = $_POST['event'][$id];
            $description = $_POST['description'][$id];
            $id_event = $events->getIdFromName($event);
            if(!$id_event){
                $validate['INPUT']['id']['message'] = $amessages['not_id_event'];
                $validate['INPUT']['id']['error'] = 1;
                $validate['invalid'] = 1;
                $template->assign('error',$validate);
                
            }else{
                    $data = array('store_id' => $storeId,
						  'name' => $name,					  
						  'event' => $id_event,
						  'description' => $description,
						  'position' => 0,
						  'status' => 1);
                    $addons->addData($data);
                    header('location:'.'/'.ADMIN_SCRIPT."?op=system&act=addon&mod=list&error=good");

            }
            
            
        }
    }
}
if(isset($listAddon)) $template->assign('listAddon',$listAddon);
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	
	if($error['INPUT']['name']['error'] ) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>