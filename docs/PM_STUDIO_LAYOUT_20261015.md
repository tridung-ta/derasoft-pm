# Thiết kế lại bố cục PM — local chờ duyệt

## Yêu cầu và quyết định

Người dùng đánh giá hai bản chỉnh màu/viền/motion chưa thay đổi đủ rõ và yêu cầu tự nâng cấp toàn giao diện rồi báo khi xong. Giữ hướng sáng/Inter/teal và header đã chốt. Lần này thay composition thật trong template và contextual layout, không chỉ thêm animation.

## Những gì đổi

- Tổng quan: luồng công việc/điều cần chú ý rộng bên trái, danh mục truy cập chức năng ở rail riêng; số liệu thành một dải đối chiếu gọn.
- Nhân sự: bộ lọc cạnh danh sách và các công cụ; không phải cuộn qua một bộ lọc ngang lớn.
- Dự án: portfolio hàng ngang cho tên–tiến độ–manager/ngày/ngân sách; chi tiết có team rail bên trái Kanban.
- Chấm công: lịch đủ bảy ngày một hàng trên desktop; form ghi bên cạnh lịch sử, tránh lịch sử nằm tận cuối form.
- Phân bổ: điều khiển cạnh canvas tuần; cảnh báo và lịch bắt đầu cùng vùng nhìn.
- Chi phí, Báo cáo, Nhật ký: contextual filter rail với dữ liệu bên phải; chi phí thực tế nổi bật và biểu đồ giữ legend/giá trị thật.
- Import và các màn hình đọc giữ luồng chức năng, đồng bộ hierarchy mới. Login/public landing có panel nhận diện teal tương phản cao.
- Mobile bỏ rail sticky, xếp dọc theo thứ tự DOM; không áp dụng desktop layout bằng thu nhỏ chữ.

## Phạm vi an toàn

Không sửa controller/service/DAO/SQL/router/permission/cron, không thay validation/input/action/CSRF. Chỉ template, CSS, tài liệu và bài kiểm tra UI. Không thêm trường dữ liệu hoặc API. Không DB/production. Animation của bản trước giữ nguyên và có reduced-motion.

## Kiểm chứng

Đã chạy:

- tests/pm_workspace_verify.ps1: PASS 23 script không DB, 30 lint, 6 manifest checks.
- python tests/pm_ui_review_contract.py: PASS 14 template giữ nguyên native form contract và PHP gate trang vào.
- pm_ui_review_browser.js: PASS 29 trạng thái x 1440/390 (58 lượt), không overflow/thiếu nhãn/pageerror. Đã xem ảnh desktop/mobile; sửa khung dự án quá cao và vị trí rail bộ lọc Báo cáo, rồi xác nhận lại.
- pm_studio_layout_browser.js: PASS 7 workbench x desktop/mobile; đo bounding box xác nhận desktop nằm cạnh nhau và mobile xếp dọc. Không chỉ kiểm tra màu/class.
- 18 suite browser chuyên biệt PASS: pm_phase9b_users_browser, pm_phase9b_projects_browser, pm_phase9b_timesheets_browser, pm_phase9b_costs_browser, pm_phase9b_allocation_browser, pm_phase11_reports_browser, pm_motion_browser, pm_header_layout_browser, pm_login_contrast_browser, pm_workspace_dashboard_browser, pm_workspace_browser, pm_notifications_inbox_browser, pm_phase9b_imports_browser, pm_import_tabs_browser, pm_import_password_feedback_browser, pm_account_browser, pm_phase9b_buttons_browser, pm_costs_missing_rate_browser.
- Lệnh mỗi suite: playwright-cli -s=workspace run-code --filename tests/<tên>.js. No-JS/keyboard/modal/CSRF/export/polling/permission gates và text scaling được kiểm tra trong các suite chuyên biệt. Không chạy lưu DB thật.
- Login panel mới trắng trên teal đo 11,73:1. Test contrast đổi mục tiêu từ nền xám cũ sang panel mới, giữ ngưỡng AAA 7:1. Các trạng thái nút được đo >=4,5:1. Không suy ra mọi thành phần đạt WCAG từ mẫu đo.
- Test portfolio đổi kỳ vọng từ grid 3 card sang flex worklist; giữ nguyên kiểm tra native progress, quyền, giá trị, escaping, filter/pagination và điều hướng. Currency vẫn tabular/right-aligned.
- Impeccable detector chạy một lần với pmstudio.css, không có finding. Review diff không có controller/service/DAO/SQL/logic JS thay đổi. Tất cả form/giới hạn input và CSRF được giữ.

Preview: http://127.0.0.1:18769/ chỉ dữ liệu giả, không lưu. Native animation/reduced-motion của bản trước được giữ. Chưa đo thiết bị cảm ứng vật lý, session/SQL tích hợp hoặc production. Không push/merge/deploy và chưa tạo gói upload.
