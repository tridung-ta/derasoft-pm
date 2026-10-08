# PLAN D2 — Cảnh báo task vượt giờ ước tính

Ngày: 08/10/2026. Trạng thái: ĐÃ DUYỆT BUILD; BUILD/VERIFY hoàn tất local ngày 08/10/2026.

## Nghiệp vụ đề xuất

- actual_hours = SUM(dc_pm_timesheets.hours) của tất cả bản ghi chưa soft-delete của task, cùng store_id và project_id, trên toàn bộ thời gian. Bao gồm giờ thường và OT một lần bằng hours; không cộng thêm ot_hours.
- Cảnh báo chỉ khi estimated_hours > 0 VÀ actual_hours > estimated_hours, so sánh DECIMAL chính xác, không dùng float. Bằng ngưỡng không cảnh báo. estimated_hours = 0 là Chưa có ước tính và không bao giờ sinh cảnh báo vượt, dù actual_hours lớn.
- Giao diện: tại nơi hiển thị ước tính task, estimate 0 hiển thị “Chưa có ước tính”, không hiển thị “0 giờ”; áp dụng nhất quán cả HTML và dữ liệu được render lại qua polling.
- Hạn chế đã chấp nhận ngày 08/10/2026: schema không phân biệt được “chưa ước tính” với “cố tình 0 giờ”. Muốn phân biệt phải thay đổi schema và được duyệt riêng. Không migration hoặc chuyển đổi dữ liệu trong D2.
- Chỉ cảnh báo, không chặn lưu allocation/timesheet, không đổi OT, rate_snapshot/cost hay lịch sử. Sửa/ẩn timesheet làm tổng thay đổi ở lần tải/polling tiếp theo.
- Tách dữ liệu task_estimate_warnings khỏi warnings hiện tại (ngày/tuần/trùng giờ). Một task chỉ xuất hiện một lần dù có nhiều allocation trong tuần.

## Quyền và phạm vi

- Dùng quyền pm.allocations.view, tài khoản active và gate role hiện có; chỉ Admin hoặc PM nhận tổng actual toàn task. Admin trong tenant; PM chỉ dự án mình quản lý, kể cả khi có nhiều role phải áp dụng ưu tiên Admin giống code hiện tại.
- Nhân viên không nhận tổng task (có thể chứa giờ của người khác), chỉ giữ cảnh báo allocation hiện có. HR/no-role bị từ chối như hiện tại. Không lấy một tổng bị giới hạn theo người rồi gọi là tổng task.
- Cảnh báo dashboard áp dụng các task gắn với các dòng allocation đã được phép hiển thị trong tuần/bộ lọc đang chọn. Bộ lọc user chọn task qua allocation, không giới hạn các timesheet dùng tính tổng task; ghi rõ nhãn tổng giờ thực tế toàn task. Nếu dashboard bị cắt 1000 dòng thì cảnh báo cũng chỉ cho các task hiện ra và giữ chỉ báo truncated, không tuyên bố bao phủ toàn bộ dự án.
- Task hoặc dự án đã ẩn: chỉ hiển thị cảnh báo lịch sử khi allocation của nó còn xuất hiện hợp lệ theo quyền lịch sử hiện có. Gắn nhãn Đã ẩn/Lịch sử, không đưa vào danh sách tạo allocation, không khôi phục/sửa/xóa dữ liệu. Không hiển thị task đã ẩn nếu không có dòng lịch sử trong phạm vi tuần đang xem.
- Tổng giờ gồm mọi nhân sự của task hợp lệ trong cùng tenant/dự án; không trả danh sách người, mô tả chấm công, đơn giá/chi phí hay dữ liệu dự án ngoài quyền.

## Thay đổi dự kiến

