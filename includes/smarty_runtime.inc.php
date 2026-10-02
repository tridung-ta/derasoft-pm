<?php
/** Configure a writable Smarty compile directory without changing production defaults. */
function configureSmartyCompileDir($template): void {
    $projectCompileDir = ROOT_PATH.'templates_c';
    if (is_dir($projectCompileDir) && is_writable($projectCompileDir)) {
        $template->setCompileDir($projectCompileDir);
        return;
    }

    $fallbackCompileDir = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'derasoft-pm-smarty';
    if (!is_dir($fallbackCompileDir)
        && !mkdir($fallbackCompileDir, 0770, true)
        && !is_dir($fallbackCompileDir)) {
        throw new RuntimeException('Unable to create a writable Smarty compile directory.');
    }
    $template->setCompileDir($fallbackCompileDir);
}
