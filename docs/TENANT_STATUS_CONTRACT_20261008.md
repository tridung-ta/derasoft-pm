# Bảng 4 — Mã trạng thái và thông báo

Ngày 08/10/2026. **Chỉ rà soát code; chờ duyệt bốn bảng trước BUILD tiếp.** HEAD `26ca279095e26c30960cf82294842fefe1f548b0`; working tree đã có sửa local theo lượt duyệt trước, không commit/push/merge/deploy. Các vị trí dòng bên dưới theo working tree hiện tại, không giả định đây là code đã phát hành.

**Cột foreign/missing là kết quả đã đo trước điều kiện mới; các cột quyền/format là static contract, CHƯA VERIFY.** Không dùng80 PASS cũ để coi điều kiện mới đã đạt.

Thông báo đề xuất thống nhất cho foreign/missing: **“Không thể truy cập dữ liệu.”** Không nhắc ID, tenant, user/task/dự án/batch hoặc loại tài nguyên. Foreign/missing dùng cùng mã trong mỗi đường xử lý (403 hoặc404 theo bảng), cùng error và cùng trạng thái dữ liệu/audit/tracking. Nội dung hiện tại ở cột cuối cho thấy nhiều nơi chưa đạt điều kiện “không chứa loại tài nguyên”; chưa sửa các thông báo này.

