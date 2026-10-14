# BUILD throttle012 — 14/10/2026

## Trạng thái và phạm vi
Người dùng duyệt BUILD, cấm SQL ghi/bật cờ/push/merge/deploy. Đã BUILD local; SQL và ứng dụng dùng DB CHƯA CHẠY, đợi người dùng chạy runner mới.
Migration012 chỉ file ADDdue_dateDATE NULL + unique snapshot, giữ khóa cũ, không backfill. Không ápDDL hoặc tạofixtureDB trong lượt này.

## Code hoàn tất
- CLI scope: --user-ids1–100positive/unique/cùngtenant hoặc --all-users; --commit thiếu scope/hai cờ cùng lúc từ chối trước DB; ID malformed/foreign chung lỗi. Dry-run khôngscope được phép đọc toàn tenant theo PLAN.
- Candidate/INSERT task có lowerboundD-7 +upperD+1; list/unread/mark-read không lowerbound7ngày.
- Nhắc chấm công có allocationpositive/hômnay/live taskproject hoặc taskin_progress+membership; không điều kiệnstart/due. Assignment/date/role được kiểmtra lại trongINSERT.
- INSERTSELECT ghi notification.due_date=t.due_date sau t.due_date=expectedrecheck; timesheetNULL. Đổi cùngngày bị khóa event;ngày kế snapshotmới đượcnhắc;quaydeadlinecũ bịkhóa snapshot.
- Strictcommitpreflight9cột,DATE NULLdefaultNULL,bothuniques+PK+recipientindex đầy đủ/khôngprefix. Trướcclockwindow,thiếu012 khôngfallback. Webbasepreflight tươngthích011/012,khôngrequirekeymới;dryrun khôngstrict012.
- SELECTaliascurrent_due_date tách khỏi storedsnapshot;clock testinject vào service không expose parameterHTTP sản phẩm.
-1062 của event/deadline đếm riêng,aggregate duplicate;unknown1062/lỗiSQLkhác failed+loggeneric,khôngrawexception.

## Lệnh và bằng chứng đã chạy (không DB)
1. .tools/php83/php.exe tests/pm_cron_throttle_smoke.php:
PASS63 throttle checks,parser/scope/strict012/read011/SQLcontracts/two1062classifications;no databaseconnected.
Bao gồm realCLI no-scope/double-scope/malformedexit2trướcDB;missingdue/key/recipientindex/wrongdefinition;strictcommitngoàigiờ;read8/9cols;scope/recheckbindings;read/unreadkhônglowerbound;DTOtitlecurrentdeadline;unknown1062failed.
2. .tools/php83/php.exe tests/pm_reminders_smoke.php:PASS53noDBchecks,legacy/service/template contracts.
3. powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1:PASS19noDBscripts,23PHP lint,6allowlist tests. Logignored.local/throttle-nodb-20261014.log.
4. tests/pm_cron_throttle_db_smoke.php --help:exit0,trảhướngdẫn,khôngconnectDB. PHP-l cho runner/worker/router PASS.
5. playwright-cli -s=workspace run-code --filename tests/pm_notifications_browser.js:PASSdesktop/mobile/Escape/outside/escape/CSRFmarkup/HTTPcron404,synthesicfixture,noDBwrite.
6. Cùnglệnh tests/pm_navigation_all_pages_browser.js:PASS12screens desktop/mobile nooverflow/nav/logoutCSRF.
7. `node --check js/pmui.js` and `python -m py_compile tests/pm_workspace_package.py`: PASS (exit 0). No SQL execution.
Browseropen lúcđầu workspaceclosed;đãmởlại. Console404favicon.ico của preview không phảiJS exception/appDBerror.
Fail giữ:chuỗi lỗi foreign bịencoding khi chèn bằngshell;testcommonmessage đãbắt,sửaUTF8,chạy lạiPASS. Không nới assertion.

## Review bảo mật / runner mới
Rà nguồn permission/membership/tenant/scope/recheck/namedlock/1062/snapshot/read alias/metadata. Không thấy blocker mới trong phạmvi đãđọc sau sửaunknown1062;review không chứng minh SQL engine/race.
Runner MỚI tests/pm_cron_throttle_db_smoke.php mặcđịnhhelp, --run mớiconnect/writefixture trênderasoft_pm_local/loopback saubackuphash/provenance/012preflight. Không tự chạyDDL. Đồng hồfixture2026-10-14;seed allocation/in_progress/0h/hidden/done/no-role/inactive vàselfPM; legacy7/allocation1/new2 làkỳvọngchưađo.
UsersTEMPexactmirror,mọiconnection;hai storetrốngcónonce. Harnesslock chốngrunđồngthời,finallyđợiworkersstop trướccleanup,observerrowchecksums. NativePMfixture rows sẽtạmcommitđể2process/HTTPchia sẻ,rồiDELETEchỉstorefixtures;khôngDELETEusersreal;AUTO_INCREMENTPMcóthểtăng,khôngreset.
Worker/router mới cótoken/loopbackguards,clockfixtureinject. Chưa chạy--run/schema/source012/nativeDDL/two-keynativecases/HTTP hoặcEXPLAIN ởcode mới. Không dùngrunner011cũ và41PASScũ đểnâng012.

## Bàn giao
PM_CRON_012_MANUAL_TEST.md mục13 hướngdẫn runner mới +backupseal,expectedcase table vàrestoreprobe nếu quyềncho phép. PM_CRON_HOST_UAT_20261014.md có011→012→upload→bật sauUAT vàcron --all-users tại bước10. Firstcommit vẫnuserids hẹp. SQLtests/backuprestore/host/load/UAT đềuCHƯACHẠY;checklist80và7pendinggiữnguyên.
Skillsfeature-development/deepseek-harnessmethodology/code-review;database-safetychofilemigration/guard,playwrightchoexistingUIregression;không chạyDeepSeek runtime. Khôngpush/merge/deploy.
