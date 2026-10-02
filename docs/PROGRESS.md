# DeraSoft PM — Progress

## Phase 0 — Discovery & Audit

### Status

COMPLETED — chờ merge theo quyết định của người dùng.

### Completed

- Audit kiến trúc, auth/session, DAO, Smarty, pagination, Tracking Data và Excel.
- Ghi project brief, plan chính thức và quy tắc làm việc.
- Chốt bảy quyết định kỹ thuật; ghi ba quyết định nghiệp vụ chờ mentor cùng mặc định an toàn.

### Database

Không tạo hoặc chạy migration. Không kết nối production.

### Git

- Branch: `feature/pm-phase0-audit`
- Commit: `3df3349 docs(pm-phase0): record audit and delivery plan`

## Phase 1 — Môi trường & Baseline

### Status

COMPLETED — local PHP/DB/web baseline và login/logout thủ công đều đạt.

### Environment

- PHP 8.3.35 portable đã được chuẩn bị trong `.tools/` bị Git ignore; PHP mặc định của máy vẫn là 8.5.10.
- MySQL client 8.4.11 và service `MySQL84` có sẵn; chưa có credential/DB copy local để test ứng dụng.
- Smarty 4.5.5 được nhúng trong source.
- PHP 8.3 portable đã bật `mysqli`, `mbstring`, `openssl`, `fileinfo`, `zip`, `gd`, `intl`.
- Chưa phát hiện XAMPP/Laragon hoặc local web root đã cấu hình.

### Completed

- Tạo `develop` và `feature/pm-phase1-baseline` tại checkpoint Phase 0.
- Xác nhận file cấu hình và license thật đang được Git ignore.
- Chuẩn hóa thư mục và quy tắc migration tại `database/migrations/README.md`.
- Lập hồ sơ môi trường và ma trận baseline tại `docs/BASELINE.md`.
- PM template smoke test đạt cho dashboard, profile, password và team.
- Smarty 4.5.5 và PhpSpreadsheet smoke test ghi XLSX tạm thời thành công trên PHP 8.3, không có warning.
- Lint 1.401 file PHP bằng PHP 8.3: 1.396 đạt, 5 file mail legacy/third-party không tương thích và đã ghi rõ trong `docs/BASELINE.md`.
- Import dump vào database mới `derasoft_pm_local`; 76 bảng và 8 user, không ghi đè database khác.
- Ứng dụng kết nối database local bằng user chỉ có quyền trên database dự án.
- Public landing và Admin login trả HTTP 200 trên PHP 8.3; ca login sai hiển thị lỗi đúng và không fatal.
- Sửa route logout bị loại khỏi danh sách operation hợp lệ; logout hiện trả 302, xóa session/cookie và truy cập lại dashboard sẽ về trang login.
- Người dùng xác nhận login, dashboard và logout hoạt động đúng trên local ngày 2026-10-02.

### Database

Không tạo migration SQL. Dump đã được import vào database local mới `derasoft_pm_local`; không tác động production hoặc database cũ. Schema baseline nằm tại `docs/DB_SCHEMA.md`.

### Known Issues

- PHP local khác phiên bản production.
- Chưa có local DB đã ẩn dữ liệu và web server/virtual host.
- `vendor/autoload.php` không có; các thư viện nhúng cần được kiểm tra riêng.
- Năm file PHPMailer/mail legacy không parse trên PHP 8.3; không nằm trong PM hiện tại nhưng phải xử lý có chọn lọc trước khi dùng email.
- Auth legacy dùng MD5 và DAO legacy ghép chuỗi SQL; dự kiến xử lý Phase 2, chưa sửa Phase 1.

### Remaining Before Phase 2

- CRUD legacy, tracking có ghi dữ liệu và upload vẫn để manual check vì không tạo dữ liệu giả vào bản import.
- Chốt ba quyết định nghiệp vụ với mentor trước phase liên quan; không cần đóng cứng chúng trong Phase 2.

### Git

- Branch: `feature/pm-phase1-baseline`
- Commit: chưa tạo.

### Next Step

Thực hiện PLAN Phase 2 — Auth, Role & Permission. Không triển khai migration/auth trước khi plan Phase 2 được duyệt.

## Phase 2 — Auth, Role & Permission

### Status

COMPLETED — build, automated verification và kiểm thử thủ công tài khoản thật đều đạt.

### Completed

