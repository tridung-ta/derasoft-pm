<?php
/*************************************************************************
V48 Editorial Control Center
**************************************************************************/
checkPermission(array(1, 2, 3));

$templateFile = 'editorial.tpl.html';
$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    'Trung tâm biên tập' => ''
);

function editorialTableExists($db, $table)
{
    $escaped = mysqli_real_escape_string($db->connection, $table);
    $result = $db->query("SHOW TABLES LIKE '" . $escaped . "'");
    if (!$result) return false;
    $exists = $db->numRows($result) > 0;
    $db->freeResult($result);
    return $exists;
}

function editorialRows($db, $sql)
{
    $result = $db->query($sql);
    if (!$result) return array();
    $rows = array();
    while ($row = $db->fetchArray($result, 1)) $rows[] = $row;
    $db->freeResult($result);
    return $rows;
}

function editorialScalar($db, $sql, $key)
{
    $rows = editorialRows($db, $sql);
    return isset($rows[0][$key]) ? (int)$rows[0][$key] : 0;
}

function editorialProperties($serialized)
{
    if (!$serialized) return array();
    $value = @unserialize($serialized, array('allowed_classes' => false));
    return is_array($value) ? $value : array();
}

function editorialHasTranslation($row, $properties, $lang)
{
    if ($lang === 'vn') {
        return trim(strip_tags((string)$row['title'])) !== '' && trim(strip_tags((string)$row['detail'])) !== '';
    }
    $langs = array_map('trim', explode(',', strtolower((string)$row['lang'])));
    $titleKey = 'custom_' . $lang . '_title';
    $detailKey = 'custom_' . $lang . '_detail';
    $slugKey = $lang === 'en' ? 'slug_en' : 'slug_zh';
    return in_array($lang, $langs, true)
        && trim((string)$row[$slugKey]) !== ''
        && trim(strip_tags(isset($properties[$titleKey]) ? $properties[$titleKey] : '')) !== ''
        && trim(strip_tags(isset($properties[$detailKey]) ? $properties[$detailKey] : '')) !== '';
}

$storeId = (int)$storeId;
$featureTableReady = editorialTableExists($db, DB_PREFIX . 'editorial_features');
$viewTableReady = editorialTableExists($db, DB_PREFIX . 'article_view_daily');
$commentTableReady = editorialTableExists($db, DB_PREFIX . 'editorial_article_comments');
$newsletterTableReady = editorialTableExists($db, DB_PREFIX . 'editorial_newsletter_subscribers');
$membershipPlanTableReady = editorialTableExists($db, DB_PREFIX . 'editorial_membership_plans');
$subscriptionTableReady = editorialTableExists($db, DB_PREFIX . 'editorial_subscriptions');
$membershipTableReady = $membershipPlanTableReady && $subscriptionTableReady;
$template->assign('featureTableReady', $featureTableReady);
$template->assign('viewTableReady', $viewTableReady);
$template->assign('commentTableReady', $commentTableReady);
$template->assign('newsletterTableReady', $newsletterTableReady);
$template->assign('membershipTableReady', $membershipTableReady);

include_once(ROOT_PATH . 'classes/dao/editorialmembershipplans.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialsubscriptions.class.php');
$editorialMembershipPlans = new EditorialMembershipPlans($storeId);
$editorialSubscriptions = new EditorialSubscriptions($storeId);

if (empty($_SESSION['editorial_csrf'])) {
    $_SESSION['editorial_csrf'] = bin2hex(random_bytes(24));
}
$template->assign('editorialCsrf', $_SESSION['editorial_csrf']);

