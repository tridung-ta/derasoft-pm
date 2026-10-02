<?php
define('ROOT_PATH', dirname(__DIR__) . '/');

require ROOT_PATH . 'classes/template/smarty.class.php';
require ROOT_PATH . 'classes/PhpSpreadSheet/PhpOffice/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (Smarty::SMARTY_VERSION !== '4.5.5') {
    fwrite(STDERR, 'Unexpected Smarty version: ' . Smarty::SMARTY_VERSION . PHP_EOL);
    exit(1);
}

$spreadsheet = new Spreadsheet();
$spreadsheet->getActiveSheet()->setCellValue('A1', 'DeraSoft PM baseline');

$outputFile = tempnam(sys_get_temp_dir(), 'derasoft-pm-');
if ($outputFile === false) {
    fwrite(STDERR, 'Unable to create a temporary workbook path.' . PHP_EOL);
    exit(1);
}

try {
    (new Xlsx($spreadsheet))->save($outputFile);

    if (!is_file($outputFile) || filesize($outputFile) === 0) {
        throw new RuntimeException('Generated workbook is empty.');
    }
} finally {
    $spreadsheet->disconnectWorksheets();
    if (is_file($outputFile)) {
        unlink($outputFile);
    }
}

echo 'Smarty and PhpSpreadsheet dependency smoke test passed.' . PHP_EOL;