- Mở rộng `dc_users` bằng cột nullable `password_hash`; giữ nguyên cột MD5 để tương thích tài khoản cũ.
- Tạo bốn bảng RBAC InnoDB: `dc_pm_roles`, `dc_pm_permissions`, `dc_pm_user_roles`, `dc_pm_role_permissions`.
- Seed idempotent bốn role `ADMIN`, `PM`, `HR`, `EMPLOYEE`, 18 permission và mapping ban đầu cho user legacy active.
- Thêm adapter prepared statement riêng cho code PM mới, không rewrite lớp `Model` legacy.
- Login hỗ trợ username hoặc email duy nhất trong store; tài khoản MD5 được nâng hash sau lần xác thực thành công.
- Mật khẩu đổi từ PM chỉ ghi `password_hash`; không xóa hash legacy.
- Regenerate session ID sau login; cookie dùng HttpOnly, SameSite=Lax, strict mode và Secure trên HTTPS.
- Thêm `requirePermission()` và `requireProjectAccess()`; project access fail closed cho tới khi bảng thành viên dự án được tạo ở phase dự án.
- Tích hợp permission vào dashboard, hồ sơ, đổi mật khẩu và danh sách nhân sự; menu nhân sự được ẩn khi không có quyền.

### Database

- Migration: `database/migrations/001_create_pm_auth_rbac.sql`.
- Seed: `database/seeds/001_seed_pm_auth_rbac.sql`.
- Đã chạy và xác minh trên `derasoft_pm_local`; không kết nối hoặc thay đổi production.
- Migration chỉ cộng thêm schema. Không có `DROP`, `RENAME`, `TRUNCATE` hoặc xóa dữ liệu.
- Rollback ứng dụng: có thể quay lại code cũ và để nguyên cột/bảng mới chưa dùng; project policy không cho rollback bằng DROP.

### Verification

- Phase 2 schema và seed: PASS.
- Password primitives, 4 role, permission boundaries và legacy mappings: PASS.
- PM Admin template smoke: PASS.
- Smarty/PhpSpreadsheet regression smoke: PASS.
- PHP 8.3 lint toàn bộ file PHP thay đổi trong Phase 2: PASS.
- Người dùng xác nhận đăng nhập username, dashboard, cập nhật hồ sơ, đổi mật khẩu và logout hoạt động đúng trên local ngày 2026-10-02.
- Đăng nhập bằng email đã có automated coverage ở tầng truy vấn nhưng chưa manual test vì người dùng chưa cấu hình email dùng để thử.

### Git

- Branch: `feature/pm-phase2-auth-role`.
- `b231629 feat(pm-auth): add RBAC schema and default roles`
- `ef047d4 feat(pm-rbac): add prepared role and permission data access`
- `69d3e04 feat(pm-auth): upgrade legacy login and session security`
- `a41fc96 feat(pm-rbac): enforce permissions in admin workspace`
- `d8ca15e test(pm-auth): verify Phase 2 security boundaries`
- `e2a11c0 fix(smarty): use writable local compile cache`
- `2e5c55b fix(pm-auth): preserve form routes on account updates`

### Next Step

Merge Phase 2 vào `develop`, sau đó thực hiện PLAN Phase 3 — User, Role & Hourly Rate. Không tạo migration hoặc code Phase 3 trước khi plan được duyệt.

## Phase 3 — User, Role & Hourly Rate

### Status

BUILD/VERIFY COMPLETED — chờ kiểm thử thủ công User CRUD, role và rate trước khi merge.

### Completed

- Chốt và ghi nhận kiến trúc PM multi-tenant theo `store_id` kế thừa DeraSoft; hiện không thêm UI chọn tenant.
- Thêm migration department, thuộc tính user và hourly rate; tenant hóa/widen schema RBAC Phase 2 theo ngoại lệ được duyệt.
- Thêm prepared DAO cho danh sách, tìm kiếm, tạo/sửa, khóa/mở khóa, soft delete user và gán nhiều role/role chính.
- Thêm `PmRateService` với XOR owner ở application layer, kiểm tra overlap đối xứng và khóa owner rows `FOR UPDATE` trong transaction.
- `resolveRate()` ưu tiên user rate, role chính rồi fallback 0 VND kèm cảnh báo.
- Thêm giao diện quản lý nhân sự responsive, role, rate, trạng thái và phân trang trong PM Admin.
- Mọi mutation qua module có CSRF, permission, tenant check và ghi Tracking Data mô tả.

### Verification

- Phase 3 tenant schema: PASS trên `derasoft_pm_local`.
- Owner XOR và overlap đối xứng, gồm rate mới mở vô hạn đè rate cũ có ngày kết thúc: PASS.
- Phase 2 auth/RBAC regression: PASS.
- PM Admin và PM users Smarty smoke: PASS.
- PHP 8.3 lint các file Phase 3: PASS.
- Không kết nối hoặc thay đổi production.

### Next Step

Kiểm thử thủ công trên local: danh sách/search, create/edit, lock/unlock, soft delete, multi-role/role chính và user rate. Sau khi được xác nhận mới commit phần nghiệm thu, push/merge Phase 3 và dừng trước Phase 4.
