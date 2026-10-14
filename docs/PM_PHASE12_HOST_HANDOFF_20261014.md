# Bàn giao Phase 12 trên host — 14/10/2026

## Bằng chứng người dùng thực hiện

- PHP CLI: log `pm-php-check.log` báo PHP 8.3.33.
- CLI help: `pm-cron-help.log` xác nhận chạy đúng file và có cờ scope.
- Schema ảnh: InnoDB/utf8mb4_unicode_ci; due_date DATE nullable mặc định NULL; unique event và deadline đúng thứ tự. Khóa deadline có NON_UNIQUE=0.
- Dry-run store1/user111/ngày14/10: timesheet candidates0, task candidates1; cả hai created0/failed0, không skipped window.
- Trước ghi, SELECT scope store1/user111 trả0row. Sau ghi: một task_overdue/entity1/event14/10/deadline10/10/read_atNULL.
- Log ghi: lần đầu created1/failed0; ba lần sau created0/duplicate_event1/failed0. Ảnh SELECT vẫn một dòng.

Chỉ chứng minh ca task trên đúng tài khoản đã thử. Chưa kiểm chứng khóa deadline qua ngày khác, concurrency/crash, nhắc chấm công có candidate, đánh dấu đã đọc hay permission HTTP trên host. Không nâng các ca đó thành PASS.

## Gói cập nhật hợp nhất

Runtime lấy từ commit cuối so với bản trước Phase12 `2bfea1e`; giả định website đã có baseline đó, không xác minh hash host. Bao gồm bản vá MariaDB `5c497bb`. Không kèm config DB, dump, log, fixture hoặc tests để upload webroot.

File cấu hình triển khai chỉ sinh trong ZIP, không commit cấu hình host:

- `private/pm-feature-flags.php`: đặt tại `/home/dung/pm-feature-flags.php`, ngoài public_html. Define PM_REMINDERS_ENABLED=true chỉ khi PHP_SAPI không phải cli; CLI vẫn cần opt-in env trong lệnh.
- `optional/.user.ini`: chỉ dùng nếu chưa có .user.ini tại webroot. Nếu đã có, thêm dòng auto_prepend_file khi chưa có chỉ thị này. Nếu đã có auto_prepend_file, giữ nguyên và đối chiếu riêng; không ghi đè bootstrap cũ.
- Runtime giữ cờ mặc định OFF khi không có env/prepend. Không upload private/optional/review/manual-migrations như thư mục website.

Đích runtime: `/home/dung/domains/pm.dung.derasoft.com/public_html/`. Chỉ ghi đè file trong gói; giữ config và cron editorial cũ. Migration011/012 đã được user áp; không nhập lại nếu schema đúng. Xóa lịch thử sau khi có log, không xóa thông báo thật.

Sau prepend, chờ PHP-FPM đọc .user.ini rồi Ctrl+F5. Tài khoản111 cần thấy chuông và thông báo đã tạo; đánh dấu đã đọc qua UI rồi SELECT chỉ đọc đối chiếu read_at khácNULL và số chưa đọc giảm. Chưa chứng nhận ca này đến khi có bằng chứng.

Rollback phần hiển thị: bỏ đúng dòng prepend vừa thêm (hoặc phục hồi .user.ini đã backup), giữ file private và dữ liệu; cờ web trở lại OFF sau khi cấu hình được nạp lại. Không cài cron tự động trong gói này.
