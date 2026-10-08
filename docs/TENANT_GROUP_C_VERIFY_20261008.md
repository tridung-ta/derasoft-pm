# Tenant1.3 — nhóm C và dependency scope chung

Người dùng duyệt bốn bảng; nhóm A b70b441, B a3b17e3. Nhóm C sửa chấm công/phân bổ/báo cáo; kèm dependency scope Chi phí/import/audit/profile đã liệt kê trong bảng throw/catch. Không thêm API hoặc đổi nghiệp vụ cost/import/OT.

## Lệnh và kết quả

- .tools/php83/php.exe tests/pm_tenant_matrix_smoke.php c:37/37 cặp,74negative,4controls,15behavioral PASS;cleanup21bảng PASS;exit0. .local/tenant-matrix-c-20261008.json/.local/tenant-group-c-command-20261008.json.
- .tools/php83/php.exe tests/pm_tenant_matrix_smoke.php:80/80 cặp,160negative+11controls+35behavioral PASS; foreign/missing cùng status và message “Không thể truy cập dữ liệu.”;audit/tracking0,fixtureunchanged;cleanup21bảng PASS;exit0. .local/tenant-matrix-all-20261008.json/.local/tenant-final-matrix-command-20261008.json.
- pm_timesheets_smoke, pm_timesheet_window_smoke, pm_allocations_smoke, pm_allocations_http_smoke, pm_task_estimate_smoke, pm_task_estimate_http_smoke, pm_reports_smoke PASS. pm_reports_http_smoke lúc đầuFAIL expectationmissing403/404, sau đó phát hiện lỗi thật cost-denial500; sửa typeddependency và chạy lạiPASS. Output giữ trong .local/tenant-group-c-suites-20261008.json và .local/tenant-common-suites-20261008.json.
- pm_role_http_smoke:157casePASS,exit0/stderr rỗng, persistent unchanged. .local/tenant-role-http-20261008.json. Tách loop validation200 khỏiscope403/notfound404; giữ nguyên assertions data/audit/tracking và dailyOTcost.
- pm_costs_http_smoke, pm_import_smoke, pm_import_http_smoke, pm_account_audit_smoke, pm_legacy_ajax_disabled_smoke, pm_reports_http_smoke, pm_audit_smoke đềuPASS; .local/tenant-common-suites-20261008.json.
- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1:exit0,56scriptsPASS,157roleHTTPcases,104preparedEXPLAINshapes; matrix included only afterPASS. Runnerkhôngmigration. Phiênexec28017 đã chờ tới exit0. Sau commit chạy lại trên trạng thái committed để lưu output đầy đủ.
- PHP lint19changedPHPfilesPASS; .local/tenant-c-lint-20261008.json.

## FAIL đã giữ và xử lý

- Fixture settings lúc đầuHTTP400 do routertest thiếuallowlist; thêm actionsettings rồi kiểm controllerPMdenial403, không đổi OT. .local/tenant-group-c-first-command-20261008.json.
- Fullmatrixđầu79PASS/1FAIL: provision_password foreign/missing403 nhưng còn message về loại tài khoản. Thêm batchscopedguard trong try/finally trước ownedUser; giữ điều kiện inactive/provenance và release cả hai lock. .local/tenant-final-first-command-20261008.json/.local/tenant-final-first-data-20261008.json. Fullmatrixcuối80PASS.

## Review và giới hạn

- Chấm công task foreign/missing/localunassigned403 chungmessage; sheet foreign/missing403 ổn định, không dựa reload đểghiđèstatus. ID malformed và businesslockedday/24h/currency giữ200; Admin sửa taskẩn lịch sử giữ nguyên. Không đổi arithmetic/snapshot/daylock/P3.
- Allocationprojectpermission/subtype và active/ẩn business được tách; dashboarduserfilter404; localuserempty200. D2warning riêng vẫnPASS, không cộngOT lần2. Reportfilter kiểmscopeduser/project trước mode/XLSX, localPMprojectkhác403; lịch sử hidden/inactive vẫngiữ.
- Preparedstore_id guards; typedcatch trướcDomain/Throwable; genericUI không expose resourceID/type; audit/logsanitized. No migration/index/engine changes. Không xóa hoặc rewrite legacy.
- Preflight chỉserverlocal8088/PID37372 đã biết;18776/18770/18768rảnh trướcfinalrun, không killuserprocess. Sessiontest riêng, không dùng cookies user. Không khẳng định kiểm kê mọi phiên local hoạt động; checksum21bảng PASS là bằng chứng dữ liệu trong cửa sổ chạy không đổi. Lượt baseline cóloginđồngthời vẫngiữFAIL và nguồnchưaxácđịnh.
- Matrix không thay thế UAT hoặc mọi permutation role/route. Coveragegapđã liệt kê; no-role/session/concurrency chờlôkiểmthử sau1.3. Khôngpush/merge/deploy. P3 chờA/B/C.
