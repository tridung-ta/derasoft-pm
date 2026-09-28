<?php
/*************************************************************************
Editorial public tag archive
**************************************************************************/
include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/articlegroups.class.php');

$uploads = new Uploads($storeId);
$articles = new Articles($storeId);
$articleGroups = new ArticleGroups($storeId);
$template->assign('uploads', $uploads);

$tagSlug = strtolower(trim((string)$request->element('slug')));
if (!preg_match('/^[a-z0-9][a-z0-9-]{0,190}$/', $tagSlug)) {
    http_response_code(404);
    $templateFile = '404.tpl.html';
    return;
}

$tagObject = $articleGroups->getObject($tagSlug, 'slug', '`status` = ' . S_ENABLED);
if (!$tagObject) {
    http_response_code(404);
    $templateFile = '404.tpl.html';
    return;
}

$tagId = (int)$tagObject->getId();
$condition = "a.status = 1 AND FIND_IN_SET(" . $tagId . ", a.article_group_ids)";
if ($lang === 'en') $condition .= " AND a.slug_en <> '' AND a.lang LIKE '%en%'";
if ($lang === 'zh') $condition .= " AND a.slug_zh <> '' AND a.lang LIKE '%zh%'";

$result = paginate(
    $request,
    $articles,
    $condition,
    $condition,
    array('COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC', 'a.id' => 'DESC'),
    12
);

$tagName = $tagObject->getNameByLang($lang);
$tagPath = $lang === 'vn' ? '/chu-de/' . rawurlencode($tagSlug) : '/' . $lang . '/tag/' . rawurlencode($tagSlug);
$template->assign('tagArchive', array('id' => $tagId, 'name' => $tagName, 'slug' => $tagSlug));
$template->assign('tagArticles', $result['items']);
$template->assign('tagPage', $result['page']);
$template->assign('tagTotalPages', $result['totalPages']);
$template->assign('tagTotalRows', $result['totalRows']);
$template->assign('tagPath', $tagPath);
$template->assign('urlVi', '/chu-de/' . rawurlencode($tagSlug));
$template->assign('urlEn', '/en/tag/' . rawurlencode($tagSlug));
$template->assign('urlZh', '/zh/tag/' . rawurlencode($tagSlug));

$tagLabel = $lang === 'en' ? 'Topic' : ($lang === 'zh' ? '主题' : 'Chủ đề');
$template->assign('pageTitle', $tagName . ' | ' . $tagLabel);
$template->assign('titlePage', $tagName);
$template->assign('pageKeywords', $tagName);
$template->assign('pageDescription', $tagLabel . ': ' . $tagName);
$topNav = array(
    array('name' => $lang === 'en' ? 'Home' : ($lang === 'zh' ? '首页' : 'Trang chủ'), 'url' => $publicHome),
    array('name' => $tagName, 'url' => $tagPath)
);
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));
$template->assign('slugActive', 'tag');
$templateFile = 'editorial-tag.tpl.html';
?>
