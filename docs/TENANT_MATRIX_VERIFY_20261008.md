# Tenant matrix 1.3 — 08/10/2026

## Kết quả trước khi sửa runtime

Lệnh thực chạy: `.tools/php83/php.exe tests/pm_tenant_matrix_smoke.php`; exit **1**, stderr rỗng. Runtime là commit `26ca279095e26c30960cf82294842fefe1f548b0`; test matrix đang local chưa commit. `git -c safe.directory=D:/derasoft-pm diff --name-only HEAD -- classes modules includes templates css js admin.php pm_ajax.php` exit0, output rỗng: không sửa runtime.

**80 cặp endpoint/ID: 39 PASS / 41 FAIL; 160 request negative + 11 control = 171 request HTTP.** Lượt cuối 2026-10-08T10:31:02+07:00 → 2026-10-08T10:31:16+07:00. Cleanup PASS; 21 bảng thật hash trước/sau bằng nhau, users không đổi trường, tracking max_id/count trước/sau đều5345. Dừng nhóm1 và các lô sau. Không suy từ 39 cặp PASS thành mục1.3 PASS.

Mỗi cặp chạy ID foreign có thật trong temporary mirror và ID999000000 được query xác nhận không tồn tại. PASS đòi403/404, cùng trạng thái và thông báo, thông báo hiện trong HTML hoặc error JSON đã decode, không thay đổi fixture, không audit/tracking mới, không lộ sentinel foreign. 80/80 cặp có parity status/message; 41 cặp FAIL đều HTTP200, không phải parity hay vượt quyền ghi.

## Bảng theo endpoint

| Endpoint | Cặp PASS | Cặp FAIL |
|---|---:|---:|
| /admin.php pmprojects | 8 | 9 |
| /pm_ajax.php pmtaskstatus | 2 | 0 |
| /admin.php pmtimesheets | 3 | 2 |
| /admin.php pmallocations | 12 | 1 |
| /pm_ajax.php pmallocations | 2 | 1 |
| /admin.php pmusers | 0 | 18 |
| /admin.php pmcosts | 1 | 0 |
| /pm_ajax.php pmcosts | 1 | 0 |
| /admin.php pmreports | 6 | 10 |
| /admin.php pmimports | 4 | 0 |

## Ngoại lệ ca gốc

Bằng chứng chạy service thực trên cùng temporary fixture, giữ checksum request:
```json
{
  "exception": "OutOfBoundsException",
  "source": "classes/services/pmprojectservice.class.php",
  "line": 122,
  "unchanged": true
}
```

Nguồn throw: `classes/services/pmprojectservice.class.php:122`, `PmProjectService::saveTask()` lookup store_id/project_id/task_id không có row. Là **OutOfBoundsException tại service**, không phải InvalidArgumentException. Controller `modules/admin/pmprojects.module.php:37` bắt chung và không đặt HTTP status. Không cần đổi loại ngoại lệ cho ca task_id gốc. Một số ID tham chiếu khác thực sự đang dùng InvalidArgumentException; phạm vi đổi chúng được nêu riêng ở PLAN, chưa BUILD.

## Bảng từng cặp thực chạy

Foreign IDs: project900000090, task900000091, user900000092, department900000093, hidden department900000089, role900000094, rate900000095, timesheet900000096, allocation900000097, capacity900000098, import900000099. Missing999000000 cho từng bảng.

