# Kiểm tra giao diện workspace local — 09/10/2026

## Phạm vi và trạng thái

Chỉ UI template/CSS/JS và test frontend trong lượt tiếp tục này; không sửa BE/DB, không push/merge/deploy. Các chỉnh sửa service/controller có sẵn từ lượt trước vẫn ở working tree; báo cáo này không nghiệm thu chúng. Dashboard tổng hợp PM/Admin vẫn chờ duyệt riêng phạm vi BE tại PLAN_WORKSPACE_REDESIGN_20261009.md. Đây không phải UAT hoặc kiểm thử production/full regression DB.

## Review và sửa lỗi

| Phát hiện | Cách xử lý | Bằng chứng |
| --- | --- | --- |
| Kanban lọc theo fullname gộp tài khoản trùng tên | Lọc theo assignee_id đã có trong payload template; tên vẫn dùng làm nhãn, thêm ID khi trùng tên | Fixture hai tài khoản cùng tên: hai lựa chọn riêng; chọn ID 1 không hiện task ID 2 |
| Quên bộ lọc vẫn giữ nút restore với dữ liệu trong closure | Xóa nút restore và khóa nút quên sau khi xóa sessionStorage | Browser assertion không còn nút restore sau quên |
| JSON.parse/stringify có thể làm tròn số lớn trong audit | Bỏ biến đổi JSON; pre giữ nguyên chuỗi server đã escape | Chuỗi amount 12345678901234567890.01 giữ nguyên byte hiển thị |

## Lệnh thực chạy

Cwd các lệnh browser: D:\derasoft-pm\.local. Server preview local tại 127.0.0.1:18767; dữ liệu synthetic, AJAX được mock trong test Kanban. Các POST native fallback cũng được route intercept, không ghi DB.

| Lệnh | Kết quả/output |
| --- | --- |
| git diff --check | PASS, exit 0; cảnh báo chuyển LF/CRLF, không lỗi whitespace |
| .tools/php83/php.exe tests/smoke_pm_projects.php --preview (cwd repo) | PASS project/task Smarty rendering, escaping and read-only forms; zero estimate label |
| playwright-cli -s=workspace run-code --filename ../tests/pm_kanban_browser.js | PASS drag uses AJAX without optimistic move; identity filter duplicate names; overdue excludes done/future; pending/duplicate; network/auth/server/invalid/timeout recovery; read-only/no-JS fallback; 4 widths/text 200% |
| playwright-cli -s=workspace run-code --filename ../tests/pm_workspace_browser.js | PASS filter restore/forget/excludes text-CSRF; account Escape; success toast/dismiss; double guard/submitter/pageshow reset/export; sticky headers; 390/768/1440 no overflow |
| playwright-cli -s=workspace run-code --filename ../tests/pm_account_browser.js | PASS readonly username; 3 keyboard password toggles; mismatch/recovery; escaped audit before/after; exact historical number assertion; desktop/mobile; no POST |
| playwright-cli -s=workspace run-code --filename ../tests/pm_preview_hub_browser.js | PASS 10 local preview links HTTP 200; desktop/mobile no overflow |
| playwright-cli -s=workspace run-code --filename ../tests/pm_ui_browser.js | PASS 13 synthetic screen/state fixtures at 360/390/768/1440; labels, skip focus, native table regions, no inline/page overflow, CSP and text scaling 200% |

## Xem thử

http://127.0.0.1:18767/.local/workspace-review.html — hub local, chưa đưa vào runtime/release. Một số fixture module không gán navigation permission thật nên sidebar không có link; không dùng việc này kết luận quyền ứng dụng. Không gửi thao tác lưu/ẩn trên trang preview khi tự xem.

## Còn lại

- Dashboard PM/Admin: CHƯA BUILD, cần duyệt BE riêng.
- Full regression/DB và UAT bản redesign: CHƯA CHẠY; không tự chuyển các mục trong TEST_REPORT_FULL.md sang PASS.
- Đóng gói/commit phase/push/merge/production: chưa thực hiện cho redesign này.

## Lượt chốt giao diện — 09/10/2026

Đã chạy lại trên mã cuối của lượt này: 10 smoke template/UI và 15 suite Playwright dưới đây, tất cả exit 0. Các kết quả không đại diện kiểm thử backend/database hoặc UAT.

Smoke: `.tools/php83/php.exe tests/smoke_pm_NAME.php --preview` với NAME = admin, users, projects, timesheets, audit, reports, allocations, costs, imports, ui. Tất cả in PASS (admin: PM admin template smoke test passed).

Cwd Playwright: D:\derasoft-pm\.local. Lệnh mỗi suite: `playwright-cli -s=workspace run-code --filename ../tests/NAME.js`.

