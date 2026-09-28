<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$root = dirname(__DIR__);
$failures = array();
$checks = 0;

function pwaCheck($condition, $message)
{
    global $checks, $failures;
    ++$checks;
    if (!$condition) $failures[] = $message;
}

$manifestPath = $root . '/manifest.webmanifest';
$manifest = json_decode(file_get_contents($manifestPath), true);
pwaCheck(is_array($manifest) && json_last_error() === JSON_ERROR_NONE, 'manifest is not valid JSON');
pwaCheck(!empty($manifest['name']) && !empty($manifest['short_name']), 'manifest names are missing');
pwaCheck(isset($manifest['start_url']) && $manifest['start_url'] === '/', 'manifest start_url is invalid');
pwaCheck(isset($manifest['scope']) && $manifest['scope'] === '/', 'manifest scope is invalid');
pwaCheck(isset($manifest['display']) && $manifest['display'] === 'standalone', 'manifest display mode is invalid');
pwaCheck(!empty($manifest['icons']) && is_array($manifest['icons']), 'manifest icons are missing');

if (!empty($manifest['icons'])) {
    foreach ($manifest['icons'] as $icon) {
        $iconPath = isset($icon['src']) ? parse_url($icon['src'], PHP_URL_PATH) : '';
        pwaCheck($iconPath && is_file($root . $iconPath), 'manifest icon does not exist: ' . $iconPath);
    }
}

$head = file_get_contents($root . '/templates/mpx/head.tpl.html');
pwaCheck(strpos($head, 'rel="manifest" href="/manifest.webmanifest"') !== false, 'head does not link the manifest');
pwaCheck(strpos($head, 'name="theme-color"') !== false, 'head theme color is missing');

$scriptTemplate = file_get_contents($root . '/templates/mpx/editorial-script.tpl.html');
pwaCheck(strpos($scriptTemplate, 'editorial-pwa.js') !== false, 'PWA registration script is not loaded');

$registration = file_get_contents($root . '/templates/mpx/js/editorial-pwa.js');
pwaCheck(strpos($registration, "serviceWorker.register('/service-worker.js'") !== false, 'service worker is not registered at root scope');

$worker = file_get_contents($root . '/service-worker.js');
pwaCheck(strpos($worker, "request.method !== 'GET'") !== false, 'worker does not exclude mutating requests');
pwaCheck(strpos($worker, "request.mode === 'navigate'") !== false, 'worker has no navigation fallback');
pwaCheck(strpos($worker, "url.pathname.startsWith('/templates/mpx/')") !== false, 'worker static asset allowlist is missing');
pwaCheck(strpos($worker, "url.origin !== self.location.origin") !== false, 'worker does not restrict cross-origin requests');
pwaCheck(is_file($root . '/offline.html'), 'offline fallback page is missing');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
    exit(1);
}

echo 'OK: ' . $checks . ' PWA checks passed.' . PHP_EOL;
