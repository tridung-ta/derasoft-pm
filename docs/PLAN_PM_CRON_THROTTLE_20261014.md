# PLAN giảm nhắc Phase12 — cập nhật theo duyệt ngày14/10/2026

Current status (2026-10-14): BUILD completed locally at commit daa16bf. No-DB verification passed. SQL/application integration remains NOT RUN pending operator evidence. Sections 1, 4, 5 and the pre-BUILD gate record historical decisions; they do not supersede this status. No DB access, flag enablement, push, merge or deployment is authorized.

## 1. Ba câu trả lời trước BUILD
### (i) Snapshot hạn chót / schema
Đã đọc code/migration011 và metadata DB local: bảng dc_pm_notifications có id,store_id,recipient_id,kind,entity_id,event_date,read_at,created_at. Không có hạn chót lưu tại lúc tạo nhắc.
event_date là ngày tạo nhắc, không phải task.due_date snapshot; JOIN task chỉ cho biết hạn chót hiện tại, không khôi phục được hạn chót cũ sau sửa.
Unique hiện tại (store_id,recipient_id,kind,entity_id,event_date) ngăn trùng theo ngày, không bảo đảm một nhắc quá hạn cho mỗi(task,recipient,deadline).
Với ý nghĩa các cột hiện tại, cần phương án schema/lưu snapshot và khóa chống trùng được duyệt riêng để đáp ứng chắc chắn. Theo chỉ định mới đã viết012 để duyệt: ADDdue_dateDATE NULL và ADDunique(store_id,recipient_id,kind,entity_id,due_date),giữ khóa cũ. Chưa chạy012,chưa sửa runtime hoặc ý nghĩaevent_date/kind;khôngbackfill. Cùng ngày đổi deadline vẫn bị khóa cũ chặn,ngày kế mới nhắc lại nếu snapshot chưa từng nhắc.

### (ii) List / unread
Cửa sổ D-7..D-1 chỉ áp dụng cho tạo mới ở candidate và INSERT recheck. Không đưa tuổi quá hạn vào list/unread/mark-read. Task ra khỏi cửa sổ vẫn giữ thông báo chưa đọc đã tạo, nếu còn hiệu lực theo quyền/membership/task/dự án chưa ẩn/chưa done hiện có. Không promise row vẫn hiện nếu đã mất quyền hoặc task hoàn thành.
Phải tách predicate tạo mới khỏi predicate hiển thị, vì code hiện tại dùng eligibleSql('task') ở cả hai đường.

### (iii) Counts read-only local
Ngày Việt Nam2026-10-14, derasoft_pm_local,loopback,store_id1:
| Quy tắc | Người đủ điều kiện |
|---|---:|
| Hiện tại:active/quyền/không có timesheet chưa ẩn hôm nay |8|
| Hiện tại + có phân bổ hôm nay |0|
| Hiện tại + (có phân bổ hôm nay OR task in_progress) |0|
| Hiện tại + có timesheet trong3ngày làm việc hoàn tất gần nhất, chưa có hôm nay |0|
Chỉ COUNT, không in tên/ID tài khoản; START TRANSACTION READ ONLY rồi rollback. Cửa sổ giờ17:30 không áp dụng vào counts để so eligibility, không chạy job hay INSERT. Không có fixture hoặc thay dữ liệu. Kết quả không phản ánh DB host và không chứng minh code policy mới đã BUILD. COUNTthứ4 lấy13/10,12/10,09/10 (thứHai–thứSáu,không lịch nghỉlễ;không tính hômnay),giữactive/quyền/zeroentryhômnay.
Lệnh: .tools/php83/php.exe .local/pm-cron-throttle-counts.php; output8/0/0,8têncột; artifactignored.local/cron-throttle-readonly-counts.json.
Phân bổ đếm theo work_date=D,hours>0,allocation/task/project chưa ẩn,task chưa done,joins cùngtenant/project. Task doing được ánh xạ tên trạng thái thực in_progress,assignee hiện tại,membership active,task/project chưa ẩn; không điều kiện start_date/due_date.

## 2. Quy tắc đã được duyệt
1. Tạo mới cho hạn hôm nay/ngày mai và quá hạn trong[D-7,D-1]. Task400ngày không tạo mới, D-7 có,D-8 không.
2. Nhắc chấm công khi active/quyền/zero-timesheet và (allocation đủ điều kiện OR task in_progress đủ điều kiện nêu trên). Bỏ nhánh start_date/due_date. Không tự đổi status hoặc suy ra thiếu8h/vắng/nghỉ phép.
3. --user-ids=ID1,ID2 cho dry-run/commit:1–100ID dương hợp lệ,unique,cùngtenant. ID sai/foreign dùng cùng thông báo lỗi chung,không echoID/loại tài nguyên. Validate toàn bộ trước write;lọc cảcandidate/INSERTrecheck. Danh sách rỗng không fallback all.
4. --commit thiếu --user-ids phải có --all-users rõ ràng; thiếu cảhai từ chối trước ghi. Đã duyệt từ chối khi đưa đồng thời hai cờ để không nhập nhằng phạm vi; phần parsing cụ thể phải nằm trong BUILDplan.
5. Nhắc quá hạn tối đa một thông báo cho mỗi(task,recipient,deadline),ngày2 không thêm;đổi deadline được nhắc lại. Không bỏ unique cũ hoặc mã hóa snapshot vào event_date/kind tự ý. Oldrows giữdue_dateNULL,khôngsuyđoán deadlinecũ. Giữ cả2khóa:đổi cùngngày thìđợi ngày kế,quay về deadlineđãnhắc khôngtạo thêm. Các1062 từhai key đều duplicate,không che lỗi khác.
6. Assignee đồng thời PM chỉ một recipient/task;UNION/EXISTS hoặc dedup không nhân queryrows.

