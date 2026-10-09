# Kiểm chứng đồng bộ frontend–backend — 09/10/2026

BUILD local được người dùng yêu cầu; sau đó người dùng nhắc lại không tạo DB test, dữ liệu/DB do họ kiểm thử thủ công trên host. Không truy cập production, không chạy migration, không kết nối DB trong lượt verify này.

## Thay đổi và review

DAO PmDashboard chỉ SELECT/prepared; store_id lấy từ server; personal task membership dùng EXISTS, gate projects.view + tasks.view. PM quản lý chỉ manager_id của actor; Admin xem phạm vi tenant. Các service Chi phí/Phân bổ giữ scope gốc. Cost lấy lifetime đã guard currency/rate trong snapshot, chỉVND/đủrate/budget>0 tính tỷ lệ bằng MySQL DECIMAL. Budget0/thiếurate/currency khác không fake0; phản hồi lỗi hiển thị unavailable, log chỉ family không exception/SQL/PII.

No-role/HR/Employee không gọi management provider. Allocation warning dedupuser/ngày; task estimate warning tách riêng và lịch sử có nhãn. Tối đa20budgetprojects và8late/alert, giới hạn ghi rõ/link chi tiết. Không thay engine/schema/OT/audit writer. Form POST/CSRF/native fallback được giữ, AJAX không di chuyển lạc quan.

## Lệnh và output thật

- tests/pm_workspace_verify.ps1: PASS15no-DBscripts,7PHP lintchecks,5manifestallowlist tests.
- PHP tests/pm_workspace_dashboard_smoke.php --preview: PASS provider gates Admin/PM/Employee/HR/no-role, SQLcontracts/date/ownership/EXISTS/DECIMAL-inputstring, invaliddecimal, cost/alloc lỗi, dedup, Smartyescape. SQL không thực thi.
- PHP tests/pm_manual_uat_files_smoke.php: PASS4XLSX bằng parser ứng dụng;3imports 1sheet/6headers/dimensions/noformulas/ZIPsafety;8sheetmaster, cached/recalculated500000/25%/1000000;30hostcasesCHƯACHẠY.
- node .local/uat-build/build.mjs (cwd .local/uat-build): created/rendered4XLSX, kiểm tra formula kết quả. 11preview mỗisheet/import đã xem contact sheet; dữ liệu giả, không credential.
- playwright-cli -s=workspace run-code --filename ../tests/pm_workspace_dashboard_browser.js (cwd .local): PASS managed panels/budget/history/escape/keyboard/360/390/768/1440/200%; endpoint unauthenticated401 thật, trướcDB.
- Browser regression cuối: 17/17 suites PASS, exit0. Lệnh từng suite: playwright-cli -s=workspace run-code --filename ../tests/<suite>.js, cwd.local. Suites: pm_ui_browser, pm_workspace_browser, pm_account_browser, pm_kanban_browser, pm_login_import_workspace_browser, pm_preview_hub_browser, pm_phase9b_timesheets_browser, pm_phase9b_allocation_browser, pm_phase9b_costs_browser, pm_phase9b_imports_browser, pm_phase9b_buttons_browser, pm_phase9b_theme_browser, pm_phase9b_final_browser, pm_sidebar_overview_browser, pm_confirmation_browser, pm_people_desk_browser, pm_workspace_dashboard_browser. Raw log ignored.local/workspace-browser-final.log. Không gọi đó là DBintegrationPASS.
- Package/checksum: chạy `python tests/pm_workspace_package.py --ref HEAD --package` sau commit local. Script in hash commit/ZIP, số file và kết quả inventory/CRC/SHA256 thật; lưu receipt cạnh ZIP. Mục này không tự gán PASS trước khi chạy.

## Lần fail được giữ

1. No-DB runner dừng ở bootstrap: test substring '<head' nhận nhầm '<header' trong fragmentnavigation. Sửa regex chỉ nhận thẻhead thật, giữhash/license/order/bootstrap assertions; chạy lạiPASS.
2. Workbook parser trảtotalRows dạngstring'3', test strictinteger fail. Chuẩn hóa(int), vẫn kiểm tra đúng sốdòng/sheet/column; chạy lạiPASS.
3. Review bổ sung gate tasks.view cho dashboard: chỉ có projects.view không được query tên/counttask; cóassertion riêng.
4. Dashboard screenshot dùng chuỗi script và tên thử để kiểmescape, không phải dữ liệu host. Không xemđó là lỗi nội dung runtime.

## CHƯA CHẠY

Đăng nhập thật→Kanbanpersist/reload/audit; scope MySQLđa-tenant; EXECUTE/EXPLAINquery mới; transactioncurrency-snapshot dưới ghi đồng thời; HTTProle refresh; importStage/Apply/collisioncollation; rate/day concurrency và fullDBregression. Đây là test cần DB/người dùng nhận kiểmthủcông, không nângPASS.

Các7mục đangtreo trong TEST_REPORT_FULL giữnguyên. UAT thực hiện theo manual-test/BO_DU_LIEU_UAT.xlsx và WORKSPACE_RELEASE_HANDOFF_20261009.md. Không tự gọi production-ready hoặc đãdeploy.

- Review giới hạn đọc: dashboard dùng dashboardSummary, tối đa100allocation (query101đểphát hiện truncation), không tải user/task/capacityeditorchoices. pm_allocation_summary_contract_smoke.php PASS bằng realservice+stub; scopeAdmin/PM/Employee/no-role, snapshottransaction, đường dashboardgốc1000/choices giữ nguyên. Queryactual/EXPLAINCHƯACHẠY.
