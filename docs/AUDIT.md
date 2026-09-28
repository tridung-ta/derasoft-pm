# PHASE 0 — AUDIT HỆ THỐNG QUẢN LÝ DỰ ÁN & CHẤM CÔNG

Ngày audit: 2026-09-28  
Phạm vi: source local tại `D:\dung-derasoft` và các bản dump MySQL đang có trong repository.  
Nguyên tắc: Phase 0 chỉ khảo sát và lập kế hoạch; không sửa code chức năng, không chạy migration, không xóa dữ liệu.

## 1. Kết luận nhanh

Có thể tiếp tục phát triển trên nền DeraSoft hiện tại, không cần viết lại framework. Những phần có thể tái sử dụng gồm entry point quản trị, session, Smarty, DAO/Info object, phân trang, layout quản trị, cơ chế quyền cũ ở mức tham khảo, Tracking Data ở mức ghi lịch sử đơn giản và PhpSpreadsheet.

Tuy nhiên, một số giả định trong kế hoạch ban đầu không đúng hoàn toàn với source thật:

- Đăng nhập quản trị hiện xác thực bằng `md5()`, chưa dùng `password_hash()` / `password_verify()`.
- Lớp DB/Model hiện dựng câu SQL bằng nối chuỗi và gọi `mysqli_query()`, không có prepared statement dùng chung.
- Quyền hiện tại gồm `users.type` và mảng quyền serialize trong `users.properties`; chưa phải RBAC nhiều-nhiều.
- Tracking Data chỉ có `username`, mô tả `action`, thời gian và IP; chưa lưu entity, entity ID, old/new JSON.
- Giao diện admin hiện là CSS riêng, MooTools và jQuery 1.12.4; không phải Bootstrap 5.
- CSRF mới xuất hiện cục bộ trong module Editorial, chưa phải cơ chế dùng chung cho toàn admin.
- Các bảng cũ phần lớn dùng MyISAM nên không hỗ trợ transaction và khóa ngoại đúng nghĩa.

Vì vậy, phần quản lý dự án nên được cô lập bằng tiền tố `pm_`, dùng DAO mới an toàn hơn, và tích hợp dần với tài khoản `dc_users`. Không nên mở rộng trực tiếp cơ chế SQL/permission cũ theo cách nối chuỗi.

## 2. Kiến trúc thực tế

### 2.1 Entry point và router

- Frontend: `index.php` khởi tạo DB, Smarty, Request và nạp `modules/estore/main.module.php`.
- Admin: `admin.php` khởi tạo các dependency, session rồi nạp `modules/admin/{op}.module.php`.
- Nhánh quản trị nội dung hiện dùng `op=manage`, sau đó `modules/admin/manage.module.php` nạp file theo quy tắc `modules/admin/manage/{act}{mod}.module.php`.
- View admin được chọn bằng biến `$templateFile` và render từ `templates/admin/`.
- Quy ước controller/view trong plan tương thích với code thật. Module PM nên dùng `op=pm` riêng để tránh va chạm với các module cũ và để ẩn toàn bộ menu cũ dễ hơn.

### 2.2 Session và đăng nhập

- Session admin nằm ở `includes/admin/sessions.inc.php`.
- Cookie đã bật `HttpOnly`, nhưng `cookie_secure` đang là `false` và chưa thấy cấu hình `SameSite`.
- Sau khi đăng nhập thành công chưa thấy gọi `session_regenerate_id(true)`.
- Đăng nhập quản trị nằm ở `modules/admin/login.module.php` và `classes/dao/users.class.php`.
- Có giới hạn đăng nhập sai qua bảng `dc_login_times` và class `CheckLogin`.
- `Users::authenticateUser()` băm mật khẩu bằng MD5 rồi so sánh trực tiếp.
- Form đăng nhập dùng `username`, chưa đăng nhập bằng email như yêu cầu mới.

Kết luận: tái sử dụng session và cơ chế khóa tạm thời, nhưng Phase 2 phải bổ sung đăng nhập email/username, hash hiện đại, rehash tương thích tài khoản MD5 cũ, regenerate session ID và cấu hình cookie theo HTTPS.

### 2.3 Phân quyền hiện tại

