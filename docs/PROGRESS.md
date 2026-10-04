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

### Resume — 2026-10-02

- Người dùng yêu cầu tiếp tục công việc và ghi nhớ cách trình bày GitHub chuyên nghiệp cho CV.
- Lưu yêu cầu danh tính Git, commit có scope, staged files an toàn, README/portfolio trung thực và cập nhật theo phase trong `AGENTS.md`.
- Danh tính Git local xác minh đúng Tạ Trí Dũng / GitHub noreply; nhánh Phase 5 đang sạch tại checkpoint trước thay đổi tài liệu resume.
- Chuẩn bị `docs/PHASE6_PLAN.md` cho chi phí/biểu đồ. Status PLAN, chưa BUILD/seed/migration Phase 6, chờ duyệt theo quy trình phase.
- Phase 5 chưa merge, chưa có xác nhận UAT. Resume không tự coi UAT đã hoàn tất.

## Phase 6 — Cost & Chart

### Status

BUILD / automated VERIFY COMPLETED trên `feature/pm-phase6-cost-chart` theo plan đã duyệt,
có bổ sung currency guard. Chờ UAT nghiệp vụ; chưa commit/push/merge/deploy Phase 6.

### Completed

- Prepared DAO `PmCosts` và `PmCostService`, route `admin.php?op=pmcosts`, menu theo permission.
- Actual dùng cost đã lưu trên timesheet; tổng hours/regular/OT và chi phí theo project/task/user/department/primary role/ngày; không resolve lại rate cho actual.
- Kiểm tra DISTINCT currency toàn tập trước mỗi SUM(cost), cùng transaction REPEATABLE READ/SERIALIZABLE. Mixed hoặc currency không hợp lệ: cost=null + warning, vẫn trả giờ. Kiểm tra riêng theo group, khoảng lọc và lifetime.
- Estimate tại ngày định giá hiển thị, từ estimated_hours × current user/primary-role rate, SQL DECIMAL; thiếu assignee/rate hoặc mixed currency không trả tổng ước tính sai. Budget VND chỉ so sánh với actual lifetime/estimate VND đủ dữ liệu.
- Actual của task đã ẩn vẫn giữ; project đã ẩn không vào view thường. Khoảng lọc inclusive, empty/error states và pagination project 20 dòng/trang.
- Cảnh báo phân loại phòng ban/role hiện tại chưa có snapshot lịch sử. JOIN rate/primary role chọn một record, không JOIN membership vào aggregate để tránh double-counting.
- Chart.js 4.5.1 local tại `js/vendor/chartjs-4.5.1/`, MIT license, npm SHA-512 integrity đã xác minh; hash asset ghi trong vendor README. Không runtime CDN.
- Polling GET tại `pm_ajax.php?op=pmcosts`, module nội bộ `modules/ajax/pmcosts.module.php`, 60 giây khi tab hiện; abort khi ẩn, giữ bộ lọc và số liệu cũ khi lỗi. Endpoint legacy ajax.php không thay đổi.
- Permission kiểm tra ở service/list/detail/polling; Admin toàn tenant, PM chỉ dự án phụ trách, HR/Employee fail closed. Session user active và tenant được xác minh lại ở endpoint JSON; no-store và allowlist cố định.
- Smarty và JSON escape an toàn, refresh dùng textContent; estimate valuation date cập nhật cả khi polling sang ngày mới. Bảng hoạt động khi chart không tải được; sửa canvas overflow trên mobile.

### Database

Backup local trước seed đã tạo trong `.local/` được Git ignore. Seed `003_seed_pm_cost_permissions.sql`
cộng thêm permission `pm.costs.view` và grant Admin/PM theo tenant, chạy idempotent hai lần PASS.
Không migration schema mới, không rewrite legacy, không production. Fixture kiểm thử rollback.
Rollback ứng dụng: ẩn route/menu costs, để nguyên grant không dùng; không xóa dữ liệu/bảng.

### Acceptance — đủ 6 nhóm

PHP dùng `.tools/php83/php.exe`:

