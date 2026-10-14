# Bộ dữ liệu kiểm thử toàn bộ DeraSoft PM trên host

Ngày chuẩn bị: 14/10/2026. Tiền tố dữ liệu: `uat261014b`; mã phòng ban `UAT261014B`. Bộ này chỉ chứa dữ liệu giả, không có mật khẩu hoặc SQL ghi. Không thay code, không tạo DB và không thay quy tắc chung của tenant.

## 1. Các file và cách dùng

| File | Cách dùng |
|---|---|
| 01_IMPORT_NHAN_SU.xlsx | Upload tại trang Import nhân sự; 7 user mới, tất cả EMPLOYEE khi nhập |
| 02_IMPORT_SAI_DU_LIEU.xlsx | Chỉ Preview/Stage để kiểm tra lỗi; không Apply |
| 03_IMPORT_TRUNG_EMAIL.xlsx | Chỉ Preview/Stage để kiểm tra email trùng trong file; không Apply |
| BO_DU_LIEU_TOAN_WEB.xlsx | Sổ tham khảo gồm 9 sheet; không upload vào trang Import |
| KIEM_TRA_CHI_DOC.sql | Chỉ các SELECT đối chiếu user mẫu/thông báo/giờ; chạy tay trong phpMyAdmin nếu cần |

Không upload ZIP hoặc các file này vào FileZilla/public_html. Đây là bộ dữ liệu UAT, không phải gói code.

## 2. Trước khi nhập

1. Backup DB production bằng chức năng Xuất của phpMyAdmin, giữ bản sao code hiện hành.
2. Đăng nhập bằng Admin hiện có. Giữ tài khoản đó để quản trị, không đổi quyền của người thật.
3. Tìm username bắt đầu `uat261014b` và mã dự án `UAT261014B_A/B/C`. Nếu đã tồn tại từ lượt thử trước, dùng lại hoặc đổi đồng bộ tiền tố trong các file; không ghi đè/xóa bản cũ để thử lại.
4. Sổ tham khảo có ngày thử tại `Chi phi!B2`, mặc định 14/10/2026. Nếu thử ngày khác, đổi ngày này trong Excel và cho Excel tính lại; các ngày dự án/task/phân bổ/chấm công sẽ đổi theo. Ngày phải là ngày thao tác thực khi kiểm thử cron commit; không dùng --date với --commit.

## 3. Tạo phòng ban và import nhân sự

1. Tại Nhân sự > Phòng ban, tạo mã `UAT261014B`, tên `UAT Kiểm thử web`, trạng thái hoạt động.
2. Vào Import nhân sự, chọn `01_IMPORT_NHAN_SU.xlsx`.
3. Preview: cần thấy 7 dòng và không có lỗi. Nếu có lỗi, dừng và lưu thông báo; không sửa dữ liệu người thật để vượt kiểm tra.
4. Stage rồi Apply đúng một lần. Lưu kết quả từng dòng và ID các tài khoản mới.
5. Tài khoản import ban đầu inactive, vai trò EMPLOYEE. Dùng chức năng của app để đặt/lấy/reset mật khẩu và kích hoạt. Mật khẩu do bạn quản lý riêng, không gửi ảnh/log chứa mật khẩu. Điện thoại để trống; email example.com chỉ là dữ liệu mẫu, không dùng gửi thư.
6. Gán lại vai trò theo sheet Nhan su:
   - `uat261014b_pm1`, `uat261014b_pm2`: chỉ PM.
   - `uat261014b_hr`: chỉ HR.
   - `uat261014b_emp1`, `uat261014b_emp2`, `uat261014b_emp3`: EMPLOYEE.
   - `uat261014b_norole`: bỏ tất cả vai trò.
7. Không cấp ADMIN cho PM/HR/Employee để làm ca kiểm thử đạt. Kiểm tra login bằng username và email trên các tài khoản mẫu.

## 4. Tạo dữ liệu nghiệp vụ qua giao diện

Các màn hình dự án/task/phân bổ/chấm công hiện không có import chung. Nhập dữ liệu từ các sheet qua giao diện, không nhập workbook tham khảo vào Import nhân sự.

1. Chỉ cho EMP1/EMP2/EMP3, tạo đơn giá 100.000 VND/giờ từ D-14; capacity 8 giờ/ngày, 40 giờ/tuần từ thứ Hai của tuần thử. Nếu app có điều kiện ngày hiệu lực riêng, tuân theo app. Không đổi cấu hình chấm công toàn tenant.
2. Nhập 3 dự án theo Du an, gồm PM, ngày, ngân sách, khách hàng. `active` tương ứng trạng thái đang thực hiện.
3. Thêm thành viên theo Thanh vien. A do PM1 quản lý; B do PM2 quản lý. PM1 không thuộc B; PM2 không thuộc A. EMP1/EMP2 thuộc A, EMP2 thuộc B, EMP3 thuộc C.
4. Nhập 12 task theo Cong viec. `todo` là cần làm; `in_progress` là đang thực hiện. Task estimate0 là chưa có ước tính. Không nhập dữ liệu ID cố định; ghi ID host mới vào sổ.
5. Thử Kanban bằng PM1, lưu/reload; task done chỉ dùng dữ liệu mẫu rồi mở lại trước các ca phân bổ/chấm công.

## 5. Thử thông báo trước khi chấm công