## 3. VERIFY sau chốt schema và duyệt BUILD
- Ngày1 tạooverdue,ngày2 không tạo thêm. Đổi deadlinecùngngày bịkhóa cũ chặn,ngày kếtiếp cólại nếu hạnđó chưa đượcnhắc;quay về deadline từngnhắc không tạo thêm. Cótest riêng từngkhóa1062.
- Lầnđầu task400ngày tạo0;D-7có,D-8không;today/tomorrow giữ.
- Oldunread vẫn list/count khi taskra khỏicửa sổ,nhưng khônglộ saumấtquyền/hide/done. Markread dùng cùngvisibilitypredicate.
- Assignee=PM đúng1 thôngbáo/task.
- Eligibilitycounts ba quy tắc,fixturecóallocation/hourzero/hidden/done/khácassignee;in_progress,NULLstart/futurestart khôngphụ thuộcstart;no-role/inactive/foreign/membership.
- userids duplicate/malformed/empty/foreign dùng lỗi chung phùhợp,khôngfallbackall;committoàntenantthiếuall-users reject;hai cờcùnglúc theo quy tắc đãchốt.
- Candidate→INSERT recheck trướcghi:taskdate/assignment/role/allocation đổi giữa hai bước không lọt.
- Giữ native1062/error separation,lock2process/2tenant/crashrecovery,CSRF/HTTPscope,EXPLAIN/schema guards và checksum/khôngusers thậtwrite.
- Không tự truy cập DB cho lượtVERIFY sau; người dùng thao tác trực tiếp theo hướng dẫn, trừ khi cấp quyền mới rõ ràng. Lượt COUNTread-only14/10 này là ngoại lệ cụ thể vừa được yêu cầu, không mở lại quyền DBwrite.
- No-DB/browser có thể chạy local; các mục SQL/host ghi CHƯACHẠY đến khi nhận bằng chứng thực. 41PASS DB trước thuộc hành vi cũ, không dùng chứng minh throttle mới.

## 4. Cổng giữ nguyên
Chưa BUILDpolicy/CLIflags/snapshot;đãviếtfile012,chưathựcthi;hostflagsOFF;backup/UAT/store_id002/migration007lock/maintenancegates giữ nguyên. Tài liệu cron bước10 chỉ được đổi sang --all-users như lệnh đãhỗtrợ sauBUILD/VERIFY. Firsthostcommit dùnguserids hẹp,khônglấytenantjob thay phạmvi cá nhân. Không push/merge/deploy.

## 5. Bàn giao trước BUILD theo chỉ định mới
Migration012_add_pm_notification_due_date.sql chỉfile ADDcolumn/ADDuniquekey, có existence/mismatch checks,giữkhóa cũ vàNULLlegacy. INSERTSELECT sauBUILD phải ghi snapshot đúngdue_date đã recheck;alias task.due_date và notification.due_date tách rõ để không nhầmcurrentdeadline/snapshot khiSELECT n.*. Runtimepreflight hiện8cột nên phải cậpnhật9cột trước bậtfeature.
Kịch bản chạy tay: PM_CRON_012_MANUAL_TEST.md + PM_CRON_012_MANUAL_CONTRACT.sql + PM_CRON_012_CHECKSUM.sql. Bao gồmbackup,targetguard,áp012sau duyệt,fixturetransaction/2key errors/rollback/checksum và bảngexpected. Khôngtự chạySQLghi. Runnerứngdụng/HTTP/concurrency/throttle sẽ hoàn thiện trongBUILD sau duyệt,không giảlàđãcó hoặc đãPASS.
Cập nhật dòngcron bước10host saucode:committoàntenant phải có --all-users;firstcommit dùng --user-ids hẹp,hai cờcùnglúc reject. Chưa sửa dòngthành lệnhlive trước flagsđượcBUILD. Khôngpush/merge/deploy.

## Điều kiện chốt012 được bổ sung — CHƯA BUILD
Đãđốichiếukhóa cũ migration011:uq_pm_notification_event(store_id,recipient_id,kind,entity_id,event_date),file012 khớp. --commit phải strictpreflight012 (due_date+unique mới),từchốithiếu/sai vàkhôngfallback,cảngoàicửa sổgiờ. Dry-run/web khôngrequire012;webcần011,basepreflight đọc tươngthích8/9cột. Cótest refusal/readcompatibility trongVERIFYsauBUILD. Agentchưa sửacode.
Kịchbản đủ6ca/expected và2SELECT cuối: PM_CRON_012_MANUAL_TEST.md mục10–12,PM_CRON_012_PREFLIGHT.sql,PM_CRON_012_BAD_COLUMN_PROBE.sql. CaNOTNULL chỉschema rỗngtáchriêng do người dùng chạy; không sửa bảngapp. COUNTđã có0người recent13/12/09 chưa hômnay (baseactive/quyền),cùng8/0/0 trước. KhôngkếtnốiDBtronglượt bổsung;chờuserduyệttrướcBUILD.

## BUILD được duyệt và đã thực hiện local —14/10
ĐãBUILDmục2/11:creationwindow/workpredicate/scopeflags/due snapshot/two1062/strictcommit012/readcompatibility/recipientindex. 63newchecks+53existingnoDB,19scripts/23lint/6allowlist và2fixturebrowsersPASS. Runner012mới đãviết/lint/help,SQL/appnativeCHƯACHẠY do người dùng chạy. Migration012 chỉfile,chưaáp. CácđoạnCHƯABUILDởtrên làlịch sửgate,khônglàtrạngtháihiệntại. Chi tiếtPM_CRON_THROTTLE_BUILD_VERIFY_20261014.md. Khôngpush/merge/deploy/SQLghi/bậtflag.
