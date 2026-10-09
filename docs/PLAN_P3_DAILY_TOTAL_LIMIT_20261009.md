# PLAN P3 — tách giới hạn tổng giờ/ngày, 09/10/2026

Trạng thái: người dùng duyệt lựa chọn B; PLAN kỹ thuật đã được người dùng duyệt; BUILD local đang VERIFY. Chỉ local; migration010 local sau backup đã được duyệt riêng. Không push/merge/deploy. Áp dụng feature-development cho phạm vi service/controller/test; impeccable và playwright-cli khi BUILD phần form.

## Nguồn và hành vi

Yêu cầu gốc: không ghi vượt số giờ đã lưu trong quy tắc. Người dùng chọn B ngày09/10: tách ngưỡng giờ thường khỏi trần tổng giờ/ngày, giữ OT. Ví dụ giờ thường8, tối đa12: tổng10 được lưu thành8 thường +2OT; tổng12 được phép;12.01 bị từ chối. Tổng tính SUM(hours) của mọi dòng chưa ẩn của cùng store_id/user_id/work_date, trên mọi dự án/task; không cộng ot_hours lần nữa. Admin cũng không được vượt trần.

## Lưu cấu hình và snapshot — đề xuất cần duyệt

1. Thêm setting_key max_total_hours_per_day vào dc_pm_system_settings theo store_id, dùng cơ chế key/value hiện có; không thêm cột cho settings. Mặc định24.00 tương thích trần cũ, không mặc định12 chỉ từ ví dụ. Admin cấu hình số thập phân tối đa2 chữ số; 0 < standard_hours_per_day <= max_total_hours_per_day <=24.00. ot_multiplier giữ nguyên.
2. Cần migration versioned ADD COLUMN max_total_hours DECIMAL(4,2) NOT NULL DEFAULT 24.00 vào dc_pm_timesheet_days để snapshot độc lập. Kiểm tra tồn tại trước ADD; không UPDATE/backfill thủ công, đổi engine hoặc xóa cột. Số migration chọn sau kiểm kê version hiện có, không ghi đè file. Chưa được chạy SQL theo lượt duyệt lựa chọn B.
3. Snapshot ngày mới lấy cả standard_hours, ot_multiplier và max_total_hours trong cùng transaction. Ngày đã có ledger giữ24.00 từ giá trị mặc định, không tự đổi theo cấu hình mới, không thay số giờ/cost/rate_snapshot cũ. Đổi quy tắc chỉ tác động ledger được tạo sau đó; cả ngày quá khứ chưa có ledger cũng dùng cấu hình lúc tạo. Không sửa snapshot ngày cũ qua form này.
4. Ledger rỗng sau ẩn hết dòng vẫn giữ snapshot; không xóa để lấy cấu hình mới. Runtime mới cần cột đã tồn tại: kiểm tra phụ thuộc/migration trước phát hành, không âm thầm bỏ kiểm tra giới hạn khi thiếu cột. Rollback ứng dụng giữ cột và toàn bộ dữ liệu.

## Service, chuyển ngày và concurrency

- PmTimesheetService::settings/saveSettings/lockDay/recalculate: đọc/lưu setting và snapshot mới; kiểm tra tổng với snapshot trong transaction trước commit. Quy đổi DECIMAL sang số nguyên phần trăm giờ bằng parse chuỗi, không float cho so sánh ngưỡng/tổng. Giới hạn đầu vào mỗi dòng vẫn <=24 nhưng tổng phải <= trần của ngày.
- Giữ khóa ledger SELECT FOR UPDATE theo store_id/user_id/work_date hiện có. Khi chuyển ngày, khóa cả ngày nguồn và đích theo thứ tự ngày tăng dần; đọc lại bản ghi sau chờ khóa, giữ guard chống chuyển ngày đồng thời và kiểm tra editable sau khóa.
- Tính lại regular/OT/cost cả hai ngày bằng snapshot riêng. Đích vượt trần: rollback toàn thao tác, nguồn/đích/cost/audit không đổi. Ẩn dòng giải phóng giờ cho ngày đó, vẫn giữ ledger. Không thêm named lock toàn database.
- Hai writer cùng ngày phải tuần tự hóa trên ledger, kể cả lần đầu ledger chưa tồn tại qua unique tenant/user/date; kiểm tra lại tổng sau chờ. Không dựa vào tổng đọc trước transaction. Khác người hoặc tenant không dùng cùng ledger lock.
- Giữ CSRF, permission/ownership, scope status403/404, lỗi nhập liệu HTTP200 và redirect thành công hiện có. Thông báo vượt trần nêu tổng dự kiến và giới hạn ngày, không chứa thông tin nhân sự khác. Audit settings/snapshot giữ actorID và dữ liệu nghiệp vụ, không PII định danh.

