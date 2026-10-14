# Phase12 — kiểm thử DB local và review chống nhắc nhiều, 14/10/2026

## Phạm vi
User cho phép DB test chỉ trên derasoft_pm_local/loopback sau backup; không dc_users thật write; không host/push/merge/deploy. Review runtime1ab0fa0, chưa sửa policy nhắc. Proposal riêng PLAN_PM_CRON_THROTTLE_20261014.md chờ duyệt.
Guard thực: hostlocalhost,databasederasoft_pm_local,95tables reachable. Backup21MB trong ignored.local/cron-backups, SHA2560a0def7931f429b74b39beeab99377fa6ca80c34e4b288db42eb8d24a441886b. Không commitdump/credential/token/log.
Backup đầu --lock-all-tables fail thiếuRELOAD/FLUSH; không ápDDL lúcfail. Backup --lock-tables phạmviDB thànhcông,bao cảMyISAM/InnoDB. Chưa restoretestbackup,hash chỉ chứngminhfiletoànvẹn.
Migration011 áp duy nhấtlocal saubackup; bảng đúng preflight,không engineuserschange; chưaáp host.

## Lệnh / output thật
.tools/php83/php.exe tests/pm_cron_db_smoke.php
Lượt1:33PASS/1error (PHP thiếucurl_init). Cleanup/hashPASS. Sửa harness dùngHTTPstreamtimeout5s,khôngsửaassertion/app.
Lượt2:40PASS/0errors; bổsungassertionđếmcategory rồi lượtcuối:
RESULT:41 PASS;0 errors. Real localDB,users exact-schema mirrors;nohostaccess.
Log ignored .local/cron-db-final-20261014.log; JSONcases/checksums/explain ở.local/cron-db-*.json,khôngcommit.
PHP-l tests/pm_cron_db_smoke.php và tests/pm_reminders_explain.php PASS.

| Mục | Kết quả / bằng chứng |
|---|---|
| Chọn người chấm công | PASS SQLhiện tại:active/quyền/zeroentry; userkhôngassignment vẫnđượcchọn;entered/inactive/no-role bịloại. Khôngcoihànhvinày làpolicy mớiđãduyệt. |
| Task recipients / đầu tiên | PASS:9rows=4overdue+3today+2tomorrow;task400ngàyvẫnđượcnhắc,khôngbackfilltừngngày;done/hidden/NULL/future ngoài phạmvi bịloại;unassignedchỉPM. |
| INSERT/1062 | PASS firstcreated9;rerunduplicate9/created0;nativeSQLdriver. |
| Hai tiến trình cùngtenant | PASS worker1giữ namedlockthật;worker2skipped_busy;worker1resumerelease. Testbarrier chèn pause trongadapter,khôngđổi lockruntime. |
| Hai tenant | PASS Bcreated9/busyfalse trongkhiA giữlock. |
| Crash recovery | PASS killprocesssauINSERTđầu autocommit;pollIS_FREE_LOCKcótimeout10s xácnhậnfree;rerun duplicate1,createdcònthiếu,khôngmấtrow/nhânđôi. |
| HTTP markread | PASS realcontroller qua tokenprotectedloopbackrouter/sessionfixture;wrongCSRF403;valid303vàread_atpersist;foreign/missing404cùngmessage. Không fulladmin.php login-flow UAT. |
| Role/membership/no-role | PASS DAOlistno-role total0;đổimembershipfixtureoff làmchitiết/unreadmất,oldrowsgiữ;khôngthayuserspersistent. |
| EXPLAIN | PASS5 SELECTshapesSQLthật; planrows6/14/20/20/19; maxestimatedrows6/2/9/29/1. Candidate cófilesort/temporary; DBnhỏkhôngbenchmarkhost,khôngthêmindex. |
| Cleanup/data | PASS hash dc_users+20bảngPM trước/sau bằngnhau,changed=[];011rỗng. Cleanup chỉ2storefixture random đãkiểmtratrống trướcseed; không DELETEdc_users. |

CácPMfixtures chia sẻ giữaprocess đặt tạm trong2store chưa có dữliệu trênDB local;users làTEMPORARYexact-schema mirrortrênmọiconnection. Xóa chỉrowfixtures sau test,giữtoàn bộrowcũ. Observerchecksum dùngconnectionkhôngmirror. Không resetAUTO_INCREMENT: countersPM có thểtăng vìfixtureINSERT;đây khôngphảichecksumrowsvàkhôngclaimmetadatahoàntoànkhôngđổi. dc_usersreal khôngwrite.
Các7mục checklist tổng giữ nguyên; không tự nângproduction/UAT,performancehayfullauthenticatedsessiontest.

## Deploy/docs
Đãthêm010/011vàoorder,preflight011,cáchEXPLAINngoàiwebrootvàxóacopytool,copymusttestwritebeforehost,hẹpfirstcommitđangblockeduntiluseridsbuilt,fallbackprivateauto_prependchoCLI/web,giữcronlegacy. Tấtcảgate002/storecount/007lockbenchmark/maintenance/backup/UAT riênggiữnguyên. Manifest sẽ sinh từcommitlocal cuối;khôngpush/merge/deploy.
