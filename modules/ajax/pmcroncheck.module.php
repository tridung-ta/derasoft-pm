<?php
// Only the authenticated, fixed web entry may execute this diagnostic.
if (!defined('PM_CRON_CHECK_AUTHORIZED') || PM_CRON_CHECK_AUTHORIZED !== true) {
    http_response_code(404);
    exit;
}

// The original CLI smoke script uses STDERR and exit(1) for failed assertions.
// Keep it unchanged; capture the fixed fixture's failure text without exposing PHP errors.
if (!defined('STDERR')) {
    define('STDERR', fopen('php://temp', 'w+'));
}
$pmCheckCompleted = false;
$pmCheckBufferLevel = ob_get_level();
ob_start();
http_response_code(500);
register_shutdown_function(static function () use (&$pmCheckCompleted, $pmCheckBufferLevel): void {
    if (!$pmCheckCompleted) {
        while (ob_get_level() > $pmCheckBufferLevel) {
            ob_end_clean();
        }
        http_response_code(500);
        error_log('PM category scope diagnostic exited before completion.');
        echo "Bài kiểm tra chưa hoàn tất. Kiểm tra nhật ký lỗi trên host.\n";
    }
});
try {
    // No request-controlled path, arguments, shell execution, DB writes or network calls.
    require ROOT_PATH.'cron/editorial-category-scope-smoke.php';
    $pmCheckOutput = ob_get_clean();
    $pmCheckCompleted = true;
    http_response_code(200);
    echo "Kiểm tra phạm vi chuyên mục: PASS\n";
    echo "Dữ liệu mẫu; không xác nhận dữ liệu hoặc toàn bộ chức năng production.\n\n";
    echo $pmCheckOutput;
} catch (Throwable $e) {
    while (ob_get_level() > $pmCheckBufferLevel) {
        ob_end_clean();
    }
    $pmCheckCompleted = true;
    http_response_code(500);
    error_log('PM category scope diagnostic failed.');
    echo "Bài kiểm tra không hoàn tất. Vui lòng xem nhật ký lỗi trên host.\n";
}
