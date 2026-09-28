<?php

/*************************************************************************
System config down module
----------------------------------------------------------------
Derasoft CMS Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/07/2008
 **************************************************************************/
checkPermission(array(2, 3));
require_once ROOT_PATH . 'classes/PhpSpreadSheet/PhpOffice/autoload.php';
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . 'classes/dao/templates.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

$templates = new Templates();
$fields = new Fields($storeId);
include_once(ROOT_PATH . 'classes/dao/trackingicons.class.php');
$trackingIcons = new TrackingIcons(1);
$templateFile = 'systemconfig.tpl.html';

$topNav = array(
    $amessages['dash_board'] => '/' . ADMIN_SCRIPT . '?op=dashboard',
    $amessages['system'] => '/' . ADMIN_SCRIPT . '?op=system',
    $amessages['system_config'] => '/' . ADMIN_SCRIPT . '?op=system&act=config',
    $amessages['site_down'] => ''
);

$tabLink = '/' . ADMIN_SCRIPT . '?op=system&act=config';
$listTabs = array(
    $amessages['general_config'] => $tabLink . '&mod=general',
    $amessages['countclick'] => $tabLink . '&mod=countclick'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 2);

$month = $request->element('month_select');
$template->assign('month', $month);
$year = $request->element('year_select');
$template->assign('year', $year);
if ($month && $year) {
    $sql = "AND `date_created` LIKE '%$year-$month%'";
}

$countUnique = function($action) use ($trackingIcons, &$sql) {
    $items = $trackingIcons->getObjects(1, "`action`='$action' $sql", array('id' => 'DESC'), 9999);
    $seenIps = [];
    if ($items) {
        foreach ($items as $item) {
            $seenIps[$item->getIp()] = true;
        }
    }
    return count($seenIps);
};

$counttel           = $countUnique('tel');
$countmap           = $countUnique('map');
$countmess          = $countUnique('mess');
$countzalo          = $countUnique('zalo');
$countfanpage       = $countUnique('fanpage');
$countgoogle        = $countUnique('google');
$counttiktok        = $countUnique('tiktok');
$countyoutube       = $countUnique('youtube');
$counttwitter       = $countUnique('twitter');
$countinstagram    = $countUnique('instagram');
$countskype         = $countUnique('skype');
$counttelegram      = $countUnique('telegram');
$countpinterest     = $countUnique('pinterest');
$countlinkedin      = $countUnique('linkedin');

$template->assign('counttel', $counttel);
$template->assign('countmap', $countmap);
$template->assign('countmess', $countmess);
$template->assign('countzalo', $countzalo);
$template->assign('countfanpage', $countfanpage);
$template->assign('countgoogle', $countgoogle);
$template->assign('counttiktok', $counttiktok);
$template->assign('countyoutube', $countyoutube);
$template->assign('counttwitter', $counttwitter);
$template->assign('countinstagram', $countinstagram);
$template->assign('countskype', $countskype);
$template->assign('counttelegram', $counttelegram);
$template->assign('countpinterest', $countpinterest);
$template->assign('countlinkedin', $countlinkedin);


if ($_POST) {
    if ($request->element('doo') == 'submit') {

        if (!$month || !$year) {
            die('Vui lòng chọn tháng và năm.');
        }

        $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
        $year = (int)$year;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Danh sách truy cập');

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(10);
        $sheet->getColumnDimension('K')->setWidth(10);
        $sheet->getColumnDimension('L')->setWidth(10);
        $sheet->getColumnDimension('M')->setWidth(10);
        $sheet->getColumnDimension('N')->setWidth(10);
        $sheet->getColumnDimension('O')->setWidth(10);

        $sheet->getStyle('A1:O1')->getFont()->setBold(true);

        $sheet->setCellValue('A1', 'Ngày');
        $sheet->setCellValue('B1', 'Số điện thoại');
        $sheet->setCellValue('C1', 'Zalo');
        $sheet->setCellValue('D1', 'Fanpage');
        $sheet->setCellValue('E1', 'Map');
        $sheet->setCellValue('F1', 'Google');
        $sheet->setCellValue('G1', 'Tiktok');
        $sheet->setCellValue('H1', 'Youtube');
        $sheet->setCellValue('I1', 'Mess fb');
        $sheet->setCellValue('J1', 'Twitter');
        $sheet->setCellValue('K1', 'Instagram');
        $sheet->setCellValue('L1', 'Skype');
        $sheet->setCellValue('M1', 'Telegram');
        $sheet->setCellValue('N1', 'Pinterest');
        $sheet->setCellValue('O1', 'Linkedin');


        $daysInMonth = cal_days_in_month(
            CAL_GREGORIAN,
            $month,
            $year
        );

        $row = 2;

        foreach (range(1, $daysInMonth) as $day) {

            $daySql = str_pad($day, 2, '0', STR_PAD_LEFT);

            $dateSql = "AND `date_created` LIKE '{$year}-{$month}-{$daySql}%'";

            $countUniqueByAction = function ($action) use ($trackingIcons, $dateSql) {

                $items = $trackingIcons->getObjects(
                    1,
                    "`action`='" . addslashes($action) . "' $dateSql",
                    array('id' => 'DESC'),
                    9999
                );

                $seenIps = [];

                if ($items) {
                    foreach ($items as $item) {
                        $ip = $item->getIp();

                        if ($ip) {
                            $seenIps[$ip] = true;
                        }
                    }
                }

                return count($seenIps);
            };

            $counttel      = $countUniqueByAction('tel');
            $countzalo     = $countUniqueByAction('zalo');
            $countfanpage  = $countUniqueByAction('fanpage');
            $countmap      = $countUniqueByAction('map');
            $countgoogle   = $countUniqueByAction('google');
            $counttiktok   = $countUniqueByAction('tiktok');
            $countyoutube  = $countUniqueByAction('youtube');
            $countmess     = $countUniqueByAction('mess');
            $counttwitter  = $countUniqueByAction('twitter');
            $countinstagram= $countUniqueByAction('instagram');
            $countskype    = $countUniqueByAction('skype');
            $counttelegram = $countUniqueByAction('telegram');
            $countpinterest= $countUniqueByAction('pinterest');
            $countlinkedin = $countUniqueByAction('linkedin');

            $sheet->setCellValue('A' . $row, $day);
            $sheet->setCellValue('B' . $row, $counttel);
            $sheet->setCellValue('C' . $row, $countzalo);
            $sheet->setCellValue('D' . $row, $countfanpage);
            $sheet->setCellValue('E' . $row, $countmap);
            $sheet->setCellValue('F' . $row, $countgoogle);
            $sheet->setCellValue('G' . $row, $counttiktok);
            $sheet->setCellValue('H' . $row, $countyoutube);
            $sheet->setCellValue('I' . $row, $countmess);
            $sheet->setCellValue('J' . $row, $counttwitter);
            $sheet->setCellValue('K' . $row, $countinstagram);
            $sheet->setCellValue('L' . $row, $countskype);
            $sheet->setCellValue('M' . $row, $counttelegram);
            $sheet->setCellValue('N' . $row, $countpinterest);
            $sheet->setCellValue('O' . $row, $countlinkedin);

            $row++;
        }

        header('Content-Type: application/vnd.ms-excel');
        header(
            'Content-Disposition: attachment;filename="So-luot-truy-cap-thang-' .
            $month . '-' . $year . '.xls"'
        );
        header('Cache-Control: max-age=0');

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');

        exit();
    }
}
