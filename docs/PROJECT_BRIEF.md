# Project Brief — Hệ thống Quản lý Dự án & Chấm công

## Mục tiêu

Tái sử dụng nền DeraSoft hiện có để xây dựng một hệ thống nội bộ quản lý nhân sự dự án, nhiệm vụ, chấm công, chi phí và phân bổ nguồn lực. Giao diện public cũ được ẩn; Admin hiện tại, session, DAO, Smarty và các tiện ích phù hợp được tái sử dụng có chọn lọc.

## Người dùng và phạm vi quyền

- Admin: quản trị toàn hệ thống.
- Project Manager: quản lý các dự án được giao và thành viên thuộc dự án đó.
- HR: quản lý thông tin nhân sự, vai trò, đơn giá và báo cáo liên quan.
- Nhân viên: xem công việc được giao, ghi nhận thời gian và xem dữ liệu của mình.

## Chức năng chính

1. Đăng nhập bằng email/tài khoản và mật khẩu an toàn; nhiều vai trò trên một người dùng; permission và kiểm tra ownership ở backend.
2. Quản lý nhân sự, phòng ban, trạng thái, vai trò và đơn giá giờ công có ngày hiệu lực.
3. Quản lý dự án, thành viên, task, độ ưu tiên, hạn hoàn thành và Kanban todo/doing/done.
4. Chấm công theo dự án/task/ngày/ca; tách giờ thường và OT; lưu đơn giá và chi phí tại thời điểm ghi nhận.
5. Audit đầy đủ các thao tác quan trọng.
6. Tổng hợp chi phí theo task, dự án, phòng ban và vai trò; so sánh thực tế với dự kiến; biểu đồ trực quan.
7. Phân bổ nguồn lực theo tuần, cảnh báo vượt giới hạn và gợi ý người còn khả dụng.
8. Báo cáo có bộ lọc; import nhân sự và export báo cáo bằng Excel.
9. Giao diện responsive, dễ dùng trên desktop/tablet/mobile, ưu tiên ít JavaScript phức tạp.

## Ràng buộc

- Không xóa chức năng legacy; chỉ ẩn những phần không còn hiển thị.
- Không thay thế framework DeraSoft.
- Database phải chuẩn hóa hợp lý, có index và migration cộng thêm an toàn.
- Không thao tác production khi phát triển; deploy thủ công chỉ thực hiện sau backup, UAT và phê duyệt.
- Không lưu secret hoặc file cấu hình thật trong Git.

## Tiêu chí thành công

- Bốn nhóm vai trò chỉ truy cập đúng dữ liệu và hành động được phép.
- Luồng project → task → timesheet → cost hoạt động nhất quán và có audit.
- Công thức OT, rate snapshot, chi phí và overbooking khớp dữ liệu kiểm thử thủ công.
- Danh sách lớn có tìm kiếm/phân trang; query trọng yếu được kiểm tra và có index phù hợp.
- Import/export có thông báo lỗi theo dòng, không làm mất dữ liệu hợp lệ.
- Có hướng dẫn test, migration, deploy và rollback cho từng release.
