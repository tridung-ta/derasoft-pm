# Báo cáo kiểm thử tổng — 07/10/2026

Checklist: [QUY_TRINH_KIEM_THU_TONG.md](QUY_TRINH_KIEM_THU_TONG.md). App local b8e0774, nhánh feature/pm-phase11-launch-copy; báo cáo trước lượt này 9263aea. Không merge/push/deploy, không chỉnh UI/runtime, không migration. Chỉ database derasoft_pm_local/loopback được guard.

**Kết luận: CHƯA ĐẠT checklist tổng; chưa sẵn sàng tuyên bố UAT đầy đủ.** Có lỗi sản phẩm, thay đổi yêu cầu so với quyết định cũ, test lỗi thời và lỗi fixture MyISAM. Đã chạy nhóm 0 rồi kiểm tra cổng Tenant trước nhóm 2–8; 1.1/1.2 và scope suites không FAIL, nhưng 1.3/1.4 chưa đủ coverage toàn endpoint. Nhóm 9–10 browser; nhóm 11 regression/lint/checksum. Các probe bổ sung chỉ xác minh case còn thiếu, không thay trạng thái CHƯA CHẠY bằng suy luận.

**Sự cố kiểm thử cần ưu tiên:** runner báo 47 PASS nhưng pm_audit_smoke thay department_id user thật trên MyISAM và không khôi phục. Đã dừng chạy thêm test ghi dữ liệu thật sau phát hiện. Không được rerun test này/regression trước khi cô lập bảng user; không đoán hoặc reset department_id. Cần tìm backup/bằng chứng giá trị gốc trước khôi phục local. Không có bằng chứng đây là lỗi production hoặc đã thay production.

Quyết định người dùng: department tùy chọn, tên/email kiểm tra format/độ dài/control characters; không đánh giá tên vô nghĩa hoặc xác minh hộp thư. Validator hiện chưa đầy đủ: fullname/email 50, username 30, tel 15; import cho fullname 100/username 50. Xem [CONSTRAINT_REVIEW_20261007.md](CONSTRAINT_REVIEW_20261007.md). Giữ dc_users MyISAM.

**80 mục: 56 PASS, 5 FAIL, 19 CHƯA CHẠY.** PASS chỉ trong phạm vi bằng chứng ghi ở dòng; CHƯA CHẠY có thể có kiểm tra một phần.

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
| 0.2 | **CHƯA CHẠY** | Probe norole đăng nhập qua PmAuth thành công và từ chối 23 permission; chưa chạy phiên HTTP/browser no-role trên mọi action. .local/constraint_extra.php norole. |
| 0.3 | **CHƯA CHẠY** | Đã duyệt không đánh giá tên vô nghĩa/hộp thư. FILTER_VALIDATE_EMAIL chấp nhận email mẫu; chưa kiểm thử end-to-end tên mẫu theo validator thống nhất vì validator độ dài/control chưa có. E0/E-V. |
| 0.4 | **CHƯA CHẠY** | 73 HTTP cases có lỗi email/ngày/task và CSRF, chưa submit từng required field của mọi form user/project/task và kiểm tra thông báo từng field. E0. |
| 0.5 | **PASS** | 13 fixture UI và template smoke render XSS thành text; HTTP search XSS không tạo script. E0, E9-ui; không gọi đây là pentest toàn framework. |
| 0.6 | **PASS** | pm_ui_http_smoke chạy search SQLi, scope/response không lỗi SQL lộ ra. E0. |

### Nhóm 1

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 1.1 | **PASS** | SHOW CREATE TABLE + information_schema cho 19 bảng dc_pm_*: tất cả có store_id. E1-schema. |
| 1.2 | **PASS** | 12 unique key nghiệp vụ không phải PRIMARY đều chứa store_id; PK id toàn cục không được coi là tenant business key. E1-schema. |
| 1.3 | **CHƯA CHẠY** | G1 service/HTTP scopes PASS project/task/timesheet/allocation/report/cost, nhưng chưa dựng đủ foreign resource có thật và đổi từng ID trên mọi endpoint. Chưa gọi nhóm 1 là hoàn toàn PASS. |
| 1.4 | **CHƯA CHẠY** | HTTP kiểm tra session tenant/wrong store PASS; chưa gửi store_id giả riêng trong mọi request cost/report để so kết quả. Module lấy storeId từ session; code review không thay test. |

