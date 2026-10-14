# PLAN — Cron nhắc việc PM, 14/10/2026
Trạng thái: PLAN, CHỜ DUYỆT. Chỉ khảo sát source/DDL local; chưa BUILD, chưa chạy DB, chưa thiết lập cron host.

## 1. Bằng chứng và giới hạn
- cron/editorial-rss-import.php: CLI-only, mặc định dry-run, thuộc module nội dung; store_id cố định trong script này. Không dùng lại script đó cho PM.
- includes/constant.inc.php có APP_KEY legacy; không dùng khóa này mở endpoint PM công khai.
- classes/dao/pmdashboard.class.php::ownHours/ownTasks đã có truy vấn tenant, soft-delete và membership cho dashboard. classes/services/pmdashboardservice.class.php có permission gates; tái sử dụng quy tắc, không gọi service theo một actor Admin để gửi dữ liệu cho mọi người.
- Migration001–010 chưa có bảng thông báo PM. Quét tên CREATE TABLE trong dump local không tìm thấy bảng cron/notification/schedule có các từ đó trong tên; không kết luận DB host không có scheduler hoặc bảng tên khác. Không đọc dữ liệu host.
- Bảng task có due_date nullable, assignee_id nullable, trạng thái done và soft-delete. Cron không tự chuyển task sang done hay quá hạn; quá hạn là trạng thái hiển thị suy ra từ ngày.
- Workspace đang có thay đổi tài liệu skill và .tmp/ của công việc khác: không gom vào tính năng này.

## 2. Phạm vi đề xuất cần duyệt
Chỉ hai chức năng:
1. Nhắc chưa chấm công hôm nay, lúc 17:30 giờ Việt Nam, thứ Hai–thứ Sáu: tài khoản active có quyền pm.timesheets.own, chưa có timesheet chưa-ẩn của ngày đó. Không nhắc người đã ghi một phần; không suy ra thiếu 8h/vi phạm. Đây là nhắc tự nguyện, không chấm vắng, không tính nghỉ phép. Admin tắt riêng job nếu lịch này không phù hợp; giai đoạn này không xây lịch nghỉ/lễ.
2. Nhắc task hạn hôm nay/ngày mai và đã quá hạn, lúc 08:00 hằng ngày: task/dự án chưa ẩn, task khác done, due_date có giá trị. Recipient là assignee active còn membership và quyền projects.view/tasks.view; PM quản lý dự án active có quyền tương ứng. Nếu assignee cũng là PM, deduplicate một recipient. Không gửi toàn bộ task cho HR/no-role/Admin không liên quan; task chưa có assignee vẫn nhắc PM hợp lệ.
Chỉ thông báo trong website; không email/SMS/OTP, không sửa hours/OT/cost/task status, không dọn/xóa dữ liệu cũ.
Tên thông báo: “Bạn chưa ghi chấm công hôm nay”, “Công việc đến hạn hôm nay”, “Công việc đến hạn ngày mai”, “Công việc đã quá hạn”.
Thời gian lấy từ Asia/Ho_Chi_Minh; không dựa timezone mặc định host.

## 3. Lưu trữ đề xuất — cần duyệt bảng mới
Migration011_create_pm_notifications.sql, CREATE TABLE IF NOT EXISTS dc_pm_notifications (InnoDB utf8mb4), không ALTER/DROP bảng cũ.
Cột dự kiến:
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY;
store_id BIGINT UNSIGNED NOT NULL;
recipient_id INT UNSIGNED NOT NULL;
kind VARCHAR(32) NOT NULL;
entity_id BIGINT UNSIGNED NOT NULL DEFAULT 0 (task ID hoặc 0 cho nhắc ngày);
event_date DATE NOT NULL (ngày tạo lời nhắc);
read_at DATETIME NULL;
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP.
UNIQUE(store_id,recipient_id,kind,entity_id,event_date); INDEX(store_id,recipient_id,read_at,id).
Không lưu tên/email/điện thoại/task title/link hay mật khẩu trong bảng này; render nội dung từ dữ liệu hiện tại và kiểm tra quyền lại. Ngày tạo lưu theo convention thời gian PM, CLI và SQL NOW phải đồng nhất timezone kiểm tra trước BUILD.
Không thêm job config vào bảng; cấu hình CLI/host nằm ngoài repo runtime secrets: store_id whitelist bắt buộc, job enable, giờ/lịch cố định nói trên. Không tự chọn tenant1 hoặc quét mọi tenant.
Đây là migration mới có phụ thuộc users/roles/projects/tasks/timesheets. Rollback: tắt cron, phục hồi code; để bảng mới nguyên vẹn. DDL tồn tại phải kiểm tra schema thật, không coi IF NOT EXISTS là đủ chứng minh đúng cấu trúc.
Nếu chưa áp dụng011: runtime cũ tiếp tục hoạt động; notification UI disabled, cron --commit fail rõ trước ghi, không làm hỏng trang PM. Chỉ bật sau schema preflight.

