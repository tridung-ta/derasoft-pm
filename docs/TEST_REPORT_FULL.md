# Báo cáo kiểm thử tổng — cập nhật 09/10/2026

Checklist: [QUY_TRINH_KIEM_THU_TONG.md](QUY_TRINH_KIEM_THU_TONG.md). Baseline local b8e0774, nhánh feature/pm-phase11-launch-copy; các cập nhật A/B/C/D1 ghi cuối báo cáo. D1 đã sửa runtime theo PLAN duyệt. Không merge/push/deploy, không migration. Chỉ database derasoft_pm_local/loopback được guard.

**Kết luận hiện tại: CHƯA ĐẠT checklist tổng do còn 7 mục CHƯA CHẠY.** 0.4 đã sửa theo PLAN được duyệt và VERIFY PASS; Tenant1.3/1.4, HTTP no-role/role refresh giữ PASS. Các lượt lỗi trước sửa bên dưới là lịch sử, không phải trạng thái hiện tại; không suy luận các mục chưa đủ bằng chứng thành PASS.

**Sự cố kiểm thử đã xử lý ở A:** runner cũ báo 47 PASS nhưng pm_audit_smoke thay department_id user thật trên MyISAM. Người dùng đã xác nhận giá trị gốc NULL cho cả hai user; đã khôi phục và cô lập test bằng temporary exact-schema mirror, xác minh bảng thật không đổi khi chạy lại. Lịch sử điều tra giữ bên dưới; 11.5 đã đối chiếu PASS tại BƯỚC 1 bên dưới. Không có bằng chứng đã thay production.

Quyết định người dùng: department tùy chọn, tên/email kiểm tra format/độ dài/control characters; không đánh giá tên vô nghĩa hoặc xác minh hộp thư. Validator hiện chưa đầy đủ: fullname/email 50, username 30, tel 15; import cho fullname 100/username 50. Xem [CONSTRAINT_REVIEW_20261007.md](CONSTRAINT_REVIEW_20261007.md). Giữ dc_users MyISAM.

**80 mục: 73 PASS, 0 FAIL, 7 CHƯA CHẠY.** Cập nhật P4 ngày 08/10/2026: 11.4 PASS trong phạm vi writer PM đã chốt, theo assertions riêng từng writer; 7.4 giữ PASS. PASS chỉ trong phạm vi bằng chứng ghi ở dòng; CHƯA CHẠY có thể có kiểm tra một phần.

## Lệnh và output được sử dụng

PHP là `.tools/php83/php.exe`; browser CLI chạy từ `.local`, localhost server 127.0.0.1:18767. Log chi tiết giữ local trong `.local/full-test/`; không commit log/PII/config.

- **E0:** `PHP tests/pm_ui_http_smoke.php` => `PASS: 18 authenticated role/route cases for ADMIN,EMPLOYEE ... search SQLi/XSS...`; output nêu PM/HR real actors NOT COVERED. `PHP tests/pm_role_http_smoke.php` => `PASS: 73 PM/HR HTTP controller cases with temporary fixtures ...`. Không coi controller fixture là login UAT.
- **E1-schema:** `PHP .local/constraint_tenant_schema.php` => 19 PM tables store_id=yes, engine=InnoDB; users=MyISAM. Read SHOW CREATE TABLE/columns/indexes, không đổi schema. JSON grouping => `Tenant unique keys checked: 12 Exceptions: []`; PRIMARY id toàn cục loại khỏi tenant business key check.
- **G1–G8:** `python .local/full_test_runner.py` chạy từng `PHP tests/<test>.php` theo nhóm (inventory bên dưới); tất cả process exit=0. Logs tên `G<group>-<test>.log`. Không suy toàn bộ case được bao phủ từ suite PASS.
- **G3-extra:** `PHP .local/constraint_extra.php rates` => `PASS 3.1-3.4: own-rate priority; primary role not highest; no-primary zero warning; expired rate ignored. Temporary exact-schema mirrors only.`
- **E-V:** `PHP .local/constraint_schema_read.php` => email/fullname=50, tel=15, username=30; email mẫu syntactic accepted. `PHP .local/constraint_extra.php norole` => auth accepted + denies all 23 permission codes, chưa HTTP no-role. `PHP .local/full_task_overload_probe.php` => `actual task=5h, estimate=4h; allocation warnings=[]`; fixture rollback.
- **E9-ui:** `playwright-cli -s=fullreview run-code --filename=../tests/pm_ui_browser.js` => `PASS: 13 synthetic PM screen/state fixtures at 360/390/768/1440px ... 200% text scaling (not OS/browser zoom)`.
- **E9-visibility:** `playwright-cli -s=fullreview run-code --filename=full-test/visibility.js` => `costs: PASS hidden stop and visible resume with valid session response`; `allocations: PASS hidden stop and visible resume with valid session response`.
- **E6-browser:** `playwright-cli -s=fullreview run-code --filename=../tests/pm_phase9b_costs_browser.js` => `PASS: real Chart.js empty/mixed/zero/recovery, decimal strings, tab keyboard/state, polling errors/auth, responsive/text scaling/CSP, no-JS and missing-chart fallbacks`. CSP violation counter không chứng minh header áp trên tất cả preview.
- **G10-buttons:** `... --filename=../tests/pm_phase9b_buttons_browser.js` => danger 4.83:1 white/red, normal 16.97:1, các state đều đạt; unlock/restore non-danger assertions pass.
- **G10-allocation:** `... --filename=../tests/pm_phase9b_allocation_browser.js` => `PASS: week rollover, server totals, overbooking, polling, escaping, keyboard, forms, responsive, text scaling, empty and no-JS fallback`.
- **G10-projects:** `... --filename=../tests/pm_phase9b_projects_browser.js` => `PASS: grid, native progress, missing/restricted data, keyboard collapse, CSRF, escaped names, pagination/navigation, responsive/text scaling and no-JS`.
- **G11:** `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1` => `PASS: 47 regression scripts. No migrations applied by this runner.` `PHP tests/pm_roles_batch_smoke.php` => exact role rows/primary for 20 users, batch 1. `python .local/full-test/lint_all.py` gọi PHP -l 103 changed files => failures=[]; log G11-lint.
- **G11-state:** checksum trước/sau bằng read-only schema helper: PM unchanged, dc_users changed. `PHP .local/checksum_diagnostic.php` => high-id fixtures=0, dangling department users=2, raw username tracking count=5332. Không in username/email/password/token ra output.

Các lần gọi bị lỗi harness không tính thành test PASS: PowerShell filename expression sai => SyntaxError (đã sửa cách gọi); visibility probe ban đầu kiểm tra trước response => “costs visible did not resume”, chạy lại chờ response thì PASS. Schema helper ở lượt review trước ban đầu DB temporary object bị destructor đóng connection, đã giữ reference và rerun read-only.

## Test cũ không đạt trong lượt này

- `pm_costs_browser.js`: `TypeError ... reading data` sau mixed currency; Chart hiện bị destroy đúng thiết kế, test cũ truy cập chart không còn. Suite hiện tại E6-browser PASS; cần sửa test cũ, không sửa app để vẽ trục rỗng.
- `pm_allocations_browser.js`: timeout click `#allocation-suggestions button` vì details mặc định đóng; cần keyboard/open panel trước. Suite lưới hiện tại PASS; chức năng suggestions service PASS, browser full suggestions vẫn chưa hoàn tất.
- `pm_phase9b_theme_browser.js`: `Dashboard muted text regressed: rgb(82,82,91)`; assertion đòi rgb(113,113,122) cũ. Màu mới đã duyệt, cần update expected/contrast evidence. Suite đã dừng ở dashboard, không gọi các trang sau PASS.

## Inventory thực thi nhóm service

G1: pm_projects_smoke, pm_timesheets_smoke, pm_allocations_smoke, pm_costs_http_smoke, pm_reports_http_smoke.
G2: pm_auth_rbac_smoke, pm_auth_identity_smoke, pm_session_guard_smoke.
G3: constraint_extra rates; pm_users_crud_smoke, pm_rates_smoke, pm_departments_smoke.
G4: pm_projects_smoke, smoke_pm_projects.
G5: pm_timesheets_smoke, pm_timesheet_window_smoke, pm_audit_smoke, smoke_pm_timesheets, smoke_pm_audit.
G6: pm_costs_smoke, pm_costs_http_smoke, smoke_pm_costs, pm_phase6_permissions.
G7: pm_allocations_smoke, pm_allocations_concurrency, pm_allocations_http_smoke, smoke_pm_allocations.
G8: pm_reports_smoke, pm_reports_http_smoke, pm_import_smoke, pm_import_http_smoke, pm_import_apply_smoke, pm_import_native_duplicate.

## Kết quả từng mã

### Nhóm 0

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 0.1 | **PASS** | Đã duyệt phòng ban tùy chọn; CRUD tạo department_id=NULL thành công, template có “Chưa có phòng ban”. G3 pm_users_crud_smoke, templates/admin/pm-users-v2.tpl.html:8. |
| 0.2 | **PASS** | Login HTTP thật no-role qua admin.php; 80 protected endpoint/op + 4 settings/import actions + 8 protected reads bị chặn 403. Admin routes assert body Forbidden từ requirePermission, CSRF hợp lệ, dữ liệu không đổi. Suite pm_session_role_http_smoke exit 0, 98/98 gồm login/cookie/refresh controls; 22 bảng thật không đổi. Phạm vi permission-protected PM, không cấm personal account routes. |
| 0.3 | **PASS** | pm_required_fields_http_smoke thực POST tạo nhân sự trong mirror: tên/email mẫu được lưu đúng, HTTP200, tracking1/audit0; 21 bảng thật không đổi. Không xác minh hộp thư. BATCH2_REQUIRED_FIELDS_VERIFY_20261009.md. |
| 0.4 | **PASS** | Sau PLAN duyệt, pm_required_fields_http_smoke exit0:30/30 case tạo/sửa, multiple field errors, trim whitespace, zero budget/estimate hợp lệ và nonempty-invalid; thông báo đúng trường, không ghi/audit/tracking khi invalid. 21 bảng thật không đổi. Regression59scripts PASS, matrix80/80+11controls+35behaviors giữ nguyên. BATCH2_REQUIRED_FIELDS_VERIFY_20261009.md lưu trước/sau và lệnh/output. |
| 0.5 | **PASS** | 13 fixture UI và template smoke render XSS thành text; HTTP search XSS không tạo script. E0, E9-ui; không gọi đây là pentest toàn framework. |
| 0.6 | **PASS** | pm_ui_http_smoke chạy search SQLi, scope/response không lỗi SQL lộ ra. E0. |