| Nhóm | Lệnh / bằng chứng | Kết quả |
| --- | --- | --- |
| 1. Actual / estimate / OT / rate | `tests/pm_costs_smoke.php`: actual DECIMAL, cost đã lưu không đổi khi rate_snapshot thay đổi, OT, valuation date, lifetime vs range, missing/mixed estimate | PASS |
| 2. Filter / empty / pagination / JOIN | Cùng smoke: ngày invalid/reversed, input array, empty range, inclusive date, page 2, thêm membership/multi-role không nhân đôi chi phí | PASS |
| 3. Permissions / IDOR / polling | Cùng smoke + `tests/pm_costs_http_smoke.php`: Admin/PM list/detail, HR/Employee denied, foreign tenant, live HTTP page/polling, auth/bad filters/allowlist/no-store/GET-only | PASS |
| 4. DECIMAL / soft delete / currency | Cùng smoke: tiền lớn `1234567890123958.03`, task lịch sử, project hidden, mixed currency ở total/project/task/user/department/role/date/lifetime, homogeneous subrange, USD không so budget VND | PASS |
| 5. Render / browser / polling | `tests/smoke_pm_costs.php`; `playwright-cli -s=pmphase6 run-code --filename=../tests/pm_costs_browser.js` từ `.local/`, server preview localhost:18767, dữ liệu giả | PASS |
| 6. Regression / lint / review | Bộ regression Phase 2–5 bên dưới, PHP lint, Node syntax check, diff check và security review | PASS |

Nhóm 5 kiểm tra desktop 1440×1000, mobile 390×844, Chart.js version, không page overflow,
polling 60s dừng/resume qua visibilitychange, giữ filter, mixed-money chart không vẽ sai,
escaping refresh, giữ số liệu khi 403 và table fallback khi asset chart bị chặn.
Screenshot đã xem trong `.local/phase6-desktop.png`, `.local/phase6-mobile.png`; không công bố dữ liệu thật.
Console lỗi 403/asset abort là tình huống cố ý trong test; favicon localhost 404 không thuộc lỗi JS ứng dụng.

- `tests/pm_phase6_permissions.php --apply` hai lần, sau đó không --apply: PASS.
- Regression: `pm_auth_rbac_smoke.php`, `pm_rates_smoke.php`, `pm_departments_smoke.php`, `pm_projects_smoke.php`, `pm_phase5_migration.php`, `pm_phase5_audit_permissions.php`, `pm_timesheets_smoke.php`, `pm_timesheet_window_smoke.php`, `pm_audit_smoke.php`, `smoke_pm_timesheets.php`, `smoke_pm_audit.php`, `smoke_pm_admin.php`, `smoke_pm_users.php`, `smoke_pm_projects.php`, `smoke_pm_dependencies.php` trong tests/: PASS.
- PHP `-l`: admin.php, pm_ajax.php, pmcosts DAO/service, pm controller thay đổi, costs controller/AJAX và bốn PHP test Phase 6: PASS.
- `node --check js/pmcosts.js`, `node --check tests/pm_costs_browser.js`, `node --check js/vendor/chartjs-4.5.1/chart.umd.js`: PASS.
- `git -c safe.directory=D:/derasoft-pm diff --check` và kiểm tra whitespace cho file mới: PASS.

### Security review / giới hạn

Rà soát theo skill code-review: prepared filters, role/tenant/project boundaries, active session,
fixed AJAX allowlist, GET-only/no-store, HTML/JSON escaping, DOM textContent, currency guards,
consistent snapshot và không JOIN nhiều membership/role làm tăng số tiền. Không phát hiện lỗi còn mở trong phạm vi đã kiểm tra.
Chưa UAT bằng thao tác tài khoản thật; browser dùng synthetic preview và HTTP dùng session test local.
Chưa chạy benchmark dữ liệu lớn hoặc test concurrency nhiều connection; không tuyên bố performance/production-ready.

### Next Step

UAT local `admin.php?op=pmcosts` bằng Admin/PM: bộ lọc, project detail, actual vs lifetime,
estimate và missing rate/currency warnings, responsive/polling. HR tài chính vẫn chưa mở.
Chờ người dùng cho phép commit/push/merge/deploy; không tự chuyển Phase 7.

### Phase 5 — sửa lỗi trang chấm công Forbidden (2026-10-02)

Người dùng phát hiện `admin.php?op=pmtimesheets` trả Forbidden. Controller gọi
`requirePermission()` trước khi khởi tạo `$pmAccess`, khiến kiểm tra quyền fail closed
ngay cả với tài khoản được cấp quyền. Đã khởi tạo PmAccess trước kiểm tra quyền;
không bỏ kiểm tra permission, ownership hoặc tenant.

Bổ sung regression HTTP trong `tests/pm_costs_http_smoke.php` cho trang chấm công
bằng session Admin và tài khoản không có role chi phí. Test tái hiện HTTP 403 trước sửa,
sau sửa trả HTTP 200 và render lịch sử chấm công ở cả hai session.

