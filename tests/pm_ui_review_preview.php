<?php
// Run after pm_workspace_verify.ps1. No DB, no application bootstrap.
define('ROOT_PATH', dirname(__DIR__).'/');
ob_start(); require ROOT_PATH.'pm-launch.php'; $landing=ob_get_clean();
file_put_contents(ROOT_PATH.'.local/ui-launch-source.html',str_replace('<head>','<head><base href="/">',$landing));
$pages=[
 'launch'=>['Trang vào hệ thống','ui-launch-source.html'],
 'login'=>['Đăng nhập / lỗi','phase9-login.html'],
 'overview'=>['Tổng quan quản lý','workspace-dashboard-managed.html'],
 'profile'=>['Hồ sơ','phase9-profile.html'],
 'password'=>['Đổi mật khẩu','phase9-password.html'],
 'team'=>['Danh sách nhóm','phase9-team.html'],
 'users'=>['Nhân sự / đơn giá','phase9-users-1.html'],
 'users-restricted'=>['Nhân sự chỉ xem','phase9-users-readonly.html'],
 'projects'=>['Danh sách dự án','phase9b-project-list.html'],
 'project-detail'=>['Dự án / thành viên / Kanban','phase9-projects-1.html'],
 'project-restricted'=>['Dự án nhân viên','phase9-projects-2.html'],
 'timesheets'=>['Chấm công / quy tắc','phase9-timesheets-1.html'],
 'timesheets-employee'=>['Chấm công nhân viên','phase9-timesheets-2.html'],
 'timesheets-error'=>['Chấm công lỗi nguồn dữ liệu','phase9b-timesheets-unavailable.html'],
 'allocations'=>['Phân bổ','phase7-preview.html'],
 'costs'=>['Chi phí / biểu đồ','phase6-preview.html'],
 'costs-empty'=>['Chi phí trống','phase9b-costs-empty.html'],
 'reports'=>['Báo cáo giờ','phase9b-reports-hours.html'],
 'reports-costs'=>['Báo cáo chi phí','phase9b-reports-costs.html'],
 'reports-users'=>['Báo cáo nhân sự','phase11-users.html'],
 'reports-projects'=>['Báo cáo dự án','phase11-projects.html'],
 'reports-tasks'=>['Báo cáo công việc','phase11-tasks.html'],
 'reports-error'=>['Báo cáo lỗi','phase9b-reports-error.html'],
 'imports'=>['Import — tải mẫu / upload','phase9b-import-initial.html'],
 'imports-validation'=>['Import — lỗi từng dòng','phase9b-import-validation.html'],
 'imports-apply'=>['Import — Apply / mật khẩu','phase9b-import-apply.html'],
 'notifications'=>['Thông báo','pm-notifications-preview.html'],
 'notifications-empty'=>['Thông báo trống','pm-notifications-empty.html'],
 'audit'=>['Nhật ký','phase9-audit-1.html'],
];
$links='';
foreach($pages as $key=>[$label,$source]) {
 $path=ROOT_PATH.'.local/'.$source;
 if(!is_file($path))throw new RuntimeException('Missing fixture '.$source);
 $html=file_get_contents($path);
 $map=['pm'=>'overview','pmusers'=>'users','pmprojects'=>'projects','pmtimesheets'=>'timesheets','pmallocations'=>'allocations','pmcosts'=>'costs','pmreports'=>'reports','pmimports'=>'imports','pmnotifications'=>'notifications','pmaudit'=>'audit'];
 $html=preg_replace_callback('~href="(?:/admin\.php)?\?op=([a-z]+)(?:[^"#]*)"~',function($m)use($map){
    $key=$map[$m[1]]??null;
    if($m[1]==='pmprojects' && str_contains($m[0],'project_id='))$key='project-detail';
    foreach(['profile','password','team'] as $accountPage)if($m[1]==='pm' && str_contains($m[0],'act='.$accountPage))$key=$accountPage;
    return $key?'href="/.local/review-'.$key.'.html"':$m[0];
 },$html);
 $html=str_replace('href="/admin.php"','href="/.local/review-login.html"',$html);
 // Keep base href="/" for assets; replace only the public landing return link.
 $html=str_replace('class="back" href="/"','class="back" href="/.local/ui-review.html"',$html);
 file_put_contents(ROOT_PATH.'.local/review-'.$key.'.html',$html);
 $links.='<li><a href="/.local/review-'.$key.'.html">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</a></li>';
}
$hub='<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="/"><title>Duyệt giao diện local — DeraSoft</title><link rel="stylesheet" href="css/pmui.css"><link rel="stylesheet" href="css/pmworkspace.css"><link rel="stylesheet" href="css/pmreview.css"></head><body class="pm-app"><main id="pm-main" style="max-width:1000px;margin:auto;padding:32px"><h1>Duyệt giao diện local</h1><p>29 màn hình và trạng thái, dùng dữ liệu giả. Các chuỗi &lt;script&gt; trong bảng là mẫu kiểm tra escape.</p><p><strong>Chỉ xem bố cục.</strong> Máy chủ chặn mọi thao tác ghi, không chạy ứng dụng PHP và không kết nối DB. Menu/chỉnh sửa có thể mở để xem; nút lưu không ghi dữ liệu. Quay lại trang này bằng nút Back.</p><section><h2>Chọn màn hình</h2><ul style="columns:2;column-gap:40px;line-height:2.4">'.$links.'</ul></section></main></body></html>';
file_put_contents(ROOT_PATH.'.local/ui-review.html',$hub);
file_put_contents(ROOT_PATH.'.local/ui-review-pages.json',json_encode($pages,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo 'PASS: '.count($pages).' local review states generated; no database.' . PHP_EOL;
