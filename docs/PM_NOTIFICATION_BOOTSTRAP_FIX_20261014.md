# Sửa bootstrap trang thông báo — 14/10/2026

Người dùng báo GET admin.php?op=pmnotifications trả HTTP500. Chưa có error_log host nên chưa xác nhận đây là nguyên nhân duy nhất trên host.

Local đã tái hiện lỗi `Error: Class "PmAccess" not found` khi include controller thật trong tiến trình mới, không preload lớp và không kết nối DB. Controller khởi tạo PmAccess nhưng thiếu include; admin bootstrap không nạp lớp này. Harness HTTP trước đó đã require lớp nên không phát hiện.

Sửa controller nạp classes/security/pmaccess.class.php trước khởi tạo. Không đổi permission, SQL, schema, CSRF, redirect hay dữ liệu. Test mới pm_notification_bootstrap_smoke.php include controller thật với adapter không có kết nối: xác nhận lớp được tải rồi từ chối adapter bằng RuntimeException đúng; không giả lập PmAccess và không kết nối DB.

Lệnh powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1: exit0, PASS20 no-DB scripts/23 PHP lint/6 manifest checks. PHP -l controller và test mới PASS. Log .local/notification-bootstrap-fix.log. Trước sửa test tái hiện Class not found; sau sửa test bootstrap PASS. Không coi kiểm tra này là SQL/GET danh sách/POST đã đọc PASS.

Bàn giao một file modules/admin/pmnotifications.module.php. Operator backup file cũ, upload đúng đường dẫn, mở lại trang rồi thử đánh dấu đã đọc; đối chiếu read_at qua SELECT. Nếu vẫn500 cần PHP error_log host mới; không bật display_errors hay sửa dữ liệu để bỏ qua lỗi. Lịch cron tự động vẫn chưa bật.