## 4. Thiết kế code dự kiến
- cron/pm-reminders.php: CLI-only; HTTP404 trước bootstrap/DB. Mặc định dry-run; --commit mới tạo lời nhắc. --store-id bắt buộc, --job=timesheet|task|all; validate trước DB. --date chỉ cho dry-run kiểm tra, không cho commit backdate. --help không kết nối DB.
- classes/dao/pmnotifications.class.php + ObjectInfo theo convention DAO; candidate queries prepared, store_id trong mọi join/read/write.
- classes/services/pmreminderservice.class.php: clock injectable để test; job window, chunk/keyset và lưu cursor trong bộ nhớ, dedup, lock. Lịch cron hệ điều hành gọi mỗi5phút; job đủ giờ được xử lý theo ngày, chạy lại không trùng. Nếu host ngừng cả ngày thì không tạo bù thông báo chấm công sai cho ngày cũ. Task deadline tính lại theo ngày hiện tại.
- classes/services/pmnotificationservice.class.php: list/unread/mark read theo actor session. Nếu task done/ẩn hoặc đổi assignee/quyền, thông báo cũ không hiện chi tiết hay tính unread; không xóa row. Validate source tại mỗi read. Dùng truy vấn điều kiện bounded, không N+1 lấy toàn bộ backlog.
- modules/admin/pmnotifications.module.php + templates/admin/pm-notifications.tpl.html; chuông/đếm trên shared header, dropdown tối đa5 và link Xem tất cả/pagination20. Dùng route admin đang có, không mở API legacy mới. CSRF cho đọc/đánh dấu, actor/store lấy session; không nhận recipient từ client.
- Tích hợp unread vào page render; nếu thêm polling phải duyệt cụ thể endpoint/permissions, chưa đề xuất mặc định. Không gửi toast lặp mỗi lần load; toast hiện hành chỉ phản hồi thao tác.
- UI áp dụng impeccable/Inter, icon có nhãn accessible, cảnh báo có text không chỉ màu, mobile/menu hiện tại không bị che.

## 5. Chống chạy trùng và lỗi
Named lock theo database + store_id + job, tên hash <=64bytes, GET_LOCK(timeout0) để không block cron tenant khác. Lock thất bại = skipped_busy, không coi là đã chạy. finally RELEASE_LOCK, runner đóng connection khi kết thúc; lỗi release chỉ log action/code chung, không SQL/PII.
Unique key là lớp bảo vệ idempotency sau crash; INSERT và bắt1062 riêng thành skipped_duplicate, không SELECT-check-then-INSERT, không INSERT IGNORE che lỗi schema/truncation.
Không một transaction kéo dài cho cả tenant; candidate keyset/chunk100, ghi từng thông báo, kiểm tra lại eligibility trước INSERT. Lần sau resume bằng unique key. Read revalidation tránh leak khi role/assignee đổi trong lúc job chạy.
Exit0 khi hoàn tất/skipped_busy có count; exit1 lỗi thật/partial failures; exit2 tham số sai/preflight. Log chỉ tên job, store/account ID nếu cần, counts/codes; không tên/email/điện thoại/IP/SQL/exception raw. System job không giả actor người dùng; không ghi tracking username.
Không ghi last_login, không thay bảngusers thật ngoài SELECT, không gửi ra ngoài.

## 6. Thứ tự BUILD sau duyệt
A. Chốt schema011, recipient và lịch trên; tạo PLAN chi tiết DDL và preflight read-only, chưa chạy migration.
B. Viết DAO/service/CLI dry-run + no-DB unit tests, khóa/duplicate/lỗi có adapter.
C. Thêm UI notifications + mark-read CSRF, review tenant/IDOR; generate fixture Smarty và browser.
D. Chạy no-DB regression + lint/browser/security review; DB cases giữ CHƯA CHẠY nếu người dùng tiếp tục chỉ định tự test.
E. Viết hướng dẫn host UAT và cron, manifest/hash theo commit cuối. Commit local khi phase hoàn tất; không push/merge/deploy cho đến duyệt riêng.

