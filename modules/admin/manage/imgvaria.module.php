<?php

/*************************************************************************
Article listing module
----------------------------------------------------------------
Derasoft CMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 05/05/2012
Coder: Mai Minh
 **************************************************************************/
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
$template->assign('imgs', $imgs);
$templateFile = 'manageimport.tpl.html';
$gallery_path = ROOT_PATH  . $estore->getProperty('custom_name_file_img') . "/";

$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=img';
$listTabs = array(
	"Hình ảnh cho biến" => $tabLink . '&mod=varia',

);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

if ($_POST && $request->element('doo') == 'submit') {

	#File Avatar
	$dataarray = [];
	for ($i = 1; $i <= 10; $i++) {
		$fileAvatr = isset($_FILES['avatarvaria' . $i]) ? $_FILES['avatarvaria' . $i] : '';
		if ($fileAvatr) {
			$textFilter = new TextFilter();
			$last_dot_position = strrpos($fileAvatr['name'], '.');
			// Cắt chuỗi từ đầu đến vị trí của dấu chấm cuối cùng (không bao gồm dấu chấm)
			if ($last_dot_position !== false) {
				$filename_without_extension = substr($fileAvatr['name'], 0, $last_dot_position);
			}
			// Lấy 3 ký tự cuối của tên tệp
			$type = strtolower(substr($fileAvatr['name'], -3));
			// Loại bỏ dấu tiếng Việt và chuyển đổi khoảng trắng thành dấu "-"
			$file_name_normalized = str_replace(' ', '-', strtolower($textFilter->convert_vi_to_en($filename_without_extension)));
			$imgl = addslashes(Filter($file_name_normalized . "_l_" . rand() . "." . $type));
			$imga = addslashes(Filter($file_name_normalized . "_a_" . rand() . "." . $type));
			$tmp_img = $fileAvatr['tmp_name'];
			$size = $fileAvatr['size'];
			# Upload
			$new_imgl = $imgl;
			$new_imga = $imga;
			move_uploaded_file($tmp_img, $gallery_path . $imgl);
			if (isBmp($imgl)) $new_imgl = preg_replace("/(bmp$)/", "jpg", $imgl);
			resize($gallery_path, $gallery_path, $imgl, $new_imgl, DEFAULT_LARGE_SIZE, DEFAULT_LARGE_SQUARE, DEFAULT_PHOTO_QUALITY);
			resize($gallery_path, $gallery_path, $imgl, $new_imga, DEFAULT_AVATAR_SIZE, DEFAULT_AVATAR_SQUARE, DEFAULT_PHOTO_QUALITY);
			if ($imgl != $new_imgl) unlink($gallery_path . $imgl);    # Delete file if it's not a JPEG
			if ($imga != $new_imga) unlink($gallery_path . $imga);    # Delete file if it's not a JPEG
			#xóa đuôi .
			$file_parts = explode('.', $fileAvatr['name']);
			// Lấy tất cả các phần trừ phần mở rộng
			$file_name_without_extension = implode('.', array_slice($file_parts, 0, -1));
			if ($file_name_without_extension) {
				#luu data img.
				$data = array(
					"url_l" => $imgl,
					"url_a" => $imga,
					"status" => 1,
					"store_id" => 1,
					"date_created" => date("Y-m-d H:i:s"),
					"name" => $file_name_without_extension,
				);

				$avatarid = $imgs->addData($data);
				$imglas['avatarvaria' . $i] = $avatarid;
			}
		}
	}
	array_push($dataarray, $imglas);
	$properties = $estore->getProperties();
	foreach ($dataarray as $key => $value) {
		foreach ($value as $subKey => $subValue) {
			$properties[$subKey] = stripslashes($subValue);
		}
	}
	$data = array(
		'properties' => serialize($properties)
	);
	$stores->updateData($data, $storeId);

	header('location:' . '/' . ADMIN_SCRIPT . "?op=manage&act=img&mod=varia&rcode=7");
}
