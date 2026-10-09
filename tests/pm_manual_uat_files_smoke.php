<?php
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/PhpSpreadSheet/PhpOffice/autoload.php';
require ROOT_PATH.'classes/services/pmuserinputservice.class.php';
$dir=ROOT_PATH.'outputs/pm-host-uat-20261009/';
$headers=['username','fullname','email','department_code','weekly_limit_hours','role_code'];
foreach(['01_import_valid.xlsx'=>2,'02_import_invalid.xlsx'=>3,'03_import_collision.xlsx'=>2] as $file=>$count) {
 $reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
 $info=$reader->listWorksheetInfo($dir.$file);
 if(count($info)!==1||(int)$info[0]['totalRows']!==$count+1||$info[0]['totalColumns']!==6)throw new RuntimeException('Import dimensions: '.$file);
 $book=$reader->load($dir.$file);$sheet=$book->getActiveSheet();
 foreach(range('A','F') as $i=>$c)if($sheet->getCell($c.'1')->getValue()!==$headers[$i])throw new RuntimeException('Header order.');
 foreach(range(2,$count+1) as $row)foreach(range('A','F') as $c)if($sheet->getCell($c.$row)->getDataType()==='f')throw new RuntimeException('Unexpected import formula.');
 if($file==='01_import_valid.xlsx') {
  foreach(range(2,$count+1) as $row)PmUserInputService::validateFields((string)$sheet->getCell('A'.$row)->getValue(),(string)$sheet->getCell('B'.$row)->getValue(),(string)$sheet->getCell('C'.$row)->getValue(),'');
 }
 $zip=new ZipArchive();if($zip->open($dir.$file)!==true)throw new RuntimeException('ZIP open.');
 for($i=0;$i<$zip->numFiles;$i++){
  $s=$zip->statIndex($i);if($s['size']>max(1,$s['comp_size'])*100)throw new RuntimeException('Import compression ratio.');
  if(preg_match('/vba|externalLinks|embeddings|activeX/i',$s['name']))throw new RuntimeException('Unexpected workbook feature.');
 }
 $zip->close();$book->disconnectWorksheets();
}
$book=(new \PhpOffice\PhpSpreadsheet\Reader\Xlsx())->load($dir.'BO_DU_LIEU_UAT.xlsx');
if($book->getSheetCount()!==8)throw new RuntimeException('UAT sheet count.');
$sheet=$book->getSheetByName('Chi phi');
foreach(['B10'=>500000,'B11'=>0.25,'B15'=>1000000] as $cell=>$expected)if(abs((float)$sheet->getCell($cell)->getCalculatedValue()-$expected)>0.000001)throw new RuntimeException('Expected formula: '.$cell);
foreach(range(2,31) as $row)if($book->getSheetByName('Kich ban')->getCell('E'.$row)->getValue()!=='CHƯA CHẠY')throw new RuntimeException('Host UAT falsely marked PASS.');
$book->disconnectWorksheets();
echo "PASS: 4 workbook files read by app PhpSpreadsheet, 3 imports exact single-sheet/six-header/rows/no formulas/ZIP safety, valid field format; 8-sheet UAT with expected formulas and all 30 host cases NOT RUN. Stage/Apply DB checks NOT RUN.\n";