- `includes/admin/functions.inc.php::checkPermission()` chỉ so sánh cấp tài khoản `users.type`.
- `UserInfo::checkPermission($act, $mod)` đọc mảng `permissions` serialize trong `users.properties`.
- Quyền cũ chuyển hướng sang trang access denied, chưa trả HTTP 403 nhất quán.
- Cơ chế hiện tại không biểu diễn tốt một user có nhiều role và không có quyền sở hữu theo project.

Kết luận: giữ quyền cũ cho module cũ. Module PM dùng RBAC mới (`pm_roles`, `pm_permissions`, bảng liên kết) và hàm kiểm tra quyền riêng; không thay đổi hành vi module cũ trong Phase 2.

### 2.4 DAO và truy cập dữ liệu

- DAO kế thừa `classes/database/model.class.php` và thường đi kèm một Info object.
- Model cung cấp `select`, `add`, `update`, `delete`, `getNumItems` và `query`.
- Điều kiện WHERE thường được controller/DAO dựng bằng chuỗi.
- DB adapter `classes/database/mysql.class.php` gọi `mysqli_query()` trực tiếp, chưa có API prepare/bind.
- Cơ chế phân trang có thể tái sử dụng: DAO `getObjects()` + `getNumItems()`, `Url::genPager()` và template `corepager.tpl.html`.

Kết luận: bắt chước cấu trúc DAO/Info object và phân trang, nhưng các DAO PM phải có lớp truy vấn prepared statement riêng hoặc bổ sung API prepare an toàn vào DB adapter mà vẫn tương thích ngược. Không đưa input người dùng vào chuỗi condition.

### 2.5 Smarty và giao diện quản trị

- Layout dùng các template `coreheader.tpl.html`, `coreleft.tpl.html`, `coretopmenu.tpl.html`, `corefooter.tpl.html`.
- CSS chính là `templates/admin/css/screen.css`.
- Script hiện có MooTools, jQuery 1.12.4, Select2 và Flatpickr.
- Không tìm thấy Bootstrap 5 hoặc Chart.js trong admin hiện tại.
- Các module cũ đã có ví dụ escape Smarty, nhưng chưa áp dụng nhất quán toàn hệ thống.

Kết luận: có thể tái sử dụng shell admin và bổ sung CSS PM được namespace để tránh phá layout cũ. Việc đưa Bootstrap 5 toàn cục có nguy cơ xung đột cao; nếu dùng, chỉ tải trong layout/module PM hoặc xây component tương thích bằng CSS riêng. Chart.js chỉ tải trên trang báo cáo.

### 2.6 Tracking Data

- DAO: `classes/dao/trackings.class.php`.
- Bảng `dc_trackings`: `id`, `store_id`, `username`, `action`, `date_created`, `ip`.
- Các module gọi `$trackings->addData(...)` sau thao tác.
- Không có `user_id`, `entity_type`, `entity_id`, `old_value`, `new_value` hoặc index phục vụ audit theo entity.

Kết luận: không nên ALTER `dc_trackings` thành audit log nghiệp vụ vì dữ liệu cũ khác mục đích và engine MyISAM. Tạo `dc_pm_audit_logs` dạng InnoDB, đồng thời có thể ghi một dòng tóm tắt sang `dc_trackings` cho tính tương thích giao diện cũ.

### 2.7 Import/export

- Source có cả thư viện PhpSpreadsheet và mã PHPExcel cũ.
- Ví dụ PhpSpreadsheet đang hoạt động nằm ở `modules/admin/system/configcountclick.module.php`.
- Có các module import/export cũ trong `modules/admin/manage/`, nhưng nhiều đoạn gắn chặt với nghiệp vụ sản phẩm.

Kết luận: dùng PhpSpreadsheet, không phát triển mới trên PHPExcel. Tái sử dụng cách bootstrap thư viện và download response; viết validation/import nhân sự riêng.

## 3. Cơ sở dữ liệu hiện tại liên quan

Nguồn schema đáng tin cậy cho các bảng tài khoản là `dung_db_mysql84_fixed.sql`. File `dung_db.sql` không chứa đầy đủ toàn bộ bảng.

