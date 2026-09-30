# Plan Final — Hệ thống Quản lý Dự án & Chấm công

## Nguyên tắc triển khai

Tái sử dụng tối đa kiến trúc DeraSoft và chỉ thêm/sửa đúng nhu cầu PM. Không xóa chức năng cũ, không thay framework, không chạm production khi phát triển. Mỗi phase có nhánh riêng, plan được duyệt trước khi build, commit nhỏ và kiểm thử rõ ràng.

Database chỉ mở rộng bằng bảng/cột mới. Query mới phải prepared, output phải escape, mọi thao tác ghi có CSRF và mọi endpoint có kiểm tra quyền/ownership. Secret, config thật, dump, cache và log không được đưa lên Git.

## Kiến trúc mục tiêu

| Thành phần | Vị trí |
|---|---|
| DAO/Model | `classes/dao/` |
| Admin controller | `modules/admin/` |
| AJAX nội bộ | `modules/ajax/` |
| Smarty view | `templates/admin/` |
| Migration | `database/migrations/` |
| Tài liệu | `docs/` |

Tên miền chức năng mới dùng tiền tố `pm`: role, rate, project, member, task, timesheet, allocation, report, import/export và audit.

## Mô hình dữ liệu đề xuất

- Tái sử dụng bảng user hiện có; chỉ thêm các trường thực sự thiếu sau khi xác nhận schema local.
- Bảng mới dự kiến: departments, roles, user_roles, permissions, role_permissions, hourly_rates, projects, project_members, tasks, timesheets, allocations, system_settings, import_logs.
- Ưu tiên mở rộng Tracking Data hiện có; chỉ tạo audit log mới nếu cấu trúc legacy không thể mở rộng an toàn.
- Timesheet lưu `rate_snapshot`, `regular_hours`, `ot_hours` và `cost` để bảo toàn lịch sử.
- Index trọng yếu theo project, task, user, work_date, allocation date và audit entity.

Đây là thiết kế khái niệm. SQL chỉ được tạo ở phase đã duyệt sau khi audit schema local; Phase 0 không tạo/chạy migration.

## Lộ trình

### Phase 0 — Discovery & Audit

Rà entrypoint, router, auth/session, DAO, Smarty, phân trang, tracking, Excel và schema có thể xác minh. Lập gap analysis, chọn module mẫu, chốt convention và câu hỏi mở. Deliverable: `docs/AUDIT.md`; dừng chờ duyệt.

### Phase 1 — Môi trường & Baseline

Chuẩn hóa môi trường PHP 8.3/local DB copy, baseline test, cấu trúc migration và progress. Không dùng production.

### Phase 2 — Auth, Role & Permission

Thêm RBAC nhiều-nhiều, permission middleware, ownership và nâng cấp mật khẩu MD5 sang `password_hash()` theo cơ chế tương thích ngược/rehash sau login.

### Phase 3 — User, Role & Hourly Rate

CRUD nhân sự, phòng ban, trạng thái/khóa, nhiều role, đơn giá theo user hoặc role có ngày hiệu lực, tìm kiếm và phân trang.

### Phase 4 — Project & Task

CRUD dự án/thành viên/task, giới hạn người được giao, trạng thái và Kanban. PM chỉ quản lý dự án thuộc phạm vi mình.

### Phase 5 — Timesheet, OT & Audit

Ghi công theo task/ngày/ca, tính lại OT theo tổng giờ ngày, lưu rate snapshot/cost và ghi audit cho mọi thay đổi.

### Phase 6 — Cost & Chart

Tổng hợp chi phí thực tế/dự kiến theo nhiều chiều và biểu đồ Chart.js; cập nhật bằng tải lại hoặc polling 30–60 giây.

### Phase 7 — Resource Allocation & Overbooking

Bảng phân bổ tuần, giới hạn giờ theo người, cảnh báo quá tải/trùng thời gian và gợi ý nguồn lực còn trống.

### Phase 8 — Reports & Excel

Báo cáo cá nhân/nhóm/chi phí có bộ lọc; export XLSX; import nhân sự có validate, transaction, log và lỗi theo dòng.

### Phase 9 — UI/UX, Performance & Security

Hoàn thiện responsive, accessibility và trải nghiệm; dùng EXPLAIN, bỏ N+1, bổ sung index; rà SQLi, XSS, CSRF, IDOR, upload và session.

### Phase 10 — Test, UAT & Release Preparation

Test theo vai trò và nghiệp vụ, UAT trên staging/local copy, lập release note, danh sách file deploy, migration, smoke test và rollback. Production chỉ triển khai sau phê duyệt và backup.

## Logic nghiệp vụ đã chốt

- Login chuyển tiếp hỗ trợ username hoặc email; seed bốn role hệ thống nhưng schema cho phép mở rộng.
- Rate tại ngày làm việc: ưu tiên rate theo user, sau đó role, nếu thiếu thì giá trị 0 kèm cảnh báo.
- Nếu user có nhiều role và không có rate riêng thì dùng role chính, không dùng role có giá cao nhất.
- OT: giờ thường tối đa theo `standard_hours_per_day` (mặc định 8); phần vượt là OT; thay đổi một dòng phải tính lại toàn bộ ngày của user.
- Overbooking: tổng allocation vượt giới hạn tuần hoặc giới hạn ngày sẽ cảnh báo; ngưỡng phải cấu hình được.
- Xóa nghiệp vụ dùng soft delete và audit.
- Import phải validate toàn bộ, báo lỗi theo dòng và dùng transaction cho phần ghi dữ liệu.
- Budget ban đầu dùng VND; giới hạn tuần mặc định 40 giờ nhưng cho phép cấu hình theo user.
- Admin legacy chỉ mở khẩn cấp qua feature flag cục bộ, mặc định tắt.
- Phạm vi HR, quy tắc OT đặc biệt và vòng đời duyệt timesheet còn chờ mentor xác nhận; dùng mặc định an toàn ghi trong `docs/AUDIT.md` nếu cần tiếp tục.

## Definition of Done chung

- PHP lint và test liên quan đạt; có hướng dẫn test tay theo vai trò.
- Không có secret, cache, dump hoặc file ngoài phạm vi trong diff.
- Permission/ownership, CSRF, XSS và SQL injection được kiểm tra.
- Migration cộng thêm, idempotent theo khả năng của MySQL đang dùng, đã thử trên DB copy và có rollback note.
- `docs/PROGRESS.md` được cập nhật trước khi đề nghị merge.
