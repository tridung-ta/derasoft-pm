# PLAN giảm nhắc Phase12 — 14/10/2026
CHỈ ĐỀ XUẤT, CHỜ DUYỆT. Không sửa hành vi ứng dụng trong lượt này.

## 1. Bốn câu trả lời từ code hiện tại
(a) PmNotifications::eligibleSql('timesheet'): u.status=1; quyền pm.timesheets.own qua role active/ADMIN; không có timesheet chưa ẩn của ngày. Không yêu cầu allocation/task. Người chưa được phân việc vẫn được chọn nếu có quyền. Test DB xác nhận user không-assignment được chọn.
(b) UNIQUE(store_id,recipient_id,kind,entity_id,event_date) có ngày và loại nhắc. Với task/assignee/PM/deadline không đổi: tối đa một thông báo cho mỗi người nhận/ngày, tối đa hai/task/ngày nếu assignee và PM khác nhau. Không có trần tổng theo thời gian. Khi đổi deadline khiến kind khác hoặc đổi người nhận, có thể thêm row cùng task/ngày; không thể tuyên bố trần hai row/ngày cho mọi lịch sử.
(c) Task có due_date<=ngày mai, chưa done/ẩn, dự án chưa ẩn, recipient hợp lệ được xét không giới hạn tuổi quá hạn. LIMIT100 là phân trang, không là trần toàn job. Lần đầu tạo một row mỗi cặp task-recipient hợp lệ, không backfill một row cho mỗi ngày đã qua. Fixture:9 row tổng,4 quá hạn (hai task cũ8/400 ngày x hai người),3 hômnay,2 ngàymai.
(d) PM_REMINDERS_ENABLED cần cho cả web/CLI --commit; web header không truy vấn notification khi OFF, route404. Dry-run CLI không cần ON. Cờ host vẫn chưa bật.

## 2. Đề xuất cần chọn
1. Task quá hạn: chỉ due_date trong [D-7,D-1], cộng hạn hômnay/ngàymai. Tham số giới hạn cố định7 trước; không schema mới. Những task cũ hơn vẫn hiển thị trong dashboard/task list, chỉ không phát nhắc cron. Áp cùng điều kiện ở candidate/INSERT recheck/list/unread, giữ row cũ.
2. Chấm công: bổ sung một trong hai điều kiện hoạt động:
   - Có allocation chưa ẩn, work_date=D, hours>0, task/dự án chưa ẩn và task chưa done.
   - Có task chưa done/ẩn được giao và membership active, dự án chưa ẩn; đang doing và start_date không ở tương lai, hoặc có start_date<=D cùng due_date NULL hoặc due_date>=D. start_date NULL của backlog todo không đủ điều kiện. Cần người dùng duyệt chính xác định nghĩa “task mở trong ngày”; không âm thầm coi mọi backlog là làm hômnay.
   Mọi điều kiện active/quyền/zero-timesheet hiện có vẫn giữ. Không suy ra nghỉ phép/vắng/thiếu8h.
3. --user-ids=ID1,ID2: tùy chọn cho dry-run và commit;1–100 ID dương hợp lệ, unique, phải thuộc tenant đang chọn; ID sai/foreign fail trước write. Lọc ở candidate và INSERT recheck, không chỉ preview. Không có option giữ phạm vi toàn tenant chỉ sau rollout duyệt. Lần commit đầu trên host bắt buộc danh sách hẹp nhưng option hiện CHƯA BUILD.
4. Giới hạn7 ngày vẫn có nhắc lại mỗi ngày. Nếu muốn tối đa một nhắc quá hạn/task/recipient suốt vòng đời, hoặc gom digest theo người/ngày, cần chọn thêm chính sách, không đổi unique/key tự ý.

## 3. VERIFY sau duyệt
D-7 có, D-8 không; today/tomorrow; deadline thay đổi giữa select/insert; row cũ không leak/count sai; allocationzero/hidden/done; doing/nullstart/futurestart/taskdatewindow; allocated nhân sự khác assignee; no-role/inactive/tenant foreign; user-ids duplicate/malformed/foreign; hẹp scope qua dry-run/commit và hai process. DB loopback/derasoft_pm_local sau backup, users exact-schema mirrors và checksum như lượt hiện tại.
Không migration mới dự kiến. Giữ011/schema,OT/cost/trạng thái task và dữ liệu cũ.
