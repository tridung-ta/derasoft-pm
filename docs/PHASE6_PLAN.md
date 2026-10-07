# Phase 6 — Cost & Chart

## Status

BUILD / automated VERIFY COMPLETED — người dùng duyệt Phase 6 và yêu cầu currency guard trước mọi SUM(cost).
Kết quả đủ 6 nhóm acceptance ghi tại docs/PROGRESS.md; chưa UAT/push/merge/deploy.
Phase 5 đã automated VERIFY; UAT chưa xác nhận.
Ngày 2026-10-02 người dùng yêu cầu tiếp tục và duy trì GitHub chuyên nghiệp cho CV.

## Mục tiêu và phạm vi đề xuất

Dashboard chi phí thực tế/dự kiến theo dự án và task, có bộ lọc thời gian, ngân sách và biểu đồ.
Tái sử dụng projects/tasks/timesheets, PmDb, PmAccess và Smarty; không rewrite hoặc xóa legacy.
Branch BUILD dự kiến `feature/pm-phase6-cost-chart`, kế thừa checkpoint Phase 5 khi được duyệt.

### Chi phí thực tế

- Tổng hợp SUM(cost), SUM(hours), SUM(regular_hours), SUM(ot_hours) từ timesheets chưa soft delete, theo store_id và khoảng work_date.
- Không tính lại từ rate hiện tại: rate_snapshot/cost trên timesheet là nguồn chuẩn.
- Trước mọi SUM(cost), service kiểm tra DISTINCT currency toàn bộ tập được tổng hợp trong cùng snapshot đọc nhất quán. Tập mixed-currency trả cost=null và cảnh báo rõ ràng, không cộng tiền hoặc tự quy đổi. Áp dụng cho tổng, từng group, lifetime, detail và polling; giờ vẫn được tổng hợp. Estimate cũng kiểm tra currency trước tổng tiền; budget VND không so sánh với actual/estimate khác currency.
- Task đã soft delete vẫn đóng góp actual cost nếu timesheet chưa ẩn; hiển thị nhãn công việc lịch sử.
- Dự án soft delete không nằm trong dashboard thông thường; không xóa dữ liệu lịch sử.
- Ngân sách và actual toàn đời dự án tách rõ actual trong khoảng lọc; tránh lấy ngân sách toàn dự án so trực tiếp với actual của một tháng mà không ghi nhãn.

### Dự kiến và ngân sách

- Task active: estimated_hours × rate hiện hành của assignee tại ngày định giá hiển thị trên dashboard; dùng user rate, primary role, fallback 0 + cảnh báo.
- Đây là ước tính tại thời điểm xem, không phải snapshot hoặc cam kết ngân sách. Không tự suy diễn OT dự kiến khi không có lịch phân bổ ngày.
- Task chưa có assignee hoặc chưa cấu hình rate được đánh dấu thiếu dữ liệu, không âm thầm coi dự kiến 0 là đầy đủ.
- Tiền dùng SQL DECIMAL; phép tổng hợp không dùng float PHP làm nguồn tiền. Chart chỉ dùng số đã tổng hợp để trình bày.
- So sánh budget, actual toàn đời và estimate; phần tổng theo task/người dùng/phòng ban/role phải nêu rõ dùng phân loại hiện tại, chưa có snapshot phòng ban/role lịch sử.

### Quyền xem — đề xuất cần duyệt

- Permission mới `pm.costs.view`; seed tenant-scoped, idempotent, không xóa permission cũ.
- Admin xem toàn tenant; PM chỉ dự án mình quản lý. Backend kiểm tra cả danh sách, detail và polling để chống IDOR.
- HR/Employee chưa được cấp dashboard chi phí trong increment đầu; phạm vi HR tài chính cần chốt riêng trước khi mở, không suy diễn từ quyền audit.
- Không tạo endpoint công khai chứa đơn giá/chi phí. Mọi query lọc tenant từ session, không lấy tenant theo input.

### UI và cập nhật

- Route `pmcosts` tại modules/admin/, Smarty tại templates/admin/; menu theo quyền.
- KPI tổng giờ và chi phí, bảng project/task, biểu đồ chi phí theo thời gian và so sánh ngân sách/actual/estimate.
- Đề xuất Chart.js bản stable được xác minh lúc BUILD, pin phiên bản và đóng gói local; chỉ thêm sau khi plan này được duyệt. Không lấy bản latest động qua CDN.
- Dữ liệu có sẵn khi tải trang; AJAX nội bộ tại modules/ajax/ polling 60 giây, dừng khi tab ẩn, xử lý lỗi và giữ bộ lọc. Không WebSocket.
- Responsive, escaping, trạng thái empty/error/missing rate; bảng vẫn đọc được nếu chart không tải. JSON chart không chèn HTML hoặc script từ dữ liệu.

## File/layer dự kiến

- `classes/dao/pmcosts.class.php`: prepared aggregation queries, tenant/project scope.
- `classes/services/pmcostservice.class.php`: điều phối quyền, bộ lọc, estimate và DTO.
- `modules/admin/pmcosts.module.php`, module AJAX prefix pm, Smarty view và JS local.
- Seed permission versioned mới; migration schema chỉ tạo nếu EXPLAIN/schema chứng minh cần và đúng policy cộng thêm. Không chạy DB trước backup local.
- Tests service/query/scope và template; cập nhật docs/PROGRESS.md, README, docs/PORTFOLIO.md theo kết quả thật.

## Acceptance & VERIFY

1. Fixture có task/timesheet nhiều dự án/ngày, OT, nhiều rate và soft delete: actual khớp cost đã lưu; estimate có nhãn ngày định giá và cảnh báo thiếu rate.
2. Filter từ/đến inclusive, ngày không hợp lệ, empty range, pagination; aggregate không nhân đôi do JOIN membership/roles.
3. Admin/PM/cross-tenant boundaries, project_id trái scope, polling trả lỗi phù hợp; HR/Employee fail closed.
4. Chi phí DECIMAL với giá trị lớn; task soft delete không làm mất actual; project soft delete bị loại khỏi view thường.
5. Render chart/table, escaping, filter persistence và empty/error; kiểm thử local browser nếu có session phù hợp. Không ghi UAT PASS thay người dùng.
6. Regression Phase 2–5, PHP 8.3 lint, diff check và security review. Dùng fixture rollback, không dữ liệu giả tồn tại sau test.

## GitHub delivery

Commit theo chức năng hoàn chỉnh, danh tính Git local đúng tài khoản, staged files cụ thể;
README/portfolio chỉ cập nhật những gì đã VERIFY. Báo cáo commit/branch/checks/giới hạn rõ ràng.
Không push/merge/deploy nếu chưa có ủy quyền tương ứng. Plan đã được duyệt trong phiên này.