## 7. VERIFY — chưa chạy, không PASS
|Case|Tiêu chí|
|---|---|
|CLI entry|HTTP404; --help/invalid args không kết nối DB; dry-run không write|
|Clock|17:29 chưa nhắc;17:30 nhắc; ngày cuối tuần không timesheet reminder;08:00 task job; VN midnight correct|
|Timesheet|zero live entries mới nhắc;1entry không nhắc;deleted entry không tính;inactive/no-role/không quyền không nhận|
|Task|due today/tomorrow/overdue đúng;NULL/future>1day/done/ẩn bỏ;membership/PM manager đúng;unassigned chỉPM|
|Tenant|tất cả query store_id; task foreign/missing không leak; ID forged read/mark không tác động recipient khác|
|Idempotency|rerun cùng ngày1row; ngày sau mớirow;1062 skipped, lỗi khác failed;recipient PM=assignee không nhân|
|Concurrency DB|hai CLI cùngtenant/job không duplicate;tenant khác không block;crash/retry safe;finally release kiểm tra thật|
|Stale permission|đổi role/assignee/manager, khóauser, hide/done sau tạo không lộ title/unread;oldrow giữ nguyên|
|UI/security|escape XSS,CSRF,markread actor/store,keyboard/mobile/empty/error,chuông không che Điều hướng/Tài khoản|
|Schema|DDL tồn tại đúng/mismatch fail; thiếu011 các trang cũ không500;EXPLAIN queries trước thêmindex ngoàiplan|
|Data safety|fixtures tạm khi được phép;fingerprint dữ liệu thật trước/sau không đổi ngoài notification fixture;khôngDB test tự tạo|
|Regression|runner khôngDB hiện có+browser;fullDB/sessionSQL/manualhost ghi rõ chưa chạy nếu không thực hiện|
Bằng chứng từngcase phải có lệnh/output và giới hạn; DB concurrency không được suy luận PASS từ mock. Checklist80 hiện hữu không tự nâng.

## 8. Quy trình triển khai dự kiến — chỉ tài liệu
Backup xác thực bởi người dùng; preflight schema/store timezone và PHP CLI path. Áp dụng011 thủ công sau duyệt migration; upload runtime; manual UAT vai trò/tenant/chấm công/task. Chạy dry-run trên tenant rõ ràng (stdout counts, không dữ liệu cá nhân), đối chiếu trước --commit. Host cron placeholder: */5 * * * * /path/to/php /path/to/app/cron/pm-reminders.php --store-id=<id> --job=all --commit, chọn timezone hoặc dùng PHP job window VN.
Bật từng tenant/job và theo dõi counts/exitcode; nếu lỗi tắt cron và notification UI, giữ bảng/dữ liệu. Không tự tạo HTTP cron nếu hosting không hỗ trợ CLI: dừng lập phương án riêng, không dùng APP_KEY legacy.

## 9. Điểm chờ duyệt
Duyệt toàn bộ phạm vi2job + lịch17:30 weekdays/08:00 daily + thông báo web/recipient + CREATE bảng011 + kiểm thửDB thủ công. Chưa có việc BUILD hoặc migration được thực hiện trong bước PLAN.

## BUILD local được duyệt — 14/10/2026
Người dùng chỉ định deepseek-harness rà plan và BUILD nếu ổn. Đã thực hiện rà source, làm rõ feature flag mặc định OFF và eligibility revalidation, không cần thay nghiệp vụ đã đề xuất. BUILD trên feature/pm-phase12-cron-reminders; cron CLI/2jobs + chuông/list/markread + migration011 chỉfile. Storage timestamps ghi thời gian VN explicit, không phụ thuộc SQL NOW khi insert/markread. Không dùng runtimeDeepSeek bên ngoài. Chi tiết PM_CRON_VERIFY_20261014.md và PM_CRON_HOST_UAT_20261014.md; DB không chạy theo chỉ định. Các đoạn 'chờ duyệt' bên trên là trạng thái lúc lập PLAN, không phải yêu cầu duyệt BUILD lại; áp dụng migration và triển khai vẫn cần duyệt riêng.
