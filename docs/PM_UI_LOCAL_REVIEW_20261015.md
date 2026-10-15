# Rà soát giao diện PM — bản local chờ duyệt

## Phạm vi đã được yêu cầu

Trang vào hệ thống, đăng nhập, tổng quan, hồ sơ/mật khẩu/nhóm, nhân sự và đơn giá, dự án và form dự án/task/Kanban, chấm công, phân bổ, chi phí/biểu đồ, báo cáo/Excel, import, thông báo, nhật ký. Không sửa giao diện legacy ngoài PM, route, controller, service, DAO, SQL, quyền hay dữ liệu. Không truy cập production. Các thay đổi chỉ local; triển khai cần người dùng duyệt riêng.

## Hướng chỉnh

Giữ Inter tự host, trung tính/teal và header đã chốt (Điều hướng trái, DeraSoft giữa). Chuẩn hóa chữ, mật độ, focus, form và nút phụ; giảm đoạn hướng dẫn hiển thị thường trực; dùng details cho nội dung bổ trợ. Cảnh báo lỗi/thiếu đơn giá và giới hạn dữ liệu không bị giấu. Không bổ sung tính năng giả hoặc bỏ chức năng. Form giữ nguyên method, action, input name, token, giá trị, min/max và permission gates.

## Kiểm tra và bằng chứng

Đã chạy:

- tests/pm_workspace_verify.ps1: PASS 23 script không DB, 30 PHP lint, 6 manifest checks.
- python tests/pm_ui_review_contract.py: PASS 14 template giữ nguyên thuộc tính form native (bỏ qua class trình bày), PHP gate của trang vào không đổi.
- PHP lint bổ sung pm-launch.php, tests/pm_ui_review_router.php, tests/pm_ui_review_preview.php: PASS.
- playwright-cli -s=workspace run-code --filename tests/pm_ui_review_browser.js: 29 trạng thái x 1440/390 = 58 lượt HTTP200, không tràn ngang, không input hiển thị thiếu nhãn, không pageerror; router chặn POST405 và không chạy admin.php (404).
- 18 bộ browser fixture PASS: pm_phase9b_users_browser, pm_phase9b_projects_browser, pm_phase9b_timesheets_browser, pm_phase9b_costs_browser, pm_phase9b_allocation_browser, pm_phase9b_imports_browser, pm_notifications_inbox_browser, pm_workspace_browser, pm_header_layout_browser, pm_navigation_all_pages_browser, pm_import_password_feedback_browser, pm_import_tabs_browser, pm_phase9b_buttons_browser, pm_phase11_reports_browser, pm_account_browser, pm_login_contrast_browser, pm_task_estimate_browser, pm_costs_missing_rate_browser. Lệnh mỗi bộ: playwright-cli -s=workspace run-code --filename tests/<tên>.js.
- Kiểm tra thêm 320/360/768 và phóng chữ 200% trong các bộ chuyên biệt; keyboard, modal, confirmation hủy, download/CSRF, polling/error/auth loss, no-JS, biểu đồ thiếu đơn giá. Bộ nút xác nhận tương phản >=4,5:1 ở các trạng thái được đo; intro đăng nhập đo 16,12:1.
- Hai lượt ảnh desktop/mobile được kiểm tra theo giới hạn của skill. Lượt sửa gom lỗi dấu tiêu đề import và tăng tương phản login. Bài kiểm thử cũ cập nhật kỳ vọng header mới, confirmation có tên và phần giải thích chuyển vào details; giữ assert quyền/CSRF/tổng giờ.
- Impeccable detector chạy một lần: cảnh báo dùng Inter (giữ theo yêu cầu dự án), bốn cảnh báo flat hierarchy do không resolve được CSS từ thư mục template. Browser đo h1 26–32px cho các trang vận hành, 30–44px login, Inter tải được. Không tuyên bố detector PASS hoặc toàn trang đạt WCAG từ các phép đo chọn lọc.
- Review diff/form contract: không thay controller/service/DAO/SQL/API/cron, không thêm runtime JS, không đổi quyền/ownership/CSRF hay giới hạn input. pm-users.tpl.html legacy không dùng bởi route hiện tại được giữ nguyên.

Không coi fixture PASS là UAT hoặc tích hợp DB PASS. Chưa chạy phiên đăng nhập/ghi DB thật, thiết bị cảm ứng vật lý, concurrency, benchmark hoặc production.

## Cách duyệt local

Mở http://127.0.0.1:18769/ . Trang chọn gồm 29 trạng thái dùng dữ liệu giả. Có thể mở menu, form thu gọn và dialog; thao tác ghi bị máy chủ từ chối. Polling chi phí/phân bổ dùng snapshot JSON giả, không gọi PHP ứng dụng. Link trang chính được ánh xạ sang fixture.

Nếu máy chủ đã tắt, chạy từ gốc dự án:

    powershell -ExecutionPolicy Bypass -File tests/pm_ui_review_start.ps1

Ảnh và log nằm trong .local/, không đưa vào commit/deploy. Tất cả file runtime của lần chỉnh này chỉ là template/CSS và phần HTML/CSS trang vào; đợi người dùng duyệt trước khi chuẩn bị gói production. Không upload tests/ hoặc fixture.
