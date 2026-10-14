# Kịch bản chạy tay migration012 và VERIFY — DB local
Ngày14/10/2026. CHƯA CHẠY. Chờ người dùng duyệt migration012 và kết quả COUNT trước BUILD.
Agent không chạy các lệnh SQL ghi trong tài liệu này. Không dùng trên host.

## 1. Phạm vi và trình tự
Backup → kiểm tra đúng DB/schema → áp012 sau duyệt → preflight → checksum baseline → fixture transaction → các lỗi1062 dự kiến → ROLLBACK → checksum sau.
Không bật PM_REMINDERS_ENABLED khi chỉ áp012: code hiện tại còn preflight8cột của011; chỉ bật sau BUILD bản tương thích012 và UAT. MigrationDDL tự commit, không nằm trong rollbackfixture.
Giữ nguyên backup/maintenance/store_id002/007lock/UAT và quyền deploy riêng. Không chạy lại migrationcũ nếu đã có.

## 2. Backup local — người dùng chạy PowerShell
Thay TEN_MYSQL_LOCAL bằng tài khoản local; nhập mật khẩu ở prompt, không gửi cho agent.
```powershell
$mysqlBin = 'C:\Program Files\MySQL\MySQL Server 8.4\bin'
$backup012 = 'D:\derasoft-pm\.local\cron-backups\before-012-20261014.sql'
New-Item -ItemType Directory -Path (Split-Path $backup012) -Force | Out-Null
& "$mysqlBin\mysqldump.exe" --host=127.0.0.1 --user='TEN_MYSQL_LOCAL' --password --lock-tables --no-tablespaces "--result-file=$backup012" derasoft_pm_local
if ($LASTEXITCODE -ne 0) { throw 'Backup thất bại: dừng, không áp012' }
Get-Item -LiteralPath $backup012 | Select-Object Length
Get-FileHash -LiteralPath $backup012 -Algorithm SHA256
```
Chọn tên mới nếu file này đã có; không ghi đè backup cũ. Backup phải exit0, có nội dung, lưu hash riêng và giữ ngoài Git/webroot. Lock-tables có thể chặn thao tác DB local trong lúc dump. Không dùng skip-lock-tables để coi backup mixed-engine nhất quán. Chưa kiểm chứng restore chỉ từ hash; nếu không chắc bản sao, dừng.

## 3. Mở một phiên MySQL interactive local
```powershell
& "$mysqlBin\mysql.exe" --host=127.0.0.1 --user='TEN_MYSQL_LOCAL' --password derasoft_pm_local
```
Trong MySQL:
```sql
SELECT DATABASE(), @@hostname, @@sql_mode;
SHOW CREATE TABLE dc_pm_notifications;
```
DB phải derasoft_pm_local, connection127.0.0.1; xác nhận011/InnoDB và khóa uq_pm_notification_event hiện tại. Sai DB/schema thì thoát, không tiếp tục.
Không dùng từng request phpMyAdmin để chạy fixture: transaction/TEMP table phụ thuộc cùng connection.

## 4. Áp012 — chỉ sau duyệt riêng
```sql
SOURCE D:/derasoft-pm/database/migrations/012_add_pm_notification_due_date.sql;
SOURCE D:/derasoft-pm/docs/WORKSPACE_PREFLIGHT_SCHEMA.sql;
```
Expected: due_date DATE nullable defaultNULL; cả khóa cũ event_date và khóa mới due_date đều unique,5cột đúng thứ tự, không prefix; recipientindex cũ giữ. Nếu có STOP/error1062 khi tạoindex hoặc schema khác, dừng và gửi status/schema cho agent; không DROP/sửa khóa/xóa dữ liệu.
Các row cũ giữ due_dateNULL vì không backfill. Điều này cho phép một nhắc mới có snapshot rõ sau cutover; không thể suy ra hạn chót cũ để dedup tuyệt đối lịch sử trước012. Cần ghi nhận giới hạn này, không tự đoán hoặc cập nhật lịch sử.
DDL ADDcolumn/index có thể giữ metadata/index locks; đánh giá trên bản sao trước host.

