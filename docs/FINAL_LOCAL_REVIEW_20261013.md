# Rà soát tổng local — 13/10/2026

Source kiểm thử: f74da9c trên feature/pm-phase11-launch-copy.

## Kết quả thực chạy
- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1: exit0, PASS 17 no-DB scripts, 10 PHP lint, 5 manifest allowlist tests.
- PHP -l trên 44 PM sources ở DAO/service/admin/ajax/includes/root và node --check trên 5 js/pm*.js: exit0, PASS. Không phải kiểm tra toàn bộ mã legacy.
- playwright-cli -s=workspace run-code --filename tests/<suite>.js: PASS 21 suite workspace hiện hành: pm_ui_browser, pm_workspace_browser, pm_account_browser, pm_kanban_browser, pm_login_import_workspace_browser, pm_preview_hub_browser, pm_phase9b_timesheets_browser, pm_phase9b_allocation_browser, pm_phase9b_costs_browser, pm_phase9b_imports_browser, pm_phase9b_buttons_browser, pm_phase9b_theme_browser, pm_phase9b_final_browser, pm_sidebar_overview_browser, pm_confirmation_browser, pm_people_desk_browser, pm_workspace_dashboard_browser, pm_vietnamese_messages_browser, pm_toast_refinement_browser, pm_navigation_all_pages_browser, pm_shift_label_browser. Log local ignored: .local/final-browser-20261013.log.
- Cùng lệnh với pm_daily_limit_browser.js: PASS CSP/min-max/focus/snapshot/responsive; pm_task_estimate_browser.js: PASS scope/history/escaped polling/error/auth-loss/no-JS; pm_warning_color_browser.js: PASS 37 fixture computed-color, bad=[].
- .tools/php83/php.exe tests/pm_manual_uat_files_smoke.php: PASS 4 XLSX files, 3 import schemas, 8-sheet UAT; 30 kịch bản host vẫn CHƯA CHẠY.
- git diff --check: PASS. Scan 186 outgoing paths: không có config/log/cache/upload/dump/archive được bảo vệ, không phát hiện chữ ký token/key phổ biến. Scan heuristic không bảo đảm không có mọi dạng secret.

## Review code
Rà các đường AJAX task status (allowlist, auth, CSRF, lỗi chung), Kanban pending/timeout và phục hồi UI, shift whitelist/giữ nhãn cũ, khóa ngày và recalculate, SQL task warning DECIMAL/tenant/manager, identity lock theo store_id, quyền dashboard và shell logout POST/CSRF. Chưa phát hiện defect mới cần chặn push trong các đường đã rà. Không tuyên bố toàn bộ code legacy sạch hay chứng minh concurrency bằng code review.

## Đã hoàn thành chưa?
Chức năng PM theo phạm vi xây dựng đã có; bản local đạt kiểm tra không DB và fixture UI. Chưa đủ bằng chứng nghiệm thu toàn bộ: checklist giữ 73 PASS / 0 FAIL / 7 CHƯA CHẠY (2.3,2.6,3.6,9.2,9.3,9.5,10.3). Không nâng mục từ kiểm tra một phần.
Theo yêu cầu không dùng DB test: không chạy pm_regression.ps1/full DB/HTTP integration, không tạo DB hay ghi dữ liệu. Tái kiểm chứng SQL/tenant/authenticated writes/collision/concurrency trên bản cuối và UAT host vẫn do người dùng thực hiện. Historical PASS không phải lượt chạy mới.
Các cổng deploy còn thiếu bằng chứng: số store_id trước migration002, bảo trì xuyên suốt và thời gian khóa007 trên bản sao tương đương. Không truy cập production.

## GitHub
Người dùng ủy quyền push ngày13/10 sau review/test. Chỉ push nhánh feature hiện hành; không merge main, không deploy. Commit báo cáo không đổi runtime đã kiểm thử.
