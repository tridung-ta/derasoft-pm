# Phase 9b — so sánh giao diện minh họa

Tạo ngày 06/10/2026 theo yêu cầu người dùng cho phép tự tạo ảnh phù hợp.
**Ảnh trước là tái dựng từ commit `1dc761f`, không phải ảnh gốc 05/10/2026.**
Ảnh sau từ commit `471950d`. Hai phiên bản dùng cùng dữ liệu giả từ các fixture
Smarty local; menu đầy đủ chỉ phục vụ minh họa, không chứng minh quyền của tài khoản.
Không truy cập production, không ghi database, không thay đổi mã ứng dụng.

Chụp bằng Chromium/Playwright: desktop 1440×1000, mobile 390×844, full-page.
Mỗi ảnh có nhãn nguồn. Đồng hồ trình duyệt được kiểm soát để chụp trạng thái ban đầu;
đây không phải kiểm thử polling hoặc UAT. Kiểm tra lúc chụp: 36/36 không có pageerror
hay tràn ngang toàn trang; bảng có thể cuộn riêng. Không thay thế regression Phase 9.

| Trang | Trước — tái dựng desktop | Sau — desktop | Mobile trước / sau |
| --- | --- | --- | --- |
| Tổng quan | ![](screenshots/before/overview-desktop.png) | ![](screenshots/after/overview-desktop.png) | [Trước](screenshots/before/overview-mobile.png) / [Sau](screenshots/after/overview-mobile.png) |
| Nhân sự | ![](screenshots/before/users-desktop.png) | ![](screenshots/after/users-desktop.png) | [Trước](screenshots/before/users-mobile.png) / [Sau](screenshots/after/users-mobile.png) |
| Dự án | ![](screenshots/before/projects-desktop.png) | ![](screenshots/after/projects-desktop.png) | [Trước](screenshots/before/projects-mobile.png) / [Sau](screenshots/after/projects-mobile.png) |
| Chấm công | ![](screenshots/before/timesheets-desktop.png) | ![](screenshots/after/timesheets-desktop.png) | [Trước](screenshots/before/timesheets-mobile.png) / [Sau](screenshots/after/timesheets-mobile.png) |
| Nhật ký | ![](screenshots/before/audit-desktop.png) | ![](screenshots/after/audit-desktop.png) | [Trước](screenshots/before/audit-mobile.png) / [Sau](screenshots/after/audit-mobile.png) |
| Chi phí | ![](screenshots/before/costs-desktop.png) | ![](screenshots/after/costs-desktop.png) | [Trước](screenshots/before/costs-mobile.png) / [Sau](screenshots/after/costs-mobile.png) |
| Phân bổ | ![](screenshots/before/allocations-desktop.png) | ![](screenshots/after/allocations-desktop.png) | [Trước](screenshots/before/allocations-mobile.png) / [Sau](screenshots/after/allocations-mobile.png) |
| Báo cáo | ![](screenshots/before/reports-desktop.png) | ![](screenshots/after/reports-desktop.png) | [Trước](screenshots/before/reports-mobile.png) / [Sau](screenshots/after/reports-mobile.png) |
| Import | ![](screenshots/before/imports-desktop.png) | ![](screenshots/after/imports-desktop.png) | [Trước](screenshots/before/imports-mobile.png) / [Sau](screenshots/after/imports-mobile.png) |

Những ảnh này minh họa thay đổi bố cục, màu nút, sidebar, KPI, bảng nhân sự,
card dự án, sổ ghi công, lưới tuần, tab chi phí và stepper import. Các chuỗi kiểm
thử escaping còn hiển thị như chữ thuộc dữ liệu giả, không phải mã thực thi.
Ảnh gốc vẫn chưa được cung cấp; không gọi bộ tái dựng này là bằng chứng trước/sau
production. Các ảnh sau cũ từ kiểm thử vẫn có thể truy xuất trong lịch sử Git.