| # | Endpoint / action / ID | Role | Foreign HTTP | Missing HTTP | Cùng status/message | Kết quả | Thông báo / lý do |
|---:|---|---|---:|---:|---|---|---|
| 1 | /admin.php pmprojects GET read / project_id | PM | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 2 | /admin.php pmprojects POST project_save / project_id | PM | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 3 | /admin.php pmprojects POST project_delete / project_id | PM | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 4 | /admin.php pmprojects POST member_save / project_id | PM | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 5 | /admin.php pmprojects POST task_save / project_id | PM | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 6 | /admin.php pmprojects POST task_status / project_id | PM | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 7 | /admin.php pmprojects POST task_delete / project_id | PM | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 8 | /admin.php pmprojects POST task_save / task_id | PM | 200 | 200 | Có | **FAIL** | Không tìm thấy task. — foreign: HTTP200; missing: HTTP200 |
| 9 | /admin.php pmprojects POST task_status / task_id | PM | 200 | 200 | Có | **FAIL** | Không tìm thấy task. — foreign: HTTP200; missing: HTTP200 |
| 10 | /admin.php pmprojects POST task_delete / task_id | PM | 200 | 200 | Có | **FAIL** | Không tìm thấy task. — foreign: HTTP200; missing: HTTP200 |
| 11 | /admin.php pmprojects POST member_save / member_id | PM | 200 | 200 | Có | **FAIL** | Thành viên phải đang hoạt động trong tenant. — foreign: HTTP200; missing: HTTP200 |
| 12 | /admin.php pmprojects POST task_save / assignee_id | PM | 200 | 200 | Có | **FAIL** | Task chỉ được giao cho thành viên active của dự án. — foreign: HTTP200; missing: HTTP200 |
| 13 | /admin.php pmprojects POST project_save / manager_id | ADMIN | 200 | 200 | Có | **FAIL** | Người phụ trách phải là Admin/PM đang hoạt động trong tenant. — foreign: HTTP200; missing: HTTP200 |
| 14 | /pm_ajax.php pmtaskstatus POST status / project_id | PM | 404 | 404 | Có | **PASS** | Không tìm thấy công việc trong phạm vi truy cập. |
| 15 | /pm_ajax.php pmtaskstatus POST status / task_id | PM | 404 | 404 | Có | **PASS** | Không tìm thấy công việc trong phạm vi truy cập. |
| 16 | /admin.php pmtimesheets GET read / id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy bản ghi chấm công. |
| 17 | /admin.php pmtimesheets POST save / id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy bản ghi chấm công. |
| 18 | /admin.php pmtimesheets POST delete / id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy bản ghi chấm công. |
| 19 | /admin.php pmtimesheets POST save / task_id | ADMIN | 200 | 200 | Có | **FAIL** | Chỉ có thể ghi công cho task được giao cho bạn trong dự án đang hoạt động. — foreign: HTTP200; missing: HTTP200 |
| 20 | /admin.php pmallocations GET read / id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy phân bổ. |
| 21 | /admin.php pmallocations POST save / id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy phân bổ. |
| 22 | /admin.php pmallocations POST delete / id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy phân bổ. |
| 23 | /admin.php pmallocations POST save / task_id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy task. |
| 24 | /admin.php pmallocations POST save / user_id | ADMIN | 403 | 403 | Có | **PASS** | Task, nhân viên hoặc membership không active/không phù hợp. |
| 25 | /admin.php pmallocations POST capacity / id | ADMIN | 403 | 403 | Có | **PASS** | Không thể đổi chủ sở hữu capacity. |
| 26 | /admin.php pmallocations POST capacity / user_id | ADMIN | 403 | 403 | Có | **PASS** | Nhân viên không active trong tenant. |
| 27 | /admin.php pmallocations POST capacity_delete / id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy capacity. |
| 28 | /admin.php pmallocations GET read / project_id | PM | 403 | 403 | Có | **PASS** | Dự án ngoài phạm vi hoặc đã ẩn. |
| 29 | /admin.php pmallocations GET read / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 30 | /pm_ajax.php pmallocations GET read / project_id | PM | 403 | 403 | Có | **PASS** | Dự án ngoài phạm vi hoặc đã ẩn. |
| 31 | /pm_ajax.php pmallocations GET read / user_id | ADMIN | 200 | 200 | Có | **FAIL** | (không có thông báo lỗi) — foreign: HTTP200; missing: HTTP200 |
| 32 | /pm_ajax.php pmallocations GET suggestions / project_id | PM | 403 | 403 | Có | **PASS** | Dự án ngoài phạm vi hoặc đã ẩn. |
| 33 | /admin.php pmusers POST save / id | ADMIN | 200 | 200 | Có | **FAIL** | Không tìm thấy nhân sự trong đơn vị hiện tại. — foreign: HTTP200; missing: HTTP200 |
| 34 | /admin.php pmusers POST status / id | ADMIN | 200 | 200 | Có | **FAIL** | Không thể hoàn tất thao tác. Vui lòng kiểm tra dữ liệu và thử lại. — foreign: HTTP200; missing: HTTP200 |
| 35 | /admin.php pmusers POST roles / id | ADMIN | 200 | 200 | Có | **FAIL** | User was not found in this tenant. — foreign: HTTP200; missing: HTTP200 |
| 36 | /admin.php pmusers POST roles / role_ids | ADMIN | 200 | 200 | Có | **FAIL** | Invalid role for tenant. — foreign: HTTP200; missing: HTTP200 |
| 37 | /admin.php pmusers POST save / department_id | ADMIN | 200 | 200 | Có | **FAIL** | Phòng ban không thuộc tenant hiện tại. — foreign: HTTP200; missing: HTTP200 |
| 38 | /admin.php pmusers POST department_save / id | ADMIN | 200 | 200 | Có | **FAIL** | Không tìm thấy phòng ban trong tenant hiện tại. — foreign: HTTP200; missing: HTTP200 |
| 39 | /admin.php pmusers POST department_status / id | ADMIN | 200 | 200 | Có | **FAIL** | Không thể hoàn tất thao tác. Vui lòng kiểm tra dữ liệu và thử lại. — foreign: HTTP200; missing: HTTP200 |
| 40 | /admin.php pmusers POST department_delete / id | ADMIN | 200 | 200 | Có | **FAIL** | Không thể hoàn tất thao tác. Vui lòng kiểm tra dữ liệu và thử lại. — foreign: HTTP200; missing: HTTP200 |
| 41 | /admin.php pmusers POST department_restore / id | ADMIN | 200 | 200 | Có | **FAIL** | Không tìm thấy phòng ban đã ẩn trong tenant hiện tại. — foreign: HTTP200; missing: HTTP200 |
| 42 | /admin.php pmusers POST rate / id | ADMIN | 200 | 200 | Có | **FAIL** | Rate user was not found in this tenant. — foreign: HTTP200; missing: HTTP200 |
| 43 | /admin.php pmusers POST rate_save / rate_user_id | ADMIN | 200 | 200 | Có | **FAIL** | Rate user was not found in this tenant. — foreign: HTTP200; missing: HTTP200 |
| 44 | /admin.php pmusers POST rate_save / rate_role_id | ADMIN | 200 | 200 | Có | **FAIL** | Rate role was not found in this tenant. — foreign: HTTP200; missing: HTTP200 |
| 45 | /admin.php pmusers POST rate_save / rate_id | ADMIN | 200 | 200 | Có | **FAIL** | Không tìm thấy đơn giá trong tenant hiện tại. — foreign: HTTP200; missing: HTTP200 |
| 46 | /admin.php pmusers POST rate_deactivate / id | ADMIN | 200 | 200 | Có | **FAIL** | Không tìm thấy đơn giá đang hoạt động. — foreign: HTTP200; missing: HTTP200 |
| 47 | /admin.php pmusers GET read / department_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 48 | /admin.php pmusers GET read / role_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 49 | /admin.php pmcosts GET read / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án không tồn tại hoặc ngoài phạm vi của bạn. |
| 50 | /pm_ajax.php pmcosts GET read / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án không tồn tại hoặc ngoài phạm vi của bạn. |
| 51 | /admin.php pmreports GET hours / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án ngoài phạm vi. |
| 52 | /admin.php pmreports POST export / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án ngoài phạm vi. |
| 53 | /admin.php pmreports GET hours / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 54 | /admin.php pmreports POST export / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 55 | /admin.php pmreports GET tasks / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án ngoài phạm vi. |
| 56 | /admin.php pmreports POST export / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án ngoài phạm vi. |
| 57 | /admin.php pmreports GET tasks / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 58 | /admin.php pmreports POST export / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 59 | /admin.php pmreports GET costs / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án không tồn tại hoặc ngoài phạm vi của bạn. |
| 60 | /admin.php pmreports POST export / project_id | ADMIN | 403 | 403 | Có | **PASS** | Dự án không tồn tại hoặc ngoài phạm vi của bạn. |
| 61 | /admin.php pmreports GET costs / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 62 | /admin.php pmreports POST export / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 63 | /admin.php pmreports GET users / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 64 | /admin.php pmreports POST export / user_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 65 | /admin.php pmreports GET projects / project_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 66 | /admin.php pmreports POST export / project_id | ADMIN | 200 | 200 | Có | **FAIL** |  — foreign: HTTP200; missing: HTTP200 |
| 67 | /admin.php pmimports POST detail / import_id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy batch trong tenant. |
| 68 | /admin.php pmimports POST apply / import_id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy batch trong tenant. |
| 69 | /admin.php pmimports POST resume / import_id | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy batch trong tenant. |
| 70 | /admin.php pmimports POST provision_password / import_id | ADMIN | 403 | 403 | Có | **PASS** | Chỉ cấp mật khẩu cho tài khoản import của dòng này đang chưa kích hoạt. |
| 71 | /admin.php pmprojects GET read / project_id [Admin read] | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy dự án. |
| 72 | /admin.php pmprojects POST member_save / member_id [remove member] | PM | 200 | 200 | Có | **FAIL** | Không tìm thấy thành viên trong dự án. — foreign: HTTP200; missing: HTTP200 |
| 73 | /admin.php pmprojects POST project_save / manager_id [create project] | ADMIN | 200 | 200 | Có | **FAIL** | Người phụ trách phải là Admin/PM đang hoạt động trong tenant. — foreign: HTTP200; missing: HTTP200 |
| 74 | /admin.php pmprojects POST task_save / assignee_id [create task] | PM | 200 | 200 | Có | **FAIL** | Task chỉ được giao cho thành viên active của dự án. — foreign: HTTP200; missing: HTTP200 |
| 75 | /admin.php pmtimesheets POST save / task_id [create timesheet] | ADMIN | 200 | 200 | Có | **FAIL** | Chỉ có thể ghi công cho task được giao cho bạn trong dự án đang hoạt động. — foreign: HTTP200; missing: HTTP200 |
| 76 | /admin.php pmallocations POST save / task_id [edit allocation] | ADMIN | 403 | 403 | Có | **PASS** | Không tìm thấy task. |
| 77 | /admin.php pmallocations POST save / user_id [edit allocation] | ADMIN | 403 | 403 | Có | **PASS** | Task, nhân viên hoặc membership không active/không phù hợp. |
| 78 | /admin.php pmallocations POST capacity / user_id [edit capacity] | ADMIN | 403 | 403 | Có | **PASS** | Nhân viên không active trong tenant. |
| 79 | /admin.php pmusers POST roles / primary_role_id | ADMIN | 200 | 200 | Có | **FAIL** | Primary role must be assigned. — foreign: HTTP200; missing: HTTP200 |
| 80 | /admin.php pmusers POST save / department_id [create personnel] | ADMIN | 200 | 200 | Có | **FAIL** | Phòng ban không thuộc tenant hiện tại. — foreign: HTTP200; missing: HTTP200 |

