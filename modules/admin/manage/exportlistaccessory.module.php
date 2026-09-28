<?php

/*************************************************************************
Static listing module
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 28-06-2012
Coder: Thai Nguyen
 **************************************************************************/
$templateFile = 'manageproduct.tpl.html';
include_once(ROOT_PATH . 'PHPExcel.php');
include_once(ROOT_PATH . 'PHPExcel/IOFactory.php');
include_once(ROOT_PATH . 'PHPExcel/Writer/Excel5.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/productoptions.class.php');
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
include_once(ROOT_PATH . 'classes/dao/specifications.class.php');

$productaccessorys = new Productaccessorys($storeId);
$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$productOptions = new ProductOptions($storeId);
$search = new Search($storeId);
$specifications = new Specifications($storeId);

// var_dump($userId);die;
if ($userId) {
    $userIdC = $users->getObject($userId);
    $curId = $userIdC->getId();
    $curType = $userIdC->getType();
    $curUserName = $userIdC->getUserName();
}
if ($request->element('doo') == 'submit') {
   
    $condition="1>0";
    $listItems = $productaccessorys->getObjects(1, $condition, array("id"=>"ASC"),99999);
        if ($listItems) {
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('Danh sách phụ kiện');
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(60);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(30);

            $objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Mã số');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Tên sản phẩm');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', 'Thương hiệu');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', 'loại sản phẩm');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Dòng xe');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', 'Má SP');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', 'Công dụng');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', 'Thông số kỹ thuật');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', 'Kích thước');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', 'Xuất xứ');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', 'Bảo hành');
            $vitri = 2;
            foreach ($listItems as $keyex => $valueex) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $vitri, $keyex+1);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $vitri, $valueex->getName());
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, $productOptions->getNameFromId($valueex->getTrademark()));
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, $valueex->getCatName());
                // $objPHPExcel->getActiveSheet()->setCellValue('E' . $vitri, $valueex->getCarcompany()!="Gốc"?$productOptions->getAllNameFromStringId($valueex->getCarcompany()):"All");
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $vitri, $valueex->getProperty('text_carcompany'));
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $vitri, $valueex->getSpcode());
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $vitri, $valueex->getUses());
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $vitri, $valueex->getSpecifications());
                $objPHPExcel->getActiveSheet()->setCellValue('I' . $vitri, $valueex->getSize());
                $objPHPExcel->getActiveSheet()->setCellValue('J' . $vitri, $valueex->getOrigin()?$specifications->getNameFromId($valueex->getOrigin()):"");
                $objPHPExcel->getActiveSheet()->setCellValue('K' . $vitri, $valueex->getGuarantee());
                $vitri++;
            }
            
            $lastrow = $objPHPExcel->getActiveSheet()->getHighestRow();
	    	$objPHPExcel->getActiveSheet()->getStyle('A1:I' . $lastrow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

            $objPHPExcel->getActiveSheet()
                ->getStyle('A1:I' . $lastrow)
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="DANH-SACH-PHU-KIEN' . date('d-m-Y') . '.xls"');
            header('Cache-Control: max-age=0');
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $objWriter->save('php://output');
            exit();
        }
    
}
