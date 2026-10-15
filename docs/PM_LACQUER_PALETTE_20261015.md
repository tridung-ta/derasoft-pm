# Sơn mài Việt — local

Người dùng chọn primary #8C1C13, vàng #D4A24C, nền ngà #F6EFE4, mực #14100E và chữ phụ #7A6A5F. Áp dụng impeccable cho màu/độ tương phản, playwright-cli cho kiểm tra render. Giữ bố cục workbench và logic nghiệp vụ.

Nút chính đỏ cánh gián, hover #6F160F; vàng nhấn lựa chọn và chuỗi ước tính. Khu vực “Công việc của bạn” và login trở về nền sáng. Biểu đồ giữ nhãn, actual đỏ, ngân sách nâu, ước tính vàng. Asset dùng phiên bản `20261015-lacquer1`. Login mobile xếp dọc.

Kiểm tra thực tế phát hiện `body.pm-app[class]` thắng rule nền chung; sửa token workspace để nền ngà hiển thị đúng. Không suy ra màu thực tế chỉ từ rule vừa thêm.

Kiểm chứng: `tests/pm_workspace_verify.ps1` PASS 23 script không DB, 30 PHP lint, 6 manifest tests. `python tests/pm_ui_review_contract.py` PASS 14 native form contracts, public entry PHP gate giữ nguyên. `pm_ui_review_browser.js`: 29 trạng thái × 1440/390px, không tràn trang, thiếu nhãn input hay pageerror; preview chặn POST405 và application PHP404. `pm_phase9b_buttons_browser.js`: nút chính 9.17:1, hover 11.68:1. `pm_login_contrast_browser.js`: intro 16.56:1; mobile, text scaling và keyboard focus PASS.

Fixture dùng dữ liệu giả, không kết nối DB. Chưa kiểm thử SQL/session integration hoặc UAT production. Không push/merge/deploy.
