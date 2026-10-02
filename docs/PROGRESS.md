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

BUILD/VERIFY COMPLETED — bổ sung CRUD phòng ban và siết permission render/action; automation Phase 2–3 PASS. Chờ kiểm thử thủ công User CRUD, role và rate trước khi merge.

### Completed

- Chốt và ghi nhận kiến trúc PM multi-tenant theo `store_id` kế thừa DeraSoft; hiện không thêm UI chọn tenant.
- Thêm migration department, thuộc tính user và hourly rate; tenant hóa/widen schema RBAC Phase 2 theo ngoại lệ được duyệt.
- Thêm prepared DAO cho danh sách, tìm kiếm, tạo/sửa, khóa/mở khóa, soft delete user và gán nhiều role/role chính.
- Thêm `PmRateService` với XOR owner ở application layer, kiểm tra overlap đối xứng và khóa owner rows `FOR UPDATE` trong transaction.
- `resolveRate()` ưu tiên user rate, role chính rồi fallback 0 VND kèm cảnh báo.
- Bổ sung màn hình thêm rate theo role, xem lịch sử, sửa khoảng hiệu lực và ngừng áp dụng rate; quyền xem/ghi được kiểm tra riêng.
- Rate input được validate dạng số thập phân theo giới hạn schema; không cho đổi owner của rate khi sửa.
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

### Follow-up 2026-10-02

- Người dùng yêu cầu tiếp tục BUILD Phase 4 sau rà soát; lưu checkpoint source bổ sung Phase 3 bằng commit `417c6e6 feat(pm-workforce): complete department and hourly rate management`.
- Lỗi include rate panel đã sửa; smoke render dùng template root `templates/` giống Admin thực tế.
- Kiểm thử thủ công bằng tài khoản thật vẫn chưa được xác nhận; không ghi nhận UAT PASS.
- Phase 4 branch kế thừa checkpoint Phase 3; chưa merge develop hoặc push remote.

## Phase 4 — Project & Task

### Status

BUILD và automated VERIFY đạt trên local; chờ nghiệm thu thao tác trình duyệt bằng tài khoản thật.

### Completed

- Kế hoạch chi tiết: `docs/PHASE4_PLAN.md`; branch `feature/pm-phase4-project-task`.
- Migration `003_create_pm_projects_tasks.sql` tạo ba bảng InnoDB tenant-scoped mới; đã áp dụng trên `derasoft_pm_local`, không sửa bảng legacy.
- CRUD dự án với search/phân trang, manager, trạng thái, ngân sách và ngày; soft delete giữ dữ liệu.
- Quản lý thành viên active; chặn gỡ manager hoặc người còn task được giao.
- CRUD task, assignee trong phạm vi membership active, trạng thái/ưu tiên/giờ dự kiến/hạn.
- Kanban bốn cột với form chỉnh sửa và chuyển trạng thái POST/CSRF.
- PM chỉ quản lý dự án mình phụ trách; Admin vẫn phải lọc tenant; helper project access từ chối dự án không tồn tại/soft deleted.
- Các view dùng relative includes, escape output và ẩn mutation forms theo quyền.

### Verification

- `tests/pm_phase4_migration.php`: PASS schema InnoDB và store_id.
- `tests/pm_projects_smoke.php`: PASS CRUD, membership, assignee, Kanban, tenant và PM boundaries; fixture rollback được xác minh.
- `tests/smoke_pm_projects.php`: PASS render các form/create/edit/read-only và escaping.
- Regression auth/RBAC, users, rates, departments, Admin và dependencies Phase 2–3: PASS.
- PHP 8.3 lint file mới/thay đổi và `git diff --check`: PASS.

### Next Step

Nghiệm thu local tại `admin.php?op=pmprojects`: tạo/sửa dự án, thêm thành viên, tạo/giao task,
chuyển trạng thái Kanban, thử gỡ thành viên còn task và soft delete. Thử thêm tài khoản PM
và Employee để xác nhận trải nghiệm theo quyền. Chưa triển khai Phase 5 hoặc production.

## Phase 5 — Timesheet, OT & Audit

### Status

BUILD và automated VERIFY hoàn tất theo phạm vi cập nhật ngày 2026-10-02: timesheet, OT,
audit viewer, khóa tự động sau cửa sổ 3 ngày và ngoại lệ Admin. Workflow duyệt bị hoãn theo
yêu cầu người dùng; Submitted/Approved/Rejected không thuộc phạm vi nghiệm thu Phase 5.
Chưa xác nhận UAT bằng trình duyệt/tài khoản thật; không ghi nhận UAT PASS.

### Completed

