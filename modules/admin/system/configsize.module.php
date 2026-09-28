<?php

/*************************************************************************
System config down module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
 **************************************************************************/
checkPermission(array(2, 3));
include_once(ROOT_PATH . 'classes/dao/templates.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
$templates = new Templates();
$fields = new Fields($storeId);
$templateFile = 'systemconfig.tpl.html';

$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
    $amessages['system_config'] => '/' . ADMIN_SCRIPT . '?op=system&act=config',
    $amessages['site_down'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=config';
$listTabs = array(
	$amessages['general_config'] => $tabLink . '&mod=general',
	$amessages['trademark'] => $tabLink . '&mod=trademark',
	$amessages['carcompany'] => $tabLink . '&mod=carcompany',
	$amessages['size'] => $tabLink . '&mod=size',
	$amessages['site_down'] => $tabLink . '&mod=down',
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 4);
# Allow some javascript
$template->assign('ckEditor',1);
$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);
$error_code = $request->element('ecode');
if ($error_code) $template->assign('error_code', $error_code);
# Get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='size'", array('position' => 'ASC'),99);
if ($fieldList) $template->assign('fieldList', $fieldList);

if ($_POST) { # if form is submitted
    if ($request->element('doo') == 'cancel') {    # Cancel
        header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=config&mod=size&lang=$lang&ecode=7");
        exit;
    }
    if ($request->element('doo') == 'submit') {
        # Validate the data input
        $validate = validateData($request);
        if ($validate['invalid']) {    # data input is not in valid form
            $template->assign('error', $validate);
        } else { # Valid data input
            # Everything is ok. Update data to DB
            if (!$validate['invalid']) {
				$properties = $estore->getProperties();
				if (!$properties) $properties = array('');
				# Custom fields
				foreach ($fieldList as $field) {
					$properties[$field->getName()] = $request->element($field->getName());
				}
				# End change by Thai Nguyen
				$data = array(
					'properties' => serialize($properties)
				);
				$stores->updateData($data, $storeId);
				$estore = $stores->getObject($storeId);
				$template->assign('item', $estore);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => $amessages['size'], 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=config&mod=size&lang=$lang&rcode=7");
            }
        }
    } else if ($estore) $template->assign('item', $estore);
} else if ($estore) $template->assign('item', $estore);

function validateData($request)
{
    global $amessages;
    include_once(ROOT_PATH . 'classes/data/validate.class.php');
    $error = array();
    $validate = new Validate();
    $error['INPUT']['site_down'] = $validate->pasteString($request->element('site_down'));

    if ($error['INPUT']['site_down']['error']) {
        $error['invalid'] = 1;
        return $error;
    }
    $error['invalid'] = 0;
    return $error;
}
