<?php
// Generate Smarty states only; no database or network connection.
require __DIR__.'/pm_reminders_smoke.php';
$readRow = $row;
$unreadRow = $row;
$unreadRow['kind'] = 'task_overdue';
$unreadRow['title'] = 'Công việc đã quá hạn';
$unreadRow['task_name'] = 'Rà soát báo cáo tiến độ dự án';
$readRow['id'] = 2;
$readRow['read_at'] = '2026-10-14 15:00:00';
$readRow['task_name'] = str_repeat('Tên công việc dài cần kiểm tra xuống dòng ', 5);
$smarty->assign(['notificationRows'=>[$unreadRow,$readRow], 'page'=>2, 'totalPages'=>3, 'pmNotificationUnread'=>1]);
file_put_contents(ROOT_PATH.'.local/pm-notifications-mixed.html', str_replace('<head>', '<head><base href="/">', $smarty->fetch('pm-notifications.tpl.html')));
$smarty->assign(['notificationRows'=>[], 'error'=>'Không thể tải thông báo. Vui lòng thử lại.', 'page'=>1, 'totalPages'=>1]);
file_put_contents(ROOT_PATH.'.local/pm-notifications-error.html', str_replace('<head>', '<head><base href="/">', $smarty->fetch('pm-notifications.tpl.html')));
echo "PASS: real Smarty read/unread/long-label/pagination/error fixture generation; no DB connected.\n";
