# Kiểm thử lại Phase 11 local — 06/10/2026

## Additional PM/HR HTTP verification - 2026-10-07

- `.tools/php83/php.exe tests/pm_role_http_smoke.php`: 30 cases PASS.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
  47 scripts PASS; no migrations applied by runner.
- PHP lint on pm_role_http_router.php and pm_role_http_smoke.php: PASS.
- Final targeted rerun with persistent-state comparison: PASS. Role/project rows
  and dc_users engine unchanged; temporary role/project tables close per request.
- Real controllers handle requests using seeded permissions and synthetic PM/HR
  assignments on a valid local session actor. Own/foreign project fixtures enforce
  ownership checks. HR cannot access project/cost/allocation/import pages or another
  user's reports; PM cannot access imports or foreign project/report data. Both
  roles export own empty hours XLSX and reject invalid export CSRF.
- Test router requires cli-server, loopback, a random environment token and fixed
  allowed routes; POST limited to report export. No request input enters eval.
  Eval decorates trusted repository source in memory only; runtime files unchanged.
- This fills controller HTTP fixture coverage, not real PM/HR account login UAT
  or form write E2E. Earlier 18 real-role cases and other coverage limits remain.

Runtime: `2bb9665`, nhánh `feature/pm-phase11-requirements`. Bổ sung kiểm thử
trong lượt này; không sửa runtime, không áp migration mới, không truy cập production.
Người dùng yêu cầu agent kiểm thử local, chuyển kiểm thử thủ công sang sau triển khai.
Kết quả dưới đây là automated VERIFY, không phải UAT hoặc production PASS.

Follow-up UI `1cad212`: shared CSS polish only, tested again with 46 regression
scripts and 14 browser suites PASS; desktop/mobile personnel screenshots inspected.
Select arrow/padding and outlined edit control explicitly tested. No business/schema
changes; existing coverage limits below remain. Manifest/ZIP updated to this runtime.

## Kết quả theo 10 nhóm yêu cầu

| Nhóm | Kết quả đã chạy | Căn cứ chính |
| --- | --- | --- |
| Đăng nhập/phân quyền | PASS | Auth identity 20 checks, RBAC, session guard, CSRF/session HTTP |
| Nhân sự/role/rate | PASS | DAO CRUD trên temporary MyISAM mirror, tìm kiếm/phân trang, sửa liên hệ, khóa/mở khóa/xóa mềm, tenant; departments/rates/role batch |
| Dự án/task | PASS | CRUD/membership/Kanban, client/start/due/role metadata, done/reopen timestamps, permission và progress |
| Chấm công/OT/audit | PASS | Tổng giờ/ngày, OT, sửa/di chuyển/xóa tính lại, window, rate snapshot, task/timesheet audit atomic rollback |
| Chi phí | PASS | DECIMAL, actual/estimate/budget, mixed currency, missing rate, polling/permissions, Chart.js empty/error/recovery |
| Phân bổ | PASS | Lưới tuần, capacity/overlap/overbooking, suggestions, hai kết nối đồng thời/locks, audit và tenant |
| Báo cáo | PASS | Hours/tasks/costs, weekly/delay, filters/role/scope, directory rendering, escaping; không tính tỷ lệ hiệu suất |
| Excel/import | PASS | XLSX round-trip/MIME/no-store/formula strings, directory whitelist, preview/errors, resumable Apply mirror/1062/concurrency |
| Bootstrap/responsive | PASS | 14 browser suites, current Smarty fixtures, Inter glyphs, focus/labels/skip link, dialogs/tabs/confirmations, mobile/text scaling |
| SQL/input security | PASS | 101 actual prepared-query EXPLAIN shapes, CSRF/IDOR/tenant/XSS/upload restrictions, CSP/session tests |

## Lệnh và phạm vi thực tế

- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
  **46 scripts PASS**; includes `pm_users_crud_smoke` newly added.
- `playwright-cli -s=pmbootstrap run-code --filename=run-phase11-ui.js`
  từ `.local/`: 9 Phase 9b suites PASS. Runner ghép nguyên các script trong `tests/`;
  fixture refresh từ các lệnh `smoke_pm_*.php --preview` dùng templates hiện hành.
- Cùng CLI, chạy `../tests/pm_phase11_reports_browser.js`,
  `pm_sidebar_overview_browser.js`, `pm_bootstrap_controls_browser.js`,
  `pm_confirmation_browser.js`, `pm_ui_browser.js`: 5 suites PASS.
  General UI suite kiểm tra 13 screen/state fixtures tại 360/390/768/1440px.
- Reports fixture mở rộng tasks/users/projects, kiểm tra HTML escaping và weekly
  section; sidebar dùng fixture hiện hành thay ảnh tái dựng lịch sử.
- `python -B tests/pm_deploy_manifest.py --check`: PASS.
  `python -B tests/pm_deploy_manifest_test.py`: 5 tests PASS.
- `php -l tests/pm_users_crud_smoke.php` (PHP 8.3 local) và `git diff --check`: PASS.

## Giới hạn, chưa suy ra đạt

- HTTP toàn trang có **18 role/route cases Admin/Employee**. Không có active local
  PM/HR actor; các quyền đó được kiểm tra ở tầng service/DAO bằng fixtures rollback,
  chưa phải đăng nhập và thao tác HTTP PM/HR đầy đủ.
- Browser fixture tests dùng dữ liệu giả, không phải mọi thao tác ghi đi qua form HTTP.
  DAO/service tests xác nhận ghi dữ liệu; không tuyên bố full end-to-end mọi luồng.
- 200% là text scaling; chưa thay zoom trình duyệt/OS thật. Excel được đọc lại bằng
  thư viện, chưa mở Microsoft Excel để đánh giá giao diện workbook.
- Prepared-query EXPLAIN không phải benchmark tải production. Không load/stress
  test toàn app trong lượt này. Legacy ngoài router PM không thuộc 10 nhóm kiểm tra.
- `dc_users` thật giữ engine và mật khẩu/dữ liệu nhân sự; bảng tạm đóng theo kết nối.
  Một bài duplicate INSERT cố ý thất bại có thể tạo khoảng trống auto-increment,
  như bài test công khai; không chèn nhân sự mới.

Không phát hiện lỗi runtime trong phạm vi đã chạy. Bổ sung test CRUD/report và
chuyển browser fixture sang bản hiện hành để tăng độ bao phủ. Merge/push/deploy
và migration 009 production vẫn cần ủy quyền riêng. Kiểm thử tay sau triển khai
được hoãn theo yêu cầu người dùng, không được đánh dấu đạt trước.
