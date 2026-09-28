<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();

include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialarticleratings.class.php');

function editorialRatingResponse($success, $data = array(), $status = 200)
{
    http_response_code($status);
    echo json_encode(array_merge(array('success' => (bool)$success), $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$lang = (string)($_POST['lang'] ?? 'vn');
if (!in_array($lang, array('vn', 'en', 'zh'), true)) $lang = 'vn';
$messages = array(
    'vn' => array('login' => 'Bạn cần đăng nhập để đánh giá bài viết.', 'method' => 'Yêu cầu không hợp lệ.', 'csrf' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 'rating' => 'Mức đánh giá phải từ 1 đến 5 sao.', 'rate' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.', 'article' => 'Bài viết không tồn tại hoặc chưa được xuất bản.', 'save' => 'Chưa thể lưu đánh giá. Vui lòng thử lại.', 'success' => 'Cảm ơn bạn đã đánh giá bài viết.'),
    'en' => array('login' => 'Please sign in to rate this article.', 'method' => 'Invalid request.', 'csrf' => 'Your session has expired. Please reload the page.', 'rating' => 'Please choose a rating from 1 to 5 stars.', 'rate' => 'Too many attempts. Please try again in a few minutes.', 'article' => 'This article is unavailable or unpublished.', 'save' => 'Your rating could not be saved. Please try again.', 'success' => 'Thank you for rating this article.'),
    'zh' => array('login' => '请登录后评价文章。', 'method' => '请求无效。', 'csrf' => '会话已过期，请刷新页面。', 'rating' => '请选择1到5星的评分。', 'rate' => '操作过于频繁，请稍后再试。', 'article' => '文章不存在或尚未发布。', 'save' => '暂时无法保存评分，请重试。', 'success' => '感谢您评价这篇文章。'),
);
$copy = $messages[$lang];

$customerId = (int)($_SESSION['store_customerId'] ?? 0);
if ($customerId < 1) editorialRatingResponse(false, array('message' => $copy['login']), 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') editorialRatingResponse(false, array('message' => $copy['method']), 405);
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    editorialRatingResponse(false, array('message' => $copy['csrf']), 403);
}

$articleId = (int)($_POST['article_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
if ($articleId < 1 || $rating < 1 || $rating > 5) {
    editorialRatingResponse(false, array('message' => $copy['rating']), 422);
}

$now = time();
$rateKey = 'editorial_rating_' . $customerId;
$attempts = isset($_SESSION[$rateKey]) && is_array($_SESSION[$rateKey]) ? $_SESSION[$rateKey] : array();
$attempts = array_values(array_filter($attempts, function ($timestamp) use ($now) { return ($now - (int)$timestamp) < 300; }));
if (count($attempts) >= 10) editorialRatingResponse(false, array('message' => $copy['rate']), 429);
$attempts[] = $now;
$_SESSION[$rateKey] = $attempts;

$storeId = 1;
$articles = new Articles($storeId);
$article = $articles->getObject($articleId, 'id', "a.`status` = '1'");
if (!$article) editorialRatingResponse(false, array('message' => $copy['article']), 404);

$ratings = new EditorialArticleRatings($storeId);
if (!$ratings->saveMemberRating($articleId, $customerId, $rating)) {
    editorialRatingResponse(false, array('message' => $copy['save']), 500);
}
$summary = $ratings->getSummary($articleId);
editorialRatingResponse(true, array(
    'message' => $copy['success'],
    'rating' => $rating,
    'average' => $summary['average'],
    'count' => $summary['count'],
));
