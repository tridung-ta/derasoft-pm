<?php
// Exercise the actual fixed diagnostic module, without a DB or browser session.
define('ROOT_PATH', dirname(__DIR__).'/');
define('PM_CRON_CHECK_AUTHORIZED', true);
ob_start();
require ROOT_PATH.'modules/ajax/pmcroncheck.module.php';
$output = ob_get_clean();
if (http_response_code() !== 200 || !str_contains($output, 'Editorial category scope smoke test passed.')
    || !str_contains($output, ': PASS')) {
    throw new RuntimeException('Original category smoke output missing.');
}
echo "PASS: fixed original category smoke executes through diagnostic module, HTTP200; no DB connected. Authentication integration NOT RUN.\n";
