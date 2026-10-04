# Database schema baseline

Ngày kiểm tra: 2026-09-30

## Nguồn và giới hạn

Schema được trích từ bản dump local `dung_pm.sql`, SHA-256:

```text
6AF3EED4E833BCA226D851BD9D5DDC2B9E6DBEA7BC4B7713B06ABD7ECAB2541B
```

File dump chứa dữ liệu thật, được `.gitignore` loại khỏi Git và không được sao chép vào tài liệu. Việc đọc ở bước này chỉ dùng các câu `CREATE TABLE`/`ALTER TABLE`; chưa import và chưa thay đổi database.

Dump có 76 bảng, 478 khối `INSERT INTO`, 156 khối `ALTER TABLE`; không phát hiện `DROP DATABASE`, `DROP TABLE`, `TRUNCATE` hoặc `DELETE FROM`.

## Bảng legacy liên quan

### `dc_users`

- Engine: MyISAM; charset `utf8mb4`.
- Primary key: `id` auto increment.
- Index đơn: `username`, `store_id`, `email`, `fullname`.
- Trường có thể tái sử dụng: `store_id`, `username`, `email`, `fullname`, `address`, `tel`, `cell`, `type`, `status`, `properties`, `last_login`.
- `password varchar(50)` đang chứa hash legacy và không đủ cho hash hiện đại dài 255 ký tự.
- `username` và `email` chỉ có index thường, chưa có unique constraint theo store.
- Không có department, weekly limit, password hash mới hoặc cờ buộc đổi mật khẩu.

### `dc_login_times`

- Engine: MyISAM.
- Trường: `uid`, `fail_times`, `last_try`, `last_ip`.
- Có index `uid`, nhưng không unique; application phải tránh tạo nhiều dòng không kiểm soát.

### `dc_trackings`

- Engine: MyISAM.
- Chỉ có `store_id`, `username`, `action`, `date_created`, `ip`.
- Chỉ index `store_id`; thiếu index thời gian/user và thiếu dữ liệu audit có cấu trúc.

### `dc_estores`

- Engine: MyISAM.
- `subdomain` unique; có `domain`, owner và status.
- PM phải tiếp tục mang `store_id` trên dữ liệu thuộc tenant.

## Quyết định thiết kế từ schema thật

1. Giữ `dc_users` làm nguồn tài khoản; không tạo bảng user thay thế.
2. Thêm `password_hash varchar(255) NULL` thay vì sửa/xóa cột `password` legacy.
3. Tái sử dụng `tel`/`cell`, không thêm cột phone trùng nghĩa.
4. Role/permission/project/task/timesheet mới dùng bảng prefix `dc_pm_` và InnoDB.
5. Không tạo foreign key từ bảng InnoDB mới sang các bảng user/store MyISAM; kiểm tra tham chiếu ở application layer và dùng index.
6. Audit PM cần dữ liệu actor/entity/old/new. Ưu tiên một bảng `dc_pm_audit_logs` InnoDB và có thể ghi tóm tắt sang `dc_trackings`; không ép bảng text MyISAM thành audit transactional.
7. Mọi unique key mới có `store_id` khi dữ liệu thuộc tenant, ví dụ `(store_id, code)` hoặc `(store_id, email)` sau khi kiểm tra dữ liệu trùng.

## Kiểm tra bắt buộc sau khi import local

- Xác nhận MySQL version, sql_mode, charset/collation và storage engine support.
- Đếm trùng username/email theo `store_id` trước khi đề xuất unique constraint.
- Xác nhận mapping `status` và `type` từ dữ liệu đang dùng.
- Kiểm tra số dòng và kích thước bốn bảng liên quan.
- Không hiển thị username, email, hash hoặc dữ liệu cá nhân trong log/test report.

## Phase 3 — User, Department & Hourly Rate


- Kiến trúc PM chính thức là multi-tenant; toàn bộ bảng `dc_pm_*` có `store_id BIGINT UNSIGNED NOT NULL` và query mới bắt buộc lọc tenant hiện tại.
- `dc_users` được mở rộng cộng thêm `department_id BIGINT UNSIGNED NULL` và `weekly_limit_hours DECIMAL(5,2) NOT NULL DEFAULT 40.00`.
- `dc_pm_departments` lưu phòng ban tenant-scoped, hỗ trợ trạng thái và soft delete.
- `dc_pm_hourly_rates` lưu rate theo đúng một user hoặc một role, khoảng hiệu lực và VND mặc định.
- XOR owner, kiểm tra khoảng ngày và overlap được thực thi tại `PmRateService`; trước kiểm tra overlap, service khóa các rate active cùng owner bằng `SELECT ... FOR UPDATE`.
- Rate resolution ưu tiên user-specific, sau đó role chính, cuối cùng trả 0 VND kèm cảnh báo.
- Migration local đã tenant hóa `dc_pm_permissions` và `dc_pm_role_permissions`, đồng thời widen `store_id` của các bảng Phase 2 lên BIGINT theo ngoại lệ đã được người dùng phê duyệt.

## Phase 4 — Project & Task

- `dc_pm_projects`: tenant, code unique `(store_id,code)`, manager_id, ngày, budget VND, status và deleted_at.
- `dc_pm_project_members`: unique `(store_id,project_id,user_id)`, status active/inactive.
- `dc_pm_tasks`: tenant/project, assignee nullable, status, priority, estimated_hours, due_date và deleted_at.
- Ba bảng InnoDB/utf8mb4 được tạo bởi migration 003; không FK tới user/store MyISAM.
- `PmProjectService` kiểm tra tham chiếu và quyền; mutation khóa project bằng FOR UPDATE
  để thao tác membership và task không chạy chồng lên nhau trong cùng dự án.
- Soft delete project làm project/task không còn truy cập qua service dù membership vẫn được giữ.
# Phase 5 increment 1 — additive schema

Migration `004_create_pm_timesheets_audit.sql` thêm bốn bảng InnoDB, tenant-scoped:

| Bảng | Nội dung / khóa |
| --- | --- |
| `dc_pm_system_settings` | UNIQUE(store_id, setting_key); ngưỡng giờ/ngày và hệ số OT |
| `dc_pm_timesheet_days` | UNIQUE(store_id, user_id, work_date); snapshot ngưỡng/hệ số, khóa ghi theo ngày |
| `dc_pm_timesheets` | Task/project/user/date/ca, hours/regular_hours/ot_hours, rate_snapshot, currency, cost, Draft status, soft delete |
| `dc_pm_audit_logs` | Actor, entity, action, old/new JSON, created_at; cùng transaction nghiệp vụ |

Chi phí dùng DECIMAL(18,2), đơn giá DECIMAL(15,2). Không sửa schema legacy.
Rollback ứng dụng giữ bảng mới không sử dụng; không DROP hoặc xóa dữ liệu.
Khóa sửa/xóa tính từ work_date qua cửa sổ 3 ngày lịch, không ghi trạng thái duyệt vào database.
Cột status giữ giá trị mặc định để tương thích, không dùng cho vòng đời Phase 5.
Seed `002_seed_pm_phase5_audit_permissions.sql` cộng thêm grant audit cho Admin/PM/HR theo store_id.
Phase 6 không thêm schema; seed `003_seed_pm_cost_permissions.sql` cộng thêm permission
`pm.costs.view` và grant Admin/PM theo tenant. Cost service kiểm tra currency trước SUM(cost)
trong consistent read transaction, mixed-currency trả null + cảnh báo, không quy đổi.
