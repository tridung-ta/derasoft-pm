<?php
/*************************************************************************
Adding product module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd
Coder: Mai Minh
Email: info@derasoft.com
Last updated: 10/05/2012
Checked by: Mai Minh (10/05/2012)
**************************************************************************/
$userInfo->checkPermission('product','add');
$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH.'classes/dao/productcategories.class.php');
include_once(ROOT_PATH.'classes/dao/products.class.php');
include_once(ROOT_PATH.'classes/dao/productoptions.class.php');
include_once(ROOT_PATH."classes/data/textfilter.class.php");
include_once(ROOT_PATH.'classes/dao/productsize.class.php');
$productsize = new ProductSize();
$template->assign('productsize',$productsize);
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$productOptions = new ProductOptions($storeId);
$topNav = array($amessages['dash_board'] => '/'.ADMIN_SCRIPT.'?op=dashboard',
				$amessages['manage_website'] => '/'.ADMIN_SCRIPT.'?op=manage',
				$amessages['manage_product'] => '/'.ADMIN_SCRIPT.'?op=manage&act=product',
				$amessages['add_new_product'] => '');
$tabLink = '/'.ADMIN_SCRIPT.'?op=manage&act=product';
$listTabs = array($amessages['list_item'] => $tabLink.'&mod=list',
				$amessages['add_new'] => $tabLink.'&mod=add',
				$amessages['list_category'] => $tabLink.'&mod=listcategory',
				$amessages['add_product_category'] => $tabLink.'&mod=addcategory',
				$amessages['list_product_option'] => $tabLink.'&mod=listoption',
				$amessages['add_product_option'] => '#',
				$amessages['clean_trash'] => $tabLink.'&mod=cleantrash');
$template->assign('listTabs',$listTabs);
$template->assign('currentTab',6);
$result_code = $request->element('rcode');
if($result_code) $template->assign('result_code',$result_code);
#lấy id sản phẩm
$id=$request->element('id');
$pc=$request->element('pc');
$template->assign('pc',$pc);
# Category combo box
$categoryCombo = $productOptions->generateCombo(1,$pc);
$idcombo=$productOptions->generateComboId(1);
$template->assign('idcombo',$idcombo);
if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

# lấy object option value 
$ObjectSize=$productsize->getObjects(1,"`pid`=$id AND `po_id`=$idcombo");
$template->assign('ObjectSize',$ObjectSize);

$ObjectSizePo=$productsize->getObjects(1,"`po_id`=$idcombo");
$template->assign('ObjectSizePo',$ObjectSizePo);

$Objectoption=$productOptions->getObject($idcombo);
$template->assign('Objectoption',$Objectoption);

$template->assign('id',$id);
#lay object sản phẩm
$objectproduct=$products->getObject($id);
$template->assign('objectproduct',$objectproduct);


# Get list of fields
#$fieldList = $fields->getObjects(1,"`status`='1' AND `module`='product'",array('position' => 'ASC'));
#if($fieldList) $template->assign('fieldList',$fieldList);

# Allow some javascript
$template->assign('ckEditor',1);

# Field types combobox
$typeCombo = optionFieldType1();

