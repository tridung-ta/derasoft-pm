# Phase 9b — Bước 1: thay token, giữ phân loại hành động

## Trạng thái — 05/10/2026

PLAN điều chỉnh đã duyệt, BUILD trên feature/pm-phase9b-ui-theme.
Thiết kế thống nhất theo PLAN_FINAL Phần 13 mới, thay bảng màu cũ của d9c026f.
Không revert semantic/template/tests đã đúng; sửa đè bằng commit mới.

## File sửa (chính xác)

- css/pmui.css: token hệ thống trung tính, primary/destructive, hover/active/focus/disabled,
  nút bo góc 6px, logout trung tính. Dùng cùng một file xuyên suốt Phase 9b.
- tests/pm_phase9b_buttons_browser.js: token computed, contrast và trạng thái nút,
  giữ kiểm tra semantic và responsive.
- docs/PLAN_FINAL.md: bổ sung nguyên Phần 13 từ bản người dùng gửi; giữ quyết định audited.
- docs/PHASE9B_STEP1_PLAN.md: PLAN thay thế này.
- docs/PROGRESS.md: ghi kết quả thật và giới hạn kiểm tra.

## Token và phạm vi

Primary #18181b / #fafafa. Destructive #dc2626 / #ffffff: ngoại lệ chữ trắng được
người dùng duyệt khi cho BUILD, vì chữ #fef2f2 chỉ đạt 4.41:1.
Background #ffffff, foreground #09090b, muted #f4f4f5, border #e4e4e7,
warning #d97706. Khai báo token chung, chỉ áp nút ở Bước 1 này.
Warning không dùng làm chữ nhỏ trên nền trắng (3.19:1); các bước warning sẽ kiểm tra
nền/nhãn để đạt contrast. Không dùng warning cho nút thường/nguy hiểm.

Giữ Khóa nguy hiểm/Mở khóa thường; Ẩn/Ngừng áp dụng nguy hiểm/Khôi phục thường.
Giữ nhãn, confirmation, POST, CSRF, field và permissions.
Không đổi route/controller/DAO/permission/business logic/schema.
Không triển khai nền/sidebar/card/font/grid/modal ở bước này. Các bước theme sau dùng
Inter duy nhất, số liệu tabular căn phải, không dùng Lora/IBM Plex hoặc hai theme FE/Admin.
Không thêm React/shadcn hoặc framework mới vào DeraSoft.

## VERIFY và delivery

- Render Smarty và regression: ./tests/pm_regression.ps1.
- Browser Phase 9: 360/390/768/1440, labels/keyboard/focus/overflow, text scaling 200%.
- Computed contrast >=4.5:1 ở default/hover/active/focus/disabled, target >=44px,
  đúng token, Khóa/Mở khóa/Khôi phục semantics và reduced motion.
- Xem ảnh desktop/mobile, kiểm tra diff phạm vi và bảo mật.
- Commit thay đổi đã kiểm chứng theo yêu cầu commit từng thay đổi của người dùng;
  không gọi automated VERIFY là UAT. Người dùng kiểm tra thị giác/browser zoom riêng.
- Dừng chờ người dùng bắt đầu Bước 2. Không push/merge/deploy.
