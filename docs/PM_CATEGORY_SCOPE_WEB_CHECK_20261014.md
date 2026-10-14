# Chạy kiểm tra phạm vi chuyên mục qua URL — 14/10/2026

## Phạm vi bổ sung

Theo yêu cầu người dùng: sau đăng nhập, mở `/editorial-category-scope-smoke.php` để xem kết quả. BUILD chỉ thêm entry webroot cùng tên và module PM mới. Không thay `cron/editorial-category-scope-smoke.php`, các cron editorial khác, `.htaccess`, cron PM, giao diện, cấu hình DB hay chức năng production đã ổn. Phần formatting-only CLI vừa làm được bỏ khỏi phạm vi để giữ bản đang chạy.

Không biến mọi file PHP trong cron thành endpoint công khai. `editorial-rss-sources.php` là cấu hình trả mảng, không phải job; RSS importer có network/DB và chế độ commit, không đưa vào endpoint này. Bài kiểm tra category scope dùng fixture tĩnh và helper gốc, không kiểm chứng dữ liệu production.

## Luồng và bảo mật

- Entry webroot chỉ GET, không nhận tham số đường dẫn/tên file; plain text UTF8/no-store/nosniff và CSP hiện hành.
- Không đăng nhập401, phương thức sai405, query parameter400; module không có hằng authorization404.
- Đọc session cùng namespace ứng dụng, đóng session lock trước khi chạy; kiểm tra active user cùng tenant qua PmSessionGuard, lấy quyền ADMIN hiện tại qua PmAccess. Không dùng username/email/session role cache thay kiểm tra quyền.
- Chỉ include một file cố định `cron/editorial-category-scope-smoke.php`, không shell/exec hoặc gọi job chấm công/RSS. Kết quả chỉ hiển thị sau auth; nonAdmin403.
- Catch lỗi trả nội dung chung, không raw exception/SQL/hostpath. Legacy exit/failure vẫn trả500, không in PASS hoặc output dở dang; log generic. Không mở `.htaccess` của cron.

## Bằng chứng local

- PHP cron/editorial-category-scope-smoke.php:PASS gốc.
- PHP tests/pm_cron_check_smoke.php:PASS actual module/output gốc/HTTP200, không DB.
- PHP tests/pm_cron_check_failure_smoke.php:PASS exit/throw trong subprocess fixture; không partialPASS/sensitive log, không DB/network.
- playwright-cli -s=workspace run-code --filename tests/pm_cron_check_http_browser.js:PASS HTTP401/405/400/404/no-store/text/plain, không DB; log.local/cron-check-http.log.
- Lần HTTP đầu fail do preview router đã include pmResponseHeaders; sửa endpoint require_once, chạy lại PASS, không nới assertion.
- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1:PASS22 noDBscripts/27PHPlint/6manifestchecks. Log.local/cron-category-web-verify.log.
- PHP CLI smoke văn học cũ: categoryPASS, public69checksPASS, translation20checksPASS, PWA17checksPASS, paymentledger/VNPaymockPASS. Không RSSrun/DB/network; không gọi đây là VNPayhost hoặc nghiệp vụ productionPASS.
- Git diff cron và parser PM không thay đổi. File category gốc SHA256 `140880874be02574f25ad5f21b35cfcc0684601bede9ed00a0bb01fd08b05eed`.

Authenticated/live-role HTTP với DB thật CHƯA CHẠY do agent không có quyền DB. User kiểm thử trên host; không tự nângPASS. Không migration, cron schedule, push/merge/deploy.

## Upload từng phần

Gói 1 chỉ hai file mới:

1. `editorial-category-scope-smoke.php` → webroot chứa admin.php, KHÔNG phải thư mục cron.
2. `modules/ajax/pmcroncheck.module.php` → đúng thư mục modules/ajax.

Không upload lại file gốc cron hay file cấu hình. Login Admin trên cùng domain, mở URL đúng; cần thấy PASS và output category scope gốc. Incognito phải401; tài khoản khôngAdmin403. Gửi kết quả trước khi làm URL cho bài kiểm tra tiếp theo. Không có route GET chạy pm-reminders --commit hoặc RSS --commit.