if($_POST && $request->element('doo') == 'submit') { # if form is submitted
	//var_dump($_POST);
	# Validate the data input
	$validate = validateData($request);
	// if($validate['invalid']) {	# data input is not in valid form
	// 	$template->assign('error',$validate);	
	// 	# Category combo box
	// 	$categoryCombo = $productOptions->generateCombo(1);
	// 	if($categoryCombo) $template->assign('categoryCombo',$categoryCombo);

	// 	# Type combo
	// 	$template->assign('error',$validate);
	// 	$typeCombo = optionFieldType($request->element('type'));
	// } else { # Valid data input
	// 	# check duplicate product option name
	// 	if($productOptions->checkDuplicate('option_'.$request->element('name'),'name',"`pc_id` = '".$request->element('cat_id')."'")) {
	// 		$validate['INPUT']['name']['message'] = $amessages['name_duplicated'];
	// 		$validate['INPUT']['name']['error'] = 1;
	// 		$validate['invalid'] = 1;
	// 		$template->assign('error',$validate);
	// 	}
		
		# Everything is ok. Add data to DB
		// if(!$validate['invalid']) {
			$value = '';
			
			# Get value list
			if($request->element('type') > 3) {	# combo, list, radio, check
				$matches = array();
				preg_match_all('/^(.+?):(.+)$/m', $request->element('value'), $matches);
				$valueList = array_combine($matches[1], $matches[2]);
				$value = serialize($valueList);
			} elseif($request->element('type') == 3) {	# WYSIWYG
				$value = $request->element('value_wysiwyg');
			} elseif($request->element('type') == 2) {	# Textarea
				$value = $request->element('value_textarea');
			} elseif($request->element('type') == 1) {	# Textbox
				$value = $request->element('value_textbox');
			}
			//var_dump($_POST);
			// $data = array('store_id' => $storeId,
			// 			  //'pc_id' => Filter($request->element('cat_id')),
			// 			  'name' => 'option_'.Filter($request->element('name')),
			// 			  'title' => Filter($request->element('title')),
			// 			  'class' => Filter($request->element('class')),
			// 			  'type' => Filter($request->element('type')),
			// 			  'value' => $value,
			// 			  'position' => Filter($request->element('position')),
			// 			  'status' => Filter($request->element('status')));
			// $idop=$productOptions->addData($data);
			// var_dump($idop);
			// # add product size
			if($_POST['valueoption']){
				for ($i=0; $i < count($_POST['valueoption']) ; $i++) {
					$value=$productsize->getObject($_POST['valueoption'][$i]);
					$id_value=$value->getPid();
					$arr_idvalue=explode(',', $id_value);
					if(!in_array($_POST['id'],$arr_idvalue)){
					if($id_value==""){
						$datasize = array(
							'pid' => $_POST['id']
							 );
					}
					else{
					$datasize = array(
							'pid' => $id_value.",".$_POST['id']
							 );
					}
						$sizeid = $productsize->updateData($datasize,$_POST['valueoption'][$i]);
					}
				}
			}
			# TRƯỜNG HỢP KHÁCH HÀNG TỰ NHẬP OPTION 
			if($_POST['kichco']){
			$flag=0;
			for ($i=0; $i < count($_POST['kichco']) ; $i++) { 
				$datasize = array(
				'pid' => $_POST['id'],
				'po_id' => $_POST['cat_id'],
				'value' => $_POST['kichco'][$i],
				'chenhlech' => $_POST['chenhlech'][$i]	
				 );
				$flag=$productsize->checkExitarray($_POST['kichco'][$i],$poid);
				var_dump($flag);
				if($flag!=0){
					$sizeid = $productsize->updateData($datasize,$flag);
				}else
					$sizeid = $productsize->addData($datasize);

				// var_dump($flag);
			}
			}		
			# Operation tracking
			$trackings->addData(array('store_id'=>$storeId,'username'=>$userInfo->getUsername(),'action'=>sprintf($amessages['tracking']['add_product_option'],$request->element('name')),'date_created'=>date("Y-m-d H:i:s"),'ip'=>$_SERVER['REMOTE_ADDR']));
			header('location:'.'/'.ADMIN_SCRIPT."?op=manage&act=product&mod=addvalueoption&id=".$_POST['id']."&pc=".$_POST['cid']."&lang=vn");
		//}
	//}
}

$template->assign('typeCombo',$typeCombo);

# Ham kiem tra du lieu nguoi dung nhap vao
function validateData($request) {
	global $amessages;
	include_once(ROOT_PATH.'classes/data/validate.class.php');
	$error = array();
	$validate = new Validate();
	$error['INPUT']['cat_id'] = $validate->pasteString($request->element('cat_id'));
	$error['INPUT']['name'] = $validate->validString($request->element('name'),$amessages['name']);
	$error['INPUT']['title'] = $validate->validString($request->element('title'),$amessages['title']);
	$error['INPUT']['class'] = $validate->pasteString($request->element('class'));
	$error['INPUT']['type'] = $validate->validNumber($request->element('type'),$amessages['custom_field_type']);
	$error['INPUT']['value'] = $validate->pasteString($request->element('value'));
	if($request->element('type')>3) $error['INPUT']['value'] = $validate->validString($request->element('value'),$amessages['custom_field_value']);
	$error['INPUT']['value_wysiwyg'] = $validate->pasteString($request->element('value_wysiwyg'));
	$error['INPUT']['value_textarea'] = $validate->pasteString($request->element('value_textarea'));
	$error['INPUT']['value_textbox'] = $validate->pasteString($request->element('value_textbox'));
	
	$error['INPUT']['position'] = $validate->pasteString($request->element('position'));
	$error['INPUT']['status'] = $validate->pasteString($request->element('status'));
	
	if($error['INPUT']['name']['error'] || $error['INPUT']['title']['error'] || $error['INPUT']['type']['error'] || $error['INPUT']['value']['error']) {
		$error['invalid'] = 1;
		$error['message'] = '';
		return $error;
	}
	$error['invalid'] = 0;
	return $error;
}
?>