<?php
require __DIR__.'/smoke_pm_imports.php';
$batch=['id'=>1,'status'=>'completed','counts'=>['applied'=>2,'skipped_duplicate'=>0,'failed'=>0,'pending'=>0],'results'=>[]];
foreach([2,3] as $row)$batch['results'][]=['source_row'=>$row,'status'=>'applied','reason_code'=>'created_inactive','user_id'=>115+$row,'attempt_count'=>1,'account_status'=>0];
$s->assign(['importPanel'=>'history','importResult'=>null,'importBatch'=>$batch,'error'=>'','notice'=>'','passwordProvisionedRow'=>null]);
file_put_contents(ROOT_PATH.'.local/import-password-feedback.html',str_replace('<head>','<head><base href="/">',$s->fetch('admin/pm-imports.tpl.html')));
$s->assign(['notice'=>'Đã cấp mật khẩu cho User ID 118 (dòng Excel 3). Tài khoản vẫn chưa kích hoạt.','passwordProvisionedRow'=>3]);
$html=$s->fetch('admin/pm-imports.tpl.html');
if(!str_contains($html,'data-pm-success')||!str_contains($html,'Đã cấp mật khẩu cho User ID 118.'))throw new RuntimeException('Missing success feedback.');
file_put_contents(ROOT_PATH.'.local/import-password-success.html',str_replace('<head>','<head><base href="/">',$html));
$s->assign(['notice'=>'','passwordProvisionedRow'=>null,'error'=>'Tài khoản đã kích hoạt.']);
file_put_contents(ROOT_PATH.'.local/import-password-error.html',str_replace('<head>','<head><base href="/">',$s->fetch('admin/pm-imports.tpl.html')));
echo "PASS: real Smarty import password success/error fixtures; no DB connected.\n";