| Ca | Endpoint/action/ID | Foreign / missing hiện đo | Local ngoài quyền dự kiến | ID sai định dạng | Message hiện tại |
|---|---|---|---|---|---|
| 1 | /admin.php pmprojects GET read / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 2 | /admin.php pmprojects POST project_save / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 3 | /admin.php pmprojects POST project_delete / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 4 | /admin.php pmprojects POST member_save / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 5 | /admin.php pmprojects POST task_save / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 6 | /admin.php pmprojects POST task_status / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 7 | /admin.php pmprojects POST task_delete / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 8 | /admin.php pmprojects POST task_save / task_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy task. |
| 9 | /admin.php pmprojects POST task_status / task_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy task. |
| 10 | /admin.php pmprojects POST task_delete / task_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy task. |
| 11 | /admin.php pmprojects POST member_save / member_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 12 | /admin.php pmprojects POST task_save / assignee_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 13 | /admin.php pmprojects POST project_save / manager_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 14 | /pm_ajax.php pmtaskstatus POST status / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 422 (array router400) | Không tìm thấy công việc trong phạm vi truy cập. |
| 15 | /pm_ajax.php pmtaskstatus POST status / task_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 422 (array router400) | Không tìm thấy công việc trong phạm vi truy cập. |
| 16 | /admin.php pmtimesheets GET read / id | 403 / 403 | 403 task không được giao; sheet xem bảng riêng | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy bản ghi chấm công. |
| 17 | /admin.php pmtimesheets POST save / id | 403 / 403 | 403 task không được giao; sheet xem bảng riêng | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy bản ghi chấm công. |
| 18 | /admin.php pmtimesheets POST delete / id | 403 / 403 | 403 task không được giao; sheet xem bảng riêng | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy bản ghi chấm công. |
| 19 | /admin.php pmtimesheets POST save / task_id | 403 / 403 | 403 task không được giao; sheet xem bảng riêng | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Chỉ có thể ghi công cho task được giao cho bạn trong dự án đang hoạt động. |
| 20 | /admin.php pmallocations GET read / id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy phân bổ. |
| 21 | /admin.php pmallocations POST save / id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy phân bổ. |
| 22 | /admin.php pmallocations POST delete / id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy phân bổ. |
| 23 | /admin.php pmallocations POST save / task_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy task. |
| 24 | /admin.php pmallocations POST save / user_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Task, nhân viên hoặc membership không active/không phù hợp. |
| 25 | /admin.php pmallocations POST capacity / id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không thể đổi chủ sở hữu capacity. |
| 26 | /admin.php pmallocations POST capacity / user_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Nhân viên không active trong tenant. |
| 27 | /admin.php pmallocations POST capacity_delete / id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy capacity. |
| 28 | /admin.php pmallocations GET read / project_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Dự án ngoài phạm vi hoặc đã ẩn. |
| 29 | /admin.php pmallocations GET read / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 30 | /pm_ajax.php pmallocations GET read / project_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Dự án ngoài phạm vi hoặc đã ẩn. |
| 31 | /pm_ajax.php pmallocations GET read / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 32 | /pm_ajax.php pmallocations GET suggestions / project_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Dự án ngoài phạm vi hoặc đã ẩn. |
| 33 | /admin.php pmusers POST save / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 34 | /admin.php pmusers POST status / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 35 | /admin.php pmusers POST roles / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 36 | /admin.php pmusers POST roles / role_ids | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy vai trò trong đơn vị hiện tại. |
| 37 | /admin.php pmusers POST save / department_id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy bộ lọc trong đơn vị hiện tại. |
| 38 | /admin.php pmusers POST department_save / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy phòng ban trong tenant hiện tại. |
| 39 | /admin.php pmusers POST department_status / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy phòng ban trong tenant hiện tại. |
| 40 | /admin.php pmusers POST department_delete / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy phòng ban trong tenant hiện tại. |
| 41 | /admin.php pmusers POST department_restore / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy phòng ban trong tenant hiện tại. |
| 42 | /admin.php pmusers POST rate / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Rate user was not found in this tenant. |
| 43 | /admin.php pmusers POST rate_save / rate_user_id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Rate user was not found in this tenant. |
| 44 | /admin.php pmusers POST rate_save / rate_role_id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Rate role was not found in this tenant. |
| 45 | /admin.php pmusers POST rate_save / rate_id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy đơn giá trong tenant hiện tại. |
| 46 | /admin.php pmusers POST rate_deactivate / id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy đơn giá đang hoạt động. |
| 47 | /admin.php pmusers GET read / department_id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy bộ lọc trong đơn vị hiện tại. |
| 48 | /admin.php pmusers GET read / role_id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy bộ lọc trong đơn vị hiện tại. |
| 49 | /admin.php pmcosts GET read / project_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Dự án không tồn tại hoặc ngoài phạm vi của bạn. |
| 50 | /pm_ajax.php pmcosts GET read / project_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Dự án không tồn tại hoặc ngoài phạm vi của bạn. |
| 51 | /admin.php pmreports GET hours / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 52 | /admin.php pmreports POST export / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 53 | /admin.php pmreports GET hours / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 54 | /admin.php pmreports POST export / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 55 | /admin.php pmreports GET tasks / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 56 | /admin.php pmreports POST export / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 57 | /admin.php pmreports GET tasks / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 58 | /admin.php pmreports POST export / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 59 | /admin.php pmreports GET costs / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 60 | /admin.php pmreports POST export / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 61 | /admin.php pmreports GET costs / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 62 | /admin.php pmreports POST export / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 63 | /admin.php pmreports GET users / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 64 | /admin.php pmreports POST export / user_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 65 | /admin.php pmreports GET projects / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 66 | /admin.php pmreports POST export / project_id | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy dự án. |
| 67 | /admin.php pmimports POST detail / import_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy batch trong tenant. |
| 68 | /admin.php pmimports POST apply / import_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy batch trong tenant. |
| 69 | /admin.php pmimports POST resume / import_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy batch trong tenant. |
| 70 | /admin.php pmimports POST provision_password / import_id | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Chỉ cấp mật khẩu cho tài khoản import của dòng này đang chưa kích hoạt. |
| 71 | /admin.php pmprojects GET read / project_id [Admin read] | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy dự án. |
| 72 | /admin.php pmprojects POST member_save / member_id [remove member] | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 73 | /admin.php pmprojects POST project_save / manager_id [create project] | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 74 | /admin.php pmprojects POST task_save / assignee_id [create task] | 404 / 404 | 403 ngoài quyền; local inactive/ẩn theo policy | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy nhân sự trong đơn vị hiện tại. |
| 75 | /admin.php pmtimesheets POST save / task_id [create timesheet] | 403 / 403 | 403 task không được giao; sheet xem bảng riêng | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Chỉ có thể ghi công cho task được giao cho bạn trong dự án đang hoạt động. |
| 76 | /admin.php pmallocations POST save / task_id [edit allocation] | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Không tìm thấy task. |
| 77 | /admin.php pmallocations POST save / user_id [edit allocation] | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Task, nhân viên hoặc membership không active/không phù hợp. |
| 78 | /admin.php pmallocations POST capacity / user_id [edit capacity] | 403 / 403 | 403 ngoài quyền; local inactive/ẩn theo policy | 400 | Nhân viên không active trong tenant. |
| 79 | /admin.php pmusers POST roles / primary_role_id | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy vai trò trong đơn vị hiện tại. |
| 80 | /admin.php pmusers POST save / department_id [create personnel] | 404 / 404 | 403 thiếu quyền action; Admin local được phép | 200 nếu InvalidArgument; hiện ép int, cần tách ca | Không tìm thấy bộ lọc trong đơn vị hiện tại. |

