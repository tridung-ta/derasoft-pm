# P0 — Điều tra regression không ổn định (07/10/2026)

Phạm vi local tại `31a5a88`. Không sửa code sản phẩm, test source, database schema/engine hoặc dữ liệu. Không push/merge/deploy. Dùng code-review, probe/diagnostic copies trong `.local` (ignored); không tự retry request để biến FAIL thành PASS.

## Kết luận và cổng tiếp tục

- **Import Apply lock release: đã tái hiện và xác định lỗi giả định timing của harness.** Test gọi GET_LOCK timeout=0 ngay sau mysqli close; close phía client không chứng minh session phía MySQL đã kết thúc tại thời điểm request acquire kế tiếp. Probe 300 lần tái hiện 8 lần acquire tức thời thất bại, nhưng cả 8 được acquire bằng bounded wait 1 giây, thực tế 0,087–0,369 ms. Sau failure tức thời, IS_USED_LOCK đều đã trả NULL. Không có lock tồn dư trong probe; runtime không thay đổi.
- **Role HTTP connection timeout: CHƯA xác định nguyên nhân gốc lịch sử.** Suite nguyên trạng chạy riêng 10/10 PASS; hai diagnostic run đạt 139 case/run. Lần FAIL cũ không có request ID/SQL ownership trace nên không đủ để kết luận là port conflict, CPU/network hay code. Không gọi đó là lỗi harness có thể bỏ qua và không dùng các lần PASS thay nguyên nhân.
- Không phát hiện leak/deadlock named lock mới trong các đường đã review/đã chạy; điều này không chứng minh tuyệt đối mọi lỗi HTTP là vô hại hoặc production không thể bị ảnh hưởng. **P0 chưa đóng đầy đủ; chưa chuyển P2/BUILD D2.** Phải giữ trace và thu bằng chứng nếu timeout tái hiện; không mở cổng push/merge/deploy.

## 1. Hai suite riêng lẻ 10 lần liên tiếp

Lệnh chạy từng lần, tuần tự, không song song: `.tools/php83/php.exe tests/pm_import_apply_smoke.php` (10 lần), rồi `.tools/php83/php.exe tests/pm_role_http_smoke.php` (10 lần). Orchestration Python gọi subprocess, ghi exit/output/thời gian mỗi lần vào `.local/p0_10_runs.json`. Không dừng hoặc bỏ dòng FAIL; kết quả cả 20 lần exit 0.

| Lần | Apply exit / giây | Role HTTP exit / giây |
|---|---|---|
| 1 | 0 / 4,985 | 0 / 12,708 |
| 2 | 0 / 4,954 | 0 / 12,610 |
| 3 | 0 / 5,012 | 0 / 12,395 |
| 4 | 0 / 5,048 | 0 / 11,905 |
| 5 | 0 / 5,039 | 0 / 12,010 |
| 6 | 0 / 4,956 | 0 / 11,688 |
| 7 | 0 / 4,996 | 0 / 12,110 |
| 8 | 0 / 5,033 | 0 / 12,030 |
| 9 | 0 / 4,976 | 0 / 12,342 |
| 10 | 0 / 5,007 | 0 / 11,730 |

Output Apply từng lần là PASS INSERT/1062/recovery/retry/locks; output Role từng lần là PASS 139 HTTP case. Các suite assert persistent state/engine không đổi. Không tái hiện port already occupied hoặc HTTP timeout trong 10 lần này; không suy ra chưa từng có port conflict.

## 2. Probe nguyên nhân close/acquire

Lệnh `.tools/php83/php.exe .local/p0_close_lock_probe.php`, exit 0. Dùng PmImports lockName/acquire thật, hai connection; holder close, observer acquire(timeout=0) ngay sau đó. Nếu thất bại thì ghi IS_USED_LOCK và đo GET_LOCK(timeout=1), không retry test nghiệp vụ.

Output: `trials=300, immediate_failures=8`; trial 31/170/177/186/212/242/244/265. Bounded acquire đều 1; wait ms tương ứng 0,128 / 0,369 / 0,328 / 0,094 / 0,087 / 0,108 / 0,116 / 0,101. Các tên khóa chỉ dùng cho probe, được nhả sau mỗi vòng; không ghi bảng nghiệp vụ.