## 5. Checksum và fixture SQL hợp đồng khóa
Trước fixture, trong một connection không có mirror:
```sql
SOURCE D:/derasoft-pm/docs/PM_CRON_012_CHECKSUM.sql;
```
Lưu nguyên bảng checksum ở file local riêng (hoặc screenshot), không gửi dữ liệu người dùng. Baseline là SAU012, vì thêm cột có thể đổi checksum notification. Các bảng khác có thể thêm baseline trước012 để chứng minh migration không đổi dữ liệu của chúng.

Mở docs/PM_CRON_012_MANUAL_CONTRACT.sql; paste từng block1→9 vào cùng phiên MySQL, không chạy batch cả file. Storefixture900000012 phải trống trước thao tác; nếu không trống, dừng, không xóa hàng đã có.
Fixture chỉ INSERT vào notification trong InnoDBtransaction; không có FKusers nên không cần tạo tài khoản. Không UPDATE/INSERT/DELETE dc_users, task hoặc dữ liệu cũ. Hai lỗi1062 được tạo chủ động để phân biệt hai khóa; sau mỗi lỗi kiểm count như bảng dưới. Có lỗi bất ngờ: ROLLBACK ngay.
```sql
ROLLBACK;
SOURCE D:/derasoft-pm/docs/PM_CRON_012_CHECKSUM.sql;
```
Mọi checksum trước/sau fixture phải trùng; NULL không phảiPASS. Rowcountstorefixture0. Nếu đóng kết nối khi transaction còn mở, InnoDB rollback; vẫn phải kiểm lại bằng connection mới. Không COMMITfixture hoặc resetAUTO_INCREMENT.

## 6. Bảng kết quả kỳ vọng — SQL sẵn chạy tay, chưa thực hiện
| Bước | Hành động | Expected | Trạng thái hiện tại |
|---|---|---|---|
|2|Backup|Exit0,file có dữ liệu,SHA256 được lưu|CHƯA CHẠY|
|3|Guard/schema011|Local đúngDB,InnoDB,khóa cũ đủ|CHƯA CHẠY|
|4|Migration012/preflight|due_dateDATE NULL;khóa mới và cũ nguyên vẹn|CHƯA CHẠY|
|5/block1|Reservationfixture|COUNT0|CHƯA CHẠY|
|block2|Day1 snapshotA|Insert1,rowcount1|CHƯA CHẠY|
|block3|Cùng ngày, snapshotB|1062 khóa cũ;rowcount1|CHƯA CHẠY|
|block4|Ngày2,snapshotA|1062 khóa mới;rowcount1|CHƯA CHẠY|
|block5|Ngày2,snapshotB|Insert1,rowcount2|CHƯA CHẠY|
|block6|Ngày3,quay lại snapshotA|1062 khóa mới;rowcount2|CHƯA CHẠY|
|block7|Hai ngày timesheet,due_dateNULL|Hai insert thành công,total4|CHƯA CHẠY|
|block8|ReferencecutoffSQL|D-4000,D-80,D-71,today1,tomorrow1|CHƯA CHẠY|
|block9|Rollback/observer|Storefixture0,allchecksumsame|CHƯA CHẠY|
Block8 là SQL mô phỏng biên ngày, không chứng minh DAO đã BUILD. Kiểm tra khóa cũng chưa chứng minh app phân loại1062 đúng; cần bước7.

