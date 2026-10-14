<?php
// Fresh CLI process: do not preload the controller's access dependency.
define('ROOT_PATH', dirname(__DIR__).'/');
define('PM_REMINDERS_ENABLED', true);
$db = (object)['connection' => null];
$storeId = 1;
$userInfo = new class { public function getId(): int { return 111; } };
if (class_exists('PmAccess', false)) throw new RuntimeException('Test preloaded access class.');
try {
    require ROOT_PATH.'modules/admin/pmnotifications.module.php';
    throw new LogicException('Invalid database adapter was accepted.');
} catch (RuntimeException $e) {
    if ($e->getMessage() !== 'PM database connection is unavailable.' || !class_exists('PmAccess', false)) throw $e;
}
echo "PASS: real notification controller loads PmAccess in fresh process before DB validation; no DB connected. Listing/POST integration NOT RUN.\n";