### Nhóm 1

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 1.1 | **PASS** | SHOW CREATE TABLE + information_schema cho 19 bảng dc_pm_*: tất cả có store_id. E1-schema. |
| 1.2 | **PASS** | 12 unique key nghiệp vụ không phải PRIMARY đều chứa store_id; PK id toàn cục không được coi là tenant business key. E1-schema. |
| 1.3 | **PASS** | Trên commit c850158: `PHP tests/pm_tenant_matrix_smoke.php` trong regression thực chạy => 80 PASS / 0 FAIL cặp endpoint–ID (160 request), 11 control và 35 ca hành vi PASS; tenant khác/thiếu cùng status và thông báo chung, ba trường hợp task chấm công 403. 21 bảng thật hash trước/sau không đổi; fixture dọn sạch. Regression 56 script PASS. Xem TENANT_GROUP_A/B/C_VERIFY_20261008.md và bổ sung ngày 08/10 dưới đây. |
| 1.4 | **PASS** | `.tools/php83/php.exe tests/pm_aggregate_store_input_smoke.php` => exit 0, 36 PASS / 0 FAIL: cost HTML/polling và 5 report modes GET/XLSX POST với 3 store_id giả, so dữ liệu baseline/forged như nhau, 21 bảng thật không đổi. Chỉ chuẩn hóa created/modified metadata Excel; xem BATCH1_HTTP_VERIFY_20261009.md. |

### Nhóm 2

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 2.1 | **PASS** | 20 auth identity checks: cả username/email dùng chung mật khẩu, đổi mới nhận cả hai, cũ bị từ chối. G2 pm_auth_identity_smoke; đây là DAO với temporary mirror, không browser login. |
| 2.2 | **PASS** | Hash hiện đại và last_login được lưu sau đăng nhập MD5; password_verify trực tiếp; 25 checks, exact-schema mirror và bảng thật không đổi. Lệnh/output: [BATCH2_DATA_VERIFY_20261009.md](BATCH2_DATA_VERIFY_20261009.md). |
| 2.3 | **CHƯA CHẠY** | 18 authenticated routes/menu Admin/Employee PASS; PM/HR có 73 controller fixture cases, chưa kiểm tra chính xác đủ menu bốn role qua login thật. E0. |
| 2.4 | **PASS** | HTTP 18 + 73 cases gọi thẳng route và POST thiếu quyền bị từ chối, gồm PM foreign project và HR financial/import denial. E0. |
| 2.5 | **PASS** | Auth mirror status 0/2 bị từ chối; session guard khóa/ẩn cũng bị từ chối. G2. |
| 2.6 | **CHƯA CHẠY** | pm_session_role_http_smoke thực chạy: session ID đổi sau login; cookie HttpOnly, SameSite=Lax, Max-Age=43200 PASS. Chưa kiểm tra thời gian hết hạn thật/server-side expiration nên không nâng mục tổng. BATCH1_HTTP_VERIFY_20261009.md. |
| 2.7 | **PASS** | pm_session_role_http_smoke: cùng cookie từ login thật, pmusers GET khi DB fixture có ADMIN 200, gỡ role 403, phục hồi role 200; không thay session actor/cookie trong ba request. Roles thay ở exact-schema mirror, PmAccess đọc DB thật mỗi request; không tuyên bố UAT quản trị role. Exit 0, 22 bảng thật không đổi. |

### Nhóm 3

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 3.1 | **PASS** | Hai role + user rate 150: resolveRate chọn user 150. G3-extra exact-schema temporary mirrors. |
| 3.2 | **PASS** | Hai role rate 100/900, role chính 100: resolveRate chọn 100, không chọn max. G3-extra. |
| 3.3 | **PASS** | Không role chính, không user rate: rate_decimal=0.00, source=fallback, warning có giá trị. G3-extra. |
| 3.4 | **PASS** | Sau effective_to: fallback, không chọn rate đã hết hạn. G3-extra. |
| 3.5 | **PASS** | pm_rates_smoke: rate cũ 01–31/01, rate mới từ 15/01 đến NULL bị phát hiện overlap; từ 01/02 không overlap. G3. |
| 3.6 | **CHƯA CHẠY** | Chưa chạy hai request rate thật đồng thời. Temporary tables không chia sẻ giữa connections; chưa có shared fixture an toàn được duyệt. Không tạo bảng persistent hoặc ghi/xóa rate thật. SELECT FOR UPDATE/code review và allocation concurrency không chứng minh rate race; xem BATCH1_HTTP_VERIFY_20261009.md. |
| 3.7 | **PASS** | validateRateOwner từ chối cùng NULL/cùng có giá trị; nhận đúng một owner. G3 pm_rates_smoke. |
| 3.8 | **PASS** | CRUD mirror soft-delete status=2, không hiện danh sách mặc định và tenant boundary. G3 pm_users_crud_smoke. |
| 3.9 | **PASS** | HTTP duplicate email từ chối và native INSERT trùng trả 1062; cùng store. E0/G8 pm_import_native_duplicate. Native test có thể gây gap auto_increment. |
| 3.10 | **PASS** | 12 trường hợp tên/email/username/phòng ban/vai trò/tổ hợp; list/count đúng ID, 4 trang không trùng; bản ghi ẩn/khác tenant bị loại. Lệnh/output: [BATCH2_DATA_VERIFY_20261009.md](BATCH2_DATA_VERIFY_20261009.md). |

### Nhóm 4

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 4.1 | **PASS** | Service và HTTP PM sửa/xem foreign project bị từ chối. G1/G4 và E0. |
| 4.2 | **PASS** | Project service từ chối assignee không thuộc member active của dự án. G4 pm_projects_smoke. |
| 4.3 | **PASS** | Employee scope task assignment, permission/foreign project assertions trong G4 pm_projects_smoke. |
| 4.4 | **PASS** | D1 đã BUILD theo PLAN duyệt: task_status AJAX qua pm_ajax.php?op=pmtaskstatus, form POST fallback vẫn hoạt động. Service smoke PASS, 33 status HTTP case PASS, 81 role HTTP case PASS và pm_kanban_browser.js PASS: chuyển cột, CSRF, permission/ownership/tenant, audit/completed_at/no-op, pending/gửi đôi, lỗi/timeout, bàn phím, responsive và no-JS. Fixture local; không phải UAT production. Xem bằng chứng D1 cuối báo cáo. |
| 4.5 | **PASS** | Project/task soft delete và historical data giữ được trong costs/reports. G4/G6/G8. |

### Nhóm 5

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 5.1 | **PASS** | tests/pm_timesheets_smoke.php: PASS 5.1/5.2: first8 regular8/OT0, max12; temporary ledger và rollback. Xem P3_BUILD_VERIFY_20261009.md. |
| 5.2 | **PASS** | Cùng test: extra2 regular0/OT2 dưới trần12, assertions trực tiếp từng dòng; không cộng OT lần nữa. Xem P3_BUILD_VERIFY_20261009.md. |
| 5.3 | **PASS** | Sửa/move tính lại ngày; HTTP 4h thành 2h khiến dòng 9h thành 6 regular/3 OT, cost 1050. E0/G5. |
| 5.4 | **PASS** | Soft-delete recalculates dòng còn lại, HTTP 9h thành 8 regular/1 OT cost 950. E0/G5. |
| 5.5 | **PASS** | Cost và rate_snapshot giữ nguyên sau đổi đơn giá và lưu lại cùng ngày, so sánh chuỗi DECIMAL chính xác; fixture rollback. Lệnh/output: [BATCH2_DATA_VERIFY_20261009.md](BATCH2_DATA_VERIFY_20261009.md). |
| 5.6 | **PASS** | pm_timesheet_window_smoke: mốc ba ngày, khóa, Admin override + special audit; role/ownership boundaries. G5. |
| 5.7 | **PASS** | Create/update/soft-delete/recalculate có audit, actor và action được assert bằng fixture; fields nghiệp vụ không có password/token. E0/G5. PII/log toàn hệ thống xem 11.4. |

### Nhóm 6

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 6.1 | **PASS** | Stored DECIMAL cost SUM, OT/estimate và double-counting assertions trong pm_costs_smoke. G6. |
| 6.2 | **PASS** | Hidden task/project historical labels và stored actual giữ lại. G6, smoke_pm_costs. |
| 6.3 | **PASS** | Mixed currency trả cost=null/cảnh báo; chart hiện tại bị hủy/ẩn canvas. G6 và E6-browser. |
| 6.4 | **PASS** | valuation_date/ước tính hiện hành template + browser polling render đúng. G6, E6-browser. |
| 6.5 | **PASS** | Admin/PM gates, HR/Employee cost route/poll denial qua service và HTTP. G6/E0. |
| 6.6 | **PASS** | Suite hiện tại pm_phase9b_costs_browser: empty/mixed/zero/recovery, missing Chart/no-JS fallback. E6-browser. |
| 6.7 | **PASS** | Cost fixture nhiều role/membership, assertions tổng không nhân đôi. G6 pm_costs_smoke. |