## 7. VERIFY ứng dụng sau BUILD — checklist tay cần runner hoàn chỉnh
Trước nghiệm thu BUILD, agent phải bàn giao runnerfixture ứng dụng đã cập nhật012 và test đủ các dòng dưới, để người dùng chạy trực tiếp. Không bảo người dùng chạy tests/pm_cron_db_smoke.php bản cũ sau012: nó còn kiểm preflight011 và policy cũ. Runner mới phải guardlocal/backup,dc_usersTEMPexactmirror trên mỗi connection,PMfixture trong storetrống có nonce,observerchecksum vàfinallycleanup; không ghi tài khoản thật.
Cần clockinjectable trong testfixture để mô phỏng ngày1/ngày2; không đổi đồng hồ Windows, không mở --date cho commit sản phẩm, không backdate dữ liệu thật.
| Ca ứng dụng | Expected |
|---|---|
|Quá hạn ngày1/ngày2/đổi về hạn cũ|Ngày1insert1/ngày2duplicate/đổi hạn sang ngày kếinsert1/quay hạn đãnhắcduplicate|
|Cùng ngày đổi hạn|Duplicate khóa cũ,ngày kếtiếp mới insert|
|Task400ngày/D-8/D-7|0/0/1 mỗi recipient hợp lệ|
|Oldunread ngoài cửa sổ|Vẫn list/unread nếu còn quyền/task hiệu lực; không tạo mới|
|Assignee=PM|1row/task,không nhân|
|Timesheetfixture|Đếm current/allocation/allocationORin_progress đúng; không nhánhstart/due|
|Snapshot đổi giữa candidate/INSERT|INSERT dùng đúngdue_date recheck; không ghi giá trị cũ|
|Sai/foreign/emptyuserids|Lỗi chung,sốinsert0,khôngfallbackall|
|Commitkhônguserids/khôngall-users|Exit2/trước ghi|
|Userids+all-users|Exit2/trước ghi|
|Haiworker/tenant/crash|Không duplicate,tenant không block,khóa nhả,retry an toàn|
|HTTPCSRF/ownership|Valid303/readpersist;CSRF403;foreign/missing404 cùngmsg|
|EXPLAIN/schema|Querythật/preflight9cột+2unique;khôngindex khác|
|Cleanup|usersreal vàallPMhashsame;chỉfixture bịdọn|

## 8. Kết thúc / bằng chứng gửi
Gửi trạng thái mỗi bước, lệnh đã chạy, output chỉschema/count/errorcode/checksum; không gửi mật khẩu,dump,tên/email/IP/token/cookie. Có thể che hashbackup nếu muốn, nhưng giữ đối chiếu local. Agent ghiPASS chỉ dựa output thật; mục chưa chạy giữCHƯACHẠY.

## 9. Cổng hiện tại
File012 đang chờ duyệt; chưa BUILD flags/throttle/snapshot/preflight012 trongruntime. COUNT14/10 localstore1 là8/0/0/0 (ba ngày13/12/09,không lịch nghỉlễ). Chưa áp012,chưa bậtflaghost,push/merge/deploy. Dòngcronbước10host chỉ cập nhật sau code vàVERIFY, không dùng optionchưacó.

## 10. Bổ sung sáu ca bắt buộc — chỉ kịch bản, CHƯA CHẠY
Khóa cũ đã đối chiếu trực tiếp migration011: uq_pm_notification_event(store_id,recipient_id,kind,entity_id,event_date). File012 giữ đúng tên và thứ tự; không sửa/drop khóa này.

Sau012, **xem tất cả result set SELECT trạng thái**, không chỉ thanh báo thành công. Nếu client bỏ qua các result set đầu, chạy riêng hai SELECT trong PM_CRON_012_PREFLIGHT.sql. Phải có1row due_date/2row unique, tất cả statusOK; zero/missing/mismatch hoặc bất kỳSTOP là dừng.
Lần chạy012 thứhai chỉ nhánh “đã tồn tại” và SELECT kiểm tra read-only, không ALTER hay sửa row. So SHOWCREATE + checksum và rowcount trước/sau lầnhai, không dùng lời báo “đã tồn tại” thay bằng chứng.

