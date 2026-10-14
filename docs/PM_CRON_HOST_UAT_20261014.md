# Host UAT / triển khai cron PM — 14/10/2026
CHƯA THỰC HIỆN. Chỉ hướng dẫn người dùng; không tự upload/DDL/cron.

1. Backup đã kiểm chứng; chọn store_id thật. Xác nhận PHP CLI có mysqli/mysqlnd/mbstring và chạy đúng bản source. Lịch nghỉ chưa có: nhắc chấm công chỉzeroentry weekdays, không chấm vắng/thiếugiờ; nếu không phù hợp chỉbật task job.
2. Giữ PM_REMINDERS_ENABLED unset/0. Upload runtime đầy đủ gồm admin.php, includes/pm_reminders.inc.php, cron/pm-reminders.php, DAO/info/services/module/template/CSS/JS. Không upload configs/tests/docs vào webroot tự động. Migration chỉđược áp dụng thủ công sau approval/backup riêng, không qua web.
3. Migration011_create_pm_notifications.sql chỉCREATE TABLEIFNOTEXISTS;khôngđổi bảngcũ/engineusers. Đãtồntại khôngphảiđúngschema;preflight kiểmtraengine/collation/8columnstype/null/idauto/defaults/primary/unique/recipientindex. Rollback tắtfeature/cron,restoremã,giữtable/history. Phụthuộc001–004 hiệncó;009/010 khôngphảiqueryreminderdependency nhưng góiPMhiệncó cần đúng migrationcủabảnPM.
4. Read-only candidates: /path/php /app/cron/pm-reminders.php --store-id=<ID> --job=all (dry-run;counts). --date=YYYY-MM-DD chỉdryrun,lúc18:00VN đểkiểmtra rules theo ngày. Output khôngtên/email/SQL. Đừngcoi0candidate làđãcóquyền/fixtuređúng.
5. Kiểmtra SQL thật/EXPLAIN: /path/php /app/tests/pm_reminders_explain.php --store-id=<ID>, chỉSELECTmetadata/EXPLAIN5queryshapes, khôngDML/DDL/fixture,index. Tests làtooloperator ngoàiwebpayload;khôngpublictestfolder. Ghi outputtênquery/plan và quy mô; cầnphêduyệt riêng nếu muốnindexthêm.
6. Dùngtài khoản/dự án thử đượccho phép trênhost;khôngxóa/thaythếdữliệucũ. Kiểmtraitimesheet0entry/1entry/ẩnentry/active/locked/no-role;tasktoday/tomorrow/overdue/NULL/done/ẩn/unassigned;PMkhác/tenantkhác/no-role khôngthấytên.
7. Sau kiểmtra schema/chọnmốcgiờ/lịch nhận, cấuhình env PM_REMINDERS_ENABLED=1 cho cảPHPwebvàCLI qua hosting (khôngcommitconfig). Chạy --commit tại mốcgiờ phùhợp. Dùng--job=task hoặc timesheet đểtắtjobcònlại;muốntắtcảhaiunsetflagvàcron.
8. Chạy2lần cùngngày:uniqueidentitykhôngtrùng. Test2process cùngtenant/job:1busy hoặcserial,khôngduplicate;tenantkhác khôngblock. Thử interruption/retry khôngmấtrow/khóa lâu;log chỉactiongeneric. CácbằngchứngnàyCHƯACHẠY.
9. Web:chuôngpreview5,allpage20,unread;markreadPOSTCSRF,tokenwrong403;foreign/missingid404cùngmessage;reload giữread. Đổiassignee/manager/role,hidetask/project/done:rowgiữtrongDBnhưngkhôngtitle/unread. Oldtimesheetreminder chỉhiệnhômnayvàzeroentry.
10. Lịchhost */5 * * * * /path/php /app/cron/pm-reminders.php --store-id=<ID> --job=all --commit. CLItựgating08:00/17:30VN,khôngđổi crontzhost. Env phảiavailable trongscheduler,hướngredirect stdout/stderr tới log ngoàiwebroot;kiểm quyềnfile/chếđộxoaylog. Timeout/hằngngàymốcnghỉ host cầnoperatorconfirm. KhôngcàiHTTPendpoint hoặcAPP_KEYlegacy thayCLI nếu hostkhônghỗtrợ.
11. Exit0completed/skipped_busy;exit1partial/SQL/schemaerror;exit2badargs/disabled. Theo dõiđầu tiên mỗijob/tenant. Nếu lỗi:tắtcron+flag,giữdata,báooutput antoàn;khôngretrymigration001–010 hoặcdrop notificationtable.
12. Bổsungcheckschema011 vào preflightdeploy. Không bỏcácgate cũ(store_id002/maintenance/007lockbenchmark) nếu triểnkhaitenantmới. UIdefaultOFF khônglàmgián đoạntrangcũkhichưacó011;flagON/schemaabsent phải báounavailable,chứkhôngleakSQL.

KhôngtựgắnPASS30hostcases hoặc7checklistpending từnoDBtest.
