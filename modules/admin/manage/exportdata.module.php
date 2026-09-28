<?php

/*************************************************************************
Article listing module
----------------------------------------------------------------
Derasoft CMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 05/05/2012
Coder: Mai Minh
 **************************************************************************/
#include_once(ROOT_PATH . 'PHPExcel.php');
#include_once(ROOT_PATH . 'PHPExcel/IOFactory.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/productaccessorys.class.php');
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
// include_once(ROOT_PATH . "classes/dao/imgs.class.php");
// $imgs = new Imgs();
$templateFile = 'manageimport.tpl.html';
$listTabs = array(
	"Nhập dữ liệu" => '/' . ADMIN_SCRIPT . '?op=manage&act=import&mod=data',
	"Xuất dữ liệu" => '/' . ADMIN_SCRIPT . '?op=manage&act=export&mod=data',
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

// # Get parameters
if ($_POST && $request->element('doo') == 'submit') {
	$type = $request->element('type');
	if ($type == 1) { #Danh sách lốp xe
		$listItems = $products->getObjects(1, "`status` = '1'", array("id" => "ASC"), 9999);
		if ($listItems) {
			$vitri = 2;
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()->setTitle('Danh sách lốp xe');
			$objPHPExcel->getActiveSheet()->getStyle('A1:W1')->getFont()->setBold(true);
			$objPHPExcel->getActiveSheet()->getStyle('A1:W1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$objPHPExcel->getActiveSheet()->getStyle('A1:W1')->getFill()->getStartColor()->setARGB('00FFFF');

			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(30);
			$objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(10);
			$objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(10);
			$objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(30);
			$objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(30);

			$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Id');
			$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Tên sản phẩm');
			$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Url');
			$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Thương hiệu');
			$objPHPExcel->getActiveSheet()->setCellValue('E1', 'Kích thước');
			$objPHPExcel->getActiveSheet()->setCellValue('F1', 'Giới thiệu');
			$objPHPExcel->getActiveSheet()->setCellValue('G1', 'Giá tiền');
			$objPHPExcel->getActiveSheet()->setCellValue('H1', 'Giá khuyến mãi');
			$objPHPExcel->getActiveSheet()->setCellValue('I1', 'Độ rộng lốp');
			$objPHPExcel->getActiveSheet()->setCellValue('J1', 'Tỷ lệ chiều cao');
			$objPHPExcel->getActiveSheet()->setCellValue('K1', 'Kích thước mâm xe');
			$objPHPExcel->getActiveSheet()->setCellValue('L1', 'Chỉ số tải trọng');
			$objPHPExcel->getActiveSheet()->setCellValue('M1', 'Dòng gai');
			$objPHPExcel->getActiveSheet()->setCellValue('N1', 'Ký hiệu gai');
			$objPHPExcel->getActiveSheet()->setCellValue('O1', 'Thiết kế lốp');
			$objPHPExcel->getActiveSheet()->setCellValue('P1', 'Xuất xứ');
			$objPHPExcel->getActiveSheet()->setCellValue('Q1', 'RunFlat');
			$objPHPExcel->getActiveSheet()->setCellValue('R1', 'Công dụng');
			$objPHPExcel->getActiveSheet()->setCellValue('S1', 'Chính sách bảo hành');
			$objPHPExcel->getActiveSheet()->setCellValue('T1', 'Cam kết');
			$objPHPExcel->getActiveSheet()->setCellValue('U1', 'Ưu đãi');


			foreach ($listItems as $key => $item) {
				$getId = $item->getId();
				$getName = $item->getName();
				$getSlug = $item->getSlug();
				$getTrademark = $productOptions->getNameFromId($item->getTrademark());
				$getSize = $productOptions->getNameFromId($item->getSize());
				$getDescription = $item->getDescription();
				$getPrice = number_format($item->getPrice());
				$getMarketPrice = number_format($item->getMarketPrice());
				$getTireWidth = $item->getTireWidth();
				$getHeightRatio = $item->getHeightRatio();
				$getWheelSize = $item->getWheelSize();
				$getLoadIndex = $item->getLoadIndex();
				$getSpeedIndex = $item->getSpeedIndex();
				if ($specifications->getNameFromId($item->getThornLine())) {
					$getThornLine = $specifications->getNameFromId($item->getThornLine());
				} else {
					$getThornLine = $item->getThornLine();
				}

				$getTireDesign = $specifications->getNameFromId($item->getTireDesign());
				$getOrigin = $specifications->getNameFromId($item->getOrigin());
				$getUses = $specifications->getNameFromId($item->getUses());
				$getWarrantyPolicy = $item->getWarrantyPolicy();
				$getCommit = $item->getCommit();
				$custom_udsp = $item->getProperty("custom_udsp");

				if ($item->getRunFlat() == 2) {
					$runFlat = "Yes";
				} else {
					$runFlat = "No";
				}
				$SymbolThornLine = "";
				if ($item->getSymbolThornLine() == 1) {
					$SymbolThornLine = "A/T";
				}
				if ($item->getSymbolThornLine() == 2) {
					$SymbolThornLine = "RT/S";
				}
				if ($item->getSymbolThornLine() == 3) {
					$SymbolThornLine = "H/T";
				}
				if ($item->getSymbolThornLine() == 4) {
					$SymbolThornLine = "T/A";
				}
				if ($item->getSymbolThornLine() == 5) {
					$SymbolThornLine = "H/L";
				}
				if ($item->getSymbolThornLine() == 6) {
					$SymbolThornLine = "H/P";
				}

				$objPHPExcel->getActiveSheet()->setCellValue('A' . $vitri, $getId);
				$objPHPExcel->getActiveSheet()->setCellValue('B' . $vitri, $getName);
				$objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, $getSlug);
				$objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, $getTrademark);
				$objPHPExcel->getActiveSheet()->setCellValue('E' . $vitri, $getSize);
				$objPHPExcel->getActiveSheet()->setCellValue('F' . $vitri, $getDescription);
				$objPHPExcel->getActiveSheet()->setCellValue('G' . $vitri, $getPrice);
				$objPHPExcel->getActiveSheet()->setCellValue('H' . $vitri, $getMarketPrice);
				$objPHPExcel->getActiveSheet()->setCellValue('I' . $vitri, $getTireWidth);
				$objPHPExcel->getActiveSheet()->setCellValue('J' . $vitri, $getHeightRatio);
				$objPHPExcel->getActiveSheet()->setCellValue('K' . $vitri, $getWheelSize);
				$objPHPExcel->getActiveSheet()->setCellValue('L' . $vitri, $getLoadIndex);
				$objPHPExcel->getActiveSheet()->setCellValue('M' . $vitri, $getThornLine);
				$objPHPExcel->getActiveSheet()->setCellValue('N' . $vitri, $SymbolThornLine);
				$objPHPExcel->getActiveSheet()->setCellValue('O' . $vitri, $getTireDesign);
				$objPHPExcel->getActiveSheet()->setCellValue('P' . $vitri, $getOrigin);
				$objPHPExcel->getActiveSheet()->setCellValue('Q' . $vitri, $runFlat);
				$objPHPExcel->getActiveSheet()->setCellValue('R' . $vitri, $getUses);
				$objPHPExcel->getActiveSheet()->setCellValue('S' . $vitri, $getWarrantyPolicy);
				$objPHPExcel->getActiveSheet()->setCellValue('T' . $vitri, $getCommit);
				$objPHPExcel->getActiveSheet()->setCellValue('U' . $vitri, $custom_udsp);


				$vitri++;
			}

			ob_end_clean();
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="Danh sách lốp xe-' . date('d-m-Y') . '.xls"');
			header('Cache-Control: max-age=0');
			$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
			$objWriter->save('php://output');
			exit();
		}
	}
	if ($type == 2) { #Danh sách kính thước
		$vitri = 2;
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('Danh sách lốp xe');
		$objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getFill()->getStartColor()->setARGB('00FFFF');



		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(70);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Vành');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Size');
		$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Size');
		$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Id');


		for ($i = 13; $i <= 23; $i++) {
			$listItems = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = 30 AND `class` = $i", array("id" => "ASC"), 9999);
			if ($listItems) {
				$objPHPExcel->getActiveSheet()->setCellValue('A' . $vitri, "Vành " . $i);

				foreach ($listItems as $key => $item) {
					$id = $item->getId();
					$name = $item->getName();
					$slug = $item->getSlug();
					$objPHPExcel->getActiveSheet()->setCellValue('B' . $vitri, $name);
					$objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, "https://thanhanautocare.com/lop-xe-" . $slug);
					$objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, $id);
					$vitri++;
				}
			}
		}
		ob_end_clean();
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Danh sách kính thước-' . date('d-m-Y') . '.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save('php://output');
		exit();
	}
	if ($type == 3) { #Danh sách hãng xe
		$vitri = 2;
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('Danh sách lốp xe');
		$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFill()->getStartColor()->setARGB('00FFFF');

		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(80);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(70);

		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Thương hiệu');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Hãng xe');
		$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Dòng xe');
		$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Mô tả xe');
		$objPHPExcel->getActiveSheet()->setCellValue('E1', 'Tính năng an toàn có sẵn');
		$objPHPExcel->getActiveSheet()->setCellValue('F1', 'Lợi ích bổ sung');
		$objPHPExcel->getActiveSheet()->setCellValue('G1', 'Tính năng bổ sung');
		$objPHPExcel->getActiveSheet()->setCellValue('H1', 'Dòng xe này dùng những size vỏ nào');
		$objPHPExcel->getActiveSheet()->setCellValue('I1', 'Dòng xe này dùng những camera nào');
		$objPHPExcel->getActiveSheet()->setCellValue('J1', 'Dòng xe này dùng những cảm biến áp suất lốp nào');
		$objPHPExcel->getActiveSheet()->setCellValue('K1', 'Dòng xe này dùng những phim cách nhiệt nào');
		$objPHPExcel->getActiveSheet()->setCellValue('L1', 'Dòng xe này dùng những PPF nào');

		$listItems = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = 29 AND `cat_id` = 0", array("id" => "ASC"), 9999);
		if ($listItems) {
			foreach ($listItems as $key => $item) {
				$item1 = $item->getName();
				$id = $item->getId();
				$objPHPExcel->getActiveSheet()->setCellValue('A' . $vitri, $item1 . "___" . $id);
				$listItems2 = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = 29 AND `cat_id` = $id", array("id" => "ASC"), 9999);
				if ($listItems2) {
					foreach ($listItems2 as $key => $items1) {
						$item2 = $items1->getName();
						$id2 = $items1->getId();
						$slug1 = $items1->getSlug();
						$objPHPExcel->getActiveSheet()->setCellValue('B' . $vitri, $item2 . "___" . $id2);
						$listItems3 = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = 29 AND `cat_id` = $id2", array("id" => "ASC"), 9999);
						if ($listItems3) {
							foreach ($listItems3 as $key => $items2) {
								$item3 = $items2->getName();
								$id3 = $items2->getId();
								$slug2 = $items2->getSlug();
								#list size
								$listSize = explode(",", $items2->getListSize());
								$dataArraySize = [];
								foreach ($listSize as $itemaa) {
									$namesize = $productOptions->getNameFromId($itemaa);
									if ($namesize) {
										array_push($dataArraySize, $namesize);
									}
								}
								$nameSize = implode(",", $dataArraySize);
								#list camera
								$listCamera = explode(",", $items2->getListCamera());
								$dataArrayCamera = [];
								foreach ($listCamera as $itemaa) {
									$nameCamera = $productaccessorys->getNameFromId($itemaa);
									if ($nameCamera) {
										array_push($dataArrayCamera, $nameCamera);
									}
								}
								$nameCamera = implode(",", $dataArrayCamera);
								#list cam biến
								$listCamBien = explode(",", $items2->getListCamBien());
								$dataArrayCamBien = [];
								foreach ($listCamBien as $itemaa) {
									$nameCamBien = $productaccessorys->getNameFromId($itemaa);
									if ($nameCamBien) {
										array_push($dataArrayCamBien, $nameCamBien);
									}
								}
								$nameCamBien = implode(",", $dataArrayCamBien);
								#list Phim
								$listFim = explode(",", $items2->getListFim());
								$dataArrayFim = [];
								foreach ($listFim as $itemaa) {
									$nameFim = $productaccessorys->getNameFromId($itemaa);
									if ($nameFim) {
										array_push($dataArrayFim, $nameFim);
									}
								}
								$nameFim = implode(",", $dataArrayFim);
								#list PPf
								$listPpf = explode(",", $items2->getListPpf());
								$dataArrayPpf = [];
								foreach ($listPpf as $itemaa) {
									$namePpf = $productaccessorys->getNameFromId($itemaa);
									if ($namePpf) {
										array_push($dataArrayPpf, $namePpf);
									}
								}
								$namePpf = implode(",", $dataArrayPpf);
								#
								$objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, $item3 . "___" . $id3);
								$objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, $items2->getProperty('custom_sapo_car'));
								$objPHPExcel->getActiveSheet()->setCellValue('E' . $vitri, $items2->getProperty('custom_tinh-nang'));
								$objPHPExcel->getActiveSheet()->setCellValue('F' . $vitri, $items2->getProperty('custom_loi-ich'));
								$objPHPExcel->getActiveSheet()->setCellValue('G' . $vitri, $items2->getProperty('custom_tinh-nang-bo-sung'));
								$objPHPExcel->getActiveSheet()->setCellValue('H' . $vitri, $nameSize);
								$objPHPExcel->getActiveSheet()->setCellValue('I' . $vitri, $nameCamera);
								$objPHPExcel->getActiveSheet()->setCellValue('J' . $vitri, $nameCamBien);
								$objPHPExcel->getActiveSheet()->setCellValue('K' . $vitri, $nameFim);
								$objPHPExcel->getActiveSheet()->setCellValue('L' . $vitri, $namePpf);
								$vitri++;
							}
						} else {
							#listSize
							$listSize = explode(",", $items1->getListSize());
							$dataArray = [];
							foreach ($listSize as $itemaa) {
								$namesize = $productOptions->getNameFromId($itemaa);
								array_push($dataArray, $namesize);
							}
							$nameSize = implode(",", $dataArray);
							#list camera
							$listCamera = explode(",", $items1->getListCamera());
							$dataArrayCamera = [];
							foreach ($listCamera as $itemaa) {
								$nameCamera = $productaccessorys->getNameFromId($itemaa);
								if ($nameCamera) {
									array_push($dataArrayCamera, $nameCamera);
								}
							}
							$nameCamera = implode(",", $dataArrayCamera);
							#list cam biến
							$listCamBien = explode(",", $items1->getListCamBien());
							$dataArrayCamBien = [];
							foreach ($listCamBien as $itemaa) {
								$nameCamBien = $productaccessorys->getNameFromId($itemaa);
								if ($nameCamBien) {
									array_push($dataArrayCamBien, $nameCamBien);
								}
							}
							$nameCamBien = implode(",", $dataArrayCamBien);
							#list Phim
							$listFim = explode(",", $items1->getListFim());
							$dataArrayFim = [];
							foreach ($listFim as $itemaa) {
								$nameFim = $productaccessorys->getNameFromId($itemaa);
								if ($nameFim) {
									array_push($dataArrayFim, $nameFim);
								}
							}
							$nameFim = implode(",", $dataArrayFim);
							#list PPf
							$listPpf = explode(",", $items1->getListPpf());
							$dataArrayPpf = [];
							foreach ($listPpf as $itemaa) {
								$namePpf = $productaccessorys->getNameFromId($itemaa);
								if ($namePpf) {
									array_push($dataArrayPpf, $namePpf);
								}
							}
							$namePpf = implode(",", $dataArrayPpf);
							#
							$objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, "");
							$objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, $items1->getProperty('custom_sapo_car'));
							$objPHPExcel->getActiveSheet()->setCellValue('E' . $vitri, $items1->getProperty('custom_tinh-nang'));
							$objPHPExcel->getActiveSheet()->setCellValue('F' . $vitri, $items1->getProperty('custom_loi-ich'));
							$objPHPExcel->getActiveSheet()->setCellValue('G' . $vitri, $items1->getProperty('custom_tinh-nang-bo-sung'));
							$objPHPExcel->getActiveSheet()->setCellValue('H' . $vitri, $nameSize);
							$objPHPExcel->getActiveSheet()->setCellValue('I' . $vitri, $nameCamera);
							$objPHPExcel->getActiveSheet()->setCellValue('J' . $vitri, $nameCamBien);
							$objPHPExcel->getActiveSheet()->setCellValue('K' . $vitri, $nameFim);
							$objPHPExcel->getActiveSheet()->setCellValue('L' . $vitri, $namePpf);
							$vitri++;
						}
					}
				}
			}
		}
		ob_end_clean();
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Danh sách hãng xe-' . date('d-m-Y') . '.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save('php://output');
		exit();
		#
	}
	if ($type == 4) { #Danh sách dòng gai
		#ex dòng gai
		$vitri = 2;
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('Danh sách dòng gai');
		$objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFill()->getStartColor()->setARGB('00FFFF');

		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(70);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(70);


		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Thương hiệu');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Dòng gai');
		$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Id');
		$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Nội dung');
		$objPHPExcel->getActiveSheet()->setCellValue('E1', 'Hình ảnh');


		$listItems = $productOptions->getObjects(1, "`status` = '1' AND `pc_id` = 28", array("id" => "ASC"), 9999);
		if ($listItems) {
			foreach ($listItems as $key => $item) {
				$item1 = $item->getName();
				$id = $item->getId();
				$objPHPExcel->getActiveSheet()->setCellValue('A' . $vitri, $item1);
				$listItems1 = $specifications->getObjects(1, "`status` = '1'AND `cat_id` = 0 AND `mc_id` = $id", array("id" => "ASC"), 9999);
				if ($listItems1) {
					foreach ($listItems1 as $key => $item1) {
						$item2 = $item1->getName();
						$getDetail2 = $item1->getProperty('detail');
						$id2 = $item1->getId();
						$objPHPExcel->getActiveSheet()->setCellValue('B' . $vitri, $item2);
						$dataImg = [];
						foreach ($item1->getProperty('photos') as $key => $img) {
							array_push($dataImg, $imgs->getUrlFromId($img));
						}
						$photo = implode(",", $dataImg);
						$listItems2 = $specifications->getObjects(1, "`status` = '1'AND `cat_id` = $id2 ", array("id" => "ASC"), 9999);
						if ($listItems2) {
							foreach ($listItems2 as $key => $item2) {
								$item3 = $item2->getName();
								$getDetail = $item2->getProperty('detail');
								$id3 = $item2->getId();
								// $objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, $item3);
								$objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, $id3);
								$objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, $getDetail);
								$objPHPExcel->getActiveSheet()->setCellValue('E' . $vitri, $photo);
								$vitri++;
							}
						} else {
							// $objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, $item2);
							$objPHPExcel->getActiveSheet()->setCellValue('C' . $vitri, $id2);
							$objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, $getDetail2);
							$objPHPExcel->getActiveSheet()->setCellValue('E' . $vitri, $photo);
							$vitri++;
						}
					}
				}
			}
			ob_end_clean();
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="Danh sách dòng gai-' . date('d-m-Y') . '.xls"');
			header('Cache-Control: max-age=0');
			$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
			$objWriter->save('php://output');
			exit();
			#
		}
	}
}
								// $objPHPExcel->getActiveSheet()->setCellValue('D' . $vitri, "https://thanhanautocare.com/" . $slug2 . "-nen-thay-lop-gi-chi-phi-bao-nhieu")
								// 	->getCell('D' . $vitri)
								// 	->getHyperlink()
								// 	->setUrl("https://thanhanautocare.com/" . $slug2 . "-nen-thay-lop-gi-chi-phi-bao-nhieu")
								// 	->setTooltip('Click to visit the link');