Lệnh `.tools/php83/php.exe` với `tests/pm_costs_http_smoke.php`,
`tests/pm_timesheets_smoke.php`, `tests/pm_timesheet_window_smoke.php`,
`tests/smoke_pm_timesheets.php`: PASS. PHP `-l` controller và HTTP test: PASS.
`git -c safe.directory=D:/derasoft-pm diff --check`: PASS (chỉ cảnh báo LF/CRLF).
Chưa xác nhận UAT trên session trình duyệt của người dùng tại cổng 8088.
Giữ nguyên thay đổi Phase 6; chưa commit/push/merge/deploy.

### Tiếp tục VERIFY / chuẩn bị UAT — 2026-10-02

- Kiểm tra các controller PM: PmAccess được khởi tạo trước requirePermission.
- Mở rộng `tests/pm_costs_http_smoke.php`: chấm công Admin/tài khoản không có role chi phí HTTP 200;
  ID không tồn tại HTTP 403; Admin Audit Viewer HTTP 200, filter entity_type sai HTTP 400.
- `.tools/php83/php.exe tests/pm_costs_http_smoke.php`, `tests/pm_audit_smoke.php`,
  `tests/smoke_pm_audit.php`: PASS. Test không thay đổi dữ liệu nghiệp vụ tồn tại.
- Bổ sung `docs/UAT_PHASE5_6.md`: checklist theo role, OT/khóa 3 ngày/audit,
  actual/estimate/currency/scope/polling và kết quả chưa kiểm tra để người dùng nghiệm thu.
- Chưa xác nhận UAT trên session thật tại cổng 8088; không chuyển Phase 7 hoặc push/merge/deploy.

### Chuẩn bị Phase 7 — 2026-10-02

Người dùng yêu cầu tiếp tục BUILD. Phase 6 đã hoàn tất BUILD/automated VERIFY;
đã chuẩn bị `docs/PHASE7_PLAN.md` cho phân bổ nguồn lực/overbooking theo PLAN_FINAL.
Status PLAN, chờ duyệt phạm vi quyền, ngưỡng ngày, overlap và lịch sử trước BUILD
theo quy trình AGENTS.md. Chưa tạo nhánh Phase 7, chưa migration/seed hoặc thay đổi ứng dụng.
Các thay đổi Phase 6 và bản sửa Forbidden vẫn được giữ nguyên; UAT Phase 5–6 chưa xác nhận.

## Phase 7 — Resource Allocation & Overbooking

### Status — 2026-10-03

Tiếp tục sau yêu cầu tạm dừng ngày 02/10. BUILD và automated VERIFY trên
`feature/pm-phase7-resource-allocation`; giữ thay đổi Phase 6 và bản sửa Forbidden.
Plan đã được duyệt, có hai điểm làm rõ capacity time-bound và overlap giờ trước BUILD.
Chưa commit/push/merge/deploy; chưa xác nhận UAT Phase 5–7.

### Completed

- DAO/service prepared, controller/Smarty/menu `pmallocations`, polling qua allowlist PM.
- Allocation theo tuần, task/assignee/membership active cùng tenant; CRUD và soft delete/audit transactional.
- Capacity hữu hạn/vô hạn, overlap đối xứng trong hasOverlappingCapacity(), Admin quản lý;
  daily theo ngày, weekly theo thứ Hai; không dùng threshold OT.
- Overlap nửa mở, hai đầu bắt buộc có cùng nhau, duration khớp giờ, không ca qua đêm;
  thiếu lịch chi tiết không khẳng định không trùng. Quá tải/trùng là cảnh báo, vẫn được lưu.
- Khóa sentinel user rồi các ngày theo thứ tự cố định; project khóa theo ID cố định;
  membership/task mutation đọc current dưới khóa. Tổng tải giữ cả historical task/project.
- Admin toàn tenant, PM dự án phụ trách, Employee chỉ đọc own, HR chưa có grant riêng.
  PM nhận tổng tải/busy ngoài scope nhưng không chi tiết; gợi ý không tự sửa assignment.
- Responsive, escaping, POST op/CSRF theo router legacy, polling 60s dừng khi tab ẩn,
  lỗi giữ dữ liệu cũ. Audit Viewer Admin thêm filter allocation/capacity; PM/HR vẫn giữ scope cũ.
