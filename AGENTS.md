# AGENTS.md — DeraSoft PM

## Bối cảnh

Dự án xây dựng hệ thống quản lý dự án và chấm công trên nền DeraSoft hiện có: PHP thuần, Smarty, MySQL và kiến trúc MVC tùy chỉnh. Không viết lại hệ thống từ đầu. Người dùng chính gồm Admin, Project Manager, HR và Nhân viên.

## Kiến trúc bắt buộc

- Model/DAO: `classes/dao/`, theo cặp lớp Objects và ObjectInfo khi phù hợp.
- Controller Admin: `modules/admin/`; AJAX nội bộ: `modules/ajax/`.
- View: `templates/admin/` bằng Smarty và `$template->assign()`.
- Module mới dùng tiền tố `pm` để tránh đụng mã legacy.
- Tên biến/hàm camelCase, class PascalCase, hằng UPPER_SNAKE_CASE, API key snake_case, file lowercase.
- Trước khi tạo module mới phải đọc module tương tự đang có và giữ convention DeraSoft.

## Quy tắc tuyệt đối

1. Không xóa chức năng legacy. Chức năng chưa dùng chỉ được ẩn khỏi giao diện/router PM và phải có thể khôi phục.
2. Không rewrite framework, module, route, DAO hoặc logic cũ ngoài phạm vi phase.
3. Thay đổi database chỉ được `CREATE TABLE` mới hoặc `ALTER TABLE ... ADD COLUMN`; không `DROP`, `RENAME`, `TRUNCATE` hay xóa dữ liệu cũ.
4. Mọi migration phải là file versioned trong `database/migrations/`, có kiểm tra tồn tại và hướng dẫn rollback an toàn.
5. Không truy cập hay thay đổi production trong quá trình phát triển. Chỉ dùng database local/staging đã sao lưu.
6. Không commit secret, mật khẩu, khóa, dump database, log, cache, file cấu hình production hoặc dữ liệu upload.
7. Không thêm framework/thư viện lớn nếu chưa được duyệt.
8. Mỗi phase dùng nhánh `feature/pm-phaseN-*`, làm đúng phạm vi phase và dừng chờ duyệt tại các điểm yêu cầu.
9. Không dùng `git add .`; chỉ stage đúng file liên quan. Cấm `reset --hard`, `clean -fd`, force push và xóa branch cưỡng bức.
10. Không tuyên bố đã test nếu chưa chạy; luôn ghi rõ lệnh và kết quả kiểm tra.

## Bảo mật mặc định

- Query mới phải dùng prepared statement; không ghép input vào SQL.
- Escape output HTML, kiểm tra CSRF cho mọi thao tác ghi, kiểm tra permission và ownership chống IDOR.
- Mật khẩu mới dùng `password_hash()`/`password_verify()` và giữ lộ trình nâng cấp tương thích tài khoản MD5 cũ.
- Regenerate session ID sau đăng nhập; cookie HttpOnly, Secure trên HTTPS và cấu hình SameSite phù hợp.
- Upload phải whitelist loại file, giới hạn dung lượng và không cho thực thi.
- Production không hiển thị lỗi/debug hoặc dữ liệu session.

## Quy trình mỗi phase

PLAN → chờ duyệt → BUILD → VERIFY → security review → cập nhật `docs/PROGRESS.md` → báo cáo. Sau Phase 0 chỉ nộp audit và dừng chờ duyệt.

## Phạm vi sản phẩm

Auth/RBAC; người dùng, vai trò và đơn giá; dự án và task/Kanban; chấm công và OT; audit log; chi phí và biểu đồ; phân bổ nguồn lực/overbooking; báo cáo và Excel; giao diện responsive.

## Quyết định đã chốt

- Lưu `rate_snapshot` và `cost` trên từng timesheet.
- OT tính theo tổng giờ/ngày; sửa hoặc xóa phải tính lại cả ngày.
- Project, task và user dùng soft delete.
- Realtime dùng truy vấn khi tải và polling 30–60 giây, không dùng WebSocket.
- Biểu đồ dự kiến dùng Chart.js sau khi được duyệt ở đúng phase.

## Tài liệu chuẩn

`docs/PROJECT_BRIEF.md`, `docs/PLAN_FINAL.md`, `docs/AUDIT.md`, `docs/DB_SCHEMA.md`, `docs/PROGRESS.md`.
