# Phase 0 — Discovery & Audit

Ngày audit: 2026-09-30

Repository: `D:\derasoft-pm`

Nhánh: `feature/pm-phase0-audit`

## 1. Phạm vi và giới hạn

Audit này chỉ đọc source trong repository mới và ghi tài liệu. Không sửa logic ứng dụng, không tạo/chạy migration, không kết nối database production và không kiểm tra dữ liệu thật trên host.

Các nhận định về schema chỉ dựa trên DAO, module và artifact trong source. `database/migrations/` hiện rỗng; repository không có dump schema phù hợp để xác nhận đầy đủ kiểu cột, khóa ngoại và index. Việc này phải thực hiện trên bản sao database local ở Phase 1.

## 2. Kiến trúc và luồng request hiện tại

### Entrypoint và router

- `index.php`: giao diện public mới; public legacy vẫn nằm trong source nhưng được ẩn.
- `admin.php`: bootstrap Admin, nạp config, constant, DB, Smarty, Request, URL và DAO nền.
- `ajax.php`: entrypoint AJAX legacy/public; `router.php` hỗ trợ web routing.
- `pm-launch.php`: điểm chuyển tiếp phục vụ PM hiện tại.

`admin.php` lấy `op`, `act`, `mod` qua `Request` rồi nạp `modules/admin/<op>.module.php`. Khi `PM_HIDE_LEGACY=true`, user đã đăng nhập chỉ được tới `op=pm` hoặc `op=logout`; request Admin legacy khác bị đưa về PM. Cơ chế này đáp ứng yêu cầu “ẩn, không xóa”.

`modules/admin/pm.module.php` hiện có `dashboard`, `team`, `profile`, `password`. View dùng `templates/admin/pm.tpl.html`; login dùng `pm-login.tpl.html`.

Smarty được nhúng trong `classes/template/`. Controller gán dữ liệu bằng `$template->assign()`. PM template hiện dùng CSS nội tuyến, chưa phải Bootstrap 5 component system.

## 3. Auth, session và phân quyền

### Có thể tái sử dụng

- User/session hiện hữu với `$_SESSION['userId']`.
- DAO `Users`, `UserInfo`, trạng thái user và `last_login`.
- Chống brute force cơ bản bằng `CheckLogin`, số lần sai và thời gian khóa.
- Tracking login/logout.
- Form profile/password PM có CSRF bằng `random_bytes()` và `hash_equals()`.

### Điểm phải sửa

- Admin xác thực bằng MD5 trong `Users::authenticateUser()`; đổi mật khẩu PM cũng ghi MD5. Cần dual verify rồi rehash sang `password_hash()` ở Phase 2.
- Sau Admin login chưa thấy `session_regenerate_id(true)`.
- `includes/admin/sessions.inc.php` dùng `cookie_secure=false`, chưa khai báo SameSite.
- Login dùng `username`, chưa phải email.
- Quyền legacy dựa trên `type`/property; chưa có RBAC nhiều-nhiều, permission code hoặc ownership dự án.
- Logout chỉ xóa một số session value; cần gia cố mà không phá luồng `adminId` legacy.

## 4. DAO, phân trang và tracking

`classes/database/model.class.php` có `select`, `add`, `update`, `delete`, `getNumItems`, `countItems`, `query`. Các DAO kế thừa Model và thường có lớp Info tương ứng.

Cấu trúc này có thể tái sử dụng, nhưng implementation ghép chuỗi SQL và dùng `addslashes()`, không có prepared-statement API. Code PM mới không được đưa input vào cách ghép chuỗi này. Phase 1/2 cần chốt lớp query prepared tương thích `mysqli` mà không rewrite legacy.

Phân trang có sẵn qua `getNumItems()`, `getObjects(page, ...)`, `classes/http/url.class.php` và template pager. Module PM phải whitelist sort/filter và bind value.

