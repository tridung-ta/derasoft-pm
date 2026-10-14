# Thiết kế lại danh sách thông báo — 14/10/2026

Impeccable, chế độ Operate: giữ Inter/teal và khung điều hướng hiện tại. Template mới dùng hộp thư giới hạn1040px, heading rõ, tổng chưa đọc từ dữ liệu header hiện có, phân biệt read/unread bằng nhãn chữ và nền, ngày dd/mm/yyyy, nút xem công việc và POST đánh dấu đã đọc. Mobile xếp thao tác theo chiều dọc. Không thêm filter, bulk-action, JavaScript hay thư viện; không sửa backend/SQL/permission/CSRF.

File runtime: templates/admin/pm-notifications.tpl.html và css/pmnotifications.css. CSS được scope vào inbox, không tác động trang chi phí hoặc điều hướng. Asset mới có version20261014-inbox.

## VERIFY đã chạy

- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1:exit0;20 noDBscripts/23PHPlint/6manifestchecks PASS. Log.local/inbox-ui-verify.log.
- PHP tests/pm_notifications_inbox_preview.php:PASS realSmarty render read/unread/longname/pagination/error, khôngDB.
- playwright-cli -s=workspace run-code --filename tests/pm_notifications_inbox_browser.js:PASS1440/390;6statecaptures,mobile/desktopbounds,nooverflow,44pxbuttons,keyboardfocus,dateformat,paginationlinks và CSRF POST. Log.local/inbox-browser.log.
- Cùng lệnh tests/pm_notifications_browser.js:PASS menu Enter/Escape/outside,navbounds/escaping/empty/CSRF. Log.local/inbox-existing-browser.log.
- Đã xem screenshots desktop/mobile trong một vòng batched, xác nhận lại một vòng sau chỉnh dữ liệu fixture. Ảnh.local/inbox-mixed-1440.png và390.png;fixturekhôngdữliệuthật.
- Impeccable detector chạy một lần: warningflat-type-hierarchy do resolver coi đường dẫn CSS từtemplates/admin thay vìwebroot, không đọc đượclinkedstylesheets. Browser computed mobile h1=29px/h3=16px,Inter; CSSdesktop34/17px. Ghi rõ giới hạn detector, không gọi detectorPASS. Consolepreviewfavicon404,khôngpageerror/appJSexception.

## Bàn giao

Backup hai file cũ nếu có, upload đúngpath từ ZIP;Ctrl+F5. Khôngmigration/configchange/cron. Host đánh dấu đã đọc chưa xác nhận bằngSQL; ảnh sauhotfix đã hiệnnhãnĐãđọc nhưng chưa córead_at output mới. KhôngnângtestDB từUIfixture. Commit local focused theo phase;khôngpush/merge/deploy.