### Nhóm 7

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 7.1 | **PASS** | Exact weekly limit không cảnh báo. G7 pm_allocations_smoke:87–89. |
| 7.2 | **PASS** | Vượt weekly limit 0.01 có cảnh báo tuần. G7. |
| 7.3 | **PASS** | Exact daily không cảnh báo, vượt daily có cảnh báo riêng; HTTP also daily/weekly. G7/E0. |
| 7.4 | **PASS** | `.tools/php83/php.exe tests/pm_task_estimate_smoke.php`: exit 0, actual 5h/estimate 4h đúng 1 task_estimate_warnings, allocation warnings=[]; DECIMAL 0/0.01/equal, nhiều allocation, lịch sử, tenant/PM và cleanup đạt. `tests/pm_task_estimate_http_smoke.php`: exit 0, 7 HTTP fixtures; Employee/HR/no-role không có tổng task. Chi tiết: docs/D2_VERIFY_20261008.md. |
| 7.5 | **PASS** | Half-open adjacent intervals không overlap. G7 pm_allocations_smoke. |
| 7.6 | **PASS** | Overlap một phần, symmetric cases và warnings. G7/E0. |
| 7.7 | **PASS** | Service suggestions/capacity scope và no-write semantics; render gợi ý/assignee không tự Apply. G7; browser cũ timeout ở panel đóng, không dùng kết quả đó làm PASS browser. |
| 7.8 | **PASS** | Tổng allocation trực tiếp, các project/membership fixtures không double-count usage. G7. |
| 7.9 | **PASS** | PM outside-project load có tổng nhưng không lộ tên/task/details ngoài scope. G7. |

### Nhóm 8

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 8.1 | **PASS** | Hours/report totals, own scope, XLSX round-trip/DECIMAL/role aggregates khớp fixture. G8. |
| 8.2 | **PASS** | Report fixture nhân dòng vượt 5000: xlsx bị từ chối, không silently truncate. G8 pm_reports_smoke; limit guards trong pmreports DAO. |
| 8.3 | **PASS** | XLSX round-trip đủ =, +, -, @; type string, literal và calculated value đúng, fixture rollback. Lệnh/output: [BATCH2_DATA_VERIFY_20261009.md](BATCH2_DATA_VERIFY_20261009.md). |
| 8.4 | **PASS** | Reconciled with approved Phase 8 design: Stage validates the entire file, reports row errors and rejects the whole file if any row is invalid. Existing pm_import_smoke execution covered rejection (Duplicate file staged assertion). Previous FAIL incorrectly assumed partial staging; no code changed or new test run in Step B. |
| 8.5 | **PASS** | Macro/formula/external link/DTD/ZIP-size/file-size/row-limit fixtures bị từ chối. G8 pm_import_smoke/http. |
| 8.6 | **PASS** | Apply mirror có injected crash/provenance/retry/UNIQUE gate và không duplicate; native email 1062 verified local. G8 pm_import_apply_smoke. Không dùng production. |
| 8.7 | **PASS** | Hai connection kiểm tra mutex/abandoned lock và apply concurrency gate; durable in_progress/retry. G8 pm_import_apply_smoke. |
| 8.8 | **PASS** | HR/Employee own-hours scope và cost export denial, HTTP fixture exports; không mở financial route. G8/E0. |

### Nhóm 9

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 9.1 | **PASS** | 20 users, 2–3 roles, compare exact roles/primary, old 20 queries thành 1, multiple-primary fail closed. G11 pm_roles_batch_smoke. |
| 9.2 | **CHƯA CHẠY** | PASS 13 fixtures 360/390/768/1440 + text scaling 200%; chưa chạy browser zoom thật 200%. E9-ui. Không đánh đồng scaling và zoom. |
| 9.3 | **CHƯA CHẠY** | Skip/tab/modal/collapse/tablist/focus đã chạy trên fixture; chưa hoàn tất keyboard-only mọi hành động toàn ứng dụng. E9-ui/G10. |
| 9.4 | **PASS** | Probe valid polling responses: Chi phí và Phân bổ dừng 120s khi hidden, tiếp tục sau visible. E9-visibility, đã chờ response chứ không chỉ kiểm tra đồng hồ. |
| 9.5 | **CHƯA CHẠY** | Có kiểm tra Chart/chi phí dưới CSP header thật; chưa thao tác mọi form/module. Bằng chứng và giới hạn: BATCH3_UI_PARTIAL_VERIFY_20261009.md. |

### Nhóm 10

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 10.1 | **PASS** | Computed normal #18181b, danger #dc2626 qua 4 loại action và default/hover/active/focus/disabled. G10-buttons. |
| 10.2 | **PASS** | Danger computed chữ #ffffff, nền #dc2626, contrast 4.83:1; normal 16.97:1. G10-buttons. |
| 10.3 | **CHƯA CHẠY** | Computed-color scan 37 fixture: bad=[]; chưa bao phủ mọi cảnh báo động/state/pseudo-element. BATCH3_UI_PARTIAL_VERIFY_20261009.md. |
| 10.4 | **PASS** | Lock/deactivate danger; unlock/restore secondary không danger, confirmation existing được giữ. G10-buttons. |
| 10.5 | **PASS** | Một theme chung css/pmui.css; computed stylesheet list còn Bootstrap và pmcosts.css legacy. Đây là 1 theme chung, không phải 1 stylesheet tổng cộng; không tạo theme thứ hai. E9-visibility + template review. |
| 10.6 | **PASS** | Existing G10-allocation browser evidence: 8 headers, exact totals/overbooking, same-day rows and year rollover. User approved light warning background with border #d97706; solid fill is not required and reduces number readability. No code change or new test run in Step B. |
| 10.7 | **PASS** | Grid card/native progress empty/0/50/100/restricted/task-delete aggregation + contrast badges. G4 and G10-projects. |

### Nhóm 11

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 11.1 | **PASS** | Runner pm_regression.ps1 exit 0: 47 scripts PASS, 73 HTTP và 101 EXPLAIN. Đây chỉ là PASS lệnh; không đồng nghĩa checklist tổng PASS, đặc biệt test audit gây lỗi fixture 11.5. |
| 11.2 | **PASS** | PHP 8.3 lint 103 file .php đổi từ 3df3349..HEAD: không syntax error; log G11-lint. Không chỉ lint bốn runtime mới. |
| 11.3 | **PASS** | Tracked inventory + scoped diff không có config thật/.env/dump/cache/upload trong phần chuẩn bị commit; chỉ docs mới. includes/config.inc.php.example và templates_c/.gitkeep là placeholders. Không quét lịch sử secret toàn repository. |
| 11.4 | **PASS** | Phạm vi writer PM khi PM_HIDE_LEGACY=true, lịch sử giữ nguyên; actor ID-only, không identity/contact/credential/IP mới trong metadata. Assertions riêng từng writer ở pm_tracking_identity_smoke, pm_role_http_smoke (156 case), pm_projects_smoke, pm_timesheets_smoke, pm_timesheet_window_smoke, pm_allocations_smoke, pm_import_smoke/apply, pm_account_audit_smoke. Legacy count 4 HTTP404. Regression55 exit0. Scope và giới hạn: docs/P4_VERIFY_20261008.md; không test render legacy hỗn hợp theo duyệt. |
| 11.5 | **PASS** | Lỗi fixture cũ đã xử lý ở A: người dùng xác nhận department_id gốc NULL, đã khôi phục. BƯỚC 1 chạy lại `.tools/php83/php.exe tests/pm_audit_smoke.php` exit 0, output exact-schema temporary users / persistent user rows and engine unchanged. Test đối chiếu toàn bộ user thật qua connection độc lập và engine MyISAM; checksum helper trước/sau đều EXTENDED=3533612692, high-id fixture=0, missing-department=0. Lịch sử lỗi và khôi phục giữ ở phần A; không dùng checksum hiện tại suy ra giá trị gốc. |

## Thứ tự sửa / kiểm tra lại — lịch sử đề xuất trước A/B/C/D1

1. Phase 5/9/10: cô lập pm_audit_smoke bằng temporary dc_users đúng schema; guard toàn runner tránh MyISAM rollback giả. Xác định bản gốc hai department_id trước sửa local; không tự gán NULL, không đổi engine. Lượt này chưa phục hồi dữ liệu.
2. Phase 3: validator chung đúng cột thật, create/edit/profile/import; chặn control characters/overlength và collision username–email chéo; test schema thật và boundary. Không coi chuỗi tên trong ảnh tự thân là lỗi.
3. Phase 7: cảnh báo actual task > estimated_hours riêng, đúng quyền và dữ liệu lịch sử; không trộn weekly/daily allocation overload.
4. Phase 4: chốt có bổ sung AJAX Kanban hay giữ form POST; nếu bổ sung giữ CSRF/ownership/audit và no-JS fallback.
5. Phase 8: quyết định lại import partial-valid semantics. Hiện quy tắc cũ all-file validate/stage; không tự thay bằng partial import từ checklist mà bỏ crash/UNIQUE/mutex safeguards.
6. Phase 3/5/9: chốt trường audit identity được phép lưu; raw username hiện vi phạm checklist 11.4 literal. Không tự xóa lịch sử tracking/legacy.
7. Sửa ba browser tests lỗi thời; bổ sung các CHƯA CHẠY, đặc biệt full tenant matrix, no-role HTTP, rate concurrency, session/role refresh, exact 8h/8+2, cost snapshot assertion, four formula prefixes, CSP headers và real zoom/keyboard.

Không merge nhánh trong lượt này. Không dùng số PASS scripts thay chứng nhận sản phẩm/fixture sạch. Chỉ xét UAT sau xử lý FAIL và hoàn tất/ghi nhận giới hạn CHƯA CHẠY với người dùng. Gói ZIP UI đã giao vẫn ở b8e0774; không tự đóng gói/deploy lại từ báo cáo này.

