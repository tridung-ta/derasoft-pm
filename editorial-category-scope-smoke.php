<?php
/** Authenticated browser entry for one read-only, fixed diagnostic. */
if (PHP_SAPI === 'cli') {
    fwrite(STDERR, "Use php cron/editorial-category-scope-smoke.php for CLI checks.\n");
    exit(2);
}
define('ROOT_PATH', __DIR__.'/');
ini_set('display_errors', '0');
require_once ROOT_PATH.'includes/pm_response.inc.php';
pmResponseHeaders();
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('Chỉ hỗ trợ kiểm tra bằng GET.');
}
if ($_GET || $_POST) {
    http_response_code(400);
    exit('Bài kiểm tra này không nhận tham số.');
}

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443,
    'use_strict_mode' => true,
]);
$actorId = (int)($_SESSION['userId'] ?? 0);
$storeId = (int)($_SESSION['storeId'] ?? 0);
session_write_close();
header('Cache-Control: no-store');
if ($actorId <= 0 || $storeId <= 0) {
    http_response_code(401);
    exit('Vui lòng đăng nhập tài khoản quản trị tại admin.php trước khi kiểm tra.');
}

$db = null;
try {
    require ROOT_PATH.'includes/config.inc.php';
    require ROOT_PATH.'includes/constant.inc.php';
    require ROOT_PATH.'classes/database/mysql.class.php';
    require ROOT_PATH.'classes/security/pmsessionguard.class.php';
    require ROOT_PATH.'classes/security/pmaccess.class.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new DB();
    if (!PmSessionGuard::valid($db, $storeId, $actorId, $storeId)) {
        http_response_code(401);
        echo 'Phiên đăng nhập không còn hợp lệ. Vui lòng đăng nhập lại.';
    } elseif (!(new PmAccess($db, $storeId, $actorId))->hasRole('ADMIN')) {
        http_response_code(403);
        echo 'Chỉ Admin được chạy bài kiểm tra này.';
    } else {
        define('PM_CRON_CHECK_AUTHORIZED', true);
        require ROOT_PATH.'modules/ajax/pmcroncheck.module.php';
    }
} catch (Throwable $e) {
    http_response_code(500);
    error_log('PM category scope diagnostic unavailable.');
    echo 'Không thể chạy kiểm tra. Vui lòng xem nhật ký lỗi trên host.';
} finally {
    if ($db && isset($db->connection) && $db->connection instanceof mysqli) {
        $db->connection->close();
        $db->connection = null;
    }
}
