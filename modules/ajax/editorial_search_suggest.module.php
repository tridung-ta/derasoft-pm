<?php
header('Content-Type: application/json; charset=UTF-8');
include_once(ROOT_PATH . 'classes/dao/estores.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');

$storeId = 1;
$q = trim((string)$request->element('q'));
$lang = strtolower(trim((string)$request->element('lang', 'vn')));
if (!in_array($lang, array('vn', 'en', 'zh'), true)) $lang = 'vn';
if (function_exists('mb_substr')) $q = mb_substr($q, 0, 80, 'UTF-8');
if (function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') < 2 : strlen($q) < 2) {
    echo json_encode(array('items' => array()), JSON_UNESCAPED_UNICODE);
    exit;
}

$safeQ = mysqli_real_escape_string($db->connection, $q);
$languageCondition = '';
if ($lang === 'en') $languageCondition = " AND a.slug_en <> '' AND a.lang LIKE '%en%'";
if ($lang === 'zh') $languageCondition = " AND a.slug_zh <> '' AND a.lang LIKE '%zh%'";
$sql = "SELECT a.id,a.title,a.slug,a.slug_en,a.slug_zh,a.properties,c.name category_name " .
    "FROM `" . DB_PREFIX . "articles` a LEFT JOIN `" . DB_PREFIX . "article_categories` c ON c.id=a.category_id " .
    "WHERE a.store_id IN (0,$storeId) AND a.status=1 $languageCondition " .
    "AND (a.title LIKE '%$safeQ%' OR a.description LIKE '%$safeQ%' OR a.keyword LIKE '%$safeQ%' OR a.properties LIKE '%$safeQ%') " .
    "ORDER BY a.viewed DESC, a.id DESC LIMIT 6";
$result = $db->query($sql);
$items = array();
if ($result) {
    while ($row = $db->fetchArray($result, 1)) {
        $properties = @unserialize($row['properties'], array('allowed_classes' => false));
        if (!is_array($properties)) $properties = array();
        $titleKey = 'custom_' . $lang . '_title';
        $title = $lang === 'vn' ? $row['title'] : (!empty($properties[$titleKey]) ? $properties[$titleKey] : $row['title']);
        $slug = $lang === 'en' ? $row['slug_en'] : ($lang === 'zh' ? $row['slug_zh'] : $row['slug']);
        $prefix = $lang === 'vn' ? '' : '/' . $lang;
        $items[] = array('title' => strip_tags($title), 'category' => $row['category_name'], 'url' => $prefix . '/' . ltrim($slug, '/'));
    }
    $db->freeResult($result);
}
echo json_encode(array('items' => $items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
