# Phase 9b — ảnh sau từ fixture tự động

Ngày lưu: 06/10/2026. Các ảnh dùng dữ liệu giả từ bộ browser Phase 9b đã chạy đạt;
không phải ảnh UAT hoặc ảnh production. Chuỗi XSS hiển thị như chữ là dữ liệu kiểm
thử escaping, không phải nội dung thực thi. Không có mật khẩu/token/dump trong ảnh.

Mỗi trang có bản desktop và mobile; tên file tương ứng bên dưới. Các nguồn nằm
trong `.local/`, ảnh được lưu ở đây để giữ bằng chứng sau khi dọn fixture local.

| Trang | Desktop | Mobile |
| --- | --- | --- |
| Tổng quan | [overview-desktop.png](overview-desktop.png) | [overview-mobile.png](overview-mobile.png) |
| Nhân sự | [users-desktop.png](users-desktop.png) | [users-mobile.png](users-mobile.png) |
| Dự án | [projects-desktop.png](projects-desktop.png) | [projects-mobile.png](projects-mobile.png) |
| Chấm công | [timesheets-desktop.png](timesheets-desktop.png) | [timesheets-mobile.png](timesheets-mobile.png) |
| Nhật ký | [audit-desktop.png](audit-desktop.png) | [audit-mobile.png](audit-mobile.png) |
| Chi phí | [costs-desktop.png](costs-desktop.png) | [costs-mobile.png](costs-mobile.png) |
| Phân bổ | [allocations-desktop.png](allocations-desktop.png) | [allocations-mobile.png](allocations-mobile.png) |
| Báo cáo | [reports-desktop.png](reports-desktop.png) | [reports-mobile.png](reports-mobile.png) |
| Import | [imports-desktop.png](imports-desktop.png) | [imports-mobile.png](imports-mobile.png) |

Nhân sự có modal/native fallback; Nhật ký/Báo cáo/Import có bảng cuộn riêng ở mobile.
Ảnh không cho thấy cột ngoài viewport không có nghĩa cột đã bị xóa.
Người dùng cần chụp thêm ảnh app thật với dữ liệu giả sau UAT và ghi rõ provenance.
Chưa có đủ ảnh trước gốc để tạo bảng so sánh before/after thật cho cả 9 trang.
