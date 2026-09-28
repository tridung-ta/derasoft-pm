<?php
$templateFile = 'search.tpl.html';

include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");

$products = new Products($storeId);
$articles = new Articles($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);
$template->assign('uploads', $uploads);

$keywordRaw = $request->element('keyword') ?? '';
$keyword = strip_tags(trim($keywordRaw));
$keywordEscaped = htmlspecialchars($keyword, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$template->assign('keyword', $keywordEscaped);

# Lấy page cho Products
$pageProduct = $request->element('page') ? (int)$request->element('page') : 1;

# Lấy page cho Articles 
$pageArticle = $request->element('pageArticle') ? (int)$request->element('pageArticle') : 1;
$itemsPerPage = 8;

# --- Conditions ---
$productConditionBaseForObjects = "p.status = '1'";
$productConditionBaseForCount   = "status = '1'";

$articleConditionBaseForObjects = "a.status = '1' AND a.category_id != '43'";
$articleConditionBaseForCount   = "a. status = '1'";

if ($keyword) {
    $safeKeyword = addslashes($keyword);

    # --- Products ---
    $productConditionObjects = "$productConditionBaseForObjects AND p.name LIKE '%$safeKeyword%'";
    $productConditionCount   = "$productConditionBaseForCount AND name LIKE '%$safeKeyword%'";

    $productCountInfo = $products->getNumItems('id', $productConditionCount, $itemsPerPage);
    $totalRowsProduct = is_array($productCountInfo) ? intval($productCountInfo['rows']) : 0;
    $totalPagesProduct = is_array($productCountInfo) ? intval($productCountInfo['pages']) : 1;
    $pageProduct = max(1, min($pageProduct, $totalPagesProduct));

    $productResults = $products->getObjects($pageProduct, $productConditionObjects, ["p.id" => "DESC"], $itemsPerPage);
    $template->assign('productResults', $productResults);
    $template->assign('pageProduct', $pageProduct);
    $template->assign('totalPagesProduct', $totalPagesProduct);
    $template->assign('totalRowsProduct', $totalRowsProduct);

    # --- Articles ---
    $articleConditionObjects = "$articleConditionBaseForObjects AND a.title LIKE '%$safeKeyword%'";
    $articleConditionCount   = "$articleConditionBaseForCount AND title LIKE '%$safeKeyword%'";

    $articleCountInfo = $articles->getNumItems('id', $articleConditionCount, $itemsPerPage);
    $totalRowsArticle = is_array($articleCountInfo) ? intval($articleCountInfo['rows']) : 0;
    $totalPagesArticle = is_array($articleCountInfo) ? intval($articleCountInfo['pages']) : 1;
    $pageArticle = max(1, min($pageArticle, $totalPagesArticle));

    $articleResults = $articles->getObjects($pageArticle, $articleConditionObjects, ["a.id" => "DESC"], $itemsPerPage);
    $template->assign('articleResults', $articleResults);
    $template->assign('pageArticle', $pageArticle);
    $template->assign('totalPagesArticle', $totalPagesArticle);
    $template->assign('totalRowsArticle', $totalRowsArticle);
}

# Breadcrumb & Meta
$topNav = [
    ["name" => "Trang chủ", "url" => "/"],
    ["name" => "Tìm kiếm", "url" => "/tim-kiem"],
];
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

$pageTitle = "Tìm kiếm: ".$keyword;
$pageDescription = "Kết quả tìm kiếm cho ".$keyword;

$template->assign('pageTitle', $pageTitle);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageKeywords', $keyword);