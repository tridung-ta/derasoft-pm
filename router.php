<?php
/**
 * Router for PHP's local development server.
 * Production web-server routing does not load this file.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

if (rtrim($path, '/') === '/kien-thuc') {
    $_GET['op'] = 'estore';
    $_GET['act'] = 'knowledge';
    $_GET['slug'] = 'kien-thuc';
}

require __DIR__ . '/index.php';
