<?php
include_once(ROOT_PATH . 'classes/dao/uploads.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/articlecategories.class.php');

$uploads = new Uploads($storeId);
$articles = new Articles($storeId);
$articleCategories = new ArticleCategories($storeId);
$template->assign('uploads', $uploads);

$categoryRows = $db->query(
    "SELECT id, parent_id, slug FROM dc_article_categories " .
    "WHERE store_id IN (0," . (int) $storeId . ") AND status = 1 " .
    "ORDER BY position ASC, id ASC"
) ?: array();

$byParent = array();
$bySlug = array();
foreach ($categoryRows as $category) {
    $categoryId = isset($category['id']) ? (int)$category['id'] : 0;
    if ($categoryId <= 0) continue;
    $parentId = isset($category['parent_id']) ? (int)$category['parent_id'] : 0;
    $categorySlug = isset($category['slug']) ? (string)$category['slug'] : '';
    $byParent[$parentId][] = $categoryId;
    if ($categorySlug !== '') $bySlug[$categorySlug][] = $categoryId;
}

$editorialCategoryIds = array();
$visitedCategoryIds = array();
$pendingCategoryIds = array();
foreach (array('van-tho', 'nghe-thuat', 'tin-tuc-moi', 'tin-tuc', 'video') as $rootSlug) {
    if (!empty($bySlug[$rootSlug])) {
        foreach ($bySlug[$rootSlug] as $categoryId) $pendingCategoryIds[] = (int)$categoryId;
    }
}
foreach (array('tho', 'van-xuoi', 'am-nhac', 'my-thuat', 'san-khau-nghe-thuat', 'van-hoa') as $fallbackSlug) {
    if (!empty($bySlug[$fallbackSlug])) {
        foreach ($bySlug[$fallbackSlug] as $categoryId) $pendingCategoryIds[] = (int)$categoryId;
    }
}
while ($pendingCategoryIds) {
    $categoryId = (int)array_shift($pendingCategoryIds);
    if ($categoryId <= 0 || isset($visitedCategoryIds[$categoryId])) continue;
    $visitedCategoryIds[$categoryId] = true;
    $editorialCategoryIds[] = $categoryId;
    if (!empty($byParent[$categoryId])) {
        foreach ($byParent[$categoryId] as $childId) $pendingCategoryIds[] = (int)$childId;
    }
}
$condition = $editorialCategoryIds
    ? 'a.status = 1 AND a.category_id IN (' . implode(',', $editorialCategoryIds) . ')'
    : '1 = 0';
if ($lang === 'en') $condition .= " AND a.slug_en <> '' AND a.lang LIKE '%en%'";
if ($lang === 'zh') $condition .= " AND a.slug_zh <> '' AND a.lang LIKE '%zh%'";

$orderedArticles = $articles->getObjects(
    1,
    $condition,
    array('a.id' => 'DESC', 'COALESCE(a.`publish_at`, a.`date_created`)' => 'DESC'),
    13
) ?: array();

$homeCover = isset($orderedArticles[0]) ? $orderedArticles[0] : null;
$homeLatest = array_slice($orderedArticles, 1, 6);
$homeEarlier = array_slice($orderedArticles, 7, 6);
$homePrimaryIds = array();
if ($homeCover) $homePrimaryIds[] = (int)$homeCover->getId();
foreach ($homeLatest as $primaryArticle) $homePrimaryIds[] = (int)$primaryArticle->getId();
$homePrimaryIds = array_values(array_unique($homePrimaryIds));

