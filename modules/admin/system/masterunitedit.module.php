<?php
$templateFile = 'systemmasterunit.tpl.html';
include_once(ROOT_PATH . 'classes/dao/units.class.php');
$units = new Units($storeId);
$topNav = array(
	$amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
	$amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
	$amessages['system_unit'] => '/' . ADMIN_SCRIPT . '?op=system&act=master',
	$amessages['edit_unit'] => '');

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=master';
$listTabs = array(
	$amessages['list_item'] => $tabLink . '&mod=unitlist',
	$amessages['edit_unit'] => '',
	$amessages['add_new'] => $tabLink . '&mod=unitadd',
	$amessages['clean_trash'] => $tabLink . '&mod=unitcleantrash');
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

# Result code
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);

# Get unit ID from request
$id = $request->element('id');
if ($id) $template->assign('id', $id);
$itemInfo = $units->getObject($id);

if (!$itemInfo) {
	$template->assign('validItem', 0);
} else {
	$template->assign('validItem', 1);
	# Allow some javascript
	$template->assign('ckEditor', 1);

	if ($_POST && $request->element('doo') == 'submit') { # if form is submitted
		# Validate the data input
		$validate = validateData($request);
		if ($validate['invalid']) {	# data input is not in valid form
			$template->assign('error', $validate);
			$template->assign('itemInfo', $itemInfo);
		} else { # Valid data input		
			# Everything is ok. Update data to DB
			if (!$validate['invalid']) {

				$data = array(
                    'store_id' => $storeId,
                    'unit_code' => Filter($request->element('unit_code')),
                    'symbol' => Filter($request->element('symbol')),
                    'name' => Filter($request->element('name')),
                    'type' => Filter($request->element('type')),
                    'conversion_rate_to_base' => (int)Filter($request->element('conversion_rate_to_base')),
                    'base_unit_code' => Filter($request->element('base_unit_code')),
                    'description' => Filter($request->element('description')),
                    'status' => (int)Filter($request->element('status')),
                    'position' => is_numeric((int)Filter($request->element('position'))) ? (int)Filter($request->element('position')) : 0,
                    'date_created' => date("Y-m-d H:i:s")
                );
				$units->updateData($data, $id);

				# Operation tracking
				$trackings->addData(array('store_id' => $storeId, 'username' => $userInfo->getUsername(), 'action' => sprintf($amessages['tracking']['edit_unit'], $units->getNameFromId($id)), 'date_created' => date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']));

				# Redirect to editing page
				header('location:' . '/' . ADMIN_SCRIPT . "?op=system&act=master&mod=unitedit&lang=$lang&id=$id&rcode=7");
			}
		}
	} else { # Load information to edit
		$template->assign('item', $itemInfo);

	}
}

# function validation
function validateData($request)
{
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['unit_code'] = $validate->validString($request->element('unit_code'),$amessages['unit_code']);
	$error['INPUT']['symbol'] = $validate->validString($request->element('symbol'),$amessages['unit_symbol']);
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['unit_name']);
	$error['INPUT']['type'] = $validate->validString($request->element('type'),$amessages['unit_type']);
	$error['INPUT']['conversion_rate_to_base'] = $validate->pasteString($request->element('conversion_rate_to_base'),$amessages['unit_conversion_rate_to_base']);
	$error['INPUT']['base_unit_code'] = $validate->validString($request->element('base_unit_code'),$amessages['base_unit_code']);
	$error['INPUT']['description'] = $validate->validString($request->element('description'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
    $error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	if($error['INPUT']['unit_code']['error'] || $error['INPUT']['symbol']['error'] || $error['INPUT']['type']['error'] || $error['INPUT']['name']['error'] || $error['INPUT']['conversion_rate_to_base']['error'] || $error['INPUT']['base_unit_code']['error']) {
		$error['invalid'] = 1;
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