### Nhóm 2

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 2.1 | **PASS** | 20 auth identity checks: cả username/email dùng chung mật khẩu, đổi mới nhận cả hai, cũ bị từ chối. G2 pm_auth_identity_smoke; đây là DAO với temporary mirror, không browser login. |
| 2.2 | **CHƯA CHẠY** | Pmauth authenticate đã chạy bằng MD5 và code có UPDATE password_hash; chưa assert trực tiếp hash mới sau login hoặc session login legacy. G2/auth code, không tự suy ra PASS hash persisted. |
| 2.3 | **CHƯA CHẠY** | 18 authenticated routes/menu Admin/Employee PASS; PM/HR có 73 controller fixture cases, chưa kiểm tra chính xác đủ menu bốn role qua login thật. E0. |
| 2.4 | **PASS** | HTTP 18 + 73 cases gọi thẳng route và POST thiếu quyền bị từ chối, gồm PM foreign project và HR financial/import denial. E0. |
| 2.5 | **PASS** | Auth mirror status 0/2 bị từ chối; session guard khóa/ẩn cũng bị từ chối. G2. |
| 2.6 | **CHƯA CHẠY** | Code session_regenerate_id(true) có ở login.module.php:49; chưa so cookie/session ID trước-sau login hoặc time-expiry thật trong lượt này. |
| 2.7 | **CHƯA CHẠY** | Session guard revoked status PASS; chưa đổi role/permission rồi reuse cookie HTTP của cùng phiên. Không coi thu hồi status là thu hồi role. G2. |

### Nhóm 3

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 3.1 | **PASS** | Hai role + user rate 150: resolveRate chọn user 150. G3-extra exact-schema temporary mirrors. |
| 3.2 | **PASS** | Hai role rate 100/900, role chính 100: resolveRate chọn 100, không chọn max. G3-extra. |
| 3.3 | **PASS** | Không role chính, không user rate: rate_decimal=0.00, source=fallback, warning có giá trị. G3-extra. |
| 3.4 | **PASS** | Sau effective_to: fallback, không chọn rate đã hết hạn. G3-extra. |
| 3.5 | **PASS** | pm_rates_smoke: rate cũ 01–31/01, rate mới từ 15/01 đến NULL bị phát hiện overlap; từ 01/02 không overlap. G3. |
| 3.6 | **CHƯA CHẠY** | lockOwnerRates có SELECT FOR UPDATE nhưng chưa chạy hai request rate thật đồng thời; test concurrency Phân bổ không chứng minh rate race. Phase 3 cần thêm test riêng, kể cả owner chưa có rate. |
| 3.7 | **PASS** | validateRateOwner từ chối cùng NULL/cùng có giá trị; nhận đúng một owner. G3 pm_rates_smoke. |
| 3.8 | **PASS** | CRUD mirror soft-delete status=2, không hiện danh sách mặc định và tenant boundary. G3 pm_users_crud_smoke. |
| 3.9 | **PASS** | HTTP duplicate email từ chối và native INSERT trùng trả 1062; cùng store. E0/G8 pm_import_native_duplicate. Native test có thể gây gap auto_increment. |
| 3.10 | **CHƯA CHẠY** | CRUD list/count/search/page tên/email PASS; template/controller có phòng ban/role filters nhưng chưa assert kết quả từng bộ lọc và tổ hợp. G3, G11 role batch không thay test lọc. |

