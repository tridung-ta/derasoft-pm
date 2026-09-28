<?php
   ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
include_once(ROOT_PATH."classes/dao/products.class.php");
include_once(ROOT_PATH."classes/dao/uploads.class.php");
include_once(ROOT_PATH."classes/dao/productcategories.class.php");

$products = new Products(1);
$uploads  = new Uploads(1);
$productCategories = new ProductCategories(1);
/* =========================
   NHẬN DỮ LIỆU FILTER
========================= */

$page  = isset($_POST['page']) ? intval($_POST['page']) : 1;
$itemsPerPage = 8;

$brand  = isset($_POST['brand']) ? (int)$_POST['brand'] : 0;
$level  = isset($_POST['level']) ? trim($_POST['level']) : '';

$isMultiDomain            = isset($_POST['isMultiDomain']) ? trim($_POST['isMultiDomain']) : '';
$isSubdomain              = isset($_POST['isSubdomain']) ? trim($_POST['isSubdomain']) : '';
$isMalwareScan            = isset($_POST['isMalwareScan']) ? trim($_POST['isMalwareScan']) : '';
$isVulnerabilityAssessment= isset($_POST['isVulnerabilityAssessment']) ? trim($_POST['isVulnerabilityAssessment']) : '';
/* =========================
   BUILD CONDITION
========================= */
$productCategoryList = $productCategories->getObjects(1, "status = 1 AND CONCAT(',', list_parent_id, ',') LIKE '%,134,%'", ["position" => "ASC"], 999);

$categoryIds = [];
foreach ($productCategoryList as $cat) {
    $categoryIds[] = $cat->getId();
}

$conditionAlias = "p.status = 1 AND p.category_id IN (" . implode(',', $categoryIds) . ")";
$conditionPlain = "status = 1 AND category_id IN (" . implode(',', $categoryIds) . ")";

if ($brand > 0) {
    $conditionAlias .= " AND p.category_id = ".$brand;
    $conditionPlain .= " AND category_id = ".$brand;
}

if ($level != '') {
    $level = addslashes($level);
    $conditionAlias .= " AND p.validation_level = '".$level."'";
    $conditionPlain .= " AND validation_level = '".$level."'";
}

if ($isMultiDomain !== '') {
    $conditionAlias .= " AND p.san_support = ".intval($isMultiDomain);
    $conditionPlain .= " AND san_support = ".intval($isMultiDomain);
}

if ($isSubdomain !== '') {
    $conditionAlias .= " AND p.wildcard_support = ".intval($isSubdomain);
    $conditionPlain .= " AND wildcard_support = ".intval($isSubdomain);
}

if ($isMalwareScan !== '') {
    $conditionAlias .= " AND p.malware_scan = ".intval($isMalwareScan);
    $conditionPlain .= " AND malware_scan = ".intval($isMalwareScan);
}

if ($isVulnerabilityAssessment !== '') {
    $conditionAlias .= " AND p.vulnerability_scan = ".intval($isVulnerabilityAssessment);
    $conditionPlain .= " AND vulnerability_scan = ".intval($isVulnerabilityAssessment);
}

/* =========================
   TÍNH TỔNG SỐ TRANG
========================= */

$countInfo = $products->getNumItems('id', $conditionPlain, $itemsPerPage);

$totalRows  = is_array($countInfo) ? intval($countInfo['rows'])  : 0;
$totalPages = is_array($countInfo) ? intval($countInfo['pages']) : 1;

if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;
if ($page < 1) $page = 1;

/* =========================
   LẤY DANH SÁCH THEO TRANG
========================= */

$productList = $products->getObjects(
    $page,
    $conditionAlias,
    array("p.price" => "ASC"),
    $itemsPerPage
);

if (!is_array($productList)) {
    $productList = [];
}

/* =========================
   BUILD RESPONSE
========================= */

$response = [
    "products"    => [],
    "totalRows"   => $totalRows,
    "totalPages"  => $totalPages,
    "currentPage" => $page
];

foreach ($productList as $product) {

    $avatar = $product->getAvatarImage($uploads);

    $response["products"][] = [
        "id"          => $product->getId(),
        "name"        => $product->getName(),
        "description" => $product->getDescription(),
        "price"       => number_format($product->getPrice(), 0, ",", "."),
        "slug"        => $product->getSlug(),
        "avatar"      => $avatar
            ? $avatar->getPath()."/".$avatar->getUrlL()
            : "/templates/digitrust/img/404/no-image.webp"
    ];
}

/* =========================
   RETURN JSON
========================= */

header('Content-Type: application/json');
echo json_encode($response);
exit;