Tracking dùng `DB_PREFIX . 'trackings'` với các trường quan sát được: `store_id`, `username`, `action`, `date_created`, `ip`, `id`. Nó phù hợp log mô tả nhưng thiếu actor id, entity, entity id và old/new JSON. Ưu tiên mở rộng Tracking Data sau khi kiểm tra schema local, chưa tạo hệ audit song song.

## 5. Thư viện và tài sản có thể tái sử dụng

- PhpSpreadsheet nằm trong `classes/PhpSpreadSheet/`; cần kiểm tra version và PHP 8.3 ở Phase 1.
- PHPMailer có trong `classes/PHPMailer/` và bản sao `PHPMailer1/`; chưa xóa hoặc hợp nhất ở Phase 0.
- Smarty nằm trong `classes/template/`.
- Admin nạp `vendor/autoload.php` có điều kiện; cần inventory dependency ở Phase 1.
- Chưa thấy Chart.js tích hợp cho PM.
- PM Admin chưa dùng Bootstrap 5; CSS đang nằm trong template.
- Module import/export legacy là nguồn tham khảo, nhưng không được sao chép query ghép chuỗi hoặc upload thiếu kiểm soát.

## 6. Gap analysis

| Yêu cầu | Hiện trạng | Phân loại | Hướng xử lý |
|---|---|---|---|
| Login/session | Có, đang chạy | MODIFY | Giữ tương thích, nâng MD5 và session |
| RBAC nhiều role | Chỉ có type/property | NEW/MODIFY | Roles, permissions, middleware, mapping cũ |
| Quản lý user | Có DAO/Admin legacy | REUSE/MODIFY | Thêm trường thiếu, search, pager, soft lock |
| Phòng ban | Chưa xác nhận | VERIFY/NEW | Kiểm tra schema local, tạo nếu thiếu |
| Đơn giá hiệu lực | Chưa có | NEW | Rate theo user/role và ngày hiệu lực |
| Project/member | Chưa có PM domain | NEW | Bảng, DAO, module và ownership |
| Task/Kanban | Chưa có | NEW | Task CRUD và Kanban |
| Timesheet/OT | Chưa có | NEW | Snapshot rate/cost, tính lại OT theo ngày |
| Audit cấu trúc | Tracking dạng text | MODIFY | Ưu tiên mở rộng Tracking Data |
| Cost/chart | Chưa có | NEW | Aggregate query, API đọc, Chart.js |
| Allocation | Chưa có | NEW | Lịch tuần và overbooking |
| Báo cáo | Có pattern list/filter | REUSE/NEW | Tái dùng pattern, query nghiệp vụ mới |
| Excel | Có PhpSpreadsheet | REUSE/MODIFY | Import/export an toàn và transaction |
| Bootstrap responsive | PM dùng custom CSS | MODIFY | Chuẩn hóa theo phase đã duyệt |
| Public legacy | Đã ẩn | REUSE | Giữ source, không xóa |

## 7. Database gap và migration đề xuất

### Có thể xác định từ source

- User: `DB_PREFIX . 'users'`.
- Store: DAO EStores.
- Tracking: `DB_PREFIX . 'trackings'`.
- Kiểm soát login sai: `CheckLogin`; tên bảng thật cần xác nhận local.

### Chưa thể xác nhận

Kiểu dữ liệu, charset/collation, index, foreign key, độ dài password, bảng role/department tương đương và dữ liệu mapping. Không viết migration trước khi kiểm tra DB local copy.

### Migration dự kiến, chưa tạo SQL

1. Mở rộng user bằng các cột thực sự thiếu: phone, department, active/soft delete, weekly limit và password hash đủ dài.
2. Tạo departments, roles, user_roles, permissions, role_permissions.
3. Tạo hourly_rates có ngày hiệu lực.
4. Tạo projects, project_members, tasks.
5. Tạo timesheets và allocations.
6. Mở rộng tracking; chỉ tạo audit_logs nếu tracking không thể mở rộng an toàn.
7. Tạo system_settings và import_logs.
8. Thêm index/unique constraint sau khi kiểm tra dữ liệu thật trên bản sao.