| Suite | Kết quả thật |
| --- | --- |
| pm_ui_browser | PASS / exit 0 |
| pm_workspace_browser | PASS / exit 0 |
| pm_account_browser | PASS / exit 0 |
| pm_kanban_browser | PASS / exit 0 |
| pm_login_import_workspace_browser | PASS / exit 0 |
| pm_preview_hub_browser | PASS / exit 0 |
| pm_phase9b_timesheets_browser | PASS / exit 0 |
| pm_phase9b_allocation_browser | PASS / exit 0 |
| pm_phase9b_costs_browser | PASS / exit 0 |
| pm_phase9b_imports_browser | PASS / exit 0 |
| pm_phase9b_buttons_browser | PASS / exit 0 |
| pm_phase9b_theme_browser | PASS / exit 0 |
| pm_phase9b_final_browser | PASS / exit 0 |
| pm_sidebar_overview_browser | PASS / exit 0 |
| pm_confirmation_browser | PASS / exit 0 |

Bằng chứng bổ sung: hub 13/13 link HTTP 200; nút chính 6.71:1, disabled 5.85:1; mỗi target đo ≥44px. Kiểm tra font qua CDP xác nhận glyph tiếng Việt dùng Inter custom font. CSS root đã sửa ưu tiên để module cũ không ghi đè theme. Test màu trước đó FAIL do kỳ vọng token cũ và cascade thật; sửa token phù hợp thiết kế mới và cascade, giữ nguyên AA/size/font/CSP/CSRF assertions. Button test chờ transition ổn định trước đo màu, không nhận PASS từ màu cũ chưa cập nhật.

Login hiện/ẩn password, import tải template 2 lần (POST action/CSRF nguyên vẹn), confirm Apply có batch/số dòng và hủy không khóa nút đã PASS. Nút template/download được miễn busy guard vì trang giữ nguyên sau download; POST lưu vẫn khóa/chống bấm đúp. Sidebar logout màu chữ bị legacy ghi đè đã sửa và chạy lại PASS.

Chỉ frontend và tests được sửa trong lượt chốt. Full regression DB chưa chạy theo yêu cầu người dùng. Dashboard tổng hợp PM/Admin cần BE, còn chờ duyệt. Redesign chưa được đóng gói/commit phase/push/merge/deploy.

## Bàn dự án và Kanban — tiếp tục UI local
- Tách metadata task thành phụ trách / ước tính / hạn; đổi bố cục cột, thẻ và trạng thái rỗng. Số lượng theo bộ lọc người phụ trách và cập nhật sau AJAX xác nhận thành công. Không thêm endpoint/query/schema.
- Dự án không có kết quả: xóa tìm kiếm; người có quyền tạo được mở form hiện có.
- VERIFY: PHP tests/smoke_pm_projects.php --preview PASS (render/escape/read-only/estimate 0); playwright-cli -s=workspace run-code --filename ../tests/pm_kanban_browser.js PASS (counts/filter/move, drag, recovery, native POST, 360/390/768/1440, 200%); pm_workspace_browser.js PASS.
- Lần đầu smoke FAIL vì assertion vẫn đòi câu không có dự án khi q đang khác rỗng; cập nhật đúng trạng thái không tìm thấy, vẫn kiểm tra quyền tạo. Assertion mới phát hiện counts chưa refresh sau AJAX do khác newline; sửa frontend, chạy lại PASS.
- Đã xem screenshot Kanban desktop; fixtures chứa chuỗi script để kiểm tra escape, không phải dữ liệu thực. Không kiểm thử DB và không push/merge/deploy.

## People desk UI ? 09/10/2026
Frontend only: compact membership rows, collapsed native member editor, named removal confirmation; personnel identity/status and editor sections refined. Cost design preserved.
VERIFY: PHP tests/smoke_pm_projects.php --preview and tests/smoke_pm_users.php --preview PASS; playwright-cli -s=workspace run-code --filename ../tests/pm_ui_browser.js PASS (13 fixtures, 360/390/768/1440, labels/CSP/200%); pm_kanban_browser.js PASS; pm_people_desk_browser.js PASS (collapsed member editor, keyboard, CSRF, person dialog Escape/focus, desktop/mobile). Screenshots people-desk-1440.png and people-desk-390.png inspected.
Initial browser runs could not execute because session and preview server had stopped; reopened local preview then ran successfully. Initial shell pipeline damaged newly inserted Vietnamese text; corrected encoding before successful final renders. No DB test/write, BE change, push/merge/deploy in this batch.

## Navigation toggle and task authentication feedback
Navigation summary now uses authored SVG menu/chevron, accent button and distinct open state on mobile. No cost-specific change. Frontend 401 copy distinguishes static .local preview (synthetic data, no session) and authenticated application. Read-only code inspection: preview router serves HTML without session; pm_ajax.php rejects missing actor before DB bootstrap. User screenshot URL not yet known; actual authenticated status saving remains unverified, not declared fixed.
Commands: PHP tests/smoke_pm_admin.php --preview PASS; tests/smoke_pm_projects.php --preview PASS; playwright-cli -s=workspace run-code --filename ../tests/pm_workspace_browser.js PASS; pm_kanban_browser.js PASS including preview 401 guidance (mock endpoint). BE/auth checks unchanged; no DB writes/push/deploy.
