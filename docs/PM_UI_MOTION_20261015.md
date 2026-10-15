# Nâng cấp thị giác và chuyển động — local chờ duyệt

Phạm vi: mọi trang PM và trang vào/đăng nhập, giữ Inter/teal, header trái–giữa–phải. Không thay controller, quyền, SQL, business JS hoặc form contract. Người dùng đã yêu cầu nâng cấp toàn diện bản local; chưa cho phép production.

## Motion thesis

- Focal moment: menu Điều hướng mở từ nút ở topbar, đọc nhóm chức năng trong một lần reveal có hướng. Trang vào dùng một reveal riêng cho khung dẫn vào không gian làm việc.
- Continuity: tab đổi panel, native details mở form, dialog mở sheet; state thay đổi ngay, animation chỉ minh họa, không trì hoãn focus hoặc submit.
- Feedback: border/background/focus của control, toast đến sau thành công. Không animate số tiền, không làm mới hàng bảng bằng chuyển động và không thêm hiệu ứng khi polling.
- Budget: WAAPI native, CSS transition giới hạn màu/opacity/transform/clip-path; 120–280ms cho thao tác, tối đa 420ms cho trang vào. Không thư viện, không loop/scroll reveal, không blur toàn trang, không will-change thường trực. Hủy animation khi bị ngắt, chuyển tab nền hoặc bật reduced motion.
- Reduced motion: giữ màu, viền và trạng thái; bỏ chuyển động không gian, dùng CSS feedback ngắn. Nội dung mặc định luôn hiển thị, no-JS dùng native details/form như trước.

## Kết quả local

- tests/pm_workspace_verify.ps1: PASS 23 script không DB, 30 lint, 6 kiểm tra manifest.
- python tests/pm_ui_review_contract.py: PASS 14 template giữ nguyên thuộc tính form và public PHP gate.
- pm_ui_review_browser.js: PASS 29 trạng thái x desktop/mobile, không tràn ngang/thiếu nhãn/pageerror; server preview chặn ghi.
- pm_motion_browser.js: PASS ở 1440 và 390 với cả no-preference/reduce. Menu 260ms ở chế độ thường; không animation không gian ở reduced motion; hủy khi đổi preference, mở/đóng nhanh, Escape, dialog/tab không mất trạng thái.
- 13 bộ hồi quy giao diện PASS: pm_phase9b_users_browser, pm_phase9b_projects_browser, pm_phase9b_costs_browser, pm_phase9b_imports_browser, pm_notifications_inbox_browser, pm_workspace_browser, pm_header_layout_browser, pm_account_browser, pm_phase9b_allocation_browser, pm_phase9b_buttons_browser, pm_login_contrast_browser, pm_phase9b_timesheets_browser, pm_import_password_feedback_browser. Lệnh: playwright-cli -s=workspace run-code --filename tests/<tên>.js.
- Nút được đo đạt tương phản >=4,5:1 ở default/hover/active/focus/disabled; hover primary 8,53:1. Login giữ 16,12:1. Các bộ chuyên biệt kiểm tra keyboard/no-JS/200% text scaling, polling và phản hồi lỗi.
- Đã xem ảnh 12 nhóm trang desktop/mobile. Detector chạy một lần với pmpolish.css và pmmotion.js, không có finding. Không suy ra toàn website đạt WCAG hoặc performance từ detector.
- Review: pmmotion.js không có network/storage/submit handler, không ghi đè nội dung form; không thay business JS. pmcosts.js chỉ thay bốn literal màu biểu đồ, vẫn tắt Chart animation khi polling. Các lời gọi animation có hủy/cleanup và giới hạn 12 instance; không animation bảng khi polling.

Chưa đo FPS/CPU trên thiết bị vật lý, chưa kiểm thử đăng nhập/ghi DB thật hoặc production. Native details đóng ngay theo state hiện hữu, không trì hoãn đóng để chạy exit animation.

## Duyệt

http://127.0.0.1:18769/ — 29 trang/trạng thái dùng dữ liệu giả. Thử nút Điều hướng, menu tài khoản, form thu gọn, chỉnh nhân sự và chuyển tab để xem chuyển động. Trang public có reveal riêng cho khung đăng nhập. Nội dung vẫn hiện khi không có JS.

Chỉ local, chưa gói upload/push/merge/deploy. Chờ người dùng duyệt.
