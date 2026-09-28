<?php
/**
 * Smoke-check multilingual editorial content without loading site config or DB.
 * Run locally: php cron/editorial-translation-smoke.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/classes/dao/articleinfo.class.php';
require_once dirname(__DIR__) . '/classes/template/smarty.class.php';

$properties = serialize(array(
    'custom_en_title' => 'English title',
    'custom_en_keyword' => 'english keyword',
    'custom_en_description' => 'English description',
    'custom_en_detail' => '<p>English detail</p>',
    'custom_zh_title' => 'Chinese title',
    'custom_zh_keyword' => 'chinese keyword',
    'custom_zh_description' => 'Chinese description',
    'custom_zh_detail' => '<p>Chinese detail</p>',
));

$article = new ArticleInfo(
    'tieu-de', 'english-title', 'chinese-title', 'Tiêu đề', 'tu khoa', 'Mô tả', '<p>Nội dung</p>',
    0, 0, '', '2026-09-25 00:00:00', '2026-09-25 00:00:00', 1, $properties, 1, 0, null,
    '', '', 0, 'vn,en,zh', '', '', 0, '', '', 1, 1, 1
);

$checks = array(
    'English title resolution' => $article->getTitle('en') === 'English title',
    'Chinese detail resolution' => $article->getDetail('zh') === '<p>Chinese detail</p>',
    'Vietnamese fallback' => $article->getTitle('fr') === 'Tiêu đề',
    'English URL' => $article->getUrl('en') === '/en/english-title',
    'Chinese URL' => $article->getUrl('zh') === '/zh/chinese-title',
);

$smarty = new Smarty();
$smarty->setTemplateDir(dirname(__DIR__) . '/templates/admin');
$smarty->setCompileDir(sys_get_temp_dir());
$smarty->force_compile = true;
$smarty->assign('item', $article);
$smarty->assign('error', array('INPUT' => array()));
$html = $smarty->fetch('managearticletranslations.tpl.html');

foreach (array('title_en', 'keyword_en', 'description_en', 'detail_en', 'title_zh', 'keyword_zh', 'description_zh', 'detail_zh') as $field) {
    $checks['Admin field ' . $field] = strpos($html, 'name="' . $field . '"') !== false;
}
$checks['Existing English value'] = strpos($html, 'value="English title"') !== false;
$checks['Existing Chinese value'] = strpos($html, 'value="Chinese title"') !== false;
$checks['Inline translation feedback'] = strpos($html, 'article-translations__panel small') !== false;

foreach (array('articleadd', 'articleedit') as $moduleName) {
    $moduleSource = file_get_contents(dirname(__DIR__) . '/modules/admin/manage/' . $moduleName . '.module.php');
    $checks[$moduleName . ' validates translated title'] = strpos($moduleSource, "array('title', 'description', 'detail')") !== false;
    $checks[$moduleName . ' keeps Vietnamese available'] = strpos($moduleSource, "array_unshift(\$selectedLanguages, 'vn')") !== false;
}

$failures = array_keys(array_filter($checks, function ($passed) { return !$passed; }));
if ($failures) {
    fwrite(STDERR, "FAIL:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'OK: ' . count($checks) . " multilingual article checks passed.\n";