// V48 editorial assignments override the automatic newest-first selection.
// The table check keeps the public homepage working before the migration is run.
$featureTable = DB_PREFIX . 'editorial_features';
$featureCheck = $db->query("SHOW TABLES LIKE '" . addslashes($featureTable) . "'");
$hasFeatureTable = $featureCheck && $db->numRows($featureCheck) > 0;
if ($featureCheck) $db->freeResult($featureCheck);
if ($hasFeatureTable) {
    $featureResult = $db->query(
        "SELECT article_id, feature_type FROM `" . $featureTable . "` " .
        "WHERE store_id = " . (int)$storeId . " AND status = 1 " .
        "AND (start_at IS NULL OR start_at <= NOW()) " .
        "AND (end_at IS NULL OR end_at >= NOW()) " .
        "AND feature_type IN ('cover','featured','trending_override') ORDER BY feature_type, position, id"
    );
    $curatedCoverId = 0;
    $curatedFeaturedIds = array();
    $curatedTrendingIds = array();
    if ($featureResult) {
        while ($feature = $db->fetchArray($featureResult, 1)) {
            if ($feature['feature_type'] === 'cover' && !$curatedCoverId) $curatedCoverId = (int)$feature['article_id'];
            if ($feature['feature_type'] === 'featured') $curatedFeaturedIds[] = (int)$feature['article_id'];
            if ($feature['feature_type'] === 'trending_override') $curatedTrendingIds[] = (int)$feature['article_id'];
        }
        $db->freeResult($featureResult);
    }

    $isAvailableForLanguage = function ($article) use ($lang) {
        if (!$article || (int)$article->getStatus() !== 1) return false;
        if ($lang === 'en') return $article->hasLang('en') && $article->getSlugEn() !== '';
        if ($lang === 'zh') return $article->hasLang('zh') && $article->getSlugZh() !== '';
        return true;
    };
    if ($curatedCoverId) {
        $candidate = $articles->getObject($curatedCoverId);
        if ($isAvailableForLanguage($candidate)) $homeCover = $candidate;
    }

    // Rebuild chronological sections after resolving a curated cover so the
    // newest automatic article is not discarded when an older cover is pinned.
    $automaticArticles = array();
    $coverId = $homeCover ? (int)$homeCover->getId() : 0;
    foreach ($orderedArticles as $candidate) {
        if ((int)$candidate->getId() !== $coverId) $automaticArticles[] = $candidate;
    }
    $homeLatest = array_slice($automaticArticles, 0, 6);
    $homeEarlier = array_slice($automaticArticles, 6, 6);

    if ($curatedFeaturedIds) {
        $selected = array();
        $usedIds = array($homeCover ? (int)$homeCover->getId() : 0);
        // Keep the newest automatic item at the front of the latest section.
        // Curated features follow it instead of replacing fresh publications.
        foreach ($orderedArticles as $candidate) {
            $candidateId = (int)$candidate->getId();
            if (!in_array($candidateId, $usedIds, true)) {
                $selected[] = $candidate;
                $usedIds[] = $candidateId;
                break;
            }
        }
        foreach ($curatedFeaturedIds as $articleId) {
            if (count($selected) >= 6) break;
            $candidate = $articles->getObject($articleId);
            if ($isAvailableForLanguage($candidate) && !in_array($articleId, $usedIds, true)) {
                $selected[] = $candidate;
                $usedIds[] = $articleId;
            }
        }
        foreach ($orderedArticles as $candidate) {
            if (count($selected) >= 6) break;
            if (!in_array((int)$candidate->getId(), $usedIds, true)) {
                $selected[] = $candidate;
                $usedIds[] = (int)$candidate->getId();
            }
        }
        $homeLatest = $selected;
        $homeEarlier = array();
        foreach ($orderedArticles as $candidate) {
            if (count($homeEarlier) >= 6) break;
            if (!in_array((int)$candidate->getId(), $usedIds, true)) $homeEarlier[] = $candidate;
        }
    }

    $homePrimaryIds = array();
    if ($homeCover) $homePrimaryIds[] = (int)$homeCover->getId();
    foreach ($homeLatest as $primaryArticle) $homePrimaryIds[] = (int)$primaryArticle->getId();
    $homePrimaryIds = array_values(array_unique($homePrimaryIds));

    $homeTrending = array();
    $trendingIds = array();
    foreach ($curatedTrendingIds as $articleId) {
        $candidate = $articles->getObject($articleId);
        if ($isAvailableForLanguage($candidate) && !in_array($articleId, $trendingIds, true) && !in_array($articleId, $homePrimaryIds, true)) {
            $homeTrending[] = $candidate;
            $trendingIds[] = $articleId;
        }
    }
    $dailyTable = DB_PREFIX . 'article_view_daily';
    $dailyCheck = $db->query("SHOW TABLES LIKE '" . addslashes($dailyTable) . "'");
    $hasDailyTable = $dailyCheck && $db->numRows($dailyCheck) > 0;
    if ($dailyCheck) $db->freeResult($dailyCheck);
    if ($hasDailyTable && count($homeTrending) < 4) {
        $dailyResult = $db->query(
            "SELECT article_id, SUM(views) AS total_views FROM `" . $dailyTable . "` " .
            "WHERE store_id = " . (int)$storeId . " AND view_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) " .
            "GROUP BY article_id ORDER BY total_views DESC LIMIT 12"
        );
        if ($dailyResult) {
            while ($daily = $db->fetchArray($dailyResult, 1)) {
                $articleId = (int)$daily['article_id'];
                if (in_array($articleId, $trendingIds, true) || in_array($articleId, $homePrimaryIds, true)) continue;
                $candidate = $articles->getObject($articleId);
                if ($isAvailableForLanguage($candidate)) {
                    $homeTrending[] = $candidate;
                    $trendingIds[] = $articleId;
                    if (count($homeTrending) >= 4) break;
                }
            }
            $db->freeResult($dailyResult);
        }
    }
    if (count($homeTrending) < 4) {
        $fallbackTrending = $articles->getObjects(1, $condition, array('a.viewed' => 'DESC'), 12) ?: array();
        foreach ($fallbackTrending as $candidate) {
            $articleId = (int)$candidate->getId();
            if (!in_array($articleId, $trendingIds, true) && !in_array($articleId, $homePrimaryIds, true)) {
                $homeTrending[] = $candidate;
                $trendingIds[] = $articleId;
                if (count($homeTrending) >= 4) break;
            }
        }
    }
    $template->assign('homeTrending', $homeTrending);
}

if (!isset($homeTrending)) {
    $trendingCandidates = $articles->getObjects(1, $condition, array('a.viewed' => 'DESC'), 12) ?: array();
    $homeTrending = array();
    foreach ($trendingCandidates as $candidate) {
        if (in_array((int)$candidate->getId(), $homePrimaryIds, true)) continue;
        $homeTrending[] = $candidate;
        if (count($homeTrending) >= 4) break;
    }
    $template->assign('homeTrending', $homeTrending);
}

$template->assign('homeCover', $homeCover);
$template->assign('homeLatest', $homeLatest);
$template->assign('homeEarlier', $homeEarlier);
$templateFile = 'editorial-home.tpl.html';
$slugActive = '';
$template->assign('slugActive', $slugActive);
$template->assign('pageTitle', $estore->getName());
$template->assign('titlePage', $estore->getName());
$template->assign('pageKeywords', 'văn thơ, nghệ thuật, tin tức, video');
$template->assign('pageDescription', 'Không gian văn học, nghệ thuật và văn hóa.');