| Bảng hiện có | Đánh giá | Quyết định |
|---|---|---|
| `dc_users` | Có username, password, email, fullname, phone, type, status, properties; mật khẩu cột `varchar(50)` và dữ liệu MD5 | Tái sử dụng làm tài khoản nhân viên; ADD các cột PM thật sự cần |
| `dc_login_times` | Có số lần sai, thời điểm và IP | Tái sử dụng sau khi sửa các lỗi DAO/condition |
| `dc_trackings` | Log text đơn giản | Giữ nguyên; không dùng thay audit log chi tiết |
| `dc_customers` | Là tài khoản khách hàng website, có password và trạng thái | Không dùng làm nhân sự; có thể tham chiếu khách hàng dự án nếu nghiệp vụ xác nhận |
| `dc_customer_groups` | Nhóm khách hàng website | Không đồng nhất với phòng ban nhân sự |
| `dc_estores` | Tenant/store hiện tại | Giữ `store_id` trên các bảng PM để không phá mô hình multi-store |

Không tìm thấy các bảng project, task, timesheet, allocation, department hay RBAC phù hợp trong dump hiện tại.

### 3.1 Cột đề xuất bổ sung vào `dc_users`

Chỉ ADD COLUMN, không đổi tên/xóa cột cũ:

- `department_id BIGINT NULL`
- `is_active TINYINT(1) NOT NULL DEFAULT 1` hoặc thống nhất dùng `status` hiện có (cần chốt trước migration)
- `weekly_limit_hours DECIMAL(5,2) NOT NULL DEFAULT 40.00`
- `must_change_password TINYINT(1) NOT NULL DEFAULT 0`
- Mở rộng `password` lên `VARCHAR(255)` là ALTER MODIFY, trái nguyên tắc “chỉ ADD COLUMN”. Do đó cần tạo cột mới `password_hash VARCHAR(255) NULL` để migration hash tương thích mà không sửa cột cũ.

`tel` và `cell` đã có, không thêm `phone` trùng lặp.

### 3.2 Bảng mới đề xuất

Mọi bảng dùng `DB_PREFIX` + `pm_`, InnoDB, `utf8mb4`, có `store_id` nếu dữ liệu thuộc tenant:

- `pm_departments`
- `pm_roles`
- `pm_user_roles`
- `pm_permissions`
- `pm_role_permissions`
- `pm_hourly_rates`
- `pm_projects`
- `pm_project_members`
- `pm_tasks`
- `pm_timesheets`
- `pm_allocations`
- `pm_audit_logs`
- `pm_system_settings`
- `pm_import_logs`

Chưa tạo bảng `pm_customers` ở Phase 2. Cần chốt dự án sẽ dùng `dc_customers`, chỉ lưu tên khách hàng text trên project, hay có master customer nội bộ riêng.

### 3.3 Ràng buộc và index cần có

- Unique role/permission theo tenant: `(store_id, code)`.
- Unique member: `(project_id, user_id)`.
- Index task: `(project_id)`, `(assignee_id, status)`, `(project_id, status)`.
- Index timesheet: `(user_id, work_date)`, `(project_id, work_date)`, `(task_id)`.
- Index allocation: `(user_id, alloc_date)`, `(project_id, alloc_date)`.
- Index audit: `(entity_type, entity_id)`, `(user_id, created_at)`.
- Các bảng PM dùng InnoDB để transaction import và cập nhật lại OT theo ngày có tính nguyên tử.

Không tạo foreign key từ InnoDB sang `dc_users` MyISAM. Quan hệ đến user được kiểm tra tại application layer cho đến khi có kế hoạch chuyển engine riêng được duyệt.

## 4. Gap analysis

