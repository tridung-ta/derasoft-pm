<?php
// Local preview only; use the exact PM response headers. No database access.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) { http_response_code(403); exit; }
require dirname(__DIR__).'/includes/pm_response.inc.php';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (preg_match('~^/\.local/[a-z0-9-]+\.html$~D',$path)) {
 $file=dirname(__DIR__).$path; if(!is_file($file)){http_response_code(404);exit;}
 pmResponseHeaders(); header('Content-Type: text/html; charset=UTF-8'); readfile($file); return true;
}
return false;
