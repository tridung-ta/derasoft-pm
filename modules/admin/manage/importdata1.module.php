<?php

/*************************************************************************
Article listing module
----------------------------------------------------------------
Derasoft CMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 05/05/2012
Coder: Mai Minh
 **************************************************************************/
include_once(ROOT_PATH . 'PHPExcel.php');
include_once(ROOT_PATH . 'PHPExcel/IOFactory.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
include_once(ROOT_PATH . 'classes/dao/carbatterys.class.php');
$carbatterys = new CarBatterys($storeId);
$productaccessorys = new Productaccessorys($storeId);
include_once(ROOT_PATH . 'classes/dao/products.class.php');
$products = new Products($storeId);
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
$productOptions = new ProductOptions($storeId);
include_once(ROOT_PATH . 'classes/dao/specifications.class.php');
$specifications = new Specifications(1);
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
$productCategories = new ProductCategories($storeId);
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
$search = new Search($storeId);
include_once(ROOT_PATH . "classes/dao/imgs.class.php");
$imgs = new Imgs();
$templateFile = 'manageimport.tpl.html';
$listTabs = array(
	"Nhập liêu" => '/' . ADMIN_SCRIPT . '?op=manage&act=import&mod=data',
	"Xuất liêu" => '/' . ADMIN_SCRIPT . '?op=manage&act=export&mod=data',
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);
// # Get parameters
if ($_POST) {
	$file_type = $_FILES['linkfile']['type'];
	if ($file_type == "application/vnd.ms-excel" || $file_type == "application/x-ms-excel" || $file_type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
		$filename = $_FILES["linkfile"]["name"];
		move_uploaded_file($_FILES["linkfile"]["tmp_name"], "./upload/1/excel" . $filename);
		$objPHPExcel = PHPExcel_IOFactory::load("./upload/1/excel" . $filename);
		$type = $request->element("type");
		$dataArray = [];
		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
			$worksheetTitle = $worksheet->getTitle();
			$highestRow = $worksheet->getHighestRow(); // e.g. 10
			$highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
			for ($row = 2; $row <= $highestRow; $row++) {
				$cell = $worksheet->getCellByColumnAndRow(0, $row); #Tên sản phẩm
				$getName = $cell->getValue();
				array_push($dataArray, $getName);
			}
		}
		var_dump(implode(';', $dataArray));
		die;
	}
}