Migration phải cộng thêm, versioned, kiểm tra tồn tại, thử trên DB copy và có rollback note. Không DROP/RENAME/DELETE dữ liệu legacy.

## 8. Module mẫu nên dùng

1. `modules/admin/system/staff*.module.php` và DAO Users: mẫu CRUD user, list, permission, tracking. Chỉ học cấu trúc, không sao chép query không prepared.
2. Module Admin list/edit và `corepager.tpl.html`: mẫu phân trang, template naming và navigation.
3. Module import/export dùng PhpSpreadsheet: mẫu bootstrap/sinh file; PM phải thêm MIME/size validation, transaction và lỗi theo dòng.

`modules/admin/pm.module.php` cùng `templates/admin/pm.tpl.html` là shell PM hiện tại và nên được mở rộng có chọn lọc thay vì mở lại toàn bộ menu legacy.

## 9. Rủi ro và thứ tự xử lý

### Cao

- MD5 cho mật khẩu Admin.
- DAO ghép chuỗi, nguy cơ SQL injection khi dùng với input mới.
- Thiếu permission/ownership chuẩn, nguy cơ IDOR.
- Chưa xác nhận schema local; migration sớm dễ xung đột production.

### Trung bình

- Cookie Admin chưa phù hợp HTTPS và chưa regenerate sau login.
- Tracking text chưa đủ audit old/new có cấu trúc.
- Thư viện nhúng thủ công/bản sao có thể không tương thích PHP 8.3.
- CSS PM nội tuyến khó mở rộng và kiểm soát responsive.

Thứ tự khuyến nghị: Phase 1 lập local baseline và inventory DB/dependency; Phase 2 gia cố auth/RBAC/prepared foundation; sau đó mới phát triển domain từ Phase 3.

## 10. Quyết định sau duyệt Phase 0

### Đã chốt kỹ thuật

1. Login giai đoạn chuyển đổi chấp nhận cả username và email; tài khoản cũ vẫn đăng nhập được và được nâng hash sau lần xác thực thành công.
2. Seed bốn role hệ thống Admin/PM/HR/Employee. Schema vẫn cho phép mở rộng role sau này, nhưng chưa làm màn hình role tùy chỉnh ngoài kế hoạch.
3. Khi user có nhiều role, rate riêng theo user được ưu tiên; nếu không có thì dùng role chính. Không tự lấy role có rate cao nhất.
4. Budget Phase đầu dùng VND. Schema tiền tệ vẫn dành chỗ mở rộng bằng mã currency, nhưng chưa triển khai quy đổi đa tiền tệ.
5. Giới hạn mặc định là 40 giờ/tuần và có thể ghi đè theo từng user/lịch làm việc.
6. Giữ đường Admin legacy cho xử lý khẩn cấp bằng feature flag cấu hình cục bộ, mặc định tắt và không commit giá trị production.
7. Phase 1 được dùng bản sao database production đã ẩn dữ liệu nhạy cảm để xác nhận schema; tuyệt đối không kết nối production trực tiếp.
8. PM Timesheet là hệ thống multi-tenant kế thừa cơ chế `store_id` của DeraSoft. Mọi bảng `pm_*` phải có `store_id BIGINT UNSIGNED NOT NULL`; mọi unique key tenant-scoped phải chứa `store_id`; mọi query và permission check phải lọc theo tenant hiện tại của session. Giai đoạn hiện tại chỉ vận hành một store DeraSoft và lấy tenant từ cơ chế xác định store có sẵn, không tạo UI chọn hoặc quản lý store mới.

### Chờ mentor xác nhận nghiệp vụ

Quyết định Phase 5 cập nhật ngày 2026-10-02 theo yêu cầu người dùng:

