<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once(ROOT_PATH . 'classes/dao/estores.class.php');
include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/productcategories.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'includes/functions.inc.php');
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/template/smarty.class.php');

$productCategories = new ProductCategories(1);

// ===== INIT =====
$template = new Smarty;
$template->setTemplateDir(ROOT_PATH . TEMPLATE_PATH . '/mpx/');
$template->registerPlugin('modifier', 'date', 'date');
$template->registerPlugin('modifier', 'strtotime', 'strtotime');

$uploads = new Uploads(1);

$searchQuery  = trim($request->element('searchQuery') ?? '');
$idCate       = (int)($request->element('idCate') ?? 0);
$lang         = $request->element('lang') ?? 'vn';
$type         = $request->element('type') ?? 'products';

if (!in_array($lang, ['vn', 'en'])) $lang = 'vn';

$messages = [];
if ($lang === 'en') {
    include ROOT_PATH . 'languages/en.php';
} else {
    include ROOT_PATH . 'languages/vn.php';
}

$isProductCate = false;
$whereSimple  = "";
$whereJoin    = "";
$templateFile = '';
$safeQuery    = $searchQuery !== '' ? addslashes($searchQuery) : '';
// Thêm dòng này để escape ký tự đặc biệt REGEXP
$safeRegex = preg_quote($safeQuery, '/');
const CATEGORY_SERVICE_PARENT_ID = 143; // Dịch vụ
const CATEGORY_PRODUCT_PARENT_ID = 145; // Sản phẩm

// ===== QUERY =====
switch ($type) {
    case 'products':
        $arrayServiceChildCategories = getListCategoryId($productCategories, CATEGORY_SERVICE_PARENT_ID);
        $arrayProductChildCategories = getListCategoryId($productCategories, CATEGORY_PRODUCT_PARENT_ID);
        $listCategories              = $productCategories->getObjects(1, "1>0", ['id' => 'ASC'], 1000);
        $isProductCate = in_array($idCate, $arrayProductChildCategories);
        $products      = new Products(1);
        $condSimple = ["status = 1"];
        $condJoin   = ["p.status = 1"];
        if ($idCate > 0) {
            // Nếu idCate thuộc nhóm sản phẩm, lọc các con của nó từ array đã build
            $targetArray = $isProductCate ? $arrayProductChildCategories : $arrayServiceChildCategories;

            // Lấy các id là con của $idCate (bao gồm chính nó)
            $childCateIds = [$idCate];
            foreach ($listCategories as $category) {
                $parentIds = explode(',', $category->getListParentId());
                // Kiểm tra xem category có thuộc cây của $idCate không
                if (!empty(array_intersect($childCateIds, $parentIds))) {
                    $childCateIds[] = $category->getId();
                }
            }
            $childCateIds = array_unique(array_intersect($childCateIds, $targetArray));
            $inClause = implode(',', array_map('intval', $childCateIds));
            $condSimple[] = "category_id IN ($inClause)";
            $condJoin[]   = "p.category_id IN ($inClause)";
        }
        if ($safeQuery !== '') {
            if ($lang === 'en') {
                $condSimple[] = "(name LIKE '%$safeQuery%' OR properties REGEXP '\"custom_en_name\";s:[0-9]+:\"[^\"]*{$safeRegex}[^\"]*\"')";
                $condJoin[]   = "(p.name LIKE '%$safeQuery%' OR p.properties REGEXP '\"custom_en_name\";s:[0-9]+:\"[^\"]*{$safeRegex}[^\"]*\"')";
            } else {
                $condSimple[] = "name LIKE '%$safeQuery%'";
                $condJoin[]   = "p.name LIKE '%$safeQuery%'";
            }
        }
        $whereSimple  = implode(" AND ", $condSimple);
        $whereJoin    = implode(" AND ", $condJoin);
        $result       = paginate($request, $products, $whereSimple, $whereJoin, ['p.id' => 'DESC'], 6);
        $templateFile = 'listproductsfromsearch.tpl.html';
        break;
    case 'articles':
        $articles = new Articles(1);

        $condSimple = ["a.status = 1"];  
        $condJoin   = ["a.status = 1"];

        if ($idCate > 0) {
            $condSimple[] = "a.category_id = '$idCate'";
            $condJoin[]   = "a.category_id = '$idCate'";
        }
        if ($safeQuery !== '') {
            if ($lang === 'en') {
                $condSimple[] = "(a.title LIKE '%$safeQuery%' OR a.properties REGEXP '\"custom_en_title\";s:[0-9]+:\"[^\"]*{$safeRegex}[^\"]*\"')";
                $condJoin[]   = "(a.title LIKE '%$safeQuery%' OR a.properties REGEXP '\"custom_en_title\";s:[0-9]+:\"[^\"]*{$safeRegex}[^\"]*\"')";
            }else{
                $condSimple[] = "a.title LIKE '%$safeQuery%'";
                $condJoin[]   = "a.title LIKE '%$safeQuery%'";
            }
        }
        $whereSimple  = implode(" AND ", $condSimple);
        $whereJoin    = implode(" AND ", $condJoin);
        $result       = paginate($request, $articles, $whereSimple, $whereJoin, ['a.id' => 'DESC'], 6);
        $templateFile = 'listarticlesfromsearch.tpl.html';
        break;
}

// ===== ASSIGN =====
$template->assign('items',        $result['items']);
$template->assign('page',         $result['page']);
$template->assign('totalPages',   $result['totalPages']);
$template->assign('totalRows',    $result['totalRows']);
$template->assign('templatePath', TEMPLATE_PATH);
$template->assign('userTemplate', 'mpx');
$template->assign('uploads',      $uploads);
$template->assign('lang',         $lang);
$template->assign('messages',     $messages);
$template->assign('isProductCate', $isProductCate);

$template->display($templateFile);
exit;