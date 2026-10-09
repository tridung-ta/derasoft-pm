# P3 BUILD — 09/10/2026

BUILD local đang VERIFY, chưa hoàn tất phase. Không commit hoàn tất/push/merge/deploy; không chạy migration trên bảng thật.

Đã thêm setting max_total_hours_per_day mặc định24.00, snapshot max_total_hours, kiểm tra tổng theo store/user/date bằng số nguyên phần trăm giờ; giữ khóa ledger và thứ tự khóa hai ngày. Form có giới hạn tổng riêng và hiển thị snapshot. Migration010 chỉ ADD COLUMN DECIMAL(4,2) NOT NULL DEFAULT24.00, có kiểm tra tồn tại; runtime cần cột trước khi sử dụng.

## Lệnh/output thực chạy

- PHP -l classes/services/pmtimesheetservice.class.php và modules/admin/pmtimesheets.module.php: No syntax errors detected.
- .tools/php83/php.exe tests/pm_timesheets_smoke.php: PASS P3: 8+2 OT; total12 allowed/12.01 rejected; old/new snapshots; over-limit move rollback; empty ledger retained; invalid settings rejected. Temporary ledger, fixtures rolled back. Các assertions OT/cost/rate snapshot/tenant/audit cũ vẫn PASS.
- .tools/php83/php.exe tests/smoke_pm_timesheets.php --preview: PASS timesheet Smarty forms, escaping, locked read-only and Admin settings visibility; thêm assertion field max_total_hours_per_day và snapshot12.00.
- Từ .local: playwright-cli -s=p3 run-code --filename=../tests/pm_daily_limit_browser.js: PASS P3 browser: actual CSP, labeled total-hours field, min/max, focus, snapshot and390/768/1440 widths. Synthetic Smarty fixture; no DB write. Browser/server của phiên đã đóng.
- impeccable detect trên template: CSS relative bị resolver trỏ nhầm templates/admin/css nên không nạp CSS; cảnh báo flat-type-hierarchy không dùng làm kết luận lỗi hay PASS. Không đổi CSS theo kết quả thiếu stylesheet.

## Chưa chạy / giới hạn

- Migration010 trên database hiện có: CHƯA CHẠY, câu hỏi duyệt local sau backup đang chờ. Test service dùng exact-schema temporary ledger thêm cột tạm trước transaction; không ALTER bảng thật.
- Regression toàn bộ trên code P3: CHƯA CHẠY vì các suite khác cần cột snapshot thật. Kết quả59scripts trước P3 không phải bằng chứng regression P3.
- Hai writer connection thật và migration lặp lại/rollback/schema/data verification: CHƯA CHẠY; cần môi trường riêng được duyệt. Không coi khóa code là bằng chứng concurrency PASS.
- EXPLAIN query dayRules mới: CHƯA CHẠY trên schema mới.
- Checklist5.1/5.2 chưa đổi trạng thái; tổng71/0/9 thuộc vòng kiểm thử trước P3, không tuyên bố toàn bộ code P3 đạt.

## Cập nhật sau duyệt migration local
Migration010 qua .tools/php83/php.exe .local/p3_apply_010.php: backup local20,853,676bytes, SHA256f41fbc150239200af7e9a4e5e78f753cf7b9bbf457f514625120ee281bedef35; run1 0.039s, run2 0.001s, schema/default24 đúng. 95bảng dữ liệu không đổi, bỏ cột mới khi so fingerprint. Local có0ledger cũ; default trên ngày cũ có dữ liệu cần fixture riêng, không suy từ0rows. Backup nằmignored.local, không đóng gói/commit.
Test5.1/5.2 thêm assertions riêng đã thực chạyPASS: first8 regular8/OT0; extra2 regular0/OT2 under daily maximum12. EXPLAIN108query shapesPASS gồm dayRules, không thêmindex. .local/p3_verify_data.php: PASS95persistent fingerprints unchanged since before migration.
Lượt regression đầu có dòng59PASS nhưng wrapperexit1 vì gitLF/CRLFwarning từstderr; chưa dùngexit0cho lượt đó. Chạy lại táchstdout/stderr và báoexitcode thật. Concurrency DB riêng vẫn chờ trả lời, không suyPASS.

Lượt cuối táchstderr: Start-Process powershell -File tests/pm_regression.ps1 với RedirectStandardOutput/RedirectStandardError; REGRESSION_EXIT=0; PASS59scripts. Output.local/p3-regression-final-20261009.txt. tests/pm_daily_limit_migration_smoke.php riêng: PASS2pre-columnledgerfixtures, giữoldvalues/default24; temporaryonly. Đã thêm test này vào runner; chờ chạy60scripts trước chốt phiênbảnrunnermới.

## Kết quả runner60 (sau thêm migration fixture)
Start-Process powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1 với stdout.local/p3-regression-60-20261009.txt/stderr.local/p3-regression-60-stderr-20261009.txt: REGRESSION_EXIT=0; PASS60regressionscripts. Không áp dụngmigration trongrunner. PHP-l service/controller/timesheets/migrationtestPASS. .local/p3_verify_data.php sau60scripts:95persistentdatafingerprintsunchanged.
Câu hỏi tạo derasoft_pm_p3_test (schema-only/syntheticdata, giữ đểđốichiếu) chưa có trả lời; không tạo DB riêng và không chạy concurrency thật. P3BUILDđãcótestchứcnăng/migration/UI/regression, nhưngVERIFYconcurrencychưađủ: không commitphasehoàntất hoặc đónggóipháthành.

Người dùng giao concurrency liên quanDB cho UATthủcông, bỏdatabase testP3: giữCHƯA CHẠY/khôngPASS. Sau yêu cầu xóaDBtest, chỉschemaPhase10đãtạo đượcbackup rồixóa; databaseứngdụng95fingerprintsunchanged.

## Quyết định chốt phạm vi local
Người dùng ngày09/10 yêu cầu bỏ test concurrency cầnDBriêng và tựtestthủcông. Vì vậy P3được chốt trong phạm vi BUILD + các VERIFYđãchạy ở trên; concurrency giữCHƯA CHẠY/UAT, không tạoDB. Không còn gatechờtạodatabase; không suyPASSconcurrency. DatabasePhase10testđãbackup/xóatheoyêucầu, source95fingerprintsunchanged.
Review cuối service/controller/migration/tests: prepared scope store/user/date, CSRF/ownership cũ giữ nguyên, tổng so sánh hundredths, hai ngày khóa tăng dần và rollback. Không phát hiện lỗi mới trong phạm vi diff; giới hạn concurrency/UAT vẫn nêu rõ.
