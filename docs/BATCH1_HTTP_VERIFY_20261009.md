# Lô 1 — HTTP no-role và quyền phiên, 09/10/2026

Phạm vi: tiếp tục fixture dở ngày 08/10; chỉ tests, không thay runtime, schema hoặc dữ liệu thật. Dùng feature-development cho hoàn thiện test, code-review cho assertions/cô lập và git-feature-commit cho commit local theo workflow. Không push/merge/deploy. P3 chờ A/B/C.

## Lệnh và output đã thực chạy

- `.tools/php83/php.exe tests/pm_tenant_matrix_smoke.php all`: exit 0, `80 PASS / 0 FAIL endpoint-ID pairs`, 11 controls, 35 behavioral controls; cleanup 21 persistent tables PASS.
- `.tools/php83/php.exe tests/pm_aggregate_store_input_smoke.php`: exit 0, `RESULT 1.4: 36 PASS / 0 FAIL; 36 comparisons.`; cleanup 21 persistent tables PASS.
- `.tools/php83/php.exe tests/pm_session_role_http_smoke.php`: exit 0, `RESULT: 98 PASS / 0 FAIL. Session expiry not tested; 2.6 remains incomplete.`; cleanup 22 persistent tables PASS. Đã chạy lại sau bổ sung assertion body Forbidden; exit 0, 98/98 PASS.

Artifacts local: `.local/tenant-matrix-command-20261009.txt`, `.local/aggregate-store-command-20261009.txt`, `.local/aggregate-store-input-20261009.json`, `.local/session-role-command-20261009.txt`, `.local/session-role-http-20261009.json`. Không commit logs/session/config/credentials.

## Bằng chứng theo mục

| Mục | Kết quả | Phạm vi thực chạy |
|---|---|---|
|1.4|PASS|12 route/mode GET/POST của cost và report, mỗi route 3 store_id giả: tenant khác, ID thiếu và sai định dạng. So response baseline với forged: status 200, hash HTML/JSON/XML dữ liệu như nhau, không sentinel tenant khác, không audit/tracking phát sinh. GET và POST export đều truyền store_id giả.|
|0.2|PASS|Login thật qua admin.php/login bằng fixture không role; session có actor hợp lệ và CSRF mới. 80 trường hợp protected endpoint/op trong matrix + 4 action settings/template/preview/stage + 8 protected reads trả 403. Personal dashboard/profile/password không phải permission-protected operation và không bị coi là phải cấm no-role.|
|2.7|PASS|Một cookie từ login thật: pmusers GET khi fixture DB gán ADMIN trả 200, gỡ role trả 403, khôi phục ADMIN trả 200. Role assignment thay trong temporary exact-schema mirror mỗi request, không giả kết quả PmAccess và không sửa session userId/cookie trong ba request. Kiểm tra trường hợp đổi role; không tuyên bố UAT thao tác quản trị role ngoài fixture.|
|2.6|CHƯA CHẠY đầy đủ|Đã PASS session ID đổi sau login; cookie HttpOnly, SameSite=Lax và Max-Age=43200. Chưa chạy thời gian hết hạn thật hoặc xác minh toàn bộ server-side expiration; giữ nguyên trạng thái tổng.|
|3.6|CHƯA CHẠY|Temporary table có phạm vi một connection, không cung cấp shared rate fixture cho hai connection cùng ghi. Chưa có harness shared fixture an toàn và được duyệt để chạy request concurrency thật; không tạo bảng persistent, không ghi/xóa rate thật. SELECT FOR UPDATE và test allocation concurrency không thay bằng chứng rate race.|

## Excel timestamp

Lượt đầu 08/10:32 PASS/4 FAIL vì full XML hash khác. Lượt điều tra ghi hash từng XML: các phần thay đổi chỉ `docProps/core.xml`, created/modified đổi theo giây; worksheet/sharedStrings và các XML khác không đổi. Test chỉ chuẩn hóa hai timestamp created/modified trong core properties; vẫn kiểm tra toàn bộ phần XML còn lại và quét leak trên XML gốc. Giữ artifact failure đầu, không sửa code export để làm test qua.

## Cô lập

Preflight 09/10 không thấy process php.exe qua CIM; port 18776 phải trống. HTTP chỉ loopback, token/nonce và allowlist; auth fixture thêm exact-schema mirror dc_login_times. Credentials dùng literal fixture riêng, không đọc/in secret thật. Shutdown observer không ghi password/hash/session token. Trước/sau hash dc_users, dc_trackings, toàn bộ PM tables và dc_login_times; cleanup của mỗi suite bắt buộc để exit 0. Chỉ dừng server do suite tạo.

Regression toàn bộ trên commit test mới sẽ ghi bổ sung sau khi lệnh kết thúc. Không suy luận PASS từ suite riêng.
