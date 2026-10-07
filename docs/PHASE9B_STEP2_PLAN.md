# Phase 9b — Bước 2: Nhân sự gọn, sửa từng người

## Trạng thái — 05/10/2026

PLAN được duyệt, BUILD/VERIFY tự động đạt. Nhánh feature/pm-phase9b-ui-theme;
token thống nhất mới của PLAN_FINAL Phần 13. Người dùng đã duyệt JS presentation.
Kết quả thật ở PROGRESS; chờ người dùng kiểm tra thị giác, chưa UAT PASS.

## Hiện trạng đã đọc trên source thật

pm-users-v2.tpl.html hiện render form hồ sơ, vai trò và thêm rate cho mọi người trong
bảng cùng lúc. Controller đã cấp users (20 người/trang), userPrimaryRoles,
userRoleState, roles, departments và flags quyền độc lập. Đủ dữ liệu để thay bố cục;
không cần query/controller mới. js/pmui.js hiện chỉ xử lý confirmation submit dùng CSP.

## File sẽ sửa/tạo — danh sách cố định

1. templates/admin/pm-users-v2.tpl.html: bảng gọn, details thêm nhân sự/phòng ban,
   dialog chỉnh một người, giữ các form POST độc lập và action/CSRF/field hiện tại.
2. css/pmui.css: style scoped trang nhân sự/dialog/actions/collapse; không CSS mới.
3. js/pmui.js: chỉ mở/đóng native dialog, focus-return và progressive enhancement;
   giữ listener confirmation, không API/AJAX hoặc xử lý nghiệp vụ.
4. tests/smoke_pm_users.php: assertions bảng/dialog, escaping, quyền độc lập,
   ID/form/CSRF và trạng thái Khóa/Mở khóa; synthetic fixtures nhiều người.
5. tests/pm_phase9b_users_browser.js (mới): mở đúng dialog, keyboard/Escape/focus,
   collapse, no-JS fallback, ID đúng form, responsive/text scaling và confirmation.
6. tests/pm_phase9b_buttons_browser.js: mở collapse/dialog chứa nút cần đo trước khi
   đo contrast; giữ assertions token/semantic Bước 1.
7. docs/PHASE9B_STEP2_PLAN.md: PLAN này và ghi phạm vi thực tế sau VERIFY.
8. docs/PROGRESS.md: kết quả thật, giới hạn kiểm tra, trạng thái chờ user visual check.

Không sửa pm-rates-panel.tpl.html, stylesheet base, DAO/controller/routes/permission,
schema, JS endpoint hoặc business logic. Không thêm thư viện/framework.

## Bố cục và hành vi

- Đưa danh sách nhân sự lên trước các khối phụ. Cột Họ tên (username phụ), Email,
  Phòng ban, Vai trò chính, Trạng thái, Thao tác. Vai trò chính map ID từ dữ liệu đã
  có, escape tên; không có thì ghi chưa gán, không tự suy ra theo rate/thứ tự role.
- Giữ filter và pagination hiện tại. Trạng thái và nút Khóa/Mở khóa/Xóa mềm trên dòng,
  cùng màu semantic Bước 1, nguyên nhãn/confirmation/action/ID/CSRF.
- Nút Sửa mở dialog của đúng người trong trang hiện tại. Hồ sơ, vai trò và thêm rate
  là các phần có heading rõ trong cùng dialog, mỗi phần giữ form submit riêng.
  Không gộp ba action vào một lần lưu. Chỉ hiện phần được quyền tương ứng.
- User có quản lý role/rate nhưng không có quản lý hồ sơ vẫn mở dialog để dùng đúng
  phần được quyền. Read-only vẫn có cột role/trạng thái nhưng không thấy mutation.
- Thêm nhân sự và Thêm phòng ban dùng details/summary, đóng mặc định, native keyboard.
  Danh sách/phần chỉnh phòng ban, khôi phục và quản lý rates vẫn truy cập được; không
  làm lại thành modal hoặc thay logic của chúng ở bước này.
- Native dialog có aria-labelledby, tiêu đề chứa tên đã escape, nút đóng type=button,
  autofocus thích hợp, Tab trong modal, Escape đóng, trả focus nút đã mở; viewport
  giới hạn và cuộn nội dung, các form không lồng nhau. Đóng không submit hay xóa dữ liệu.
- Không có JS/native showModal thì editor nằm trong details có thể mở; chỉ chuyển sang
  modal sau khi enhancement hoạt động. Không để chức năng sửa phụ thuộc JS hoàn toàn.
- POST vẫn full-page; lỗi/notice hiện ở trang như hiện tại. Không hứa giữ draft sau
  reload vì controller hiện không cung cấp dữ liệu form thất bại.
- Dùng token mới, không thiết kế lại toàn app/sidebar/font ở bước này. Giữ target 44px,
  focus-visible, label, skip link, table scroll, reduced motion của Phase 9.

## Ngoại lệ presentation cần duyệt cùng PLAN

Yêu cầu ban đầu chỉ CSS/Smarty. Modal có quản lý focus cần JS presentation nhỏ trong
js/pmui.js hiện có. Duyệt PLAN này bao gồm phạm vi JS mở/đóng dialog nêu trên;
không mở rộng sang business logic, network request hoặc thư viện shadcn/React.

## VERIFY / nghiệm thu

- ./tests/pm_regression.ps1 và render nhiều tổ hợp quyền độc lập, trạng thái/escaping.
- Browser Phase 9 360/390/768/1440, keyboard/labels/focus/skip, no page overflow,
  text scaling 200%, reduced motion; computed contrast nút và target 44px.
- Browser nhân sự riêng: hai người không lẫn ID; chỉ một dialog mở; focus trap/Escape/
  close/focus-return; details và no-JS fallback; confirmation Cancel ngăn submit.
- So sánh cấu trúc form trước/sau: field/action/hidden ID/CSRF đúng; submit local test
  qua HTTP regression/service rollback, không submit synthetic preview vào production.
- Chụp và xem desktop/mobile, code/security review: escaped IDs/names, không innerHTML
  từ input, không bỏ permission gates/confirmation; git diff --check.
- Báo link preview, file đã đổi và lệnh/kết quả thật; commit verified change theo yêu cầu
  hiện hành. Chờ người dùng visual check và bắt đầu bước tiếp theo; không push/deploy/
  merge hoặc gọi automated PASS là UAT. Merge develop sau đủ 9 bước và duyệt cuối.