[MySQL 8.4 Locking Functions](https://dev.mysql.com/doc/refman/8.4/en/locking-functions.html) quy định named lock tự nhả khi session kết thúc, không phải khi transaction rollback/commit. Probe chứng minh assertion “phải lấy được ngay với timeout=0 sau close phía client” không ổn định. Đề xuất sửa **test**, không runtime: assert holder sở hữu khóa, close holder, bounded GET_LOCK(1) phải thành công, assert owner chuyển sang observer, release rồi owner NULL. Nếu quá deadline vẫn FAIL, không sleep/retry vô hạn hoặc đổi runtime GET_LOCK timeout.

## 3. Rà luồng lock và đo HTTP

- `PmUsers::create/updateSafe`: acquire identity một lần, check/write trong try, release trong finally. Profile cũng cùng pattern. Không có acquire batch trong các đường này.
- `PmImportService::apply/provisionPassword`: batch trước identity; finally nhả identity rồi batch, nested finally bảo đảm batch được nhả nếu release identity lỗi. Chưa thấy đường lấy ngược thứ tự hoặc reentrant cùng tên trong phạm vi PM này.
- Timeout identity là 2 giây; batch timeout=0. Busy trả lỗi rõ, không ghi data. Lock cố tình giữ trong test để kiểm tra busy không phải deadlock. Apply giữ identity suốt batch (tối đa 500 dòng), nên batch lớn có thể giữ khóa lâu hơn một thao tác user; đó là phạm vi thiết kế hiện tại, không có benchmark production/batch 500 trong lượt này. Không giả định 2 giây đủ để chờ mọi batch; vượt 2 giây được trả busy theo thiết kế, không nới timeout.
- Test HTTP kiểm tra port 18770 trước start, chờ socket ready, proc_terminate/proc_close trong finally. Log hiện có không có bằng chứng Failed to listen; không suy ra nguyên nhân timeout lịch sử từ đó.

Diagnostic copies: `.local/p0_trace_role_smoke.php`, `.local/p0_trace_router.php`, `.local/p0_trace_helpers.php`; lệnh `.tools/php83/php.exe .local/p0_trace_role_smoke.php` chạy hai lần exit 0, mỗi lần 139 HTTP case. Trace chỉ ghi run/case/PID/thread/timestamp/operation/duration/errno, không password/cookie/token/email/username/payload. Giữ timeout HTTP 15 giây; không thêm request retry.

Lần trace cuối `bbb51e36441c`: 139 response, 0 failure, max HTTP 2054,531 ms. Case 113/114 cố tình tranh chấp: GET_LOCK 2012,572 / 2001,857 ms, errno=0, HTTP 200 với busy error được suite assert. Tổng 17 acquire attempts, 15 releases; 2 attempts bị timeout không có grant nên không cần release. Max release 0,093 ms. Case 115 (mật khẩu rỗng ngay sau contention) không có named-lock SQL. Tracing supports review rằng request invalid create này không trực tiếp đợi identity lock; **không tái dựng được nguyên nhân lỗi transport cũ**.

Lượt diagnostic đầu chưa override case=0 ở client vì array union giữ default; đã sửa helper ignored sang array_merge và chạy lại. Số SQL/server timing ở lượt đầu vẫn quan sát được, nhưng chỉ dùng lượt cuối để đối chiếu client/server case.

Lệnh `.tools/php83/php.exe .local/p0_lock_state_read.php`: version=8.4.11. `performance_schema.metadata_locks` **CHƯA KIỂM TRA ĐƯỢC** vì SELECT denied (1142); không cấp thêm quyền. Fallback IS_USED_LOCK kiểm tra 1 tên hiện hành xác định được từ stores/batches persistent: held=[] tại thời điểm chạy. Không gọi đây là scan toàn bộ locks; không bao gồm các tên batch temporary đã rollback.

## Việc còn mở

### Bổ sung trace transport khi tiếp tục P0

Lệnh `.tools/php83/php.exe .local/p0_trace_role_smoke.php` chạy thêm một lần, exit 0:
139 HTTP cases PASS, persistent state và engine unchanged theo assertion của suite.
Diagnostic copy dùng stream notification để ghi thời điểm TCP CONNECT, giữ nguyên
`file_get_contents`, timeout 15 giây và không retry. Không thay đổi test source/product.
Run `fae0c2cfc989`: 139 response, 139 CONNECT events, 0 failure; max TCP connect
29,620 ms, max HTTP 2061,033 ms. Case 115: TCP connect 0,258 ms, DB connect
21,740 ms, toàn request 52,034 ms, HTTP 200; không có GET_LOCK trong request này.
Artifact ignored: `.local/p0_transport_summary.json` và `.local/p0_role_trace.jsonl`.

Harness parent đã `session_write_close()` trước khi start server; các request của
suite gửi tuần tự. Đây là đối chiếu code, không phải bằng chứng đo session wait của
lượt timeout lịch sử. Lượt mới chưa tái hiện timeout nên vẫn chưa xác định được
nguyên nhân gốc HTTP. Không đóng P0 hoặc kết luận production lock hoàn toàn an toàn.

1. Đề xuất sửa assertion close/acquire trong test theo bounded wait như trên để bỏ race đã chứng minh, vẫn bắt lock thật không nhả. Lượt điều tra này chưa sửa test source.
2. Với timeout HTTP, cần giữ trace ở harness chính hoặc chạy diagnostic copy khi tái hiện. Khi lỗi xảy ra, so client_start/client_done, server_start/shutdown, thread/GET_LOCK timing và chủ sở hữu khóa. Chỉ khi có bằng chứng mới phân loại harness/transport hay application/lock. Không ghi “chỉ flaky” từ 10 lần PASS.
3. P2 chờ cổng P0; P3/P4 chưa thực hiện. Checklist tổng vẫn 59 PASS/2 FAIL/19 CHƯA CHẠY; báo cáo P0 không nâng các case khác.