## Bước A — Tra cứu khôi phục, đang chờ xác nhận (07/10/2026)

Theo chỉ định mới, ưu tiên audit/tracking, rồi seed/fixture Git, rồi hỏi người dùng; không suy đoán giá trị gốc. Hai target local ID 93/110 không có user audit history/department snapshot. Read-only toàn bảng: tracking chứa department_id=0, audit snapshots chứa department_id=0. Lệnh `.tools/php83/php.exe .local/recovery_audit_read.php` xác nhận; không in dữ liệu cá nhân.

`python .local/recovery_seed_history.py` kiểm tra 6 revision liên quan database/seeds, docs/fixtures, tests/pm_phase10_fixture.php: không có định nghĩa gốc cho ID 93/110. Fixture Phase 10 chỉ tạo ID 900000001..900000004/900000010 trên schema riêng. Đã đọc backup trước khi nhận thứ tự ưu tiên chi tiết; không dùng backup để sửa ngoài quy trình người dùng vừa chỉ định.

Đã hỏi người dùng hai tài khoản có phải fixture/test được phép lập phương án tạo lại, hoặc dữ liệu cần giữ để tìm phòng ban gốc. Chưa sửa/xóa user, chưa sửa test audit và chưa chuyển Bước B. Các mục 8.4/10.6 sẽ cập nhật theo quyết định đã duyệt ở Bước B sau khi A hoàn tất; PII chờ Bước C. Không merge/push/deploy, không rerun runner audit chưa cô lập.

Xác nhận người dùng: ID 93/110 là **dữ liệu cần giữ — chờ xác định phòng ban gốc**. Không được lập phương án xóa/tạo lại; Bước A chưa hoàn tất. Tiếp tục giữ nguyên dữ liệu hiện tại tới khi có nguồn gốc được xác nhận. Không chuyển Bước B–E hoặc build gap trong lúc A còn chờ.

### Tiếp tục Bước A — nguồn binlog local (07/10/2026)

Đã kiểm tra chỉ đọc: local MySQL log_bin=ON, binlog_format=ROW, binlog_row_image=FULL. SHOW BINARY LOGS bị từ chối mã 1227; đọc thư mục Data local cũng bị Windows từ chối. Không tự cấp privilege hoặc thay ACL, không sửa database. Có khả năng tìm before-image bằng binlog nhưng chưa đọc được, nên không coi đây là bằng chứng đã khôi phục.

Hai backup 02/10 11:18 và 11:33 lưu department_id=NULL của cả hai target; backup về sau có các department fixture khác nhau. Đây chỉ là bằng chứng lịch sử, chưa chứng minh giá trị đúng ngay trước lượt test. Không tự áp dụng NULL. Đã hỏi người dùng chọn nguồn đối chiếu (chuẩn bị binlog local hoặc cung cấp/xác nhận phòng ban gốc). Giữ nguyên Bước A chờ nguồn được xác nhận; chưa sửa test, chưa chuyển B–E.

## Step A complete - 2026-10-07

User confirmed original department_id=NULL for IDs 93 and 110. Executed `.tools/php83/php.exe .local/restore_confirmed_departments.php`: PASS, only those fields restored; all other user rows/fields unchanged; MyISAM retained. No accounts deleted or recreated. Earlier pending notes above are historical.

`tests/pm_audit_smoke.php` now uses SHOW CREATE TABLE exact-schema temporary users. HR self-join uses a synchronized second temporary viewer mirror to avoid MySQL temporary-table reopen limitation; only test connection rewrites the viewer alias. No production DAO/runtime change. Independent connection in finally compares all persistent user rows and engine before/after.

Executed `.tools/php83/php.exe -l tests/pm_audit_smoke.php`: PASS. Executed `.tools/php83/php.exe tests/pm_audit_smoke.php` twice: PASS audit role/tenant/project boundaries, financial redaction, filter validation, rollback, persistent rows/engine unchanged. Initial mirror development failed Can't reopen table owner; corrected harness before both successful reruns. Executed `.tools/php83/php.exe .local/checksum_diagnostic.php`: EXTENDED checksum=129141672 unchanged after both runs; high-id fixture rows=0; missing-department references=0.

11.5 audit fixture defect is fixed with targeted evidence. Final related-case reconciliation remains Step E; no claim that all other tests were rerun. No merge/push/deploy.

## Step B complete - 2026-10-07

8.4 corrected FAIL to PASS by reconciliation with original approved all-file Stage validation (not partial staging). 10.6 remains PASS; removed solid-fill caveat according to explicit approval of light background plus #d97706 border. Existing executed evidence retained, no new execution claimed. Total now 57 PASS / 4 FAIL / 19 NOT RUN; targeted 11.5 reconciliation deferred to Step E. No runtime changes. Step C PII policy still pending at this point in history.

## Bước C — Chính sách chốt và sửa tracking mới (07/10/2026)

Người dùng chọn nhật ký mới chỉ lưu ID tài khoản. Giữ toàn bộ lịch sử; không
UPDATE/xóa log cũ, không migration. Trường username của tracking legacy ghi
chuỗi ID số cho bản ghi PM mới, không đổi tên cột. Legacy ngoài PM giữ hành vi cũ.

- Sửa login (success/lock), logout, pmusers và pmprojects. Tracking PM mới không
  ghi username/email/IP thô. Login thất bại: login_times mới ghi uid và last_ip
  rỗng; UPDATE counter cũ không chạm last_ip lịch sử. Không đổi quy tắc xác thực.
- `.tools/php83/php.exe tests/pm_tracking_identity_smoke.php`: **PASS 10 case**
  chạy module login/logout bằng recording collaborators, không truy cập DB.
  Bao gồm PM/legacy, success/lock/logout/failure-new/failure-existing. Không phải
  bằng chứng HTTP đăng nhập hay UAT tài khoản thật.
- `.tools/php83/php.exe tests/pm_role_http_smoke.php`: **PASS 73 HTTP case**;
  assert tracking mới projects/users có ID actor và IP rỗng. Persistent state
  và user engine không đổi.
- `.tools/php83/php.exe tests/pm_auth_identity_smoke.php`: **PASS 20 case**.
  `.tools/php83/php.exe tests/pm_session_guard_smoke.php`: **PASS**.