### Nhóm 4

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 4.1 | **PASS** | Service và HTTP PM sửa/xem foreign project bị từ chối. G1/G4 và E0. |
| 4.2 | **PASS** | Project service từ chối assignee không thuộc member active của dự án. G4 pm_projects_smoke. |
| 4.3 | **PASS** | Employee scope task assignment, permission/foreign project assertions trong G4 pm_projects_smoke. |
| 4.4 | **FAIL** | Không có luồng Kanban AJAX. Template pm-projects.tpl.html:11 dùng form task_save POST; save/audit POST PASS nhưng không đáp ứng AJAX. Quay Phase 4; không tự đổi framework/route. |
| 4.5 | **PASS** | Project/task soft delete và historical data giữ được trong costs/reports. G4/G6/G8. |

### Nhóm 5

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 5.1 | **CHƯA CHẠY** | Smoke thực tế dùng 6h+4h và HTTP fixture 4h+9h; chưa chạy đúng fixture một dòng 8h regular=8/OT=0 trong lượt này. Không suy từ case 10h. |
| 5.2 | **CHƯA CHẠY** | HTTP đã kiểm tra thêm 2h sau tổng 13h => 0 regular/2 OT; chưa chạy đúng case trước đó đúng 8h rồi thêm 2h. E0, G5. |
| 5.3 | **PASS** | Sửa/move tính lại ngày; HTTP 4h thành 2h khiến dòng 9h thành 6 regular/3 OT, cost 1050. E0/G5. |
| 5.4 | **PASS** | Soft-delete recalculates dòng còn lại, HTTP 9h thành 8 regular/1 OT cost 950. E0/G5. |
| 5.5 | **CHƯA CHẠY** | Smoke assert rate_snapshot giữ nguyên sau đổi rate; chưa assert riêng cost trước/sau đổi rate để hoàn tất đúng mục 5.5. G5. |
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
| 7.4 | **FAIL** | Probe actual 5h > estimated 4h: dashboard allocation warnings=[]; không cảnh báo task overload riêng. .local/full_task_overload_probe.php; pmallocationservice warnings chỉ xét allocation usage. Quay Phase 7. |
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
| 8.3 | **CHƯA CHẠY** | XLSX đã assert =HYPERLINK và @SUM là type string; tất cả cells export explicit string. Chưa tạo đủ mẫu + và - để round-trip từng prefix trong lượt này. G8. |
| 8.4 | **FAIL** | Import hiện preview cả file, stage() return ngay nếu valid=false, dòng đúng không được staging khi cùng file có dòng sai. pmimportservice: stage() + pm_import_smoke “Duplicate file staged” assertion. Quay Phase 8; khác quyết định cũ validate toàn bộ rồi stage, cần chốt thay đổi semantics trước BUILD. |
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
| 9.5 | **CHƯA CHẠY** | HTTP CSP header self/no unsafe-inline PASS; preview Chart và UI no-pageerror PASS. Chưa áp cùng CSP header thật lên mọi fixture và thao tác form/Chart dưới header đó trong lượt này. E0/E6-browser. |

### Nhóm 10

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 10.1 | **PASS** | Computed normal #18181b, danger #dc2626 qua 4 loại action và default/hover/active/focus/disabled. G10-buttons. |
| 10.2 | **PASS** | Danger computed chữ #ffffff, nền #dc2626, contrast 4.83:1; normal 16.97:1. G10-buttons. |
| 10.3 | **CHƯA CHẠY** | CSS warning foreground là #92400e, #d97706 dùng border; chưa computed-scan mọi small text trong mọi cảnh báo/state. Không coi grep CSS là kiểm thử toàn UI. |
| 10.4 | **PASS** | Lock/deactivate danger; unlock/restore secondary không danger, confirmation existing được giữ. G10-buttons. |
| 10.5 | **PASS** | Một theme chung css/pmui.css; computed stylesheet list còn Bootstrap và pmcosts.css legacy. Đây là 1 theme chung, không phải 1 stylesheet tổng cộng; không tạo theme thứ hai. E9-visibility + template review. |
| 10.6 | **PASS** | Week renderer 8 headers (employee + 7 days), exact totals/overbooking, same-day rows, year rollover. G10-allocation; ô vượt dùng nền amber nhẹ + viền warning #d97706, không solid amber toàn ô. Nếu checklist đòi solid background, đây là điểm cần duyệt lại. |
| 10.7 | **PASS** | Grid card/native progress empty/0/50/100/restricted/task-delete aggregation + contrast badges. G4 and G10-projects. |

