<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();

include_once(ROOT_PATH . 'classes/dao/editorialnewslettersubscribers.class.php');

function editorialNewsletterResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode(array('success' => (bool)$success, 'message' => (string)$message), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$lang = (string)($_POST['lang'] ?? 'vn');
if (!in_array($lang, array('vn', 'en', 'zh'), true)) $lang = 'vn';
$messages = array(
    'vn' => array(
        'method' => 'Yêu cầu không hợp lệ.',
        'csrf' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.',
        'email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
        'consent' => 'Bạn cần đồng ý nhận bản tin trước khi đăng ký.',
        'rate' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.',
        'save' => 'Chưa thể ghi nhận đăng ký. Vui lòng thử lại.',
        'success' => 'Đăng ký đã được ghi nhận và đang chờ xác nhận.',
    ),
    'en' => array(
        'method' => 'Invalid request.',
        'csrf' => 'Your session has expired. Please reload the page.',
        'email' => 'Please enter a valid email address.',
        'consent' => 'Please agree to receive the newsletter before subscribing.',
        'rate' => 'Too many attempts. Please try again in a few minutes.',
        'save' => 'Your subscription could not be recorded. Please try again.',
        'success' => 'Your subscription was recorded and is awaiting confirmation.',
    ),
    'zh' => array(
        'method' => '请求无效。',
        'csrf' => '会话已过期，请刷新页面。',
        'email' => '请输入有效的电子邮箱地址。',
        'consent' => '订阅前请同意接收电子简报。',
        'rate' => '操作过于频繁，请稍后再试。',
        'save' => '暂时无法记录订阅，请重试。',
        'success' => '订阅已记录，正在等待确认。',
    ),
);
$copy = $messages[$lang];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') editorialNewsletterResponse(false, $copy['method'], 405);
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    editorialNewsletterResponse(false, $copy['csrf'], 403);
}

// Bots commonly fill this visually hidden field. Return the same public success response.
if (trim((string)($_POST['website'] ?? '')) !== '') editorialNewsletterResponse(true, $copy['success'], 200);

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    editorialNewsletterResponse(false, $copy['email'], 422);
}
$consent = (string)($_POST['consent'] ?? '');
if (!in_array($consent, array('1', 'on', 'yes'), true)) {
    editorialNewsletterResponse(false, $copy['consent'], 422);
}

$now = time();
$rateKey = 'editorial_newsletter_attempts';
$attempts = isset($_SESSION[$rateKey]) && is_array($_SESSION[$rateKey]) ? $_SESSION[$rateKey] : array();
$attempts = array_values(array_filter($attempts, function ($timestamp) use ($now) { return ($now - (int)$timestamp) < 600; }));
if (count($attempts) >= 5) editorialNewsletterResponse(false, $copy['rate'], 429);
$attempts[] = $now;
$_SESSION[$rateKey] = $attempts;

try {
    $confirmationToken = bin2hex(random_bytes(32));
    $unsubscribeToken = bin2hex(random_bytes(32));
} catch (Exception $exception) {
    editorialNewsletterResponse(false, $copy['save'], 500);
}

$subscribers = new EditorialNewsletterSubscribers(1);
$saved = $subscribers->savePending(
    $email,
    $lang,
    'footer',
    hash('sha256', $confirmationToken),
    hash('sha256', $unsubscribeToken)
);
if (!isset($saved['result']) || $saved['result'] === 'error' || $saved['result'] === 'invalid') {
    editorialNewsletterResponse(false, $copy['save'], 500);
}

// Deliberately use one response for pending, active and blocked addresses.
editorialNewsletterResponse(true, $copy['success'], 201);
?>