- Giữ phần BUILD có sẵn trên `feature/pm-phase5-timesheet-ot`; không commit hoặc push.
- Route `admin.php?op=pmtimesheets`, menu theo permission, giao diện Smarty responsive để tạo/sửa/ẩn timesheet của chính user; Admin xem và sửa bản ghi của mọi user trong tenant.
- Chỉ ghi công vào task được giao, membership active và cùng tenant; các thao tác ghi kiểm tra CSRF.
- Tổng giờ tối đa 24/ngày, phân bổ giờ thường/OT theo id trên mọi dự án; sửa/chuyển ngày/xóa mềm tính lại ngày liên quan.
- Snapshot rate khi tạo/chuyển ngày; sửa cùng ngày giữ rate. Chi phí tính bằng CAST DECIMAL trong MySQL, không dùng float PHP để tính tiền.
- Ledger khóa ngày theo tenant/user/date, snapshot ngưỡng và hệ số. Cửa sổ sửa/xóa là 3 ngày lịch theo giờ Việt Nam (work_date là ngày thứ nhất), tự khóa từ ngày thứ tư; kiểm tra server cả ngày cũ/mới, chặn backdate/future của non-Admin.
- Admin sửa/xóa sau khóa, vẫn giữ owner và tính lại OT trên ngày của owner; audit riêng `admin_update_locked`/`admin_delete_locked`. Task lịch sử đã ẩn/đổi assignee vẫn sửa được khi Admin giữ nguyên task_id.
- Route `admin.php?op=pmaudit`, lọc entity/action và phân trang. Admin xem toàn tenant và settings; PM xem dự án mình quản lý; HR tạm xem phòng ban active hiện tại, chưa có phòng ban chỉ xem cá nhân. PM/HR không nhận financial fields trong audit JSON. Phạm vi HR toàn công ty chưa được chốt.
- Không xây endpoint duyệt, không reject-cả-ngày; cột status/permission approve đã có giữ nguyên không sử dụng. Quyết định hoãn workflow ghi trong `docs/AUDIT.md`.
- Admin cấu hình ngưỡng/hệ số cho ngày mới; audit JSON nằm cùng transaction với mutation và recalculation.

### Database

- Backup `derasoft_pm_local` trước migration trong `.local/` được Git ignore; không in credential hoặc stage backup.
- `004_create_pm_timesheets_audit.sql` chỉ CREATE TABLE IF NOT EXISTS cho settings, ledger, timesheets, audit logs.
- Áp dụng hai lần trên local đều PASS; không thay đổi production hoặc dữ liệu legacy.
- Backup local thêm trước seed `002_seed_pm_phase5_audit_permissions.sql`; seed cộng thêm permission `pm.audit.view` cho Admin/PM/HR theo tenant, chạy hai lần đều PASS. Không xóa grant hoặc sửa schema legacy.
- Rollback ứng dụng giữ bảng cộng thêm và ngừng sử dụng route mới; không DROP bảng.

### Verification — lệnh đã chạy

Tất cả lệnh PHP dưới đây dùng `.tools/php83/php.exe` (PHP 8.3):

- `tests/pm_phase5_migration.php --apply` (hai lần), sau đó `tests/pm_phase5_migration.php`: PASS schema InnoDB và tenant.
- `tests/pm_timesheets_smoke.php`: PASS OT, tính lại khi sửa/xóa/chuyển ngày, rate và settings snapshot, chi phí DECIMAL với đơn giá lớn, rollback vượt 24 giờ, ownership/tenant/task boundaries, Admin settings và audit. Fixture transaction rollback, kiểm tra project fixture không còn tồn tại.
- `tests/pm_timesheet_window_smoke.php`: PASS ranh giới ngày thứ ba/thứ tư, sửa/xóa/chuyển ngày/backdate sau khóa, future denial, Admin sửa/xóa bản ghi của user khác và task đã ẩn, tính lại OT đúng owner, audit riêng và fixture rollback.
- `tests/pm_audit_smoke.php`: PASS tenant/role/PM project scopes, HR own/same/other/inactive department scopes, financial redaction, filter validation và fixture rollback.
- `tests/pm_phase5_audit_permissions.php --apply` (hai lần): PASS tenant-scoped grants.
- `tests/smoke_pm_timesheets.php`, `tests/smoke_pm_audit.php`: PASS render create/locked/read-only, escaping, Admin settings, audit details/filter/pagination/empty state.
- Regression `tests/pm_auth_rbac_smoke.php`, `tests/pm_rates_smoke.php`, `tests/pm_departments_smoke.php`, `tests/pm_projects_smoke.php`, `tests/smoke_pm_admin.php`, `tests/smoke_pm_users.php`, `tests/smoke_pm_projects.php`, `tests/smoke_pm_dependencies.php`: PASS.
- `-l` trên toàn bộ PHP mới/thay đổi của Phase 5: PASS.
- `git -c safe.directory=D:/derasoft-pm diff --check`: PASS.

### Security review

Rà soát controller/service/view/migration: prepared queries, tenant + ownership scope, POST/CSRF, escape HTML,
Admin-only settings, khóa 3 ngày kiểm tra server kể cả sau chờ ledger lock, override Admin có audit riêng,
audit scopes và financial redaction, audit trong transaction. Không phát hiện lỗi còn mở trong phạm vi đã kiểm tra.
Chưa chạy kiểm thử concurrency nhiều connection hoặc E2E HTTP/CSRF; không coi smoke service/template là UAT.

### Next Step

Nghiệm thu local tại `admin.php?op=pmtimesheets` và `admin.php?op=pmaudit`: tạo hai ca tổng trên 8 giờ,
sửa/chuyển ngày/ẩn, thử vượt 24 giờ, xem bản ghi bị khóa và Admin sửa sau khóa, thử PM/HR audit scope.
Xác nhận phạm vi đọc HR tạm thời, UAT trước merge. Workflow duyệt bị hoãn;
không tự triển khai submit/approve/reject, production, commit hoặc merge.

### Checkpoint tạm dừng — 2026-10-02

Người dùng yêu cầu tạm dừng BUILD, tạo commit và push nhánh Phase 5 lên GitHub.
Lưu phạm vi BUILD đã automated VERIFY tại `feature/pm-phase5-timesheet-ot`; chưa merge,
chưa deploy và chưa ghi nhận UAT PASS. Tiếp tục nghiệm thu khi người dùng yêu cầu resume.
Không đưa config bảo mật, backup database, log hoặc cache vào commit.