## Controls và phạm vi

| Control | HTTP | Kết quả |
|---|---:|---|
| pmusers | 200 | PASS |
| pmprojects | 200 | PASS |
| pmtimesheets | 200 | PASS |
| pmaudit | 200 | PASS |
| pmcosts | 200 | PASS |
| pmallocations | 200 | PASS |
| pmreports | 200 | PASS |
| pmimports | 200 | PASS |
| /pm_ajax.php pmcosts | 200 | PASS |
| /pm_ajax.php pmallocations | 200 | PASS |
| /pm_ajax.php pmtaskstatus | 200 | PASS |

Controls kiểm HTML/JSON không lộ tên hay ID foreign seed; audit control có cả bản ghi own và foreign trong temporary audit mirror. Không có endpoint PM đọc chi tiết audit theo ID; pmaudit chỉ list theo entity_type/action nên là control scoped-list. pm/profile/password tự lấy actor từ session, không nhận targetID. Settings, Preview/Stage upload, template không nhận resourceID; CSRF/auth/tenant-input injection thuộc các mục khác, chưa dùng matrix này nâng1.4/0.2/2.6/2.7. Matrix kiểm các vị trí resourceID hiện được route PM thực sự nhận, không gọi key bị bỏ qua là IDOR PASS. Không chứng minh mọi vai trò hoặc lịch sử chưa có fixture; UAT/nhóm khác giữ trạng thái cũ.