- Migration 005/seed 004 đã chạy idempotent hai lần trên local sau backup ngày 02/10;
  không schema legacy/production. Rollback ẩn feature, giữ dữ liệu/bảng/grant.

### Acceptance và lệnh kiểm thử

PHP: `.tools/php83/php.exe`, fixture transaction rollback.

| Nhóm | Lệnh / bằng chứng | Kết quả |
| --- | --- | --- |
| 1. CRUD/validation/audit | tests/pm_allocations_smoke.php | PASS |
| 2. Capacity/tuần/overlap hiệu lực | Cùng smoke: hữu hạn/vô hạn, inclusive biên, excludeId, soft delete, midweek, đúng bằng/vượt ngưỡng, tuần qua năm | PASS |
| 3. Trùng giờ/ngoài scope | Cùng smoke: partial hai phía, containment hai chiều, exact, liền kề hai đầu, separate, NULL, chuyển ngày/user, tổng tải ngoài project | PASS |
| 4. Scope/CSRF/history/escaping | Cùng smoke + tests/pm_allocations_http_smoke.php: page/poll/detail/suggestions/session tenant, rejected POST và no-store | PASS |
| 5. UI/gợi ý/polling | tests/smoke_pm_allocations.php; playwright-cli -s=pmphase7 run-code --filename=../tests/pm_allocations_browser.js từ .local | PASS ngày 02/10 |
| 6. Lock/regression | tests/pm_allocations_concurrency.php: hai connection, input khóa đảo thứ tự, chờ rồi tiếp tục sau nhả khóa; regression Phase 2–6 | PASS |

Browser dùng synthetic preview local: desktop 1440×1000/mobile 390×844, không page overflow,
suggestions và refresh XSS-safe, route/CSRF sau refresh, filter/timer/visibility/error/empty.
Đã xem screenshot .local/phase7-desktop.png và phase7-mobile.png (dữ liệu giả).
Console 403 chủ ý khi test lỗi, favicon 404; không pageerror trong test.

Regression đã chạy ngày 02/10: pm_auth_rbac_smoke, pm_rates_smoke, pm_departments_smoke,
pm_projects_smoke, pm_phase5_migration, pm_phase5_audit_permissions, pm_timesheets_smoke,
pm_timesheet_window_smoke, pm_audit_smoke, smoke_pm_timesheets, smoke_pm_audit, smoke_pm_admin,
smoke_pm_users, smoke_pm_projects, smoke_pm_dependencies, pm_phase6_permissions,
pm_costs_smoke, pm_costs_http_smoke, smoke_pm_costs: PASS.

### Security review / giới hạn

Rà soát prepared input, role/tenant/project/owner, current reads sau mutex, transaction/audit,
CSRF và POST op, HTML escape/DOM textContent, fixed AJAX allowlist/GET-only/no-store.
Không phát hiện vấn đề bảo mật còn mở trong các đường dẫn đã rà soát.
Concurrency test xác minh mutex thực bằng hai connection; chưa kiểm thử hai mutation nghiệp vụ
đầy đủ đồng thời, benchmark tải lớn hoặc UAT session người dùng. Không tuyên bố production-ready.
List giới hạn 1000 allocation (cảnh báo), 1000 task chọn và 200 capacity mới nhất;
hướng dẫn nghiệm thu tại docs/UAT_PHASE7.md. Không tự chuyển Phase 8.

### Resume verification — 2026-10-03

Chạy lại `.tools/php83/php.exe` cho pm_phase7_migration, pm_allocations_smoke,
pm_allocations_concurrency, pm_allocations_http_smoke, smoke_pm_allocations,
pm_audit_smoke, smoke_pm_audit, pm_costs_smoke và pm_costs_http_smoke trong tests/: PASS.
PHP `-l` 14 file DAO/service/controller/router/test liên quan Phase 7: PASS.
`node --check js/pmallocations.js` và `node --check tests/pm_allocations_browser.js`: PASS.
`git -c safe.directory=D:/derasoft-pm diff --check`: PASS (cảnh báo chuyển LF/CRLF).
Browser không chạy lại ngày 03/10 vì không thay đổi UI/JS từ lần browser PASS ngày 02/10.
Hoàn tất DB_SCHEMA, PORTFOLIO, PROGRESS và UAT_PHASE7; giữ nguyên giới hạn nêu trên.

### Local checkpoint delivery

Phase 7 checkpoint includes the verified implementation and related tests.
Created retrospectively while completing Phase 9; no push/merge/deploy.