## Đường ngoài80 cặp và quyền local

| Endpoint/nhánh | Foreign | Missing | Local ngoài quyền | Sai định dạng |
|---|---|---|---|---|
|pm/dashboard,team,profile,password|Không nhận targetID|Không nhận targetID|403 middleware; profile actor stale hiệnDomain 200 cần review|profile/password200 validation|
|pmaudit list|Không nhận entityID|Không nhận entityID|403 middleware; DAO Domain catchThrowable200 cần review|400 enum invalid|
|pmcosts user_id/role_code HTML/AJAX|Không nhận/chuyển filter này|Không nhận/chuyển filter này|Không mở rộng API; service nhận filter qua pmreports đã phủ|400 cho numeric filter được hỗ trợ|
|pmimports preview/stage department_id trong file|Lỗi dòng/refusedfile, chưa matrixpair|Lỗi dòng/refusedfile, chưa matrixpair|Admin only403; giữ Stage wholefile refusal|400 upload; rowvalidation theo dòng|
|pmimports provision source_row|Không là tenantID độc lập|Servicevalidation/status, chưa đoHTTP|Batch scoped trước row; markerownership403|400 rowformat|
|pmtimesheets settings|Store session, không targetID|Không áp dụng|Hiện nonAdmin Domain 200; đề xuất scope 403|200 InvalidArgument|
|pm_ajax root|Unknown op404|Unknown op404|Anonymous401,CSRF403,wrongmethod405|Scalar guard400; pmtaskstatusID422|

## Chấm công — ba trường hợp task

| task_id | HTTP dự kiến | Thông báo | Cần test bổ sung |
|---|---|---|---|
|Task của tenant khác|403|Không thể truy cập dữ liệu.|Đã đo403 trong80; message vẫn nhắc task nên chưa đạt điều kiện mới.|
|Task không tồn tại|403|Không thể truy cập dữ liệu.|Cùng mã/message với foreign; không query tenant khác để phân biệt.|
|Task tồn tại cùng tenant nhưng không được giao/không có membership active/không thuộc quyền actor|403|Không thể truy cập dữ liệu.|80 chưa phủ localunassigned; phải thêm ownershipcase.|

