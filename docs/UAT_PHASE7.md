# UAT Phase 7 — phân bổ nguồn lực

Chưa xác nhận UAT. Dùng local đã sao lưu, tài khoản Admin/PM/Employee/HR;
route `http://127.0.0.1:8088/admin.php?op=pmallocations`.
Chuẩn bị dự án thử có membership active, task được giao đúng nhân viên.

| Thao tác | Kết quả mong đợi | Kết quả thực tế |
| --- | --- | --- |
| Admin/PM mở lịch và lọc tuần | Tuần thứ Hai–Chủ nhật, đúng scope dự án | Chưa kiểm tra |
| Tạo/sửa/chuyển ngày/chuyển task và user/ẩn allocation | Cập nhật tải; lịch sử audit giữ trước/sau | Chưa kiểm tra |
| Tạo đúng 8 giờ/ngày, rồi thêm 1 giờ | Đúng ngưỡng không cảnh báo; vượt ngưỡng cảnh báo, vẫn cho lưu | Chưa kiểm tra |
| Tạo tổng tuần đúng/vượt weekly_limit_hours | Tổng tính cả các dự án khác; cảnh báo chỉ khi vượt | Chưa kiểm tra |
| Admin thêm capacity hữu hạn/vô hạn | Dùng đúng ngày; ngưỡng tuần lấy tại thứ Hai, riêng với OT | Chưa kiểm tra |
| Thêm capacity bao phủ/bị bao phủ/chung ngày biên | Bị từ chối overlap; sửa chính ID không tự overlap | Chưa kiểm tra |
| Allocation 09:00–12:00 và 11:00–13:00 / 08:00–13:00 / 10:00–11:00 | Đều cảnh báo trùng | Chưa kiểm tra |
| Allocation 09:00–12:00 và 12:00–14:00 / 07:00–09:00 | Liền kề không trùng | Chưa kiểm tra |
| Bỏ cả hai giờ / chỉ nhập một đầu | Cả hai trống: cảnh báo thiếu lịch chi tiết; một đầu: từ chối | Chưa kiểm tra |
| PM có user đang bận trên dự án ngoài scope | Tổng tải vẫn đúng; không lộ tên/ID/task/chi phí dự án ngoài scope | Chưa kiểm tra |
| Employee mở lịch / sửa ID người khác; HR mở lịch | Employee chỉ đọc của mình; IDOR bị từ chối; HR không có grant riêng | Chưa kiểm tra |
| Task/project soft delete sau khi phân bổ | Lịch cũ vẫn đóng góp tải và gắn nhãn lịch sử; không nhận phân bổ mới | Chưa kiểm tra |
| Xem gợi ý người còn trống | Chỉ membership active, capacity đủ; không tự đổi assignee hoặc ghi allocation | Chưa kiểm tra |
| Giữ tab hiện 60 giây, ẩn/hiện tab, mô phỏng mất mạng | Giữ filter; tab ẩn dừng; lỗi giữ dữ liệu cũ | Chưa kiểm tra |
| Mobile, audit allocation/capacity bằng Admin | Bảng cuộn trong khung; audit theo entity filter | Chưa kiểm tra |

Các list có giới hạn: lịch 1000 dòng (có cảnh báo/lọc), task chọn 1000, capacity Admin 200 mới nhất.
Không khẳng định capacity gợi ý là reservation hoặc phù hợp kỹ năng.
Người nghiệm thu/ngày: chưa xác nhận. Lỗi phát hiện: ghi bước tái hiện, role, URL;
không gửi mật khẩu/cookie/session. UAT không đồng nghĩa với quyền push/merge/deploy.