1. DAO pmallocations: truy vấn aggregate theo tập ID task đã được scope ở service; mọi JOIN ghép store_id và project_id. Prepared placeholders, không ghép input. Query theo tập, không N+1; SUM không bị nhân bởi JOIN allocation.
2. PmAllocationService dashboard: thêm task_estimate_warnings với task_id, project_id, tên đã được phép xem, estimated_hours, actual_hours, excess_hours, historical. Có service method kiểm tra task/scoping nội bộ; không endpoint công khai nhận ID tùy ý.
3. Controller/template/polling allocation: assign riêng, khung Cảnh báo giờ thực tế vượt ước tính, tách với overbooking. Dùng impeccable trước BUILD giao diện, giữ Inter, màu trung tính, viền rõ; escape tên. Endpoint modules/ajax/pmallocations.module.php hiện trả dashboard trực tiếp: thêm trường riêng vào dashboard cho cả HTML và polling; giữ suggestions không đổi. Cập nhật/xóa cảnh báo khi polling, gồm trạng thái rỗng.
4. Không migration, không sửa dữ liệu cũ. Không thêm hard-cap giờ hay thay đổi endpoint ghi.

## VERIFY bắt buộc sau duyệt

- Dưới/bằng/vượt estimate dương; estimate 0 với actual lớn không cảnh báo; estimate 0.01 với actual 0.02 có cảnh báo; estimate 1.00 với actual 1.00 không cảnh báo. So sánh DECIMAL chính xác, không float. Nhiều nhân sự, nhiều ngày, OT không đếm đôi; không timesheet; timesheet đã ẩn.
- Nhiều allocation cùng task không nhân SUM/không lặp cảnh báo; sửa/ẩn timesheet khiến cảnh báo cập nhật hoặc biến mất.
- Admin/PM/nhân viên/HR/no-role; khác tenant có ID liên quan; PM dự án không phụ trách; project/user filters và giới hạn dashboard.
- Task/dự án đã ẩn có lịch sử: nhãn đúng, chỉ đọc, không thêm lựa chọn tạo mới. Không lộ giờ/người/dự án ngoài quyền.
- Regression overbooking tuần/ngày, overlap, OT và cost snapshot không thay đổi; fixtures temporary/rollback, persistent state unchanged.
- HTTP và UI/polling desktop/mobile, JS tắt, trạng thái rỗng/lỗi; chưa chạy thì ghi CHƯA CHẠY. Review bảo mật và cập nhật TEST_REPORT_FULL/PROGRESS sau VERIFY.

## Cổng phê duyệt

Người dùng đã chọn (a): estimate 0 là Chưa có ước tính. PLAN đã cập nhật quyết định và VERIFY bổ sung; người dùng đã duyệt lần cuối và yêu cầu BUILD. Không push/merge/deploy.

## Điều kiện duyệt bổ sung — 08/10/2026

Đối chiếu trước BUILD: schema local estimated_hours DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00; form thêm mặc định 0 và required. Người dùng chọn (a), giữ schema và luồng lưu hiện có, coi 0 là Chưa có ước tính. Dữ liệu persistent local đã kiểm tra: tổng 0 task. Bằng chứng read-only .local/d2_estimate_schema_counts.json. Khi VERIFY dùng fixture tạm, xác nhận cleanup và so sánh trạng thái dữ liệu persistent trước/sau, không ghi fixture vào dữ liệu thật.

VERIFY được bổ sung theo yêu cầu người dùng: chuyển .local/full_task_overload_probe.php thành test cố định trong tests (chỉ thực hiện sau mở cổng BUILD); 5 giờ actual/4 giờ estimate đúng 1 task_estimate_warnings, allocation warnings rỗng; PM không nhận task/tên dự án PM khác; polling Employee/HR/no-role không có tổng task; nhiều allocation không nhân SUM; DECIMAL không float; thêm query mới vào EXPLAIN, không thêm index; cảnh báo có nhãn/văn bản, không chỉ màu. Giao diện ghi rõ task đang được phân bổ trong tuần này. Chỉ sau test thực chạy PASS mới đổi 7.4 sang PASS, giữ các mục khác.

Trạng thái VERIFY bổ sung: **PASS** theo lệnh và output trong docs/D2_VERIFY_20261008.md. Mục 7.4 đã đổi sang PASS; giữ nguyên các mục khác.