1. Nhập allocation của EMP3 cho task C đang thực hiện, ngày D, 2 giờ, 08:00–10:00.
2. Chưa nhập timesheet hôm nay cho EMP3. User mới có việc và quyền sẽ tự được xét bởi cron --all-users; không sửa command để thêm ID của EMP3.
3. Task C có hạn D-7, D-8, D-400, hôm nay/ngày mai và PM tự phụ trách. Kiểm tra thông báo mới bằng ngày chạy thật: D-7 có, D-8/D-400 không; không suy từ row cũ.
4. Cron task chỉ xét sau08:00; nhắc chấm công sau17:30 thứHai–thứSáu, giờ Việt Nam. Trước thời điểm này `skipped_window` là đúng thiết kế.
5. Nếu lịch toàn store đang chạy, log candidates bao gồm các user khác. Dùng SELECT chỉ đọc lọc 7 user mẫu để đối chiếu riêng; không xóa thông báo cũ.
6. Mở chuông/list, đánh dấu đã đọc, tải lại và đối chiếu read_at. Chạy lặp không tạo trùng. Ca đổi hạn qua ngày kế cần thực hiện ngày kế, không giả lập ngày bằng --commit.

## 6. Phân bổ, giờ và chi phí

1. Nhập dòng đầu Phan bo (EMP1 4 giờ) và dòng đầu Cham cong (EMP1 5 giờ) trước. Allocation không overbooking, nhưng task actual5>estimate4 có một cảnh báo riêng.
2. Ngay tại mốc đó, đối chiếu ngân sách A: nếu rate100.000, ngưỡng thường8, hệ sốOT1, cost500.000 / budget2.000.000 =25%. Chưa thêm các entry khác trước ca này.
3. Thêm allocation thứ hai cùng task (1 giờ): task actual vẫn5, không nhân SUM và không nhân số cảnh báo.
4. Thêm allocation cuối (4 giờ): tổng allocationEMP1=9>capacity8, cảnh báo allocation riêng; task warning vẫn actual5/estimate4.
5. EMP2 chấm5 giờ taskestimate0,0.02 giờ taskestimate0.01 và1 giờ taskestimate1; phân bổ mỗi task1 giờ. Chỉ task0.01 có cảnh báo vượt, tổngtimesheetEMP2=6.02 giờ.
6. Ca OT dùng EMP1 ngàyD-1:8 giờ rồi2 giờ. Đọc snapshot thật của ngày trước khi nhập. Nếu max cho phép10 và thường8/hệ số1 thì regular8,OT2,cost1.000.000. Nếu max thấp hơn10, app phải chặn; ghi đúng kết quả đó, không sửa quy tắc tenant để đạt mẫu.
7. Trong sheet Chi phi, sửa các giả định theo snapshot thực để tính lại kỳ vọng. Snapshot cũ không được đổi theo đơn giá mới. Tất cả thao tác thay rate/ngày chỉ thực hiện trên user/entry UAT.

## 7. Rà toàn bộ web

Sheet Kich ban có 40 ca: đăng nhập/phân quyền, PM khác, no-role, CSRF/session, nhân sự/mật khẩu/điện thoại, import đúng/sai/trùng, ngày dự án/task, Kanban, progress, chấm công/OT/snapshot, phân bổ/D2, dashboard/chi phí/budget0, reports/Excel, audit/hồ sơ, responsive/toast/navigation, legacy404, notifications và user mới tự vào cron.

- `02_IMPORT_SAI_DU_LIEU.xlsx`: username quá ngắn, email sai định dạng, weekly_limit169. Preview/Stage cần báo lỗi theo dòng và từ chối cả file.
- `03_IMPORT_TRUNG_EMAIL.xlsx`: hai dòng trùng email. Preview/Stage phải từ chối cả file.
- Export nhân sự/dự án/báo cáo phải giữ bộ lọc và mở được bằng Excel. Chỉ đối chiếu dữ liệu mẫu và ngày tương ứng, không so tổng toàn tenant với tổng mẫu.
- Kiểm tra HR/PM/Employee bằng trình duyệt hoặc phiên riêng; không dùng quyền Admin để kết luận quyền các vai trò đó.
- Các ca tenant2/concurrency/qua ngày chỉ ghi PASS sau khi thực sự chạy. Nếu không có điều kiện, giữ CHƯA CHẠY với lý do.
- Ghi trạng thái và bằng chứng vào Kich ban; không gửi mật khẩu, cookie/token hoặc dữ liệu người thật trong ảnh.

## 8. Kết thúc

Giữ các bản ghi để đối chiếu. Nếu muốn dọn giao diện, dùng Ẩn dự án/Khóa tài khoản mẫu theo UI; không DELETE/TRUNCATE. Không sửa hoặc xóa dữ liệu production cũ. Gói dữ liệu này chưa được import hoặc kiểm thử chức năng trên host bởi agent; người dùng thực hiện UAT.

## Kiểm tra file đã hoàn tất local

4 workbook được tạo bằng artifact-tool; đã render 12 view cho toàn bộ 9 sheet tham khảo và 3 sheet import. Đọc lại bằng PhpSpreadsheet:7 nhân sự hợp lệ, header đúng6cột, khôngformula/macro/external links trong fileimport,9sheet/12task/40ca đềuCHƯACHẠY, ngàyExceltyped và4phép tínhgiờ/chi phí đúng. Lệnh: `.tools/php83/php.exe tests/pm_full_host_uat_files_smoke.php` PASS. Không kết nối DB/host, không thực hiện Stage/Apply.