### Nhóm 11

| Mã | Kết quả | Bằng chứng, phạm vi / việc còn lại |
|---|---|---|
| 11.1 | **PASS** | Runner pm_regression.ps1 exit 0: 47 scripts PASS, 73 HTTP và 101 EXPLAIN. Đây chỉ là PASS lệnh; không đồng nghĩa checklist tổng PASS, đặc biệt test audit gây lỗi fixture 11.5. |
| 11.2 | **PASS** | PHP 8.3 lint 103 file .php đổi từ 3df3349..HEAD: không syntax error; log G11-lint. Không chỉ lint bốn runtime mới. |
| 11.3 | **PASS** | Tracked inventory + scoped diff không có config thật/.env/dump/cache/upload trong phần chuẩn bị commit; chỉ docs mới. includes/config.inc.php.example và templates_c/.gitkeep là placeholders. Không quét lịch sử secret toàn repository. |
| 11.4 | **FAIL** | Legacy tracking lưu raw username; read-only count 5332 dòng username non-empty. pmusers.module.php:66 ghi username thật, chưa đáp ứng “không PII thô” theo checklist mới. Chưa kiểm tra mọi log sample cho secret; không in PII/secret ra report. Quay Phase 3/5/9, chốt PII policy rồi redact/pseudonymize đúng phạm vi. |
| 11.5 | **FAIL** | dc_users checksum 879749036 -> 951768798; 2 users tham chiếu department đã mất. pm_audit_smoke.php:56,67,71,74 UPDATE bảng thật MyISAM, finally chỉ rollback; PM department fixture rollback nhưng user reference không rollback. 19 bảng PM checksum/engine giữ nguyên, user engine vẫn MyISAM, high-id fixtures=0. Quay Phase 5/9/10 test isolation; dừng chạy test này/runner trước khi sửa. Chưa khôi phục giá trị gốc vì không có baseline field values. |

## Thứ tự sửa / kiểm tra lại

1. Phase 5/9/10: cô lập pm_audit_smoke bằng temporary dc_users đúng schema; guard toàn runner tránh MyISAM rollback giả. Xác định bản gốc hai department_id trước sửa local; không tự gán NULL, không đổi engine. Lượt này chưa phục hồi dữ liệu.
2. Phase 3: validator chung đúng cột thật, create/edit/profile/import; chặn control characters/overlength và collision username–email chéo; test schema thật và boundary. Không coi chuỗi tên trong ảnh tự thân là lỗi.
3. Phase 7: cảnh báo actual task > estimated_hours riêng, đúng quyền và dữ liệu lịch sử; không trộn weekly/daily allocation overload.
4. Phase 4: chốt có bổ sung AJAX Kanban hay giữ form POST; nếu bổ sung giữ CSRF/ownership/audit và no-JS fallback.
5. Phase 8: quyết định lại import partial-valid semantics. Hiện quy tắc cũ all-file validate/stage; không tự thay bằng partial import từ checklist mà bỏ crash/UNIQUE/mutex safeguards.
6. Phase 3/5/9: chốt trường audit identity được phép lưu; raw username hiện vi phạm checklist 11.4 literal. Không tự xóa lịch sử tracking/legacy.
7. Sửa ba browser tests lỗi thời; bổ sung các CHƯA CHẠY, đặc biệt full tenant matrix, no-role HTTP, rate concurrency, session/role refresh, exact 8h/8+2, cost snapshot assertion, four formula prefixes, CSP headers và real zoom/keyboard.

Không merge nhánh trong lượt này. Không dùng số PASS scripts thay chứng nhận sản phẩm/fixture sạch. Chỉ xét UAT sau xử lý FAIL và hoàn tất/ghi nhận giới hạn CHƯA CHẠY với người dùng. Gói ZIP UI đã giao vẫn ở b8e0774; không tự đóng gói/deploy lại từ báo cáo này.
