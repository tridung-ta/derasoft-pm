# VERIFY cron PM — 14/10/2026

## Phạm vi / ủy quyền
Người dùng yêu cầu deepseek-harness rà PLAN rồi BUILD nếu hợp lý. Dùng methodology của skill, không chạy DeepSeek runtime/model bên ngoài. BUILD local trên feature/pm-phase12-cron-reminders. Chỉ viết migration011 additive; không thực thi SQL, tạo DB, truy cập host hoặc push/merge/deploy.
PLAN hiện thực có feature flag mặc định OFF: PM_REMINDERS_ENABLED=1 chỉ sau schema và UAT. Header bắt lỗi schema để không làm trang cũ500. CLI --commit bị chặn khi OFF; dry-run không viết DB/lock.

## Bằng chứng thực chạy
- .tools/php83/php.exe tests/pm_reminders_smoke.php: PASS 53 checks. Real service với adapter: cửa sổ17:30 weekdays/08:00 daily, timezone VN, dry-run khôngwrite/preflight/lock, chunk termination, skipped_busy,3categorydeadline,1062duplicate riêng,1406failed,INSERTSELECTstale,finallyrelease/failedrelease,log không sentinel SECRET, tenant/placeholder SQL contracts, missing/foreign ID khôngupdate, default/index schema mismatch refuse, CLI args/help exit0/2, DDL additive, Smarty escape/CSRF. SQL thật chưa thực thi; mock không chứng minh user eligibility/concurrency/idempotency thực trênDB.
- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1: exit0, PASS18 no-DB scripts +19 PHP lint checks +6 manifest allowlist tests. Log ignored .local/cron-nodb-final-20261014.log.
- playwright-cli -s=workspace run-code --filename tests/<suite>.js: exit0 cho25suite:21workspace suites hiện hành (ui/workspace/account/kanban/login-import/previewhub/timesheets/allocation/costs/imports/buttons/theme/final/sidebar/confirmation/people/dashboard/Vietnamese/toast/navigation/shift) +pm_daily_limit_browser +pm_task_estimate_browser +pm_warning_color_browser +pm_notifications_browser. Log .local/cron-browser-regression-20261014.log. Browsercron GET404 bodyempty; menu Enter/Escape/outside/focus; desktop1440/mobile390 bounds, no horizontaloverflow, CSRFPOSTmarkup,escaped task,empty state. Không authenticatedsaveDB.
- node --check js/pmui.js và tests/pm_notifications_browser.js: PASS.
- python -m py_compile tests/pm_workspace_package.py tests/pm_deploy_manifest.py: PASS.
- git diff --check: PASS trước commit.
- Impeccable detector chạy1lần: Inter warning giữ theo AGENTS; headingwarning do không resolveCSS relative template. Screenshot thật heading hierarchy rõ, desktop/mobile không tràn. Không gọi detector PASS a11y toàn bộ.

## Fail đã giữ và xử lý
1. Test gọi helper pmNavigationFixture không tồn tại; dùng PmUiService/fixture access theo helper thực. Không phải runtime defect.
2. Preview browser/session/server hôm trước đã tắt; mởbrowser/start server loopback khôngDB. Lượt đầu connection refused, chưa xem làPASS.
3. Browser click h1 khi dropdown mobile phủ tiêu đề bị interception; dùng click tọa độ bên ngoài panel, giữ assertionclosed/focus/bounds.
4. No-DB regression chặn English Invalid actor/store/job; đổi thông báo Việt, giữ test scanner nguyên.
5. Recording adapter nhầm SELECT information_schema chứa DATABASE() thành SELECTDATABASE; thu hẹp match exact. Guard schema fields và test mặc định read_at/uniqueindex để tránh schema không đúng nhưng lọt preflight.
Fault injection chủ động log “PM reminder insert failed.”/“PM reminder lock release failed.” là output dự kiến, không bỏ assertion lỗi.

## Code/security review
DAO mới có store trong read/write/join và permission mapping, activeuser/role, membership hoặc managerPM; EXISTS không nhânrecipient. INSERT SELECT revalidate candidate, compare due_date trước write. Source task/permission/current status revalidate cả list/count; stale/ẩn không leakname hoặc unread. Markread theo store/actor, foreign/missing chung404. Namedlock database+tenant+job hash,timeout0; finallyrelease+close CLI;1062 riêng, khôngIGNORE. Web404 trướcbootstrap, defaultdryrun,explicittenant,commit không backdate. CSRF mọi markread; escape title/name/url; schema/log failures không rawSQL/PII. Không sửahours/OT/cost/taskstatus/users.
Không phát hiện blocker mới trong luồng đã đọc; code-review không chứng minh SQLexecution hoặc production race.

## CHƯA CHẠY / giới hạn
Migration011, schemaMySQL thật, eligibility/scope/role refresh trênDB,INSERT/1062 thực, hai process/tenantconcurrency/crash recovery, markreadHTTPauthenticated,EXPLAIN và lượng dữ liệu lớn. Theo chỉ định người dùng khôngDBtest; giữ cácca trênCHƯACHẠY. tests/pm_reminders_explain.php đã viết cho operator, chưa chạy. Khôngindex ngoài2key kế hoạch. Checklist80 không tự đổi,7mục cũ cònmở.
Host UAT: PM_CRON_HOST_UAT_20261014.md. Không chứng nhận production-ready hay đãbậtcron.

## Skills
deepseek-harness cho outcome/authorization/decomposition/evidence; feature-development cho BUILD; database-safety cho migration additive/rollback chỉfile; impeccable+playwright-cli cho UIquality; code-review cho SQL/quyền/CSRF/log; git-feature-commit cho một commitlocal. Giữ tài liệu skill/.tmp của công việc khác ngoàicommit.
