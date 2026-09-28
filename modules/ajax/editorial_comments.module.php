<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();

include_once(ROOT_PATH . 'classes/dao/articles.class.php');
include_once(ROOT_PATH . 'classes/dao/customers.class.php');
include_once(ROOT_PATH . 'classes/dao/editorialarticlecomments.class.php');

function editorialCommentsResponse($success, $data = array(), $status = 200)
{
    http_response_code($status);
    echo json_encode(array_merge(array('success' => (bool)$success), $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$lang = (string)($input['lang'] ?? 'vn');
if (!in_array($lang, array('vn', 'en', 'zh'), true)) $lang = 'vn';
$messages = array(
    'vn' => array(
        'login' => 'Bạn cần đăng nhập để sử dụng bình luận.',
        'method' => 'Yêu cầu không hợp lệ.',
        'csrf' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.',
        'article' => 'Bài viết không tồn tại hoặc chưa được xuất bản.',
        'content' => 'Bình luận cần có từ 10 đến 2.000 ký tự.',
        'rate' => 'Bạn đã gửi nhiều bình luận trong thời gian ngắn. Vui lòng thử lại sau.',
        'save' => 'Chưa thể lưu bình luận. Vui lòng thử lại.',
        'success' => 'Bình luận đã được gửi và đang chờ duyệt.',
        'reader' => 'Bạn đọc',
    ),
    'en' => array(
        'login' => 'Please sign in to use comments.',
        'method' => 'Invalid request.',
        'csrf' => 'Your session has expired. Please reload the page.',
        'article' => 'This article is unavailable or unpublished.',
        'content' => 'Comments must contain between 10 and 2,000 characters.',
        'rate' => 'You have submitted too many comments recently. Please try again later.',
        'save' => 'Your comment could not be saved. Please try again.',
        'success' => 'Your comment was submitted and is awaiting moderation.',
        'reader' => 'Reader',
    ),
    'zh' => array(
        'login' => '请登录后使用评论功能。',
        'method' => '请求无效。',
        'csrf' => '会话已过期，请刷新页面。',
        'article' => '文章不存在或尚未发布。',
        'content' => '评论内容需为10至2000个字符。',
        'rate' => '您最近提交的评论过多，请稍后再试。',
        'save' => '暂时无法保存评论，请重试。',
        'success' => '评论已提交，正在等待审核。',
        'reader' => '读者',
    ),
);
$copy = $messages[$lang];
$customerId = (int)($_SESSION['store_customerId'] ?? 0);
if ($customerId < 1) editorialCommentsResponse(false, array('message' => $copy['login']), 401);

$storeId = 1;
$articleId = (int)($input['article_id'] ?? 0);
$articles = new Articles($storeId);
$article = $articleId > 0 ? $articles->getObject($articleId, 'id', "a.`status` = '1'") : 0;
if (!$article) editorialCommentsResponse(false, array('message' => $copy['article']), 404);

$comments = new EditorialArticleComments($storeId);
$customers = new Customers($storeId);
$action = (string)($input['action'] ?? 'list');

if ($action === 'list') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') editorialCommentsResponse(false, array('message' => $copy['method']), 405);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 10;
    $rows = $comments->getApproved($articleId, $page, $perPage);
    $items = array();
    foreach ($rows as $row) {
        $author = trim((string)$customers->getFullNameFromId((int)$row['customer_id']));
        if ($author === '') $author = trim((string)$customers->getUserNameFromId((int)$row['customer_id']));
        if ($author === '') $author = $copy['reader'];
        $items[] = array(
            'id' => (int)$row['id'],
            'author' => $author,
            'content' => (string)$row['content'],
            'created_at' => (string)$row['date_created'],
        );
    }
    $total = $comments->countApproved($articleId);
    editorialCommentsResponse(true, array(
        'items' => $items,
        'page' => $page,
        'total' => $total,
        'total_pages' => $total > 0 ? (int)ceil($total / $perPage) : 0,
    ));
}

if ($action !== 'create' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    editorialCommentsResponse(false, array('message' => $copy['method']), 405);
}
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    editorialCommentsResponse(false, array('message' => $copy['csrf']), 403);
}

$content = html_entity_decode((string)($_POST['content'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$content = strip_tags($content);
$content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);
$content = trim(preg_replace('/[ \t]+/u', ' ', $content));
$length = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
if ($length < 10 || $length > 2000) editorialCommentsResponse(false, array('message' => $copy['content']), 422);

$rateKey = 'editorial_comments_' . $customerId;
$now = time();
$attempts = isset($_SESSION[$rateKey]) && is_array($_SESSION[$rateKey]) ? $_SESSION[$rateKey] : array();
$attempts = array_values(array_filter($attempts, function ($timestamp) use ($now) { return ($now - (int)$timestamp) < 600; }));
if (count($attempts) >= 3 || $comments->countRecentByCustomer($customerId, 10) >= 3) {
    editorialCommentsResponse(false, array('message' => $copy['rate']), 429);
}

$commentId = $comments->addPending($articleId, $customerId, $content);
if (!$commentId) editorialCommentsResponse(false, array('message' => $copy['save']), 500);
$attempts[] = $now;
$_SESSION[$rateKey] = $attempts;
editorialCommentsResponse(true, array('message' => $copy['success'], 'comment_id' => (int)$commentId), 201);