Artifacts ignored: `.local/tenant-matrix-full-20261008.json` (từng request ms, message/flag và hashes); `.local/tenant-matrix-full-command-20261008.json` (stdout/exit/stderr); không chứa bản dump rows hoặc credential.

## Lượt lỗi harness và cleanup — giữ bằng chứng

- Lượt đầu hash giả định mọi bảng có id, lỗi trước server: SQL unknown column id. Đổi checksum sang sort các row trong PHP để hỗ trợ bảng khóa composite; không đổi schema.
- Fixture cost đầu có MySQL1137 do reopen temporary alias; probe tái hiện1137, observer thấy alias ur. Route cost chỉ đọc rate/role assignments, nên không mirror hai bảng này, dùng quyền Admin local thật đã SELECT xác nhận. Hash persistent hai bảng vẫn nằm trong kiểm tra trước/sau. Không sửa query sản phẩm, không tạo bảng thật.
- Ca manager_id dùng PM ban đầu nhận303 vì service cố định manager=actor; đổi ca tham chiếu manager sang Admin. Không ghi nhầm thành lỗi quyền.
- Kiểm thông báo JSON ban đầu so raw Unicode escape với chữ đã decode; sửa harness dùng error JSON thực đã decode. Không thay encoding sản phẩm.
- Một lượt 10:27:14 kết thúc cleanup FAIL: dc_users/dc_trackings hash khác, các bảng PM và trạng thái từng request không đổi. Bằng chứng giữ `.local/tenant-matrix-cleanup-fail-20261008.json` và file command tương ứng. Probe read-only `.tools/php83/php.exe .local/tenant_cleanup_readonly_probe.php` exit0: tracking5345 action_kind=login, date_created10:27:11; userID111 last_login cùng thời điểm. Có dấu hiệu đăng nhập đồng thời, chưa xác định nguồn request cụ thể; không dùng lượt sau để xóa FAIL này, không khôi phục dữ liệu thật.
- Lượt chẩn đoán 10:29:02–10:29:17 và lượt cuối sau đó cleanup PASS; vẫn39PASS/41FAIL endpoint-ID. Có giám sát field-name-only và tracking count/max để ghi biến động đồng thời; không in giá trị user.