- PHP `-l` cả 7 file PHP sửa/mới: **PASS**; `git diff --check`: **PASS**.
- Regression đầu **FAIL** tại pm_import_apply_smoke:
  `Connection close did not release lock`. Test này chạy riêng **PASS**.
  Sau khi hoàn tất login-failure code/test, chạy lại
  `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
  **PASS 48 script**. Không xác định chắc nguyên nhân lỗi lock ban đầu; không
  xóa kết quả FAIL hoặc tự kết luận không còn tính không ổn định. Không sửa import.
- `.tools/php83/php.exe .local/checksum_diagnostic.php`: dc_users EXTENDED=129141672,
  high-ID fixture=0, department thiếu=0, tracking lịch sử non-empty=5332 giữ nguyên.

Review code/security: ID chỉ lấy từ server; không đổi CSRF/permission/ownership;
không thấy lỗi mới trong diff. Chưa rà đầy đủ mọi payload audit/log, nên 11.4
chưa PASS toàn phạm vi, chờ đối chiếu ở E. Giữ 19 CHƯA CHẠY. Không merge/push/deploy.
D1 mới có PLAN, chưa BUILD ở thời điểm Bước C.

## Bước D1 — Kanban AJAX hoàn tất local (07/10/2026)

Người dùng duyệt docs/PLAN_D1_KANBAN_AJAX.md rồi BUILD riêng D1. Endpoint
`POST pm_ajax.php?op=pmtaskstatus` được allowlist; endpoints chi phí/phân bổ
vẫn GET-only. Kiểm tra scalar/method/session/user active cùng tenant/CSRF
trước ghi. Module writer có guard chống truy cập trực tiếp hoặc include từ
entry khác chưa xác thực. Client không quyết định actor_id/store_id.

Service khóa project/task, dùng ownership/permission hiện có; cập nhật riêng
status/completed_at, audit cùng transaction. No-op không tạo audit mới hoặc
đổi thời điểm hoàn thành. Form POST fallback giữ nguyên; full-edit/ẩn task giữ
nhãn và confirmation. AJAX không tự gửi lại khi kết quả chưa xác nhận, timeout
20 giây trả quyền thao tác và hướng dẫn tải lại. Text lỗi dùng textContent.

### Lệnh đã chạy và kết quả

- `.tools/php83/php.exe tests/pm_projects_smoke.php`: **PASS**; thêm assert trường
  khác giữ nguyên, audit old/new, no-op, completion/reopen, deny và rollback khi
  audit lỗi. Fixture outer transaction/savepoint; không commit dữ liệu thật.
- `.tools/php83/php.exe tests/pm_task_status_http_smoke.php`: **PASS 33 HTTP case**;
  Admin/PM success, no-role/HR/Employee/inactive/session/tenant/method/CSRF/scalar,
  malformed ID, task/project ẩn hoặc sai scope, client actor/store ignored,
  no-op/completion và direct-module guard. Exact-schema users temporary MyISAM;
  persistent users/projects/tasks/audit/tracking và engine không đổi.
- `.tools/php83/php.exe tests/pm_role_http_smoke.php`: **PASS 81 HTTP case**;
  bổ sung status POST success, field preservation, CSRF/ID/ownership/role denial,
  audit và ID-only tracking. Lượt đầu harness mong HR 200 nhưng controller
  đúng ra trả 403; sửa expected code, không đổi quyền ứng dụng.
- `.tools/php83/php.exe tests/smoke_pm_projects.php --preview`: **PASS**, tạo
  preview ignored .local bằng Smarty. Không phải UAT production.
- `playwright-cli -s=kanban run-code --filename=../tests/pm_kanban_browser.js`
  (cwd .local): **PASS** success/pending/duplicate, full-edit status sync, focus
  và live announcement; network/401/500/invalid response/timeout; 360/390/768/1440,
  text scaling 200%, read-only, escaping và native POST payload khi tắt JS.
  Browser dùng synthetic preview và response intercept; backend kiểm tra HTTP
  riêng ở trên. Không chứng nhận browser-to-DB persistence E2E hoặc UAT thật.
  Lượt đầu harness chờ script visible nên timeout; sửa sang attached rồi PASS.
- PHP `-l` 9 file PHP sửa/mới: **PASS**; các file guard sửa sau đó lint lại PASS.
  `git diff --check`: **PASS**.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
  **PASS 49 script**, lần chạy cuối gồm 33 status HTTP và 81 role HTTP. Không
  migration do runner. Không dùng số script PASS để suy 80 mục đều đạt.
- `.tools/php83/php.exe .local/checksum_diagnostic.php`: user EXTENDED=129141672,
  fixture high-ID=0, missing department=0, tracking lịch sử non-empty=5332.
- Đã xem ảnh .local/kanban-desktop.png và kanban-mobile.png. Impeccable detect
  báo flat hierarchy nhưng không resolve được linked CSS từ đường dẫn template;
  detector có giới hạn, không coi cảnh báo đó là computed style thực tế. Giữ
  theme hiện có, không thêm CSS hoặc thay nền/font trong D1.

Review code/security: prepared queries, lock và audit cùng transaction, không
ghi user thật, service fail closed theo tenant/ownership, JSON lỗi không lộ
exception, module chỉ chạy qua entry đã kiểm tra CSRF. Không thấy defect mới
trong diff đã chốt. Mục 4.4 PASS; tổng 58 PASS / 3 FAIL / 19 CHƯA CHẠY. D2 chưa
BUILD; 11.4/11.5 chờ đối chiếu E. Không merge/push/deploy.

## Điều chỉnh điều hướng sau Lưu/Ẩn dự án — 07/10/2026

Người dùng yêu cầu thành công thì về danh sách Dự án & công việc. Controller
pmprojects trả 303 Location `admin.php?op=pmprojects` sau project_save hoặc
project_delete thành công. Flash notice chỉ nhận mã saved/hidden do server ghi,
được consume một lần trên GET danh sách. Lỗi không redirect, quyền/CSRF/service
và confirmation ẩn không đổi. Không sửa schema/DAO/template/CSS, không migration.

- `.tools/php83/php.exe tests/pm_role_http_smoke.php`: **PASS 90 HTTP case**;
  kiểm tra create/edit/hide 303 đúng Location, state ghi trong request trước khi
  exit, GET landing danh sách có notice đúng và GET tiếp không lặp notice.
  Validation và CSRF sai giữ 200 với lỗi, không ghi project/tracking hoặc redirect.
  Fixture router quan sát qua shutdown để redirect exit không làm mất bằng chứng;
  HTTP client không tự follow redirect khi kiểm tra response POST.
- `.tools/php83/php.exe -l` trên modules/admin/pmprojects.module.php,
  tests/pm_role_http_router.php, tests/pm_role_http_smoke.php: **PASS**.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
  **PASS 49 script**, gồm 90 role HTTP case và 33 task status HTTP case.
- `git diff --check`: **PASS**. Role HTTP finally xác nhận persistent user/business
  state và engine không đổi. Các GET dùng fixture request mới; đây là bằng chứng
  redirect/flash, không phải full browser-to-DB persistence E2E hay UAT.

Review không thấy defect mới trong diff: đích redirect cố định, notice server-only,
service/tracking thực thi trước redirect, CSRF/ownership và legacy functionality
giữ nguyên. Tổng 80 mục không thay trạng thái; giữ 19 CHƯA CHẠY. Không merge/push/deploy.

## Ràng buộc ngày dự án/task — 07/10/2026

Theo quyết định người dùng: dự án nhập cả hai ngày phải end_date > start_date.
Ngày trùng hoặc đảo ngược bị từ chối trước ghi; controller dùng exception riêng
để trả 303 về danh sách, GET hiện lỗi cố định một lần. Task vẫn cho phép start_date
= due_date; không đổi rule task. Không migration hoặc sửa dữ liệu cũ hàng loạt.

- `.tools/php83/php.exe tests/pm_projects_smoke.php`: **PASS**, create/edit dự án
  02/10 → 02/10 và 02/10 → 01/10 bị từ chối, hàng cũ và số project không đổi;
  02/10 → 03/10 được tạo/sửa; task cùng ngày được tạo/sửa. Outer rollback fixture.
- `.tools/php83/php.exe tests/pm_role_http_smoke.php`: **PASS 103 HTTP case**;
  project create/edit same-day/reversed → 303 đúng danh sách, error GET một lần,
  không project/tracking write; task same-day POST → saved/audit. Persistent
  state/engine trước-sau của suite giữ nguyên. Không browser persistence E2E/UAT.
- PHP `-l` classes/services/pmprojectservice.class.php,
  modules/admin/pmprojects.module.php, tests/pm_projects_smoke.php,
  tests/pm_role_http_smoke.php: **PASS**; `git diff --check`: **PASS**.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
  **PASS 49 script**, gồm 103 role HTTP. Bổ sung boundary ngày kế tiếp sau đó,
  chạy lại service smoke và role HTTP **PASS**. Không đổi runtime sau regression.

Review không thấy defect mới trong diff: service check server-side trước ghi,
exception riêng không đổi luồng lỗi task, flash server-only/escaped và redirect
cố định, CSRF/permission/ownership không thay. PLAN_FINAL ghi quyết định mới;
80 mục không tự nâng trạng thái, giữ 19 CHƯA CHẠY. Không merge/push/deploy.

## Bổ sung 07/10/2026 — Thêm nhân sự không OTP

Phạm vi: tạo mới qua pmusers, không đổi phòng ban, sửa nhân sự cũ hay vòng đời tài khoản.
Email chỉ kiểm tra định dạng/độ dài, không xác nhận hộp thư tồn tại. Điện thoại tùy chọn,
nếu nhập chỉ chấp nhận 1–10 chữ số; không kiểm tra nhà mạng/thuê bao đang hoạt động.

- `.tools/php83/php.exe tests/pm_user_input_smoke.php` → `PASS: 13 create input boundaries`.
- `.tools/php83/php.exe tests/pm_role_http_smoke.php` → `PASS: 116 PM/HR HTTP controller cases`;
  tạo hợp lệ, mật khẩu rỗng/ngắn/chỉ khoảng trắng, email sai, điện thoại chữ/dài/space/newline,
  CSRF và array rejection. Các lỗi không ghi nhân sự/tracking; bảng thật và MyISAM giữ nguyên.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1` →
  `PASS: 50 regression scripts. No migrations applied by this runner.`
- `.tools/php83/php.exe tests/smoke_pm_users.php --preview`, rồi từ `.local`
  `playwright-cli -s=personnel run-code --filename=../tests/pm_user_input_browser.js` →
  PASS password required/minlength, phone digits/optional/10-digit boundary, 1440/390 không overflow.
  Template render hiện hành, dữ liệu giả; không submit hoặc chứng nhận UAT.

Lượt đầu FAIL do pattern có ngoặc Smarty; sửa bằng ldelim/rdelim. HTTP test phát hiện
Request trim bỏ newline: chuyển kiểm tra điện thoại sang giá trị POST gốc. Array trả
400 từ middleware đúng hành vi, sửa expected của test. Một lượt regression trùng cổng
18770 với suite HTTP chạy đồng thời nên FAIL; chạy tuần tự cuối cùng PASS 50 script.
Browser ban đầu trỏ preview cũ thiếu form nên timeout; đổi sang phase9-users-two.html
vừa render rồi PASS. Console chỉ ghi favicon 404 của preview; không lỗi runtime form.
Review: validation trước INSERT; giữ CSRF, permission, tenant, hash và SQL prepared.
Giữ trạng thái 80 mục/19 CHƯA CHẠY; không dùng các test này suy ra email tồn tại.

## Bổ sung 07/10/2026 — Lỗi mật khẩu/điện thoại dưới từng ô

- `.tools/php83/php.exe tests/pm_user_input_smoke.php` → PASS 21 case, bao gồm
  thông báo đúng theo mật khẩu rỗng/ngắn/chỉ khoảng trắng, 4/8 ký tự Unicode,
  điện thoại chữ/quá 10 số và hai lỗi cùng lúc.
