<?php
    //   ini_set('display_errors', 1);
    //     ini_set('display_startup_errors', 1);
    //     error_reporting(E_ALL);
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/articles.class.php");
include_once(ROOT_PATH . "classes/PhpSpreadSheet/PhpOffice/autoload.php");
include_once(ROOT_PATH . "classes/dao/menus.class.php");

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$products = new Products($storeId);
$productCategories = new ProductCategories($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$articles = new Articles($storeId);
$menus = new Menus($storeId);

$templateFile = 'manageimport.tpl.html';

$listTabs = array(
	"Nhập liêu" => '/' . ADMIN_SCRIPT . '?op=manage&act=import&mod=data',
	// "Xuất liêu" => '/' . ADMIN_SCRIPT . '?op=manage&act=export&mod=data',
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 1);

/**
 * Parse giá trị date từ cell Excel.
 * Xử lý 3 trường hợp:
 *   1. Excel date serial (số thực/nguyên) — Excel tự format
 *   2. Text dạng d/m/Y, d-m-Y, Y-m-d, v.v.
 *   3. Ô trống → trả về null
 *
 * @param \PhpOffice\PhpSpreadsheet\Cell\Cell $cell
 * @return string|null  Định dạng Y-m-d hoặc null nếu không parse được
 */
function parseExcelDate($cell): ?string
{
	$value = $cell->getValue();

	if ($value === null || $value === '') {
		return null;
	}

	// Trường hợp 1: Excel date serial (số nguyên hoặc số thực)
	if (is_numeric($value)) {
		try {
			$timestamp = ExcelDate::excelToTimestamp((float)$value);
			return date('Y-m-d', $timestamp);
		} catch (\Exception $e) {
			return null;
		}
	}

	// Trường hợp 2: Text — thử parse nhiều format phổ biến
	$formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'm/d/Y'];
	$valueStr = trim((string)$value);
	foreach ($formats as $fmt) {
		$dt = \DateTime::createFromFormat($fmt, $valueStr);
		// Strict check: tránh parse sai kiểu 32/13/2026 vẫn trả object
		if ($dt && $dt->format($fmt) === $valueStr) {
			return $dt->format('Y-m-d');
		}
	}

	// Trường hợp 3: strtotime fallback (bắt các dạng còn lại)
	$ts = strtotime($valueStr);
	return $ts ? date('Y-m-d', $ts) : null;
}

function resolveStatus(?string $publishAt): int
{
    if (!$publishAt) return 1;
    return ($publishAt > date('Y-m-d')) ? 0 : 1;
}