$featureTypes = array(
    'cover' => 1,
    'featured' => 6,
    'trending_override' => 3,
    'video_featured' => 1
);
$editorialVideoChoices = array(
    'Q8ucXj2pDbo' => 'Không gian văn hóa nghệ thuật: Bảo tàng Mỹ thuật Việt Nam',
    'q-r5WwQukik' => 'Phim tài liệu 60 năm Bảo tàng Mỹ thuật Việt Nam',
    'vx54SQs3A1M' => 'Trải nghiệm mỹ thuật với ứng dụng iMuseum VFA',
    'tJTkPdk3Mks' => 'Không gian mỹ thuật đương đại'
);
$template->assign('editorialVideoChoices', $editorialVideoChoices);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request->element('doo') === 'moderate_comment') {
    $userInfo->checkPermission('comment', 'edit');
    if (!$commentTableReady) {
        $template->assign('editorialError', 'Chưa cài bảng bình luận editorial.');
    } elseif (!hash_equals($_SESSION['editorial_csrf'], (string)$request->element('csrf_token'))) {
        $template->assign('editorialError', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    } else {
        $commentId = (int)$request->element('comment_id');
        $commentStatus = (int)$request->element('comment_status');
        $allowedCommentStatuses = array(1, 2, 3);
        if ($commentId < 1 || !in_array($commentStatus, $allowedCommentStatuses, true)) {
            $template->assign('editorialError', 'Thao tác kiểm duyệt không hợp lệ.');
        } else {
            $updated = $db->query(
                "UPDATE `" . DB_PREFIX . "editorial_article_comments` SET status = $commentStatus, date_updated = NOW() " .
                "WHERE id = $commentId AND store_id = $storeId LIMIT 1"
            );
            if ($updated) {
                $trackings->addData(array(
                    'store_id' => $storeId,
                    'username' => $userInfo->getUsername(),
                    'action' => 'Kiểm duyệt bình luận editorial #' . $commentId . ' thành trạng thái ' . $commentStatus,
                    'date_created' => date('Y-m-d H:i:s'),
                    'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
                ));
                header('Location: /' . ADMIN_SCRIPT . '?op=editorial&comment_saved=1#editorial-comments');
                exit;
            }
            $template->assign('editorialError', 'Không thể cập nhật bình luận. Dữ liệu cũ được giữ nguyên.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request->element('doo') === 'manage_newsletter_subscriber') {
    $userInfo->checkPermission('comment', 'edit');
    if (!$newsletterTableReady) {
        $template->assign('editorialError', 'Chưa cài bảng subscriber Newsletter.');
    } elseif (!hash_equals($_SESSION['editorial_csrf'], (string)$request->element('csrf_token'))) {
        $template->assign('editorialError', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    } else {
        $subscriberId = (int)$request->element('subscriber_id');
        $subscriberStatus = (int)$request->element('subscriber_status');
        if ($subscriberId < 1 || !in_array($subscriberStatus, array(0, 3), true)) {
            $template->assign('editorialError', 'Thao tác subscriber không hợp lệ.');
        } else {
            $updated = $db->query(
                "UPDATE `" . DB_PREFIX . "editorial_newsletter_subscribers` " .
                "SET status = $subscriberStatus, date_updated = NOW() " .
                "WHERE id = $subscriberId AND store_id = $storeId LIMIT 1"
            );
            if ($updated) {
                $trackings->addData(array(
                    'store_id' => $storeId,
                    'username' => $userInfo->getUsername(),
                    'action' => 'Cập nhật Newsletter subscriber #' . $subscriberId . ' thành trạng thái ' . $subscriberStatus,
                    'date_created' => date('Y-m-d H:i:s'),
                    'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
                ));
                header('Location: /' . ADMIN_SCRIPT . '?op=editorial&newsletter_saved=1#editorial-newsletter');
                exit;
            }
            $template->assign('editorialError', 'Không thể cập nhật subscriber. Dữ liệu cũ được giữ nguyên.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($request->element('doo'), array('save_membership_plan', 'change_membership_plan_status', 'grant_editorial_subscription', 'revoke_editorial_subscription'), true)) {
    $userInfo->checkPermission('customer', 'edit');
    if (!$membershipTableReady) {
        $template->assign('editorialError', 'Chưa cài đầy đủ bảng membership editorial.');
    } elseif (!hash_equals($_SESSION['editorial_csrf'], (string)$request->element('csrf_token'))) {
        $template->assign('editorialError', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    } else {
        $membershipAction = $request->element('doo');
        $membershipSaved = false;
        $trackingAction = '';
        if ($membershipAction === 'save_membership_plan') {
            $membershipSaved = (bool)$editorialMembershipPlans->savePlan(0, array(
                'code' => $request->element('plan_code'),
                'name' => $request->element('plan_name'),
                'description' => $request->element('plan_description'),
                'price' => $request->element('plan_price'),
                'currency' => $request->element('plan_currency'),
                'duration_days' => $request->element('plan_duration_days'),
                'status' => $request->element('plan_status'),
                'position' => $request->element('plan_position'),
            ));
            $trackingAction = 'Tạo gói membership editorial';
        } elseif ($membershipAction === 'change_membership_plan_status') {
            $planId = (int)$request->element('plan_id');
            $planStatus = (int)$request->element('plan_status');
            $membershipSaved = (bool)$editorialMembershipPlans->changeStatus($planId, $planStatus);
            $trackingAction = 'Cập nhật trạng thái gói membership #' . $planId;
        } elseif ($membershipAction === 'grant_editorial_subscription') {
            $customerId = (int)$request->element('membership_customer_id');
            $planId = (int)$request->element('membership_plan_id');
            $plan = $editorialMembershipPlans->getById($planId);
            $customerExists = editorialScalar($db, "SELECT COUNT(id) total FROM `" . DB_PREFIX . "customers` WHERE id = $customerId AND store_id IN (0,$storeId) AND status = 1", 'total');
            $startsAtInput = trim((string)$request->element('membership_starts_at'));
            $endsAtInput = trim((string)$request->element('membership_ends_at'));
            $startsAt = $startsAtInput !== '' && strtotime($startsAtInput) !== false ? date('Y-m-d H:i:s', strtotime($startsAtInput)) : date('Y-m-d H:i:s');
            $endsAt = $endsAtInput !== '' && strtotime($endsAtInput) !== false
                ? date('Y-m-d H:i:s', strtotime($endsAtInput))
                : ($plan ? date('Y-m-d H:i:s', strtotime($startsAt . ' +' . (int)$plan['duration_days'] . ' days')) : '');
            if ($plan && (int)$plan['status'] === EditorialMembershipPlans::STATUS_ACTIVE && $customerExists && $endsAt !== '') {
                $membershipSaved = (bool)$editorialSubscriptions->grant(
                    $customerId,
                    $planId,
                    $startsAt,
                    $endsAt,
                    'manual',
                    (int)$userInfo->getId(),
                    $request->element('membership_note')
                );
            }
            $trackingAction = 'Cấp quyền membership cho customer #' . $customerId . ', gói #' . $planId;
        } else {
            $subscriptionId = (int)$request->element('subscription_id');
            $membershipSaved = (bool)$editorialSubscriptions->revoke($subscriptionId);
            $trackingAction = 'Thu hồi subscription editorial #' . $subscriptionId;
        }
        if ($membershipSaved) {
            $trackings->addData(array(
                'store_id' => $storeId,
                'username' => $userInfo->getUsername(),
                'action' => $trackingAction,
                'date_created' => date('Y-m-d H:i:s'),
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
            ));
            header('Location: /' . ADMIN_SCRIPT . '?op=editorial&membership_saved=1#editorial-membership');
            exit;
        }
        $template->assign('editorialError', 'Không thể cập nhật membership. Vui lòng kiểm tra dữ liệu và thử lại.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request->element('doo') === 'save_features') {
    if (!$featureTableReady) {
        $template->assign('editorialError', 'Chưa cài bảng V48. Hãy chạy file SQL được cung cấp trước.');
    } elseif (!hash_equals($_SESSION['editorial_csrf'], (string)$request->element('csrf_token'))) {
        $template->assign('editorialError', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    } else {
        $db->query('START TRANSACTION');
        $saved = true;
        foreach ($featureTypes as $type => $limit) {
            $input = $request->element('feature_' . $type, array());
            $externalKey = '';
            if ($type === 'video_featured') {
                $externalKey = is_array($input) ? '' : trim((string)$input);
                $input = array();
                if ($externalKey !== '' && !isset($editorialVideoChoices[$externalKey])) {
                    $saved = false;
                    break;
                }
            }
            if (!is_array($input)) $input = array($input);
            $ids = array_values(array_unique(array_filter(array_map('intval', $input))));
            $ids = array_slice($ids, 0, $limit);
            $typeSql = mysqli_real_escape_string($db->connection, $type);
            $startInput = trim((string)$request->element('schedule_' . $type . '_start'));
            $endInput = trim((string)$request->element('schedule_' . $type . '_end'));
            $startAt = $startInput !== '' && strtotime($startInput) !== false ? date('Y-m-d H:i:s', strtotime($startInput)) : '';
            $endAt = $endInput !== '' && strtotime($endInput) !== false ? date('Y-m-d H:i:s', strtotime($endInput)) : '';
            if ($startAt !== '' && $endAt !== '' && strtotime($endAt) <= strtotime($startAt)) {
                $saved = false;
                break;
            }
            $startSql = $startAt === '' ? 'NULL' : "'" . mysqli_real_escape_string($db->connection, $startAt) . "'";
            $endSql = $endAt === '' ? 'NULL' : "'" . mysqli_real_escape_string($db->connection, $endAt) . "'";
            if (!$db->query("DELETE FROM `" . DB_PREFIX . "editorial_features` WHERE store_id = $storeId AND feature_type = '$typeSql'")) {
                $saved = false;
                break;
            }
            if ($type === 'video_featured' && $externalKey !== '') {
                $externalSql = mysqli_real_escape_string($db->connection, $externalKey);
                if (!$db->query(
                    "INSERT INTO `" . DB_PREFIX . "editorial_features` " .
                    "(store_id, article_id, external_key, feature_type, position, start_at, end_at, status, created_at, updated_at) " .
                    "VALUES ($storeId, 0, '$externalSql', '$typeSql', 1, $startSql, $endSql, 1, NOW(), NOW())"
                )) {
                    $saved = false;
                    break;
                }
            }
            foreach ($ids as $position => $articleId) {
                $sql = "INSERT INTO `" . DB_PREFIX . "editorial_features` " .
                    "(store_id, article_id, feature_type, position, start_at, end_at, status, created_at, updated_at) " .
                    "SELECT $storeId, id, '$typeSql', " . ($position + 1) . ", $startSql, $endSql, 1, NOW(), NOW() " .
                    "FROM `" . DB_PREFIX . "articles` WHERE id = $articleId AND store_id IN (0,$storeId) LIMIT 1";
                if (!$db->query($sql)) {
                    $saved = false;
                    break 2;
                }
            }
        }
        $db->query($saved ? 'COMMIT' : 'ROLLBACK');
        if ($saved) {
            $trackings->addData(array(
                'store_id' => $storeId,
                'username' => $userInfo->getUsername(),
                'action' => 'Cập nhật Trung tâm biên tập V48',
                'date_created' => date('Y-m-d H:i:s'),
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
            ));
            header('Location: /' . ADMIN_SCRIPT . '?op=editorial&saved=1');
            exit;
        }
        $template->assign('editorialError', 'Không thể lưu cấu hình. Dữ liệu cũ đã được giữ nguyên.');
    }
}

if ($request->element('saved')) $template->assign('editorialSaved', 1);
if ($request->element('comment_saved')) $template->assign('editorialCommentSaved', 1);
if ($request->element('newsletter_saved')) $template->assign('editorialNewsletterSaved', 1);
if ($request->element('membership_saved')) $template->assign('editorialMembershipSaved', 1);

$articleRows = editorialRows($db,
    "SELECT a.id, a.title, a.slug, a.slug_en, a.slug_zh, a.lang, a.description, a.detail, " .
    "a.properties, a.viewed, a.status, a.category_id, a.date_updated, c.name AS category_name " .
    "FROM `" . DB_PREFIX . "articles` a " .
    "LEFT JOIN `" . DB_PREFIX . "article_categories` c ON c.id = a.category_id " .
    "WHERE a.store_id IN (0,$storeId) AND a.status <> 2 " .
    "ORDER BY COALESCE(a.publish_at, a.date_created) DESC, a.id DESC LIMIT 300"
);

$activeFeatures = array('cover' => array(), 'featured' => array(), 'trending_override' => array(), 'video_featured' => array());
$activeVideoFeature = '';
$featureSchedules = array('cover' => array(), 'featured' => array(), 'trending_override' => array(), 'video_featured' => array());
if ($featureTableReady) {
    $featureRows = editorialRows($db,
        "SELECT feature_type, article_id, external_key, start_at, end_at FROM `" . DB_PREFIX . "editorial_features` " .
        "WHERE store_id = $storeId AND status = 1 ORDER BY position ASC, id ASC"
    );
    foreach ($featureRows as $feature) {
        if (isset($activeFeatures[$feature['feature_type']])) {
            if ((int)$feature['article_id'] > 0) $activeFeatures[$feature['feature_type']][] = (int)$feature['article_id'];
            if ($feature['feature_type'] === 'video_featured') $activeVideoFeature = (string)$feature['external_key'];
            if (empty($featureSchedules[$feature['feature_type']])) {
                $featureSchedules[$feature['feature_type']] = array(
                    'start' => $feature['start_at'] ? date('Y-m-d\TH:i', strtotime($feature['start_at'])) : '',
                    'end' => $feature['end_at'] ? date('Y-m-d\TH:i', strtotime($feature['end_at'])) : ''
                );
            }
        }
    }
}
$template->assign('activeFeatures', $activeFeatures);
$template->assign('activeVideoFeature', $activeVideoFeature);
$template->assign('featureSchedules', $featureSchedules);

$healthRows = array();
$languageSummary = array('vn' => 0, 'en' => 0, 'zh' => 0, 'total' => count($articleRows));
foreach ($articleRows as &$article) {
    $properties = editorialProperties($article['properties']);
    $article['has_vn'] = editorialHasTranslation($article, $properties, 'vn');
    $article['has_en'] = editorialHasTranslation($article, $properties, 'en');
    $article['has_zh'] = editorialHasTranslation($article, $properties, 'zh');
    foreach (array('vn', 'en', 'zh') as $code) if ($article['has_' . $code]) $languageSummary[$code]++;

    $issues = array();
    $avatarUrl = isset($properties['avatarUrl']) ? trim($properties['avatarUrl']) : '';
    if (empty($properties['avatarId']) && $avatarUrl === '') $issues[] = 'Thiếu ảnh';
    if (trim(strip_tags((string)$article['description'])) === '') $issues[] = 'Thiếu mô tả';
    if (!$article['has_en']) $issues[] = 'Thiếu EN';
    if (!$article['has_zh']) $issues[] = 'Thiếu 中文';
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)$article['slug'])) $issues[] = 'Slug lỗi';
    if ((int)$article['category_id'] <= 0 || empty($article['category_name'])) $issues[] = 'Chưa có chuyên mục';
    if ($avatarUrl !== '') {
        $imagePath = ROOT_PATH . ltrim(parse_url($avatarUrl, PHP_URL_PATH), '/');
        if (is_file($imagePath) && filesize($imagePath) > 2 * 1024 * 1024) $issues[] = 'Ảnh quá 2 MB';
    }
    $isFeatured = false;
    foreach ($activeFeatures as $ids) if (in_array((int)$article['id'], $ids, true)) $isFeatured = true;
    if ($isFeatured && (int)$article['status'] !== 1) $issues[] = 'Đang nổi bật nhưng đã tắt';
    $article['issues'] = $issues;
    if ($issues) $healthRows[] = $article;
}
unset($article);

$template->assign('editorialArticles', $articleRows);
$template->assign('languageSummary', $languageSummary);
$template->assign('healthRows', array_slice($healthRows, 0, 100));
$template->assign('healthCount', count($healthRows));

$analytics = array('today' => 0, 'days7' => 0, 'days30' => 0, 'read_articles' => 0);
$topGrowth = array();
$topCategory = array();
if ($viewTableReady) {
    $analytics['today'] = editorialScalar($db, "SELECT COALESCE(SUM(views),0) total FROM `" . DB_PREFIX . "article_view_daily` WHERE store_id=$storeId AND view_date=CURDATE()", 'total');
    $analytics['days7'] = editorialScalar($db, "SELECT COALESCE(SUM(views),0) total FROM `" . DB_PREFIX . "article_view_daily` WHERE store_id=$storeId AND view_date>=DATE_SUB(CURDATE(), INTERVAL 6 DAY)", 'total');
    $analytics['days30'] = editorialScalar($db, "SELECT COALESCE(SUM(views),0) total FROM `" . DB_PREFIX . "article_view_daily` WHERE store_id=$storeId AND view_date>=DATE_SUB(CURDATE(), INTERVAL 29 DAY)", 'total');
    $analytics['read_articles'] = editorialScalar($db, "SELECT COUNT(DISTINCT article_id) total FROM `" . DB_PREFIX . "article_view_daily` WHERE store_id=$storeId AND view_date>=DATE_SUB(CURDATE(), INTERVAL 29 DAY)", 'total');
    $topGrowth = editorialRows($db,
        "SELECT a.id, a.title, SUM(v.views) views FROM `" . DB_PREFIX . "article_view_daily` v " .
        "INNER JOIN `" . DB_PREFIX . "articles` a ON a.id=v.article_id " .
        "WHERE v.store_id=$storeId AND v.view_date>=DATE_SUB(CURDATE(), INTERVAL 6 DAY) " .
        "GROUP BY a.id, a.title ORDER BY views DESC LIMIT 5"
    );
    $topCategoryRows = editorialRows($db,
        "SELECT c.name, SUM(v.views) views FROM `" . DB_PREFIX . "article_view_daily` v " .
        "INNER JOIN `" . DB_PREFIX . "articles` a ON a.id=v.article_id " .
        "LEFT JOIN `" . DB_PREFIX . "article_categories` c ON c.id=a.category_id " .
        "WHERE v.store_id=$storeId AND v.view_date>=DATE_SUB(CURDATE(), INTERVAL 29 DAY) " .
        "GROUP BY c.id, c.name ORDER BY views DESC LIMIT 1"
    );
    if ($topCategoryRows) $topCategory = $topCategoryRows[0];
}
$template->assign('editorialAnalytics', $analytics);
$template->assign('topGrowth', $topGrowth);
$template->assign('topCategory', $topCategory);

$commentStatusFilter = $request->element('comment_status_filter');
$commentStatusFilter = $commentStatusFilter === '' || $commentStatusFilter === null ? 0 : (int)$commentStatusFilter;
if (!in_array($commentStatusFilter, array(-1, 0, 1, 2, 3), true)) $commentStatusFilter = 0;
$editorialCommentRows = array();
if ($commentTableReady) {
    $commentCondition = $commentStatusFilter >= 0 ? ' AND ec.status = ' . $commentStatusFilter : '';
    $editorialCommentRows = editorialRows($db,
        "SELECT ec.id, ec.article_id, ec.customer_id, ec.content, ec.status, ec.date_created, " .
        "a.title AS article_title, COALESCE(NULLIF(c.fullname,''), c.username, 'Bạn đọc') AS customer_name " .
        "FROM `" . DB_PREFIX . "editorial_article_comments` ec " .
        "LEFT JOIN `" . DB_PREFIX . "articles` a ON a.id = ec.article_id AND a.store_id IN (0,$storeId) " .
        "LEFT JOIN `" . DB_PREFIX . "customers` c ON c.id = ec.customer_id AND c.store_id = $storeId " .
        "WHERE ec.store_id = $storeId$commentCondition ORDER BY ec.date_created DESC, ec.id DESC LIMIT 100"
    );
}
$template->assign('editorialCommentRows', $editorialCommentRows);
$template->assign('commentStatusFilter', $commentStatusFilter);

$newsletterStatusFilter = $request->element('newsletter_status_filter');
$newsletterStatusFilter = $newsletterStatusFilter === '' || $newsletterStatusFilter === null ? -1 : (int)$newsletterStatusFilter;
if (!in_array($newsletterStatusFilter, array(-1, 0, 1, 2, 3), true)) $newsletterStatusFilter = -1;
$editorialNewsletterRows = array();
if ($newsletterTableReady) {
    $newsletterCondition = $newsletterStatusFilter >= 0 ? ' AND status = ' . $newsletterStatusFilter : '';
    $editorialNewsletterRows = editorialRows($db,
        "SELECT id, email, language, status, source, consented_at, confirmed_at, unsubscribed_at, date_created " .
        "FROM `" . DB_PREFIX . "editorial_newsletter_subscribers` " .
        "WHERE store_id = $storeId$newsletterCondition ORDER BY date_created DESC, id DESC LIMIT 100"
    );
}
$template->assign('editorialNewsletterRows', $editorialNewsletterRows);
$template->assign('newsletterStatusFilter', $newsletterStatusFilter);

$editorialMembershipPlanRows = $membershipTableReady ? $editorialMembershipPlans->getAdminItems() : array();
$editorialSubscriptionRows = $membershipTableReady ? $editorialSubscriptions->getAdminItems(-1, 100) : array();
$editorialMembershipCustomers = $membershipTableReady ? editorialRows($db,
    "SELECT id, username, email, fullname FROM `" . DB_PREFIX . "customers` " .
    "WHERE store_id IN (0,$storeId) AND status = 1 ORDER BY id DESC LIMIT 300"
) : array();
$template->assign('editorialMembershipPlanRows', $editorialMembershipPlanRows);
$template->assign('editorialSubscriptionRows', $editorialSubscriptionRows);
$template->assign('editorialMembershipCustomers', $editorialMembershipCustomers);
?>