- `.tools/php83/php.exe tests/pm_role_http_smoke.php` → PASS 119 HTTP case;
  lỗi dưới ô render từ server, phần thêm tự mở, không echo mật khẩu,
  không INSERT/tracking khi lỗi, persistent state/engine không đổi.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`
  → `PASS: 50 regression scripts. No migrations applied by this runner.`
- `.tools/php83/php.exe tests/smoke_pm_users.php --preview`, từ `.local` chạy
  `playwright-cli -s=personnel run-code --filename=../tests/pm_user_input_browser.js`
  → PASS exact inline errors, correction clears errors, invalid submit blocked,
  optional phone, desktop/mobile overflow. Fake-data rendered fixture, no POST.
- PHP lint service/controller PASS; `node --check js/pmui.js` và
  `node --check tests/pm_user_input_browser.js` PASS; `git diff --check` PASS.

Review: lỗi escaped trên server, JS chỉ textContent, aria liên kết ô và lỗi,
không in lại mật khẩu, CSRF/quyền/hash/tenant giữ nguyên. Không mở rộng kết quả
thành UAT hay xác minh email; giữ 19 CHƯA CHẠY và các gap D2/E hiện có.

## Bổ sung 07/10/2026 — Chi phí thực tế thiếu đơn giá

Scope: chỉ cảnh báo completeness, không tính lại tiền/snapshot lịch sử, không chặn
ghi công hoặc đổi quy tắc OT. rate_source=fallback hoặc rate_warning không rỗng
được đếm là thiếu đơn giá; cost=0 với user/role rate hợp lệ không bị gắn nhãn thiếu.

- `.tools/php83/php.exe tests/pm_costs_smoke.php` → PASS groups 1–4, bổ sung:
  partial SUM(cost) giữ nguyên; toàn bộ thiếu rate trả 0.00 + incomplete; nhóm
  project/task/user/department/role/date cùng đếm; range sạch và lifetime thiếu
  tách riêng; currency guard đồng thời với missing-rate; deleted row bỏ qua;
  rate hiện tại không sửa history; dashboard không UPDATE snapshots;
  PM không nhìn cảnh báo dự án ngoài quyền. Fixtures rollback.
- `.tools/php83/php.exe tests/smoke_pm_costs.php --preview` → PASS group 5,
  5 vị trí nhãn server và tiền 0.00 giữ nguyên; ignored .local preview dùng dữ liệu giả.
- `.tools/php83/php.exe tests/pm_costs_http_smoke.php` → PASS group 3 HTTP,
  polling metadata count int/complete bool; role/tenant/IDOR/filter/method/no-store.
- PHP lint DAO/service PASS; `node --check js/pmcosts.js` và
  `node --check tests/pm_costs_missing_rate_browser.js` PASS.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`
  → `PASS: 50 regression scripts. No migrations applied by this runner.`
  Bao gồm 119 role HTTP và 101 prepared query shapes EXPLAIN.
- Từ `.local`, `playwright-cli -s=personnel run-code --filename=../tests/pm_costs_missing_rate_browser.js`
  → PASS saved-zero label initial/polling/no-JS, summary/project/task/group,
  incomplete charts hidden, configured-zero charts retained, transitions, 1440/390 overflow.
- `playwright-cli -s=personnel run-code --filename=../tests/pm_phase9b_costs_browser.js`
  → PASS Chart.js empty/mixed/zero/recovery, decimal strings, tab keyboard/state,
  polling errors/auth, responsive/text scaling/CSP, no-JS/missing-chart fallbacks.

Review: scope and currency guards unchanged, no per-row query, prepared parameters,
escaped partial/textContent and no raw rate-warning content leak. No schema writes
or persistent data repair. Tests above do not complete separate snapshot-concurrency
case 5.5, D2 task-hour warning or E reconciliation; 19 CHƯA CHẠY remain unchanged.
No merge/push/deploy; OT/daily-hard-limit question remains open.

## BƯỚC 1 — Đối chiếu E cho 4.4 và 11.5 (07/10/2026)

4.4 giữ PASS: đã chạy lại `.tools/php83/php.exe tests/pm_task_status_http_smoke.php`, exit 0, output `PASS: 33 task-status HTTP cases, ADMIN/PM, CSRF/method/scalar/role/tenant/ownership/hidden guards, audit/no-op/completion; persistent users/projects/tasks/audit/tracking unchanged.` Bằng chứng browser thực chạy ở phần D1 được giữ: `playwright-cli -s=kanban run-code --filename=../tests/pm_kanban_browser.js` PASS với preview/intercept và no-JS POST payload. Lượt đối chiếu này không chạy lại browser; không gọi đó là browser-to-DB E2E.

11.5 đổi FAIL sang PASS: chạy lần lượt `.tools/php83/php.exe .local/checksum_diagnostic.php`, `.tools/php83/php.exe tests/pm_audit_smoke.php`, rồi checksum helper lần nữa; cả ba exit 0. Audit output `PASS: audit role/tenant/project boundaries, financial redaction, filter validation and fixture rollback; exact-schema temporary users, persistent user rows/engine unchanged.` Helper trước/sau: EXTENDED checksum=3533612692; high-id fixture rows=0; missing-department references=0; tracking raw username count=5341 không đổi. Test còn assert toàn bộ user thật và engine qua connection độc lập. Khôi phục gốc NULL đã có bằng chứng ở A; không suy từ checksum.

Tổng hiện hành **59 PASS / 2 FAIL / 19 CHƯA CHẠY = 80**. Hai mục chưa PASS: 7.4 (D2 chưa BUILD), 11.4 (chưa audit toàn phạm vi log). Không nâng các mục khác, không chạy lại toàn bộ checklist. Đây là đối chiếu E giới hạn đúng hai mục được yêu cầu; không tuyên bố E toàn phạm vi hay dự án hoàn tất. Các con số cũ phía trên là lịch sử.

BƯỚC 0 đã báo trước khi chạy test: reflog main fast-forward 07/10/2026 15:37:45 +0700, origin/main update by push 15:37:58, đích 3253dbaff295e0e9517808ba15f649c2da56b261, theo lệnh người dùng “push vô main để hiện chấm xanh”. Không push/merge mới trong lượt này.

## BƯỚC 2 — BUILD và VERIFY local (07/10/2026)

PLAN được người dùng duyệt, bổ sung bắt buộc quét read-only dữ liệu hiện có. Validator chung giới hạn username 30/fullname 50/email 50/tel 15, kiểm tra UTF-8/control; tạo nhân sự vẫn điện thoại tùy chọn chỉ số tối đa 10. Import giữ HEADERS 6 cột và tel rỗng; Preview/Stage báo lỗi theo dòng, từ chối cả file sai. Apply revalidate batch cũ, INSERT từng dòng và bắt native 1062 cho email duplicate/recovery. Collision khác bị chặn trước INSERT; không đổi engine, DDL persistent hoặc dữ liệu cũ.

Nhân sự tạo/sửa, PM Hồ sơ, Apply và cấp mật khẩu import dùng cùng namespace named lock `pmidentity:` + hash(database:store_id). Hai tenant khác nhau dùng tên khác nhau. Import lấy batch lock trước identity lock, nhả identity trước batch bằng finally; các đường khác chỉ lấy identity lock. Các đường legacy/SQL ngoài PM không tham gia lock, không tuyên bố bảo vệ chúng.

### Bằng chứng đã chạy