if ($_POST) {
	if (!isset($_FILES['linkfile']) || $_FILES['linkfile']['error'] != 0) {
		die("Không có file upload");
	}
	$file_type = $_FILES['linkfile']['type'];
	if (
		$file_type == "application/vnd.ms-excel" ||
		$file_type == "application/x-ms-excel" ||
		$file_type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
	) {
		$filename = basename($_FILES["linkfile"]["name"]);
		$uploadPath = "./upload/1/excel/" . $filename;
		if (!move_uploaded_file($_FILES["linkfile"]["tmp_name"], $uploadPath)) {
			die("Upload file thất bại");
		}
		$type = $request->element("type");
		if ($type == 1) { # bài viết
			try {
				$spreadsheet = IOFactory::load($uploadPath);
				// Bắt đầu transaction
				$db->query("START TRANSACTION");
				foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
					$highestRow = $worksheet->getHighestRow();
					for ($row = 2; $row <= $highestRow; $row++) {
						$getCatId          = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue()); #ID danh mục
						$getSlug           = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue()); #SLug
						$getSlugEn = trim($worksheet->getCellByColumnAndRow(3, $row)->getValue()); #Slug (EN)
						$getSlugZh = trim($worksheet->getCellByColumnAndRow(4, $row)->getValue()); #Slug (ZH)
						$getH1                = trim($worksheet->getCellByColumnAndRow(5, $row)->getValue()); #H1 (VN)
						$getTitle          = trim($worksheet->getCellByColumnAndRow(6, $row)->getValue()); #Tiêu đề (VN)
						$getKeyword        = trim($worksheet->getCellByColumnAndRow(7, $row)->getValue()); #Từ khóa (VN)
						$getDescription    = $worksheet->getCellByColumnAndRow(8, $row)->getValue(); #Giới thiệu (VN)
						$getDetail         = $worksheet->getCellByColumnAndRow(9, $row)->getValue(); #Tổng quan (VN)
						$getH1En              = trim($worksheet->getCellByColumnAndRow(10, $row)->getValue()); #H1 (EN)
						$getTitleEn 	  = trim($worksheet->getCellByColumnAndRow(11, $row)->getValue()); #Tiêu đề (EN)
						$getKeywordEn 	  = trim($worksheet->getCellByColumnAndRow(12, $row)->getValue()); #Từ khóa (EN)
						$getDescriptionEn = $worksheet->getCellByColumnAndRow(13, $row)->getValue(); #Giới thiệu (EN)
						$getDetailEn 	  = $worksheet->getCellByColumnAndRow(14, $row)->getValue(); #Tổng quan (EN)
						$getTitleSEO       = trim($worksheet->getCellByColumnAndRow(15, $row)->getValue()); #Title SEO (VN)
						$getKeywordSEO     = trim($worksheet->getCellByColumnAndRow(16, $row)->getValue()); #Keyword SEO (VN)
						$getDescriptionSEO = trim($worksheet->getCellByColumnAndRow(17, $row)->getValue()); #Description SEO (VN)
						$getTitleSEOEn     = trim($worksheet->getCellByColumnAndRow(18, $row)->getValue()); #Title SEO (EN)
						$getKeywordSEOEn   = trim($worksheet->getCellByColumnAndRow(19, $row)->getValue()); #Keyword SEO (EN)
						$getDescriptionSEOEn = trim($worksheet->getCellByColumnAndRow(20, $row)->getValue()); #Description SEO (EN)
						$getPublishAt      = parseExcelDate($worksheet->getCellByColumnAndRow(21, $row)); #Ngày đặt lịch 
						$getArticleGroupIds   = trim($worksheet->getCellByColumnAndRow(22, $row)->getValue()); #Article group ids (1,2,3)
						$getLang              = trim($worksheet->getCellByColumnAndRow(23, $row)->getValue()); #Language (vn,en)
						$resolvedStatus = resolveStatus($getPublishAt);

						// if (empty($getSlugEn) || empty($getSlugZh)) {
						// 	$db->query("ROLLBACK");
						// 	$template->assign('importfail', "Thiếu Slug EN hoặc Slug ZH tại dòng {$row}");
						// }

						// Clean HTML
						$getDetail = stripslashes($getDetail);
						$getDetail = html_entity_decode($getDetail, ENT_QUOTES, 'UTF-8');
						$getDetail = str_replace(['\&quot;', '&quot;'], '"', $getDetail);

						// Xóa style trong span
						$getDetail = preg_replace('/<span\b[^>]*style="[^"]*"([^>]*)>/i', '<span$1>', $getDetail);

						// Xóa span rỗng attribute (optional)
						$getDetail = preg_replace('/<span\s*>/i', '<span>', $getDetail);

						if (!$getSlug) {
							continue;
						}
						$article = $articles->getObject($getSlug, 'slug');

						// Avatar theo slug
						$getSlugSafe = addslashes($getSlug);
						$list = $uploads->getObjects(1, "u.name = '$getSlugSafe'", ["id" => "DESC"], 1);
						$avatarNew = $list ? $list[0] : null;

						if ($article) {
							# UPDATE
							$properties = $article->getProperties();
							if (!is_array($properties)) {
								$properties = [];
							}
							$avatarIdOld = $article->getProperty('avatarId');
							if ($avatarNew && $avatarNew->getId() != $avatarIdOld) {
								if ($avatarIdOld) {
									$uploadList = $uploads->getObjects(1, "u.id = '$avatarIdOld'", ["id" => "DESC"], 1);
									$upload = $uploadList ? $uploadList[0] : null;
									if ($upload) {
										$upload->deleteFiles();
										$uploads->DeteImg($avatarIdOld);
									}
								}
								$properties['avatarId'] = $avatarNew->getId();
							} else {
								$properties['avatarId'] = $avatarIdOld;
							}

							$properties['custom_h1']     = $getH1     ?: $article->getProperty('custom_h1');
							$properties['custom_titleSeo']     = $getTitleSEO     ?: $article->getProperty('custom_titleSeo');
							$properties['custom_meta_keyword'] = $getKeywordSEO   ?: $article->getProperty('custom_meta_keyword');
							$properties['custom_captionSeo']   = $getDescriptionSEO ?: $article->getProperty('custom_captionSeo');
							$properties['custom_h1_en']     = $getH1En     ?: $article->getProperty('custom_h1_en');
							$properties['custom_en_titleSeo']     = $getTitleSEOEn     ?: $article->getProperty('custom_en_titleSeo');
							$properties['custom_en_meta_keyword'] = $getKeywordSEOEn   ?: $article->getProperty('custom_en_meta_keyword');
							$properties['custom_en_captionSeo']   = $getDescriptionSEOEn ?: $article->getProperty('custom_en_captionSeo');
							$properties['custom_en_title']     = $getTitleEn     ?: $article->getProperty('custom_en_title');
							$properties['custom_en_keyword'] = $getKeywordEn   ?: $article->getProperty('custom_en_keyword');
							$properties['custom_en_description']   = $getDescriptionEn ?: $article->getProperty('custom_en_description');
							$properties['custom_en_detail']   = $getDetailEn ?: $article->getProperty('custom_en_detail');
							$data = [
								'slug_en'         => $getSlugEn,
								'slug_zh'         => $getSlugZh,
								'lang'            => $getLang,
								'category_id'  => $getCatId      ?: $article->getCategoryId(),
								'title'        => $getTitle      ?: $article->getTitle(),
								'keyword'      => $getKeyword    ?: $article->getKeyword(),
								'description'  => $getDescription ?: $article->getDescription(),
								'detail'       => $getDetail     ?: $article->getDetail(),
								'updater_id'   => 100,
								'properties'   => serialize($properties),
								'status'       => $resolvedStatus,
								'publish_at'   => $getPublishAt  ?: $article->getPublishAt(),
								'date_updated' => date("Y-m-d H:i:s")
							];
							if (!$articles->updateData($data, $article->getId())) {
								throw new Exception("Update failed at row " . $row);
							}
						} else {
							# INSERT
							$properties = [
								'avatarId'            => is_object($avatarNew) ? $avatarNew->getId() : null,
								'custom_h1'           => $getH1,
								'custom_titleSeo'     => $getTitleSEO,
								'custom_meta_keyword' => $getKeywordSEO,
								'custom_captionSeo'   => $getDescriptionSEO,
								'custom_h1_en'           => $getH1En,
								'custom_en_titleSeo'     => $getTitleSEOEn,
								'custom_en_meta_keyword' => $getKeywordSEOEn,
								'custom_en_captionSeo'   => $getDescriptionSEOEn,
								'custom_en_title'     => $getTitleEn,
								'custom_en_keyword' => $getKeywordEn,
								'custom_en_description'   => $getDescriptionEn,
								'custom_en_detail'   => $getDetailEn
							];
							$data = [
								'store_id'     => $storeId,
								'category_id'  => $getCatId,
								'slug'         => $getSlug,
								'slug_en'      => $getSlugEn,
								'slug_zh'      => $getSlugZh,
								'lang'         => $getLang ? $getLang : 'vn',
								'title'        => $getTitle,
								'keyword'      => $getKeyword,
								'description'  => $getDescription,
								'detail'       => $getDetail,
								'poster_id'    => 107,
								'status'       => $resolvedStatus,
								'properties'   => serialize($properties),
								'publish_at'   => $getPublishAt,
								'article_group_ids' => $getArticleGroupIds,
								'date_created' => date("Y-m-d H:i:s")
							];
							if (!$articles->addData($data)) {
								throw new Exception("Insert failed at row " . $row);
							}
						}
					}
				}
				// Commit
				$db->query("COMMIT");
				$template->assign('importsuccess', "Thành công");
			}  catch (Throwable $e) {
				$db->query("ROLLBACK");

				error_log(
					"IMPORT ARTICLE ERROR: "
					. $e->getMessage()
					. " | File: " . $e->getFile()
					. " | Line: " . $e->getLine()
					. " | Trace: " . $e->getTraceAsString()
				);

				$template->assign(
					'importfail',
					"Lỗi import tại dòng: " . ($row ?? 'unknown')
					. "<br>"
					. htmlspecialchars($e->getMessage())
				);
			}
		}else if ($type == 2){ # Schema FAQ
		$moduleType = $request->element('moduleType');
		try {
			$spreadsheet = IOFactory::load($uploadPath);
			$db->query("START TRANSACTION");
			foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
				$highestRow = $worksheet->getHighestRow();
				for ($row = 2; $row <= $highestRow; $row++) {
					if ($moduleType == 'menu'){
						$getSchemaFaq = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue()); # Schema
						$getUrl       = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue()); # URL

						if (empty($getUrl)) {
							continue;
						}
						$menuInfo = $menus->getObject($getUrl, 'url');
						if (!$menuInfo) {
							throw new Exception("Không tìm thấy URL '{$getUrl}' ở dòng {$row}");
						}
						$properties = $menuInfo->getProperties();
						if (!is_array($properties)) {
							$properties = [];
						}
						$properties['custom_schema_faq'] = $getSchemaFaq;
						$data = [
							'properties'   => serialize($properties),
							'date_updated' => date("Y-m-d H:i:s")
						];
						if (!$menus->updateData($data, $menuInfo->getId())) {
							throw new Exception("Update failed at row {$row}");
						}
					}else if ($moduleType == 'detailProduct'){
						$getSchemaFaq = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue()); # Schema
						$getSlug       = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue()); # Slug

						if (empty($getSlug)) {
							continue;
						}
						$productInfo = $products->getObject($getSlug, 'slug');
						if (!$productInfo) {
							throw new Exception("Không tìm thấy Slug '{$getSlug}' ở dòng {$row}");
						}
						$properties = $productInfo->getProperties();
						if (!is_array($properties)) {
							$properties = [];
						}
						$properties['custom_schema_faq'] = $getSchemaFaq;
						$data = [
							'properties'   => serialize($properties),
							'date_updated' => date("Y-m-d H:i:s")
						];
						if (!$products->updateData($data, $productInfo->getId())) {
							throw new Exception("Update failed at row {$row}");
						}
					}else if ($moduleType == 'detailArticle'){
						$getSchemaFaq = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue()); # Schema
						$getSlug       = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue()); # Slug

						if (empty($getSlug)) {
							continue;
						}
						$articleInfo = $articles->getObject($getSlug, 'slug');
						if (!$articleInfo) {
							throw new Exception("Không tìm thấy Slug '{$getSlug}' ở dòng {$row}");
						}
						$properties = $articleInfo->getProperties();
						if (!is_array($properties)) {
							$properties = [];
						}
						$properties['custom_schema_faq'] = $getSchemaFaq;
						$data = [
							'properties'   => serialize($properties),
							'date_updated' => date("Y-m-d H:i:s")
						];
						if (!$articles->updateData($data, $articleInfo->getId())) {
							throw new Exception("Update failed at row {$row}");
						}
					}
				}
			}
			$db->query("COMMIT");
			$template->assign('importsuccess', 'Thành công');

		} catch (Throwable $e) {
			$db->query("ROLLBACK");
			$template->assign('importfail', 'Lỗi import: ' . $e->getMessage());
		}
		} else {
			$template->assign('importfail', "Lỗi import");
		}
	}
}