| Yêu cầu | Hiện trạng thật | Hướng thực hiện |
|---|---|---|
| Login email + mật khẩu an toàn | Login username, MD5 | Login email hoặc username; `password_hash` mới; fallback MD5 một lần rồi rehash |
| Nhiều role/user | `type` + permission serialize | RBAC bảng mới, giữ quyền cũ cho module cũ |
| Quản lý nhân sự | Có `dc_users`, thiếu phòng ban/giới hạn giờ | Mở rộng user + CRUD PM riêng |
| Đơn giá theo user/role/ngày | Chưa có | `pm_hourly_rates`, snapshot lúc chấm công |
| Project/task/Kanban | Chưa có | Module và bảng mới |
| Timesheet/OT | Chưa có | Module và bảng mới; tính lại cả ngày trong transaction |
| Audit old/new | Tracking text không đủ | `pm_audit_logs`; tùy chọn ghi tóm tắt sang Tracking |
| Chi phí và biểu đồ | Chưa có | SQL aggregate + Chart.js chỉ ở trang PM report |
| Allocation/overbooking | Chưa có | Bảng và module mới |
| Báo cáo | Report cũ không đúng nghiệp vụ | Module PM report mới |
| Excel | Có PhpSpreadsheet + ví dụ | Tái sử dụng thư viện, viết import/export riêng |
| Responsive Bootstrap 5 | Admin cũ không dùng Bootstrap 5 | Cô lập asset PM; không thay CSS toàn admin |
| Prepared statement | Chưa có nền dùng chung | Bổ sung abstraction an toàn hoặc DAO PM dùng mysqli prepare |
| CSRF | Chỉ có ở Editorial | Helper CSRF dùng chung cho mọi POST/AJAX PM |

## 5. Module và quy ước đã chốt ở mức đề xuất

### 5.1 Namespace/router

- Admin op mới: `pm`.
- Controller tổng: `modules/admin/pm.module.php`.
- Controller con: `modules/admin/pm/{act}{mod}.module.php`.
- Template: `templates/admin/pm{act}{mod}.tpl.html`.
- AJAX mới vẫn nằm trong `modules/ajax/`, tên bắt đầu `pm`.
- DAO/Info object: tên class PascalCase, file lowercase theo convention hiện tại; bảng bắt đầu `pm_`.

Tên nghiệp vụ: `pmuser`, `pmrole`, `pmrate`, `pmproject`, `pmmember`, `pmtask`, `pmtimesheet`, `pmallocation`, `pmreport`, `pmimport`, `pmexport`, `pmauditlog`.

### 5.2 Module mẫu nên đọc trước khi code

1. `modules/admin/manage/articlelist.module.php` + `classes/dao/articles.class.php` + `templates/admin/managearticlelist.tpl.html`: mẫu list, filter, sort, pager và thao tác trạng thái.
2. `modules/admin/manage/articleadd.module.php`: mẫu form/add, validation và tracking; chỉ học flow, không sao chép phần nối SQL/upload thiếu liên quan.
3. `modules/admin/editorial.module.php` + `templates/admin/editorial.tpl.html`: mẫu CSRF/escape gần nhất và thao tác POST hiện đại hơn.
4. `modules/admin/system/configcountclick.module.php`: mẫu PhpSpreadsheet hiện có.

## 6. Danh sách file dự kiến theo phase

Đây là danh sách định hướng, không phải quyền sửa trước khi phase tương ứng được duyệt.

### Phase 1

- Tạo `AGENTS.md`, `docs/PROJECT_BRIEF.md`, `docs/PLAN_FINAL.md`, `docs/DB_SCHEMA.md`, `docs/PROGRESS.md`.
- Cập nhật `.gitignore`; loại cache, backup, IDE và dump khỏi repository mới theo quy trình không làm mất file local.
- Thiết lập repository mới, `main`, `develop`; không push cho đến khi user duyệt tên và visibility.

### Phase 2

- Migration tạo RBAC và thêm cột user an toàn.
- Sửa/tạo helper auth, CSRF, permission và prepared query.
- Sửa login để tương thích MD5 cũ và hash mới.
- Tạo router/layout PM tối thiểu và seed quyền.

### Phase 3–8

- Tạo DAO/Info object, controller, template và AJAX tương ứng từng nghiệp vụ đã nêu trong plan.
- Mỗi phase có migration độc lập, chỉ CREATE TABLE hoặc ADD COLUMN.

### Phase 9–10

- Tối ưu query/index, responsive/security review, test/UAT, release note và checklist FileZilla.

## 7. Ẩn chức năng cũ và dọn repository

### 7.1 Ẩn, không xóa

- Không xóa controller, DAO, template hay route cũ.
- Tạo menu PM riêng và chỉ hiển thị các mục PM theo permission.
- Menu cũ có thể ẩn bằng cấu hình/feature flag theo môi trường hoặc role; URL cũ vẫn phải có permission check để tránh truy cập trực tiếp.
- Không dùng CSS `display:none` làm biện pháp phân quyền.

