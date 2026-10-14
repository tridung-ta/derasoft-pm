<?php
// Child processes exercise legacy exit/fatal handling; only create local PHP fixtures.
$root = dirname(__DIR__);
$fixtureRoot = $root.'/.local/cron-check-failure-'.bin2hex(random_bytes(6));
$module = $root.'/modules/ajax/pmcroncheck.module.php';
foreach (['exit'=>"echo 'UNTRUSTED_PARTIAL_OUTPUT'; exit(1);", 'throw'=>"throw new RuntimeException('SECRET_EXCEPTION_SENTINEL');"] as $case=>$code) {
    $caseRoot = $fixtureRoot.'/'.$case.'/';
    mkdir($caseRoot.'cron', 0700, true);
    file_put_contents($caseRoot.'cron/editorial-category-scope-smoke.php', '<?php '.$code);
    $child = "define('ROOT_PATH',".var_export($caseRoot,true)."); define('PM_CRON_CHECK_AUTHORIZED',true); require ".var_export($module,true).';';
    $command = escapeshellarg(PHP_BINARY).' -d display_errors=0 -d error_log='.escapeshellarg($caseRoot.'fault.log').' -r '.escapeshellarg($child).' 2>&1';
    exec($command, $lines, $status);
    $output = implode("\n", $lines);
    if (str_contains($output, ': PASS') || str_contains($output, 'UNTRUSTED_PARTIAL_OUTPUT')
        || str_contains($output, 'SECRET_EXCEPTION_SENTINEL') || $output === '') {
        throw new RuntimeException('Failed check leaked or falsely passed: '.$case);
    }
    if (is_file($caseRoot.'fault.log') && str_contains(file_get_contents($caseRoot.'fault.log'), 'SECRET_EXCEPTION_SENTINEL')) {
        throw new RuntimeException('Sensitive exception leaked to log.');
    }
    $lines = [];
}
echo "PASS: fixed diagnostic exit/throw suppress partial PASS and sensitive errors; no DB/network connected.\n";