## Lint và review

`.tools/php83/php.exe -l tests/pm_tenant_matrix_router.php` và `-l tests/pm_tenant_matrix_smoke.php`: exit0, No syntax errors detected. Review: guard local DB/token/loopback/route-method/action; mọi write target temporary exact-schema (FK clauses chỉ bỏ ở mirror do giới hạn MySQL); không UPDATE users thật; sidecar không serialize user rows/credential; SQL input prepared; only own server stopped; cleanup finally và failed row collection giữ exit1. Không phát hiện defect mở trong harness đã sửa ở lượt cuối; hạn chế concurrent local activity và temporary cost aliases giữ rõ phía trên.

## Tiếp theo

[PLAN sửa chung — chờ duyệt](TENANT_SCOPE_FIX_PLAN_20261008.md). Không sửa runtime, chưa thêm matrix vào regression, chưa commit fix, chưa tạo lại manifest. P3 chờA/B/C. Không push/merge/deploy.


## Cập nhật sau duyệt và BUILD — 08/10/2026

Bốn bảng đã được người dùng duyệt. BUILD/VERIFY theo từng nhóm, commit A b70b441, B a3b17e3, C c850158. Matrix cuối trên commit C: 80/80 cặp, 11 control, 35 ca hành vi PASS; regression 56 script PASS; 21 bảng thật không đổi. Các phát hiện/lỗi baseline ở trên được giữ làm lịch sử, không phải trạng thái hiện tại. Xem TEST_REPORT_FULL.md và TENANT_GROUP_A_VERIFY_20261008.md, TENANT_GROUP_B_VERIFY_20261008.md, TENANT_GROUP_C_VERIFY_20261008.md. P3 vẫn chờ A/B/C. Không push/merge/deploy/migration.
