# Nghiệm thu local — Phase 5 và Phase 6

Trạng thái: chưa có xác nhận UAT của người dùng. Các test tự động không thay thế nghiệm thu.
Chỉ thao tác trên database local đã sao lưu; dùng bản ghi thử riêng, không sửa dữ liệu nghiệp vụ thật.
URL theo server đang dùng: `http://127.0.0.1:8088/`.

## Chuẩn bị

- Đăng nhập lần lượt bằng Admin, PM, HR và Employee đã được cấu hình role đúng tenant.
- Dự án thử có PM phụ trách; task active giao cho Employee, có estimated_hours và đơn giá hiệu lực.
- Chuẩn bị thêm dự án ngoài phạm vi PM, task thiếu assignee/rate và bản ghi cũ đã khóa.
- Ghi kết quả thực tế vào bảng bên dưới. Nếu gặp lỗi, ghi role, URL, bước tái hiện và kết quả;
  không gửi mật khẩu, cookie hoặc dữ liệu session.

## Phase 5 — chấm công và nhật ký

Mở `admin.php?op=pmtimesheets`, Audit Viewer tại `admin.php?op=pmaudit`.

| Bước | Kết quả cần thấy | Kết quả thực tế |
| --- | --- | --- |
| Employee mở chấm công khi đã đăng nhập | Hiện trang và lịch sử của mình, không Forbidden; chưa có task thì có thông báo phù hợp | Chưa kiểm tra |
| Ghi hai ca trong ngày hiện tại, 5 giờ và 5 giờ | Tổng 10 giờ; mặc định 8 giờ thường, 2 giờ OT; cost dùng snapshot đã lưu | Chưa kiểm tra |
| Sửa ca thứ hai còn 2 giờ | Tổng ngày 7 giờ, OT 0; cả ngày được tính lại | Chưa kiểm tra |
| Chuyển ngày hoặc ẩn một ca trong cửa sổ sửa | Ngày cũ/mới được tính lại; bản ghi ẩn không còn đóng góp giờ/cost | Chưa kiểm tra |
| Ghi tổng vượt 24 giờ/ngày | Báo lỗi và không lưu thay đổi một phần | Chưa kiểm tra |
| Sửa ngày hiện tại và hai ngày trước | Được sửa/xóa; work_date là ngày thứ nhất theo giờ Việt Nam | Chưa kiểm tra |
| Employee sửa bản ghi từ ba ngày trước | Đã khóa; không cho sửa/xóa; không có Submitted/Approved/Rejected | Chưa kiểm tra |
| Admin sửa bản ghi đã khóa | Sửa được; có audit thao tác sau khóa và OT được tính lại | Chưa kiểm tra |
| Admin xem audit, lọc thao tác sửa sau khóa | Thấy lịch sử trước/sau trong tenant | Chưa kiểm tra |
| PM/HR xem audit | PM theo dự án phụ trách; HR theo phòng ban hiện tại, chưa có phòng ban thì lịch sử của mình; không lộ dữ liệu tài chính | Chưa kiểm tra |
| Employee mở Audit Viewer | Bị từ chối truy cập | Chưa kiểm tra |

## Phase 6 — chi phí và biểu đồ

Mở `admin.php?op=pmcosts`. Polling nội bộ: `pm_ajax.php?op=pmcosts`.

| Bước | Kết quả cần thấy | Kết quả thực tế |
| --- | --- | --- |
| Admin mở dashboard | Thấy dự án active trong tenant, KPI/bảng/biểu đồ hoặc trạng thái trống | Chưa kiểm tra |
| PM mở dashboard và chọn dự án | Chỉ thấy dự án phụ trách; project_id ngoài scope bị từ chối ở page và polling | Chưa kiểm tra |
| HR/Employee mở page và polling | Bị từ chối; quyền audit không cấp quyền chi phí | Chưa kiểm tra |
| Lọc từ/đến bao gồm ngày đã ghi công | Actual đúng cost đã lưu, giờ/OT đúng; lifetime được ghi nhãn riêng | Chưa kiểm tra |
| Đổi đơn giá hiện hành | Actual cũ giữ nguyên; estimate thay đổi theo rate tại ngày định giá, có nhãn ước tính | Chưa kiểm tra |
| Task thiếu assignee/rate | Hiện cảnh báo, không trình bày tổng thiếu dữ liệu như một ước tính đầy đủ | Chưa kiểm tra |
| Tập dữ liệu thử có nhiều currency | Không cộng chung tiền; chi phí không khả dụng kèm cảnh báo, giờ vẫn có; không tự quy đổi | Chưa kiểm tra |
| Actual/estimate khác VND | Không so trực tiếp với budget VND | Chưa kiểm tra |
| Task lịch sử đã soft delete | Actual vẫn đóng góp khi timesheet chưa ẩn; có nhãn lịch sử | Chưa kiểm tra |
| Project đã soft delete | Không xuất hiện trong dashboard thông thường | Chưa kiểm tra |
| Xem nhóm người/phòng ban/role | Có cảnh báo phân loại hiện tại chưa có snapshot lịch sử | Chưa kiểm tra |
| Giữ tab hiện ít nhất 60 giây, rồi chuyển tab | Polling giữ filter; tab ẩn dừng polling; quay lại tiếp tục | Chưa kiểm tra |
| Mất kết nối khi polling | Báo lỗi, giữ số liệu cũ; không xóa bảng đang xem | Chưa kiểm tra |
| Mobile và chart asset không tải | Không tràn toàn trang; bảng vẫn đọc được khi chart không hoạt động | Chưa kiểm tra |

Không cần tự thay đổi currency qua SQL để UAT; có thể dùng fixture trên database thử riêng.
Guard mixed-currency đã có test tự động rollback trong `tests/pm_costs_smoke.php`.

## Xác nhận

Người kiểm tra / ngày: chưa xác nhận.
Lỗi còn mở: ghi theo kết quả nghiệm thu thực tế.
UAT PASS chỉ ghi sau khi người dùng xác nhận; không đồng nghĩa với quyền push/merge/deploy.