## UI

Form quy tắc: nhãn riêng Giờ thường/ngày và Tổng giờ tối đa/ngày (gồm OT); giải thích quy tắc mới chỉ áp dụng ngày chưa có snapshot. Trang chấm công hiển thị trần áp dụng của ngày đang chọn, không lấy cấu hình mới thay snapshot cũ. Backend là nguồn xác thực; JavaScript chỉ hỗ trợ. Giữ Inter, trung tính, viền/nút rõ; không redesign.

## VERIFY phải thực chạy

- Giờ thường8/trần12: tổng10 =>8 thường+2OT;12 được;12.01 bị chặn. Nhiều task/dự án cùng ngày cộng một lần; dòng ẩn/tenant khác/người khác không tính. Các biên DECIMAL0.01, ngưỡng bằng nhau, tổng trên24 và config không hợp lệ.
- Thay cấu hình sau khi tạo ngày: snapshot/chi phí cũ giữ nguyên; ngày mới dùng trần mới. Ngày cũ chưa có ledger và ledger rỗng có hành vi đúng như mục3/4; không nâng PASS nếu chưa chạy.
- Sửa tăng/giảm, ẩn và chuyển ngày: tính lại đúng cả ngày; đích quá trần rollback nguyên trạng hai ngày và audit; Admin không bypass trần. Giữ hạn khóa3ngày và recheck qua midnight.
- Concurrency hai connection thật: cùng ngày mới và ngày có ledger, hai request riêng hợp lệ nhưng cộng vượt trần chỉ một thành công; hai ngày chuyển ngược không đảo thứ tự lock. Dùng database test riêng/copy local đã sao lưu và được duyệt, tuyệt đối không INSERT rồi DELETE fixture trên bảng thật hiện có. Nếu môi trường chưa được duyệt thì giữ concurrency CHƯA CHẠY, không thay bằng temporary table không chia sẻ connection.
- Rà và cập nhật tests/pm_timesheets_smoke.php (6+4 với standard8: vẫn8+2 khi max>=10; trần24 giữ control), pm_timesheet_window_smoke, pm_account_audit_smoke và các fixture settings/snapshot/HTTP/tenant matrix/reports/costs. Không đổi kỳ vọng OT cũ thành từ chối10h khi fixture max24. Thêm fixture trần12 riêng; không xóa assertion cost snapshot, audit hoặc tenant.
- Regression đầy đủ, prepared query EXPLAIN cho query mới nếu có, kiểm tra schema tồn tại và rollback migration trên DB test được duyệt; browser form/validation/no-JS. Checklist5.1/5.2 chỉ đổi sau đối chiếu tiêu chí và kết quả thật, không đánh PASS theo PLAN.

## Điểm chờ duyệt

Duyệt PLAN bao gồm mặc định24.00, snapshot ngày cũ24.00/ngày mới theo cấu hình, Admin không bypass, ADD COLUMN và chính sách ngày quá khứ chưa có ledger. Việc chạy migration local/copy DB để test concurrency cần xác nhận môi trường riêng; không ngầm cấp quyền production. Không BUILD trước khi người dùng duyệt PLAN này.
