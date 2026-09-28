<?php
/*************************************************************************
Editorial public author page
**************************************************************************/
include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/users.class.php');

$uploads = new Uploads($storeId);
$articles = new Articles($storeId);
$users = new Users($storeId);
$template->assign('uploads', $uploads);

$authorId = (int)$request->element('author_id');
$author = $authorId > 0 ? $users->getObject($authorId) : 0;
$authorCondition = 'a.status = 1 AND a.poster_id = ' . $authorId;
if ($lang === 'en') $authorCondition .= " AND a.slug_en <> '' AND a.lang LIKE '%en%'";
if ($lang === 'zh') $authorCondition .= " AND a.slug_zh <> '' AND a.lang LIKE '%zh%'";

$authorCount = $authorId > 0 ? $articles->getNumItems('id', $authorCondition, 12) : 0;
$hasPublishedArticles = $authorCount && !empty($authorCount['rows']);
if (!$author || !$author->isEnabled() || !$hasPublishedArticles) {
    http_response_code(404);
    $templateFile = '404.tpl.html';
    return;
}

$result = paginate(
    $request,
    $articles,
    $authorCondition,
    $authorCondition,
    array('COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC', 'a.id' => 'DESC'),
    12
);

$authorName = trim((string)$author->getFullName());
if ($authorName === '') {
    $authorName = $lang === 'en' ? 'Editorial Board' : ($lang === 'zh' ? '编辑部' : 'Ban biên tập');
}
$authorIntro = trim(strip_tags((string)$author->getProperty('custom_intro')));
if ($lang === 'en' && $author->getProperty('custom_intro_en')) {
    $authorIntro = trim(strip_tags((string)$author->getProperty('custom_intro_en')));
} elseif ($lang === 'zh' && $author->getProperty('custom_intro_zh')) {
    $authorIntro = trim(strip_tags((string)$author->getProperty('custom_intro_zh')));
}
if (function_exists('mb_substr')) $authorIntro = mb_substr($authorIntro, 0, 600, 'UTF-8');
else $authorIntro = substr($authorIntro, 0, 600);

$authorProfile = array(
    'id' => $authorId,
    'name' => $authorName,
    'intro' => $authorIntro
);
$authorAvatar = $author->getAvatarImage($uploads);
$authorPath = $lang === 'vn' ? '/tac-gia/' . $authorId : '/' . $lang . '/author/' . $authorId;

$template->assign('authorProfile', $authorProfile);
$template->assign('authorAvatar', $authorAvatar);
$template->assign('authorArticles', $result['items']);
$template->assign('authorPage', $result['page']);
$template->assign('authorTotalPages', $result['totalPages']);
$template->assign('authorTotalRows', $result['totalRows']);
$template->assign('authorPath', $authorPath);
$template->assign('urlVi', '/tac-gia/' . $authorId);
$template->assign('urlEn', '/en/author/' . $authorId);
$template->assign('urlZh', '/zh/author/' . $authorId);

$authorLabel = $lang === 'en' ? 'Author' : ($lang === 'zh' ? '作者' : 'Tác giả');
$pageTitle = $authorName . ' | ' . $authorLabel;
$pageDescription = $authorIntro !== '' ? $authorIntro : ($authorLabel . ': ' . $authorName);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $authorName);
$template->assign('pageKeywords', $authorName);
$template->assign('pageDescription', $pageDescription);
$topNav = array(
    array('name' => $lang === 'en' ? 'Home' : ($lang === 'zh' ? '首页' : 'Trang chủ'), 'url' => $publicHome),
    array('name' => $authorName, 'url' => $authorPath)
);
$template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));
$template->assign('slugActive', 'author');
$templateFile = 'editorial-author.tpl.html';
?>
