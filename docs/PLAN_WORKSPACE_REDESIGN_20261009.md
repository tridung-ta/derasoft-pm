# Workspace redesign — BUILD local đã duyệt

Người dùng duyệt tự quyết thiết kế/BUILD local, giữ nghiệp vụ/quyền/dữ liệu/framework. Khungsidebar thống nhất vàtopbaraccount; toast/busy/confirmation; dashboard metrics theoquyền; bảngsticky/filterremember/empty/export; Kanban drag trênAJAX, button fallback/filter/overdue. Khôngmigration/thưviệnlớn/production. Bản khungpreview đang kiểmtra, cácchứcnăng còn lại sẽVERIFY riêng, khôngtựPASS.

## Dashboard theo vai trò — BE cần duyệt riêng
Theo yêu cầu mới chỉ được tự quyết UI, các dữ liệu tổng hợp PM/Admin dưới đây chưa BUILD.
- PM: tổng hợp dự án trong quyền xem có task quá hạn; cảnh báo phân bổ trong tuần hiện tại; tỷ lệ chi phí thực tế toàn đời/ngân sách chỉ khi đơn vị tiền tệ đồng nhất và ngân sách > 0. Dữ liệu thiếu/khác tiền tệ hiển thị không khả dụng, không giả 0.
- Admin: cảnh báo dữ liệu từ service có sẵn (đơn giá thiếu/chi phí không đồng nhất, overbooking), dẫn đến màn hình xử lý tương ứng; chỉ hiển thị khi có permission của màn hình đó.
- Chỉ BE đọc dữ liệu: mở rộng dashboard service/controller assign, tái sử dụng service scope tenant/role hiện có. Không migration, không thay bảng, không đổi logic OT/cost/allocation, không endpoint ghi mới.
- VERIFY sau khi duyệt: fixture theo Admin/PM/Employee/HR/no-role; không lộ task/dự án tenant khác; tỷ lệ không chia 0/không trộn currency; UI lỗi/không có dữ liệu/mobile; ghi rõ test DB nào không chạy theo yêu cầu người dùng.

## Cập nhật 09/10/2026 — tích hợp BE đã được yêu cầu
Người dùng yêu cầu bắt đầu đồng bộ frontend với backend và chuẩn bị gói để tự triển khai. Dashboard PM/Admin đã BUILD theo PLAN_WORKSPACE_INTEGRATION_20261009.md; dữ liệu thật/DB test do người dùng nhận kiểm thử thủ công trên host. Không còn chờ duyệt BE cho đúng phạm vi đọc dữ liệu này; không có quyền push/merge/deploy hay thay schema.
