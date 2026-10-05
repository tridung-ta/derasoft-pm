# Phase 9b — Bước 1: phân biệt nút thường và nguy hiểm

## Trạng thái — 05/10/2026

BUILD/VERIFY tự động đạt — chờ người dùng kiểm tra thị giác.
Nguồn yêu cầu: 9 màn hình thực tế và đề xuất Phase 9b của người dùng.
Nhánh BUILD dự kiến `feature/pm-phase9b-ui-theme`; giữ nguyên toàn bộ thay đổi
follow-up Phase 10 đang có, không reset/clean hoặc gộp bước UI khác vào bước này.

## Phạm vi và hành vi

- Nút hành động thường (Lưu, Thêm, Lọc, Apply, Export, Khôi phục/Mở khóa):
  nền `#B98A2E`, chữ tối `#161A20` để đạt contrast; không dùng chữ trắng khi không đủ 4.5:1.
- Nút nguy hiểm (Khóa, Xóa mềm/Ẩn, ngừng áp dụng rate, ẩn capacity/allocation):
  nền `#A6432D`, chữ `#E7E9EC` nếu đo đạt 4.5:1; nếu không, dùng trắng.
  Giữ nguyên nhãn hành động và confirmation đang có, không chỉ phân biệt bằng màu.
- Nút trạng thái động: chỉ Khóa là nguy hiểm; Mở khóa là hành động thường.
- Secondary (Hủy/điều hướng/Đăng xuất) giữ mức nhấn trung tính, không biến thành nút phá hủy.
- Hover/active/disabled/focus phải phân biệt được trên nền hiện tại và nền tối tương lai.
  Không giảm contrast bằng opacity cho nội dung nút enabled; focus visible vẫn rõ.
- `#C98A2E` dành cho cảnh báo nghiệp vụ, không dùng làm màu nút nguy hiểm.
- Không áp nền tối toàn trang, modal, font, grid tuần, chart, tabs hoặc stepper trong bước 1.

## File dự kiến sửa

1. `css/pmui.css`: token và style semantic chung, specificity thắng base CSS của module.
2. `templates/admin/pm-users-v2.tpl.html`: class semantic Khóa/Mở khóa, Ẩn nhân sự/phòng ban.
3. `templates/admin/pm-projects.tpl.html`: đã có class danger; không cần sửa file.
4. `templates/admin/pm-timesheets.tpl.html`: class Ẩn bản ghi.
5. `templates/admin/pm-rates-panel.tpl.html`: class Ngừng áp dụng.
6. `templates/admin/pm-allocations.tpl.html`: class Ẩn phân bổ/capacity.
7. Inventory hoàn tất: các template PM còn lại không cần sửa;
   phải ghi đúng danh sách cuối trong PROGRESS. Không thay đổi markup form/field/action.
8. `tests/smoke_pm_users.php` và `tests/pm_phase9b_buttons_browser.js`: assertions semantic nút trạng thái,
   contrast/keyboard/responsive. Tài liệu `docs/PROGRESS.md`, manifest release sau VERIFY.

Không sửa route/controller/DAO/permission, query hoặc schema. Không sửa config/legacy.

## VERIFY / acceptance bước 1

1. Inventory từng nút ở 9 màn hình: action thường/nguy hiểm/secondary đúng,
   đặc biệt Khóa↔Mở khóa và Ẩn↔Khôi phục; form POST/CSRF/ID giữ nguyên.
2. Render Smarty Admin và read-only: menu/quyền/escaping không đổi, không lộ mutation.
3. Browser synthetic matrix Phase 9 ở 360/390/768/1440px: no page overflow,
   focus/Tab/labels/skip link/reduced-motion, trạng thái disabled; chụp và xem desktop/mobile.
4. Đo contrast foreground/background computed cho nút thường/nguy hiểm/secondary,
   hover và focus. Normal text ít nhất 4.5:1; focus đủ nhìn rõ, target ít nhất 44px.
5. Regression `./tests/pm_regression.ps1`, lint phù hợp, diff/code/security review.
   Synthetic không gọi là authenticated browser/UAT/production PASS.
6. Cập nhật PROGRESS, phạm vi file thực tế và giới hạn theo kết quả đã chạy.
   Dừng sau bước 1 và chờ người dùng bắt đầu bước 2; không tự chuyển bước.

## Delivery

Chỉ BUILD local sau duyệt bước 1. Không tự upload/push/merge/deploy hoặc tắt maintenance.
Commit riêng từng bước sau VERIFY và người dùng xác nhận kiểm tra bằng mắt đạt; không đánh dấu
Phase 9b hoàn tất khi mới xong bước 1. Giữ kết quả Phase 9 và các bản sửa Phase 10.

## Ràng buộc cần giữ cho các PLAN sau

Lưới giờ tuần/timesheet và phân bổ chỉ dùng dữ liệu view đã được cấp quyền; không suy ra
tải toàn tenant từ các dòng visible hoặc truncated. Dữ liệu thiếu phải ghi rõ, không giả
lập tổng đầy đủ. Chart empty-state/tabs có thể cần điều chỉnh JS presentation nội bộ;
nếu cần sẽ nêu trong PLAN bước 5 và xin duyệt, không lách giới hạn chỉ CSS/Smarty.
Modal nhân sự phải có keyboard/focus-return/Escape và form submit độc lập; nêu phạm vi
JS presentation trong PLAN bước 2 để duyệt riêng nếu CSS/Smarty không đủ thực hiện.
