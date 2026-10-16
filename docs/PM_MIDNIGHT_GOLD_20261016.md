# Midnight Gold — 16/10/2026

Người dùng thay bảng màu Sơn mài Việt bằng dark mode mặc định: nền #0E1320, bề mặt #171E30, vàng đồng #D9B26A, chữ kem #EDE6D8 và chữ phụ #9AA0B2. Giữ Inter và bố cục workbench hiện có. Áp dụng impeccable, kiểm tra trình duyệt bằng playwright-cli.

Đồng bộ palette trên form, bảng, menu, dialog, import, thông báo, trang đăng nhập và trang vào. Biểu đồ dùng actual vàng, ngân sách xám và ước tính xanh nhạt, có legend. Asset đổi phiên bản `20261016-midnight1`. Không sửa controller/DAO/SQL hoặc điều kiện nghiệp vụ.

Hiệu ứng theo mẫu người dùng: ánh sáng quét qua nút chính trong 800ms khi hover; project card và liên kết workspace nâng 6px, bóng mềm và viền vàng. Overlay không nhận pointer; không chạy lặp. Reduced motion tắt quét và nâng. Không nâng bảng dữ liệu hay vùng nhập liệu.

Lượt kiểm tra đầu phát hiện token --card/--muted còn sáng và heading kế thừa màu Bootstrap tối. Đã sửa semantic tokens, biến Bootstrap và màu login. Lượt đo xác nhận 10 trang đại diện không có cặp chữ/nền dưới 4.5:1 trong các phần tử văn bản được script lấy mẫu. Đây không phải chứng nhận WCAG toàn bộ sản phẩm.

Đã chạy trên local, không DB:

- `tests/pm_workspace_verify.ps1`: PASS 23 script, 30 PHP lint, 6 manifest tests.
- `python tests/pm_ui_review_contract.py`: PASS 14 template giữ native form contracts và public PHP gate.
- `pm_ui_review_browser.js`: PASS 29 trạng thái × 1440/390px; không overflow/thiếu nhãn/pageerror; POST405 và application PHP404 tại preview.
- `pm_phase9b_buttons_browser.js`: PASS các trạng thái; nút vàng 9.29:1, hover 11.69:1, disabled 6.30:1.
- `pm_login_contrast_browser.js`: PASS intro 13.37:1, mobile stack, text scaling và focus.
- `pm_midnight_contrast_browser.js`: PASS chữ/nền trên 10 trang đại diện.
- `pm_midnight_motion_browser.js`: PASS sweep 800ms, overlay pointer-safe, lift 6px và reduced-motion.
- Các suite projects, costs, header, motion: PASS. Chart animation vẫn tắt; tiền và phép tính không đổi.

Impeccable detector chạy một lượt: cảnh báo stripe 3px ở ngày hiện tại; đã giảm xuống 1px. Đã xem ảnh tổng quan desktop và login mobile. Không kiểm tra thiết bị cảm ứng vật lý, DB/session integration hoặc production. Các fixture có dữ liệu giả, bao gồm chuỗi kiểm tra escaping. Chỉ local; không push/merge/deploy.
