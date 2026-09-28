<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

$loginPath = $lang === 'en' ? '/en/login' : ($lang === 'zh' ? '/zh/login' : '/dang-nhap');
header('Location: ' . $loginPath . '?logged_out=1', true, 302);
exit;