<?php
require_once dirname(__DIR__) . '/includes/editorial_category_scope.inc.php';

function assertCategoryScope($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

$rows = array(
    array('id' => 10, 'parent_id' => 0, 'slug' => 'van-tho'),
    array('id' => 11, 'parent_id' => 10, 'slug' => 'tho'),
    array('id' => 12, 'parent_id' => 11, 'slug' => 'tho-hien-dai'),
    array('id' => 20, 'parent_id' => 999, 'slug' => 'van-xuoi'),
    array('id' => 30, 'parent_id' => 0, 'slug' => 'nghe-thuat'),
    array('id' => 31, 'parent_id' => 30, 'slug' => 'am-nhac'),
    array('id' => 32, 'parent_id' => 0, 'slug' => 'am-nhac'),
    array('id' => 90, 'parent_id' => 0, 'slug' => 'dich-vu'),
);

$ids = editorialCollectCategoryIds(
    $rows,
    array('van-tho', 'nghe-thuat'),
    array('tho', 'van-xuoi', 'am-nhac')
);

foreach (array(10, 11, 12, 20, 30, 31, 32) as $expectedId) {
    assertCategoryScope(in_array($expectedId, $ids, true), 'missing editorial category ' . $expectedId);
}
assertCategoryScope(!in_array(90, $ids, true), 'unrelated category was included');
assertCategoryScope(count($ids) === count(array_unique($ids)), 'category IDs contain duplicates');

echo "Editorial category scope smoke test passed." . PHP_EOL;