- Task local ẩn/projectẩn không được dùng cho lần ghi mới; dùng lỗi403 không lộ trạng thái. Ngoại lệ **Admin sửa bản ghi lịch sử, giữ task_id cũ** hiện có phải giữ nguyên.
- id timesheet GET hiệnOutOfBounds→403. POST có OutOfBounds404 nhưng GET edit reload cuối trang có thể overwrite403;80 dùngAdmin foreign/missing cuối cùng403. Cần chốt rõ GET/POSTsheet: đề xuất403 với mọi foreign/missing/không thuộc actor để không phân biệt; không dựa vào reload để xác định status. Chưa BUILD.
- ID malformed: pmprojects/pmusers/pmtimesheets castint ở nhiều vị trí; ID chữ có thể thành0 và bị coi là tạo mới/no-op, không luôn tạoInvalidArgument. **Không thể xác nhận200mọi ID malformed chỉ bằng đọc code**. Đề xuất prevalidate optionalID trước cast, throwInvalidArgument→200+message; cần duyệt cùng bảng, không tự thực hiện. GETproject cần catchInvalidArgument 200. AJAX giữ422; allocations/reports/import giữ400; arrays router400 đã có.
- Local resource thiếu quyền rõ ràng dùng subtype403 trướcparent; validation/inactive/duplicate business giữDomain hoặcInvalidArgument theo hành vi đã duyệt, không gom business thành403.
- PmProjectDateRangeException303; Save/Hideproject thành công303; task same-day hợp lệ; không đổi OT/costsnapshot/daylocking/hiddenhistory.

## Preflight và bằng chứng hiện có

Lượt matrix đã chạy trước yêu cầu mới: .tools/php83/php.exe tests/pm_tenant_matrix_smoke.php:80 PASS/0FAIL,160negative+11controls,21 persistenthashes unchanged,exit0. **Không chạy matrix cuối sau yêu cầu mới.** Test hiện kiểm tra cổng18776rảnh và tạo session riêng; chưa chứng minh không có phiên/tiến trình lạ. Đọc process sau khi dừng: PHP PID37372 chạy8088 từ terminalVSCode PID36288, chưa kết thúc tiến trình người dùng. Không có PHPfixture/harnesssót trong danh sách lúc kiểm tra. Không tuyên bố “không phiên lạ” vì chưa inventorysession hoạt động.

Một lượt baseline trước đây có persistentcleanupFAIL khi tracking5345login và user111last_login cùng10:27:11; nguồn request chưa xác định. Giữ .local/tenant-matrix-cleanup-fail-20261008.json; không gán cho người dùng hoặc matrix khi chưa đủ chứng cứ. Lượt80 PASS cuối có changed_user_fields=[] và tất cả21hashtrước/sau giống.

Regression dừngexit1 tại pm_projects_smoke: assignee2147483647 nay némOutOfBounds trong khi test cũ chỉ catchInvalidArgument. Không sửaassertion hay chạy tiếp lô. Lệnh powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1; output .local/tenant-fix-regression-20261008.json.

## Migration009 — dependency bắt buộc (chỉ đọc)

| Bảng.cột | Khả năng NULL | Tính năng đọc/ghi | Runtime thiếu cột |
|---|---|---|---|
|dc_pm_projects.client_name|VARCHAR150 NULL|Tạo/sửa khách hàng; xuất danh sách dự án|ProjectINSERT/UPDATE và directorySELECT lỗiunknowncolumn; không an toàn.|
|dc_pm_project_members.project_role|VARCHAR50 NULL|Vai trò thành viên; listmembers/upsert|SELECT/INSERT nhắc trực tiếp; không an toàn.|
|dc_pm_tasks.start_date|DATE NULL|Ngày bắt đầu task; báo cáo task|UPDATEmetadata/reportSELECT nhắc trực tiếp; không an toàn.|
|dc_pm_tasks.completed_at|DATETIME NULL|Kanban trạng thái/ngày hoàn thành; báo cáo hoàn thành/quá hạn|UPDATEstatus và reportSELECT/aggregate nhắc trực tiếp; không an toàn.|

Đọc database/migrations/009_add_pm_project_task_metadata.sql, PmProjectService::saveProject/members/setMember/saveTask/changeTaskStatus, PmReports::tasks/directory. Không có schemafeatureguard/fallback cho các câu trên. **Không triển khai runtimePhase11 lên DB chưa đủ bốn cột.** Một số trangkhác có thể tải nhưng không đủ để nói runtimean toàn.009chỉ4ADDCOLUMN, kiểm tồn tại mỗi cột, không backfill; phụ thuộc003projects/members/tasks và001/002; rollbackcode giữcột; không chạy migration ở lầnrà.
