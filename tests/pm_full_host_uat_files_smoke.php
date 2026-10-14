<?php
// Validate delivered XLSX with the application reader; never connects to a DB.
define('ROOT_PATH', dirname(__DIR__).'/');
require ROOT_PATH.'classes/PhpSpreadSheet/PhpOffice/autoload.php';
require ROOT_PATH.'classes/services/pmuserinputservice.class.php';
$dir = ROOT_PATH.'outputs/pm-full-host-uat-20261014b/';
$headers = ['username','fullname','email','department_code','weekly_limit_hours','role_code'];
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
foreach (['01_IMPORT_NHAN_SU.xlsx'=>7,'02_IMPORT_SAI_DU_LIEU.xlsx'=>3,'03_IMPORT_TRUNG_EMAIL.xlsx'=>2] as $file=>$count) {
    $info = $reader->listWorksheetInfo($dir.$file);
    if (count($info)!==1 || (int)$info[0]['totalRows']!==$count+1 || (int)$info[0]['totalColumns']!==6) throw new RuntimeException('Import dimensions: '.$file);
    $book = $reader->load($dir.$file);
    $sheet = $book->getActiveSheet();
    foreach (range('A','F') as $i=>$column) if ($sheet->getCell($column.'1')->getValue()!==$headers[$i]) throw new RuntimeException('Import header order.');
    for ($row=2;$row<=$count+1;$row++) {
        foreach (range('A','F') as $column) if ($sheet->getCell($column.$row)->getDataType()==='f') throw new RuntimeException('Import formula.');
        if ($sheet->getCell('F'.$row)->getValue()!=='EMPLOYEE') throw new RuntimeException('Unexpected import privilege.');
        if ($file==='01_IMPORT_NHAN_SU.xlsx') {
            PmUserInputService::validateFields((string)$sheet->getCell('A'.$row)->getValue(),(string)$sheet->getCell('B'.$row)->getValue(),(string)$sheet->getCell('C'.$row)->getValue(),'');
        }
    }
    if ($file==='03_IMPORT_TRUNG_EMAIL.xlsx' && $sheet->getCell('C2')->getValue()!==$sheet->getCell('C3')->getValue()) throw new RuntimeException('Collision fixture mismatch.');
    $zip = new ZipArchive();
    if ($zip->open($dir.$file)!==true) throw new RuntimeException('ZIP open.');
    for($i=0;$i<$zip->numFiles;$i++) {
        $part=$zip->statIndex($i);
        if ($part['size']>max(1,$part['comp_size'])*100 || preg_match('/vba|externalLinks|embeddings|activeX/i',$part['name'])) throw new RuntimeException('Import ZIP safety.');
    }
    $zip->close();
    $book->disconnectWorksheets();
}
$book=$reader->load($dir.'BO_DU_LIEU_TOAN_WEB.xlsx');
if ($book->getSheetCount()!==9) throw new RuntimeException('Reference sheet count.');
$model=$book->getSheetByName('Chi phi');
foreach (['B12'=>500000,'B13'=>0.25,'B15'=>1000000,'B16'=>6.02] as $cell=>$expected) {
    if(abs((float)$model->getCell($cell)->getCalculatedValue()-$expected)>0.000001) throw new RuntimeException('Calculation mismatch: '.$cell);
}
if ($model->getCell('B2')->getDataType()!=='n' || \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($model->getCell('B2')->getValue())->format('Y-m-d')!=='2026-10-14') throw new RuntimeException('Typed reference date.');
$projectDate = (float)$book->getSheetByName('Du an')->getCell('D2')->getCalculatedValue();
$referenceDate = $model->getCell('B2')->getValue();
$model->setCellValue('B2', $referenceDate + 1);
\PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($book)->clearCalculationCache();
if ((float)$book->getSheetByName('Du an')->getCell('D2')->getCalculatedValue() !== $projectDate + 1) throw new RuntimeException('Reference date does not shift project dates.');
if ($book->getSheetByName('Cong viec')->getHighestDataRow()!==13) throw new RuntimeException('Task count.');
for($row=2;$row<=41;$row++) if($book->getSheetByName('Kich ban')->getCell('E'.$row)->getValue()!=='CHƯA CHẠY') throw new RuntimeException('UAT status must remain unrun.');
if($book->getSheetByName('Kich ban')->getCell('B41')->getValue()!=='Kết thúc bộ mẫu') throw new RuntimeException('Cleanup must be last.');
$book->disconnectWorksheets();
echo "PASS: 4 XLSX files, 7 valid Employee imports, exact headers/no formulas/ZIP safety, collision fixture, 9-sheet reference/12 tasks/40 unrun UAT cases, typed date and 4 cost/hour calculations. No DB/host connected; Stage/Apply/production UAT NOT RUN.\n";