- Timesheet được sửa/xóa trong 3 ngày lịch kể từ work_date, tính work_date là ngày thứ nhất theo Asia/Ho_Chi_Minh. Ví dụ work_date 02/10 được sửa hết 04/10, khóa từ 00:00 05/10. Khóa tính khi đọc/ghi, không cần cron hoặc thao tác duyệt.
- Admin sửa/xóa mọi bản ghi trong tenant kể cả sau khóa; audit riêng `admin_update_locked`/`admin_delete_locked`, vẫn tính lại OT cả ngày và giữ rate snapshot khi sửa cùng ngày.
- Workflow duyệt timesheet bị hoãn, có thể bổ sung sau nếu mentor yêu cầu và sau khi phạm vi HR được chốt. Phase 5 không triển khai Submitted/Approved/Rejected, reject-cả-ngày hoặc endpoint duyệt. Cột status và permission approve đã có được giữ để tương thích, không dùng làm vòng đời Phase 5.
- Audit Viewer: Admin xem toàn tenant và settings; PM xem lịch sử timesheet trong dự án mình quản lý; HR tạm xem phòng ban hiện tại đang active, chưa có phòng ban chỉ xem lịch sử cá nhân. PM/HR không nhận fields đơn giá, chi phí hoặc cấu hình trong JSON. Đây là phạm vi đọc tạm thời theo mặc định an toàn, chưa mở HR toàn công ty.

1. Phạm vi HR xem đơn giá/chi phí: mặc định an toàn tạm thời là chỉ phòng ban được phân quyền; Admin xem toàn công ty.
2. Quy tắc OT: mặc định tạm thời là phần tổng giờ vượt 8 giờ/ngày; cuối tuần, ngày lễ và khung giờ đặc biệt chưa tự suy diễn.
3. Workflow duyệt timesheet bị hoãn, có thể bổ sung sau nếu mentor yêu cầu và sau khi phạm vi HR được chốt. Phase 5 dùng cửa sổ sửa/xóa 3 ngày và ngoại lệ Admin đã chốt ở trên.

Ba mặc định nghiệp vụ trên không được đóng cứng trong migration; phải cấu hình được hoặc hoãn triển khai đến phase liên quan nếu mentor chưa xác nhận.

## Kết luận

DeraSoft đủ nền để tiếp tục mà không làm lại Admin: router, session/user, Smarty, DAO convention, phân trang, tracking và Excel đều có thể tái sử dụng. Tuy nhiên chỉ nên tái sử dụng cấu trúc; auth MD5 và query ghép chuỗi phải được nâng cấp trước khi mở rộng nghiệp vụ.

Phase 0 dừng tại đây chờ duyệt. Chưa có thay đổi code ứng dụng hoặc database.

## Quyết định import Phase 8 — 04/10/2026

Giữ dc_users MyISAM, không đổi ENGINE kể cả local. Audit local trước migration: email index
thường, không UNIQUE, 9 users và không duplicate groups. Người dùng đã duyệt riêng migration
007 thêm UNIQUE(store_id,email); local áp dụng sau backup và xác nhận ENGINE/count giữ nguyên.
Không áp dụng production. Index email thường/collation/nullability được giữ.
Apply INSERT từng dòng bắt 1062, journal intent/outcome và in_progress + connection-owned
mutex; không SELECT-check-then-INSERT, không atomic toàn file MyISAM.
Chỉ phục hồi role cho user có provenance khớp batch/source_row; duplicate khác bị bỏ qua.
Account mới inactive; Admin cấp mật khẩu có CSRF/ownership rồi kích hoạt riêng.
Apply/crash thành công kiểm thử trên temporary mirror; native 1062 và hai-connection lock
kiểm thử thật. Chưa kiểm thử kill process sau successful MyISAM INSERT trên DB cô lập;
không ghi UAT/production-ready. Username DB chưa UNIQUE; xung đột giữ inactive và báo lỗi,
không tự thêm constraint thứ hai hoặc sửa dữ liệu để xử lý.