| Ca | Cách chạy tay | Kết quả kỳ vọng |
|---|---|---|
|1. Chạy012 hai lần|SOURCE012; lưuSHOWCREATE/checksum; SOURCE lại; chạy2SELECTpreflight|Lần2 thông báo cột/khóa đã tồn tại;0ALTER, cấu trúc/data checksum không đổi|
|2. due_date saiNOTNULL|Trên schema thử RỖNG riêng, theo PM_CRON_012_BAD_COLUMN_PROBE.sql từngblock;không sửa bảngapp|012 inSTOP, cột vẫnNOTNULL, COUNTmetadata khóa mới=0,khóa cũ giữ|
|3. Hai tuple giống códue_date khácNULL|Contractblock2+4: cùng store/recipient/kind/entity/due_date, khác event_date để cô lập khóa mới|Row2 1062 uq_pm_notification_deadline,count vẫn1; nếuevent_date cũnggiống thì1062 cũngđúng nhưngkhôngphânbiệtkhóa|
|4. Hai NULL, event_date khác|Contractblock7|Cả2insert,count tổng4 (gồm2overdue trướcđó)|
|5. NULL,event_date giống|Contractblock7b|1062 uq_pm_notification_event,count vẫn4|
|6. Cùngtask,due_date khác|Contractblock2+5 vớievent_date khác|Cảhai deadline vào được,countoverdue2; cùngeventdate thìkhóa cũ chặn theoquyếtđịnh đãduyệt|
Tất cảcaCHƯACHẠY cho đến khi người dùng gửi output. Phầncộtsai dùngschema riêng chỉkhiuser/DBA chophépCREATEschema rỗnglocal;không có quyền thìgiữCHƯACHẠY,khôngsửa due_date app thànhNOTNULL. KhôngSOURCEtoàn bộprobe vì cóSTOP kiểmtra thủcông;paste từngblock. Giữschema thử,khôngDROP. QuayUSEderasoft_pm_local trước cácfixture khác.

## 11. Preflight code cần BUILD sau duyệt — chưa triển khai
- --commit bắt buộc due_dateDATE NULL và uq_pm_notification_deadline đúng cấu trúc, bêncạnh schema011/khóa cũ. Thiếucột/khóa → từchối trướcINSERT/lock/chọnngười; khôngfallbackhànhvi cũ.
- Kiểmtra schema commit trước cửa sổ giờ: --commit thiếu012 vẫn bị từchối kểcả chưa08:00/17:30. FlagOFF/argsinvalid cũng từchối theoquyđịnh.
- Dry-run vàweb không yêu cầu012; web vẫn cần schema011, dryrun chỉCOUNTcandidate. Basepreflight cần tươngthích8cột011 và9cột012, không buộc khóa snapshot cho read.
- TestnoDB/SQL:commitmissingdue_date;commitmissingnewkey;wrongdefinition;ngoàicửa sổgiờ vẫnrefuse;dryrun/web011-only vẫnđọc;commit012đầyđủ mớighi. Không đánhdấuPASS từ review.
- Dòngcron bước10host sẽcậpnhật saucode/VERIFY;khôngdùng --all-users hay --user-ids nhưcờ đãhỗtrợ ởbản hiện tại.

## 12. Kết quả COUNT đã có — không chạy lại DB trong lượt này
COUNTread-only đãthực hiện14/10/2026 trênderasoft_pm_local/store1:
current8;allocation_only0;allocation_or_in_progress0;recent_3_workdays_no_today0.
Ba ngày đãhoànthành13/10,12/10,09/10,thứHai–thứSáu,không tính nghỉlễ. Giữ active/quyềnchấm công/khôngentryhômnay;không intên/IDtài khoản.
Bằngchứng .local/cron-throttle-readonly-counts.json. Khôngphải sốhost,không làPASSpolicy đãBUILD.

