<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$templateFile = 'managecontact.tpl.html';

require_once ROOT_PATH . 'classes/dao/contacts.class.php';
require_once ROOT_PATH . "classes/data/textfilter.class.php";
include_once(ROOT_PATH.'classes/dao/fields.class.php');
$fields = new Fields($storeId);
$contacts = new Contacts($storeId);

$topNav = array(
  $amessages['dash_board']      => '/' . ADMIN_SCRIPT . '?op=dashboard',
  $amessages['manage_website']  => '/' . ADMIN_SCRIPT . '?op=manage',
  $amessages['manage_contact'] => '/'.ADMIN_SCRIPT.'?op=manage&act=contact',
  $amessages['add_new']         => ''
);
$template->assign('topNav', $topNav);

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=contact';
$listTabs = array(
  $amessages['list_item']   => $tabLink . '&mod=list',
  $amessages['add_new']     => '#',
  $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$result_code = $request->element('rcode');
if ($result_code) $template->assign('result_code', $result_code);

# Get list of custom fields
$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='contact'",array('position' => 'ASC'));
if($fieldList) $template->assign('fieldList',$fieldList);


if ($_POST && $request->element('doo') == 'submit') {
  $validate = validateData($request);
  if ($validate['invalid']) {
    $template->assign('error', $validate);
  } else {
    $status   = (int)$request->element('status', 1);
    $position = (int)$request->element('position', 0);
    $name     = trim($request->element('name', ''));
    $address     = trim($request->element('address', ''));
    $link_map     = trim($request->element('link_map', ''));
    $phone_number     = trim($request->element('phone_number', ''));
    $is_head_office = (int)$request->element('is_head_office', 0);

    # Custom fields
    foreach($fieldList as $field) {
        $properties[$field->getName()] = $request->element($field->getName());
    }

    // Nếu ok thì lưu DB
    if (!$validate['invalid']) {
      $data = array(
        'store_id'     => $storeId,
        'name'         => $name,
        'position'     => $position,
        'link_map'     => $link_map,
        'phone_number' => $phone_number,
        'address'      => $address,
        'status'       => $status,
        'is_head_office' => $is_head_office,
        'properties' => serialize($properties),
      );
      // var_dump($data);die;
      $newId = $contacts->addData($data);

      if ($newId) {
        header('location:/' . ADMIN_SCRIPT . "?op=manage&act=contact&mod=list&rcode=6");
        exit;
      } else {
        $validate['invalid'] = 1;
        $template->assign('error', $validate);
      }
    }
  }
}

function validateData($request) {
  global $amessages;
  require_once ROOT_PATH . 'classes/data/validate.class.php';

  $validate = new Validate();
  $error = array('invalid' => 0, 'INPUT' => array());

  # Paste value of custom fields
	global $fieldList;
	foreach ($fieldList as $field) {
		$error['INPUT'][$field->getName()] = $validate->pasteString($request->element($field->getName()));
		if ($field->getType() == 4 || $field->getType() == 7) {	# Listbox and checkbox
			$error['INPUT'][$field->getName()]['value'] = $request->element($field->getName());
		}
	}

  $error['INPUT']['name']     = $validate->validString($request->element('name'), $amessages['name']);
  $error['INPUT']['link_map']     = $validate->validString($request->element('link_map'), $amessages['link_map']);
  $error['INPUT']['phone_number']     = $validate->validString($request->element('phone_number'), $amessages['manage_phone']);
  $error['INPUT']['address']     = $validate->validString($request->element('address'), $amessages['address']);


if ($error['INPUT']['name']['error'] || $error['INPUT']['link_map']['error'] || $error['INPUT']['phone_number']['error'] || $error['INPUT']['address']['error']) {
		$error['invalid'] = 1;
}

  return $error;
}
