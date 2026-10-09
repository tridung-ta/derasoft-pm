# Kiểm thử theo lô — 08/10/2026

## Commit và regression

- P0:833a148, `git add -p -- tests/pm_import_apply_smoke.php`, chọn hunk chờ có giới hạn; không bỏ assertion. `.tools/php83/php.exe tests/pm_import_apply_smoke.php` exit0 PASS.
- P4 bổ sung:26ca279095e26c30960cf82294842fefe1f548b0, forced audit failure sau password persisted. UI thông báo đúng kết quả một phần; error_log chỉ thông báo tổng quát, không chứa các sentinel email/phone/password/hash. `.tools/php83/php.exe tests/pm_role_http_smoke.php` exit0:157 cases PASS. P4 implementation commit1dc540e đã có trước đó, giữ lịch sử.
- `powershell.exe -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1` trên26ca279, code/tests tracked sạch: exit0, stderr rỗng, output `PASS:55 regression scripts. No migrations applied by this runner.` Artifact local: `.local/committed-regression-20261008.json`.

## Lô1 — dừng tại cổng Tenant

Lệnh thực chạy: `.tools/php83/php.exe tests/pm_tenant_matrix_smoke.php`; exit255, stderr rỗng.

```text
PASS 1.3 partial: GET existing foreign-tenant project ->403.
Observed: foreign resource exists; unchanged; no audit/tracking; UI does not reveal foreign name; HTTP=200
PASS cleanup: persistent users/projects/tasks/audit/tracking unchanged; temporary mirrors destroyed.

Fatal error: Uncaught RuntimeException: FAIL 1.3 POST foreign task expected403/404 got200; tenant gate STOP. Source Phase4 modules/admin/pmprojects.module.php exception HTTP status. in D:\derasoft-pm\tests\pm_tenant_matrix_smoke.php:18
Stack trace:
#0 {main}
  thrown in D:\derasoft-pm\tests\pm_tenant_matrix_smoke.php on line 18
```

Fixture dùng temporary mirrors, có project/task thuộc store khác thật trong bảng tạm. GET project403; POST task_save với task foreign nhưng project hiện tại hợp lệ trả200. Không sửa foreign task, không phát sinh audit/tracking, HTML không lộ tên foreign. Hash bảng thật trước/sau không đổi; server của test được dừng trong finally. FAIL là HTTP contract403/404, chưa có bằng chứng vượt quyền ghi hoặc lộ dữ liệu.

| Mục | Kết quả vòng này | Bằng chứng / lý do |
|---|---|---|
|1.3|FAIL|Lệnh/output phía trên; matrix chưa hoàn tất.|
|1.4,0.2,2.6,2.7,3.6|CHƯA CHẠY|Dừng ngay khi1.3 FAIL; chưa chạy lệnh cho những mục này trong lô.|
|0.3,0.4,2.2,3.10,5.5,8.3|CHƯA CHẠY|Lô2 chưa bắt đầu do cổng Tenant.|
|9.5,10.3|CHƯA CHẠY|Lô3 chưa bắt đầu do cổng Tenant.|
|2.3,9.2,9.3|CHƯA CHẠY|Đưa vào UAT_FINAL_LOCAL.md, chưa người dùng nghiệm thu.|
|5.1,5.2|CHƯA CHẠY|P3 chờ A/B/C; chưa PLAN/BUILD.|

## PLAN1.3 đã được thay theo duyệt mới

Không còn đề xuất invalid input400 cho pmprojects. InvalidArgumentException giữ200, date-range và redirect303 giữ nguyên. Matrix được đổi sang collecting, chạy đủ endpoint/ID trước sửa runtime. Kết quả và những lượt harness/cleanup được giữ ở [TENANT_MATRIX_VERIFY_20261008.md](TENANT_MATRIX_VERIFY_20261008.md). PLAN chung cho các controller lỗi, có phân loại scope riêng để không đổi nghiệp vụ Domain: [TENANT_SCOPE_FIX_PLAN_20261008.md](TENANT_SCOPE_FIX_PLAN_20261008.md), **chờ duyệt**. Chưa BUILD runtime, chưa thêm matrix vào regression, chưa commit fix hoặc tạo lại manifest.


## Tiếp tục 09/10/2026

Gate1.3 đã PASS trên commit c850158. Lô1:1.4 PASS36/36;0.2/2.7 PASS trong suite98/98;2.6 CHƯA CHẠY đủ expiry;3.6 CHƯA CHẠY concurrency thật vì chưa có shared fixture an toàn. Bằng chứng và lý do [BATCH1_HTTP_VERIFY_20261009.md](BATCH1_HTTP_VERIFY_20261009.md). Không thay trạng thái lô2/lô3/UAT/P3 từ kết quả này.


Lô2 ngày09/10:0.3PASS;0.4FAIL (21case,7PASS/14FAIL,cleanupPASS). Dừng và chờ PLAN_REQUIRED_FIELDS_20261009.md. 2.2/3.10/5.5/8.3 vàlô3 chưa chạy bổ sung. Committed regression5533099 trướcprobe này:58PASS.