- `.tools/php83/php.exe .local/step2_sql_mode_read.php` (BƯỚC 2 PLAN): exit 0, SESSION và GLOBAL `ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`; giới hạn cột 30/50/50/15, identity collation utf8mb4_unicode_ci. Không kiểm tra production.
- `.tools/php83/php.exe tests/pm_user_identity_smoke.php`: exit 0, **43 case PASS**: boundary, Unicode/control/invalid UTF-8, same-user exclusion và own email=username, same/cross identity, DB case/accent/padding, tenant khác, user khóa/ẩn, hai connection lock timeout/release và tenant khác vẫn ghi được vào mirror. Positive fixture scan assert chính xác một cặp và hai cờ username_email/email_username. Exact-schema MyISAM temporary mirrors; user thật và engine được đối chiếu không đổi.
- `.tools/php83/php.exe tests/pm_import_smoke.php`: exit 0, Preview/Stage boundary 30/50/50, lỗi dòng 3, file có dòng tốt+xấu không stage phần nào; collision case trong file, cả hai chiều chéo có thông báo dòng, collision với legacy username dạng email theo accent/case DB. Các kiểm tra file/security/staging rollback cũ vẫn PASS. Fixture users là schema/index thật trong temporary mirrors, user thật/engine không đổi. Chạy lại sau bổ sung assertions cross-file, PASS.
- `.tools/php83/php.exe tests/pm_import_apply_smoke.php`: exit 0, Apply boundary và batch cũ vượt username/fullname/email/tel failed `invalid_user_fields` không thêm user; username/cross collision failed trước ghi; duplicate email 1062/provenance recovery/row retry vẫn PASS. Tenant identity lock chặn Apply/provision bằng connection thứ hai, batch lock không rò sau timeout, Apply thành công sau nhả lock. Không đổi core user rows/engine qua connection độc lập.
- `.tools/php83/php.exe tests/pm_role_http_smoke.php`: exit 0, **139 HTTP case PASS**; thêm create/edit/profile boundary và collision, CSRF/no-write/no false saved notice, lỗi identity lock timeout rõ ở nhân sự/Hồ sơ và không ghi. Profile giữ email tùy chọn. Lượt đầu harness thiếu shutdown observation cho route profile, fail `Missing profile write state`; đã sửa guard observer test-only rồi chạy đạt. Không phải lỗi sản phẩm, không browser/password-login UAT.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`: exit 0, **51 script PASS**, gồm 43 identity case, 139 role HTTP case, 33 task status HTTP case và 101 EXPLAIN shapes. Không apply migration. Lần cuối gồm assertions import cross-file và timeout HTTP đã bổ sung.
- PHP `-l` cho 14 file PHP sửa/mới: PASS. Diff whitespace check PASS trước ghi tài liệu, kiểm tra lại trước commit.

Một lượt regression sau bổ sung timeout HTTP thất bại tại pm_role_http_smoke: `file_get_contents` connection timeout, `HR/op=pmusers expected 200 got none`, ở request invalid create sau hai lock-timeout cases. Không bỏ qua hoặc tự retry request trong test. Chạy lại suite HTTP riêng đạt 139 case; chưa xác định nguyên nhân timeout từ bằng chứng hiện có, không quy lỗi runtime hoặc môi trường bằng suy đoán. Lượt regression đầy đủ chạy lại được ghi kết quả cuối riêng ở trên; giữ lịch sử thất bại này.

### Quét read-only toàn bộ user hiện có — bổ sung bắt buộc

Lệnh `.tools/php83/php.exe tests/pm_identity_collision_audit.php`, exit 0; chạy lại sau regression:

`READ_ONLY identity collision scan: users=10 pairs=0`

`PASS: scan executed for all stores/statuses using DB collation and same-store pairs; user rows unchanged. Finding count is reported separately, not a clean-data assertion.`

Quét không lọc trạng thái, cùng store_id và khác ID, cả username=username, email=email, username=email, email=username bằng phép so cột MySQL. Nếu có kết quả script xuất store_id/user_a/user_b/cờ loại collision, không xuất email/username hoặc sửa/khóa. **Danh sách cặp collision local hiện có: rỗng (0 cặp trên 10 user)**. Đây chỉ là kết quả local tại thời điểm chạy, không suy ra production.

Review bảo mật: input limits trước ghi; prepared parameters; tên collation lấy metadata và whitelist identifier; permission/CSRF/tenant giữ nguyên; collision không bỏ user khóa/ẩn; credential không in hoặc đưa vào audit; batch/identity lock thứ tự thống nhất và nhả khi lỗi; fixture không ghi bảng users thật. Không phát hiện defect còn mở trong phạm vi BƯỚC 2 đã review. Giữ **59 PASS / 2 FAIL / 19 CHƯA CHẠY**; không tự nâng checklist từ regression. Không sửa OT, Audit Log UI hay D2; dừng trước BƯỚC 3. Không push/merge/deploy mới.

## P0/P1 — Điều tra flaky và đối chiếu tài liệu (07/10/2026)

Đã thực chạy Apply smoke 10 lần liên tiếp rồi Role HTTP smoke 10 lần liên tiếp: mỗi suite 10/10 PASS. Probe close/acquire 300 lần tái hiện 8 lần GET_LOCK(timeout=0) thất bại tức thời sau close phía client, rồi bounded wait acquire thành công dưới 0,4 ms: xác định giả định timing harness. Hai diagnostic HTTP run, mỗi run 139 case PASS; lần cuối trace max HTTP 2054,531 ms, hai acquire cố tình bị chặn khoảng 2 giây, release tối đa 0,093 ms; invalid-password case sau contention không gọi named lock.

Timeout HTTP lịch sử **chưa có nguyên nhân gốc xác định**; không coi 10 lần PASS là bằng chứng “chỉ lỗi harness”. metadata_locks SELECT denied 1142, không đổi quyền; IS_USED_LOCK chỉ kiểm tra được các tên hiện hành xác định từ persistent data, không phải toàn bộ lock graph. P0 chưa đóng đầy đủ, P2–P4 chưa thực hiện, không push/merge/deploy. Bằng chứng từng lần, lệnh, giới hạn và kết luận ở `docs/P0_FLAKY_INVESTIGATION_20261007.md`. Giữ 59 PASS/2 FAIL/19 CHƯA CHẠY.

P1 theo câu hỏi người dùng: PLAN_FINAL mục “Logic nghiệp vụ đã chốt”, dòng 88 có rule ngày dự án (khi có cả hai ngày) và ngoại lệ task cùng ngày. Đã bổ sung mục “Điều hướng dự án đã chốt” làm nguồn chuẩn cho 303 Lưu/Ẩn thành công và ngoại lệ 303 lỗi ngày; các lỗi khác không redirect thành công. Đây là cập nhật tài liệu khớp code đã có, không thay đổi runtime.
# Tiếp tục P0 — trace kết nối HTTP (07/10/2026)

Chạy `.tools/php83/php.exe .local/p0_trace_role_smoke.php`: exit 0, 139 HTTP cases
PASS; suite xác nhận persistent state/engine unchanged. Run `fae0c2cfc989` ghi đủ
139 TCP CONNECT events, 0 response failure; max connect 29,620 ms, max HTTP
2061,033 ms. Case 115: connect 0,258 ms, HTTP 200 sau 52,034 ms, không GET_LOCK.
Bằng chứng: `.local/p0_transport_summary.json`, `.local/p0_role_trace.jsonl`;
chi tiết trong `docs/P0_FLAKY_INVESTIGATION_20261007.md`.
Không tái hiện lỗi HTTP lịch sử; nguyên nhân vẫn chưa xác định. P0 chưa đóng,
không chuyển P2. Tổng checklist giữ 59 PASS / 2 FAIL / 19 CHƯA CHẠY.
Không sửa mã sản phẩm, không push/merge/deploy.

## P0 bổ sung và PLAN D2 — 08/10/2026

- tests/pm_import_apply_smoke.php đã đổi assertion close/acquire sang GET_LOCK(name,1) hữu hạn, giữ ownership trước/sau và owner NULL sau release; không sleep hoặc bỏ assertion. Lệnh .tools/php83/php.exe tests/pm_import_apply_smoke.php chạy 10 lần liên tiếp: 10/10 exit 0/PASS, dc_users và MyISAM unchanged. PHP lint exit 0.
- Timeout sản phẩm identity 2 giây, batch 0 giây giữ nguyên. Batch fail-fast có thể busy tạm thời tại cửa sổ session close; độ chờ probe 0,087–0,369 ms không phải trần thời gian close-to-release.
- HTTP diagnostic suite giữ client timeout 15 giây: 20/20 lượt có preflight hợp lệ PASS, 2780 request, max HTTP 2108,098 ms, max TCP 34,165 ms. Kiểm tra process/cổng trước mỗi lượt bằng Get-Process/netstat; giữ server local 8088, không còn fixture 18770 sau kiểm thử. CIM denied/lượt bị dừng được giữ bằng chứng riêng.
- Kết luận HTTP: **chưa tái hiện**, chưa xác định nguyên nhân timeout cũ; không kết luận chỉ harness hoặc production-safe.
- Bằng chứng/lệnh từng lượt và thời gian từng request: docs/P0_VERIFY_20261008.md cùng các artifact .local được liệt kê trong đó. Checklist tổng vẫn 59 PASS / 2 FAIL / 19 CHƯA CHẠY.
- docs/PLAN_D2_TASK_ESTIMATE_WARNING.md chỉ PLAN, chờ duyệt scope/quyền/historical/estimate 0/NULL. Chưa BUILD D2; không push/merge/deploy.


## D2 BUILD/VERIFY — 08/10/2026

Người dùng duyệt BUILD sau khi chốt estimate 0 là Chưa có ước tính. Đã triển khai cảnh báo riêng task_estimate_warnings, chỉ cho Admin/PM và task đang được phân bổ trong tuần đang xem; tổng hours toàn thời gian, không cộng ot_hours. So sánh và phép trừ bằng MySQL DECIMAL; task/dự án ẩn có nhãn lịch sử. Không migration/index mới, không thay đổi OT/cost/overbooking.

VERIFY thực chạy: service smoke PASS, 7 polling HTTP fixtures PASS, 53 regression scripts PASS (exit 0), 103 prepared query shapes EXPLAIN PASS, Smarty/zero-estimate PASS, PHP lint 8 file PASS, browser desktop/mobile/200% text/no-JS/error/authorization-loss PASS. Fixtures temporary exact-schema; hash persistent và engine trước/sau không đổi. Bằng chứng đầy đủ: docs/D2_VERIFY_20261008.md.

Checklist hiện hành: **60 PASS / 1 FAIL / 19 CHƯA CHẠY**. Chỉ nâng 7.4; 11.4 và các mục còn lại giữ nguyên. HTTP timeout P0 lịch sử vẫn chưa xác định nguyên nhân; kết quả regression không tự đóng P0 hoặc thay UAT. Không push/merge/deploy.


## Phạm vi 11.4 đã chốt — P4, 08/10/2026

Áp dụng writer PM qua admin.php/pm_ajax.php khi PM_HIDE_LEGACY=true: actor chỉ ID; metadata định danh không username/fullname/email/điện thoại/password/hash/IP mới. Profile event chỉ changed_fields (tên trường), password event không payload. Giữ snapshots nghiệp vụ và mọi lịch sử; không áp như cam kết toàn bộ logger hạ tầng/legacy. Bỏ test render tracking legacy hỗn hợp theo duyệt mới. Legacy count GET/POST HTTP thật trong PM mode đều404; mặc định và dự kiến triển khai PM_HIDE_LEGACY=true, chưa kiểm tra production. Assertions riêng từng writer/lệnh/output/giới hạn: docs/P4_VERIFY_20261008.md.

P3 chờ A/B/C, chưa PLAN/chưa BUILD và không sửa OT. P0:20/20 lượt hợp lệ,2780 request,client15s/identity2s/batch0s; maxHTTP2108.098ms. Từng request trong .local/p0_http_request_timings_20261008.csv và docs/P0_VERIFY_20261008.md. Timeout lịch sử **chưa tái hiện**, không kết luận nguyên nhân hoặc chỉ harness.


## P4 BUILD/VERIFY hoàn tất local — 08/10/2026

Audit hồ sơ chỉ actor ID + action + tên trường đã đổi; audit mật khẩu actor ID + action, không giá trị liên hệ/credential/hash/IP. PM_HIDE_LEGACY mặc định/dự kiến triển khai true, legacy AJAX count 4 request HTTP404. Assertions riêng từng writer PM đã chạy; 55 regression scripts, 156 role HTTP cases, PHP lint14 file PASS, diff --check exit0. Fixture rollback/temporary exact-schema và persistent unchanged theo từng suite. Chi tiết docs/P4_VERIFY_20261008.md.

Chỉ11.4 đổi FAIL→PASS trong phạm vi đã chốt; tổng **61 PASS / 0 FAIL / 19 CHƯA CHẠY**. Không tự nâng19mục hoặc tuyên bố UAT/production-ready. P3 chờA/B/C, chưaPLAN/chưaBUILD; P0 timeout lịch sử chưa tái hiện/chưa xác định nguyên nhân. Không migration/push/merge/deploy.


## Kết quả hiện hành 08/10/2026 — commit và các lô

P0 commit riêng833a148 bằng git add -p; P4 test fault commit26ca279. Regression trên code/tests đã commit26ca279 exit0:55 scripts PASS,157 role HTTP cases. Test matrix mới nằm ngoài regression này và phát hiện1.3 FAIL; regression PASS không thay thế cổng Tenant. Tổng hiện hành61 PASS /1 FAIL /18 CHƯA CHẠY. Lô1 dừng ngay; Lô2–3 chưa chạy. 2.3/9.2/9.3 chuyển kịch bản UAT, vẫn CHƯA CHẠY;5.1/5.2 chờP3 A/B/C. Bằng chứng và PLAN sửa chờ duyệt: [VERIFY_BATCHES_20261008.md](VERIFY_BATCHES_20261008.md). Không push/merge/deploy.


## 08/10/2026 — collecting matrix theo duyệt mới

80 cặp endpoint/ID đã thực chạy trên runtime chưa sửa:39 PASS/41 FAIL;11 control PASS;exit1,stderr rỗng. Những kết quả này là subcases của1.3, không thay số mục checklist80. Tổng vẫn **61 PASS /1 FAIL /18 CHƯA CHẠY**. Foreign có thật và missing được kiểm chứng, cùng status/message; toàn bộ FAIL hiện doHTTP200. Trace ca gốc thực sự làOutOfBoundsException từPmProjectService::saveTask(), dòng122, controller catch bỏstatus.

Lượt cuối hash21bảng thật/field user/tracking không đổi. Lượt cleanupFAIL do biến độngdc_users/dc_trackings được giữ riêng; probe read-only cho thấy login10:27:11 và last_login củaID111 cùng thời điểm, chưa xác định nguồnrequest. Không khôi phục hoặc viết dữ liệu thật. Các lỗi harness1137/compositechecksum/PMmanager/UnicodeJSON đã được mô tả bằng bằng chứng, không quy thành lỗi sản phẩm.

[Bảng đầy đủ80cặp, thông báo và output](TENANT_MATRIX_VERIFY_20261008.md); [PLAN chung chờ duyệt](TENANT_SCOPE_FIX_PLAN_20261008.md). Giữ InvalidArgumentException200 ở form đã duyệt, bỏ expectation400 chopmprojects; date-range/303/Lưu/Ẩn giữ nguyên. Không thêm matrix đangFAIL vào regression, chưa commit fix/chưa tạo lại manifest. Đã thêm009 vào thứ tự deploy ởDEPLOY_LOG.md (chỉ4ADD COLUMN nullable, phụ thuộc003). Các lô và1.4 vẫn dừng;P3 chờA/B/C. Không push/merge/deploy.


## Gate rà soát bổ sung Tenant 1.3 — 08/10/2026

Trước khi nhận yêu cầu dừng BUILD có điều kiện, sửa local theo lượt duyệt trước đã chạy matrix:80/80 cặp PASS,11 controls PASS,exit0;21 bảng persistent hash trước/sau không đổi. Output .local/tenant-matrix-full-command-20261008.json; data .local/tenant-matrix-full-20261008.json. Đây chỉ là kết quả contract cũ, không đủ nâng1.3 khi yêu cầu mới bổ sung message không chứa loại tài nguyên và rà endpoint/local quyền/malformed.

Regression khởi chạy trên working tree chưa commit: powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1; exit1 tại pm_projects_smoke (assignee không tồn tại: test cũ chỉ bắt InvalidArgumentException, service local ném OutOfBoundsException). Chưa sửa assertion/chưa chạy tiếp nhóm. Công cụ chờ từng timeout nhưng artifact hoàn tất cho biết FAIL, không ghi PASS.

Hiện vẫn **61 PASS /1 FAIL /18 CHƯA CHẠY**;1.3 giữ FAIL cho tới khi VERIFY toàn bộ phạm vi cuối được duyệt. [Bốn bảng chờ duyệt](TENANT_SCOPE_REVIEW_GATE_20261008.md). Không commit thêm, push/merge/deploy/migration;P3 chờ A/B/C. Bốn bảng chỉ đọc code, không thêm runtime changes sau gate mới.


## Tenant 1.3 — VERIFY trên commit cuối, 08/10/2026

Người dùng đã duyệt bốn bảng và BUILD theo nhóm. Commit local riêng: A `b70b441`, B `a3b17e3`, C `c850158c3734298c3f96755c9df006fc3a9305f7`. Không push/merge/deploy/migration.

Lệnh thực chạy: `powershell.exe -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1` trên commit C; exit 0. Output: `PASS: 56 regression scripts. No migrations applied by this runner.` Suite role HTTP: 157 ca PASS. Matrix: `RESULT: 80 PASS / 0 FAIL endpoint-ID pairs; 160 requests; 0 control failures; cleanup=PASS.` 11 positive controls, 35 behavioral controls, không lỗi. Artifact `.local/tenant-matrix-all-20261008.json` ghi source_commit đúng C và runtime_modified=false; 21 hash persistent trước/sau bằng nhau, changed_user_fields rỗng, cleanup=true. Output đầy đủ giữ local `.local/tenant-committed-regression-20261008.txt`; không commit log.

Preflight cuối: chỉ thấy process PHP PID 37372 chạy server local 8088 đã biết; harness kiểm tra port 18776 trống và tạo session test cô lập, chỉ dừng server do nó tạo. Không tuyên bố đã kiểm kê mọi session trình duyệt. Lượt trước có thay đổi last-login/tracking ngoài fixture đã ghi riêng; chưa xác định nguồn, không gán nguyên nhân. Lượt cuối không có thay đổi bảng thật.

Chỉ nâng 1.3. Các mục CHƯA CHẠY còn lại giữ nguyên đến khi có bằng chứng riêng; 5.1/5.2 chờ P3, 2.3/9.2/9.3 dành UAT người dùng.


## Lô 1 bổ sung — 09/10/2026

Đã hoàn thiện phần HTTP dở hôm qua. Lệnh thực: `PHP tests/pm_aggregate_store_input_smoke.php` exit 0:36/36; `PHP tests/pm_session_role_http_smoke.php` exit 0:98/98; `PHP tests/pm_tenant_matrix_smoke.php all` exit 0:80/80 +11 controls +35 behaviors. Assertions dữ liệu/audit/tracking giữ nguyên; 21/22 bảng thật hash trước/sau bằng nhau. Nâng đúng 1.4,0.2,2.7, không nâng 2.6/3.6. Chi tiết [BATCH1_HTTP_VERIFY_20261009.md](BATCH1_HTTP_VERIFY_20261009.md). Các mục còn lại giữ nguyên; P3 vẫn chờ A/B/C. Regression trên commit 5533099963c6332047c0a912f148919147e6f8c0 đã kết thúc exit0:58 scripts PASS; output đầy đủ .local/committed-regression-20261009.txt. Probe0.4 bổ sung sau đó FAIL, không thuộc regression58 và chưa thêm runner.


## Lô2 — dừng 0.4, 09/10/2026

`PHP tests/pm_required_fields_http_smoke.php` exit1:7 PASS/14 FAIL/21cases; cleanup21 bảng PASS. 0.3 nângPASS,0.4FAIL theo thông báo không rõ trường; không có bằng chứng ghi dữ liệu trống. [BATCH2_REQUIRED_FIELDS_VERIFY_20261009.md](BATCH2_REQUIRED_FIELDS_VERIFY_20261009.md) chứa bảng từng case/status/output. Chờ [PLAN_REQUIRED_FIELDS_20261009.md](PLAN_REQUIRED_FIELDS_20261009.md); chưa sửa runtime, chưa chạy các mục còn lại của lô2/lô3.


## 0.4 — BUILD/VERIFY sau duyệt, 09/10/2026

Targeted`PHP tests/pm_required_fields_http_smoke.php` exit0:30PASS/0FAIL, cleanup21 bảng PASS. Regression`powershell.exe -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1` exit0:59scripts PASS; stdout .local/required-fields-regression-20261009.txt. Matrix80/80+11controls+35behaviors,roleHTTP157,no-role98,store-input36PASS. Đã review query/CSRF/scope/status/escaping, không đổi OT/schema. Chỉ nâng0.4PASS;13mục còn lại giữ nguyên, tiếp tục lô2 sau phần này.

## P3 BUILD sau vòng71/0/9 — 09/10/2026
Code đang VERIFY theo lựa chọn B; bằng chứng P3_BUILD_VERIFY_20261009.md. 5.1/5.2 đã có assertions thực chạy PASS, tổng73PASS/0FAIL/7CHƯA CHẠY. Migration010 local sau backup đã duyệt/chạy, regressionP3 mới60scripts exit0. Concurrency P3 trênDBriêng chưa chạy và chưa tính PASS; khôngpush/merge/deploy.

P3 chốtscope09/10: usernhậnkiểmthửDBthủcông; runtimecommitfb67557/regression60exit0/explain108/fingerprint95PASS. Tổng73PASS/0FAIL/7CHƯACHẠY, khôngnângconcurrencyP3/3.6hoặccácUAT. Gói95runtime chỉreview, chưađạtgateproduction.

## Gói workspace integration 09/10/2026 — không đổi kết quả checklist cũ
Người dùng yêu cầu không tạoDBtest; kiểm thửDB/host nhận chạy thủ công. Kết quả checklist và7mục CHƯACHẠY trước giữnguyên, không chạy lại toàn bộ để xác nhận release mới. No-DB15scripts+7lint+5allowlist,17browser suites và4workbook files đạt trong phạm vi mô tảWORKSPACE_INTEGRATION_VERIFY_20261009.md. AuthenticatedKanbanpersist/audit, SQLscope/currency/concurrency/EXPLAIN vàimportApplyDB của bảnmớiCHƯACHẠY. Bộmanual-test có30ca vàdữliệu sẵn, tất cả trạng tháiCHƯACHẠY cho tới khi có bằng chứnghost.