### 7.2 Các nhóm cần dọn ở Phase 1

- `templates_c/`: cache Smarty sinh tự động, hiện có file đã tracked.
- File `*.bak` và `*.bak-*`: có ít nhất 53 file local và một số file đã tracked.
- `.vscode/`: cấu hình máy cá nhân.
- Các dump `dung_db*.sql`: chứa dữ liệu/tài khoản thực, không được đưa sang repository mới.
- File cấu hình thật `includes/config.inc.php`: đã ignore, tiếp tục giữ ngoài Git.

Việc dọn phải thực hiện bằng cách cập nhật `.gitignore` và `git rm --cached` đối với file đã tracked, giữ bản local/backup ngoài repository khi cần. Không chạy `git clean` hoặc xóa hàng loạt khi worktree hiện đang có thay đổi chưa commit.

## 8. Rủi ro bắt buộc xử lý trước khi build

1. Repository hiện tại đang có thay đổi chưa commit và nhiều backup untracked; không được đổi branch/xóa file hàng loạt trước khi chụp diff hoặc backup.
2. Dump SQL chứa dữ liệu cá nhân và password hash thực; repository mới phải loại toàn bộ dump và cân nhắc xóa lịch sử nếu từng public.
3. MD5 phải được chuyển đổi tương thích; không thể “giải mã” MD5, chỉ rehash khi user đăng nhập thành công hoặc reset password.
4. `password VARCHAR(50)` không đủ cho Argon2id/bcrypt trong mọi cấu hình; dùng cột `password_hash VARCHAR(255)` mới nếu giữ nguyên nguyên tắc không MODIFY cột.
5. MyISAM không hỗ trợ transaction/foreign key; bảng PM mới phải dùng InnoDB.
6. Bootstrap 5 có thể xung đột CSS/JS với admin cũ; phải cô lập asset.
7. Nâng cấp jQuery/MooTools toàn cục nằm ngoài phạm vi và có nguy cơ làm hỏng module cũ.
8. Permission cũ có chỗ bị comment hoặc chỉ redirect; module PM phải kiểm tra quyền server-side trên mọi action/AJAX.

## 9. Quyết định cần Product Owner/mentor xác nhận

Trước Phase 1/2 cần trả lời:

1. Tên repository GitHub mới và chế độ `private` hay `public`.
2. Module PM chỉ dành cho admin nội bộ hay nhân viên cũng đăng nhập tại `admin.php`.
3. Có bắt buộc chỉ đăng nhập bằng email, hay chấp nhận cả email và username trong giai đoạn chuyển đổi.
4. Có giữ nguyên quy tắc “chỉ ADD COLUMN” để dùng `password_hash` mới, hay cho phép MODIFY cột `password` lên 255 ký tự.
5. Project customer sẽ dùng `dc_customers`, text tự do, hay bảng khách hàng nội bộ mới.
6. Quy tắc OT: chỉ vượt 8 giờ/ngày, hay còn phụ thuộc ca/ngày nghỉ/hệ số OT.
7. Ai được duyệt timesheet và timesheet đã duyệt có được sửa không.
8. “Xóa mềm user” dùng `status` hiện có hay thêm `is_deleted`; đề xuất dùng `status` để tránh hai nguồn trạng thái.
9. Các menu cũ nào phải ẩn cho từng role; mặc định đề xuất chỉ Admin hệ thống nhìn thấy menu cũ.
10. Đường dẫn/thư mục production dùng FileZilla và quy trình backup hiện tại (không ghi credential vào Git).

## 10. Tiêu chí duyệt Phase 0

- [x] Đã xác minh entry point, router, session/auth, DAO/Model, Smarty, phân trang, Tracking và Excel từ source thật.
- [x] Đã lập gap analysis dựa trên source.
- [x] Đã xác định bảng dùng lại, cột cần thêm và bảng mới dự kiến.
- [x] Đã chỉ ra module mẫu.
- [x] Đã nêu rủi ro, chiến lược ẩn chức năng cũ và dọn repository.
- [ ] Product Owner duyệt các quyết định tại mục 9.

Sau khi mục 9 được chốt mới lập `docs/PLAN_FINAL.md` và bắt đầu Phase 1. Không chạy migration hoặc sửa chức năng trong trạng thái hiện tại.
