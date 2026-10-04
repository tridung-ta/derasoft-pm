# Portfolio notes — DeraSoft PM

## CV summary

**DeraSoft PM — Project & Timesheet Management** · PHP, Smarty, MySQL, custom MVC

Extended an existing DeraSoft application with tenant-scoped RBAC, workforce and project/task management, daily overtime calculation, rate snapshots, automatic timesheet locking and transactional audit trails. Verified service behavior and security boundaries with local automated smoke tests.

**Tiếng Việt:** Phát triển hệ thống quản lý dự án và chấm công trên nền PHP/Smarty/MySQL có sẵn; bổ sung RBAC theo tenant, nhân sự, dự án/task, tính OT theo ngày, snapshot đơn giá, khóa chấm công sau 3 ngày và audit log trong transaction.

Không ghi production-ready, UAT hoàn tất, kết quả hiệu năng hoặc số người dùng khi chưa có bằng chứng. Chi phí dashboard, allocation và reporting vẫn nằm trong roadmap.

## Trình bày trên GitHub

- Giữ repository `derasoft-pm` cho một sản phẩm rõ ràng; các phase là branch, không tạo repo cho từng phase.
- Main trình bày dự án và dẫn tới nhánh BUILD mới nhất; không merge code chưa nghiệm thu chỉ để tăng contribution.
- README nêu mục tiêu, chức năng đã có, quyết định kỹ thuật, trạng thái và cách kiểm thử.
- Banner là đồ họa giới thiệu, không phải screenshot giao diện. Bổ sung screenshot dữ liệu giả sau UAT khi được yêu cầu.
- Profile nên pin 3–6 dự án có vai trò khác nhau. Không đổi tên, xóa hoặc archive repo cũ trong đợt chỉnh DeraSoft PM này.

## Danh sách repo cần phân loại trước khi chỉnh tiếp

| Repo hiện có | Hướng trình bày cần xác minh |
| --- | --- |
| derasoft-pm | Project & timesheet management; đang là dự án portfolio được chỉnh trong đợt này |
| derasoft-editorial | Cần đọc nội dung để viết mô tả riêng, tránh trùng với PM |
| Product-CMS | Cần xác minh phạm vi và phần đóng góp trước khi viết CV |
| BE_MauThietKe / FE_MauThietKe | Có thể trình bày backend/frontend liên quan nếu source xác nhận cùng một sản phẩm |
| KHOA_LUAN_TOT_NGHIEP | Cần tên đề tài và vai trò cá nhân để mô tả chính xác |
| QUANLYTHUVIEN_NHOM8 / VSCODE_QUANLLYTHUVIEN | Repo riêng của dự án cũ; cần đọc source trước khi đề xuất đổi tên/ghim |

## Tạm dừng

Ngày 2026-10-02: chỉ chỉnh phần trình bày repository và danh tính Git. BUILD nghiệp vụ tiếp tục tạm dừng; chờ lệnh mới của người dùng. Workflow duyệt timesheet đã hoãn, UAT chưa xác nhận, chưa deploy production.

## Phase 6 — local BUILD verified

Phase 6 đã BUILD và automated VERIFY trên `feature/pm-phase6-cost-chart`, chưa được push/merge/deploy.
Có thể bổ sung vào CV sau khi nghiệm thu: built a cost dashboard with stored timesheet costs,
DECIMAL estimates, mixed-currency guards, scoped permissions, local Chart.js and visibility-aware 60-second polling.
Evidence: service/HTTP/template/browser acceptance và regression ghi trong docs/PROGRESS.md.
Không ghi UAT hoàn tất hoặc production-ready; link GitHub Phase 6 chỉ thêm sau khi người dùng cho phép push.

## Phase 7 — local BUILD verified

Phase 6–7 đã được tách checkpoint local theo phase; chưa push/merge.
Đã xây dựng lịch phân bổ tuần, time-bound capacity, cảnh báo quá tải/trùng giờ nửa mở,
gợi ý capacity chỉ đọc và permission tenant/project/owner. Mutation và audit transactional;
mutex theo user/ngày đã kiểm tra bằng hai kết nối MySQL.
Service/HTTP/Smarty/synthetic-browser checks PASS; chưa UAT tài khoản thực tế,
chưa benchmark hoặc kiểm thử đầy đủ hai mutation nghiệp vụ đồng thời.
Chỉ công bố link triển khai và đưa vào CV sau khi nghiệm thu/phê duyệt tương ứng.