## 13. Bản BUILD012 và runner ứng dụng mới — bàn giao chạy tay
Code đã BUILD local, chưa thực thi SQL. Dùng **tests/pm_cron_throttle_db_smoke.php**, không dùng pm_cron_db_smoke.php cũ.
Trước chạy: backup mới có provenance derasoft_pm_local/loopback; áp012 thủ công đã duyệt; xem tất cảstatusvàhaiSELECTpreflight;codecopy phải đúngbảnBUILD. CờhostvẫnOFF.
Lưu sealbackup (thay đúngpathbackup đãtạo):
```powershell
(Get-FileHash -LiteralPath $backup012 -Algorithm SHA256).Hash.ToLowerInvariant() | Set-Content -LiteralPath "$backup012.sha256" -Encoding ASCII
.tools/php83/php.exe tests/pm_cron_throttle_db_smoke.php --help
.tools/php83/php.exe tests/pm_cron_throttle_db_smoke.php --run
```
Runnerchọnbackup.sql.sha256 cuối theo tên trong.local/cron-backups,kiểmhash/headerđúngDB;đảm bảođó làbackup bạn vừa tạo. Không dùng --force nếubackup/schema/port fail.
Runner không áp011/012,không ghiusers thật. TạoPMfixtures tại2stoređãkiểmtratrống,usersmirrortạm;thêmallocation/in_progress,bản0h/ẩn/done/no-role/inactive;clock14/10giảkhôngđổiWindows. Seedtiếntrình/mirror tạm tạoSQLghi do NGƯỜI DÙNGchạy,agentchỉlint/help.
NativePMfixture đượccommit tạm cho2workers/HTTPshare;finallykill/waitworkers rồi dọn đúngstorefixture,observerchecksumtrước/sau. Nếuworkerchưa dừng thìdừngcleanupđểtránh race,báolỗi;khôngresetidcounters. Khierrorgiữlog vàkhông tự xóa tài khoản/dữliệu cũ.
Expected cácdòngPASS:strict012,recipientcounts7/1/2,scopehẹp/foreignreject,taskcandidate8/4000/D-80/D-7có,selfPM1,snapshotmatch,day2nooverduerepeat,oldunreadpersist,deadline-stale0,1062oldkey/1062newkey,deadlinechange-nextday/revert,2timesheetrecipients/nullsnapshotdaily,2process/2tenant/crash,Http303/403/404,no-role/membershiploss,EXPLAIN5shapes,allrowschecksumunchanged.
Khôngghi sẵnPASS;reportcaseJSON .local/cron-throttle-db-results.json vàchecksums/explaincùngprefix. NativeSQL/appcases hiệnCHƯACHẠY.

## 14. Phục hồi thử backup nếu có quyền
Đề nghị DBA/user phục hồi backup vào **schema thử mới hoàn toàn rỗng** trên local, không phải derasoft_pm_local hiện tại. Cần quyềnCREATE riêng;thiếuquyềngiữCHƯACHẠY,khôngtự cấpquyền.
Ưu tiên dump với --skip-add-drop-table khi tạobackup. Xem dump không có USE/CREATE DATABASE trỏschema gốc;STOP nếu có. Chọnschema restoreprobe riêng,SHOW TABLES phải0;khôngDROP/clear schema đãcódata. Import bằngMySQLlocal vào schema đó,kiểm sốtables/rowcounts/schema và đốichiếu dữliệu liênquan;ghi outputkhôngPII. Khôngđổiappconfig đểchạyhost/đổiDB đangdùng.
Backuphash/fileđủkhông chứngminh restorethànhcông; chỉnâng restorePASSkhiuserthựcrun/gửibằngchứng. Giữbảnrestoreprobe/backup,khôngtựxóa schema haydump.

## 15. Thứ tự và preflight hiện tại
011 →012 (backup/duyệtSQLriêng) →upload code → UATcopy/host → bậtcờCLI/web sauduyệt.
Code mới thiếu012 từchối --commit,cảtrước giờjob;khônglùi policycũ. Dryrun/web chỉcầnbase011 (read8/9cols),khôngrequire snapshotkey;recipientindex vẫnđượckiểmtra trongpreflight. Dòngcron --all-users đãđượcchuẩn bị trongbước10tài liệuhost,khôngcài lịchhoặcbậtcờ.
