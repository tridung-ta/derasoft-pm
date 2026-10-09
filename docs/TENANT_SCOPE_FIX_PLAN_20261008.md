# PLAN sửa phạm vi Tenant1.3 — 08/10/2026

**PLAN đã được duyệt ở lượt trước; BUILD local đã bắt đầu, hiện DỪNG theo gate bốn bảng mới.** Xem TENANT_SCOPE_REVIEW_GATE_20261008.md; chưa commit. Thay PLAN sơ bộ trong VERIFY_BATCHES_20261008.md. Người dùng yêu cầu chạy collecting matrix trước, rồi dừng duyệt lại mọi controller có lỗi. P3 chờA/B/C; không migration/push/merge/deploy.

## Bằng chứng và mục tiêu

[Matrix trước sửa](TENANT_MATRIX_VERIFY_20261008.md):80 cặp,39PASS/41FAIL;160 negative requests +11 controls; exit1. Lượt cuối cleanup21bảng PASS. Các cặp foreign/missing có cùng status/message nhưng41cặp trả200. Không phát hiện write/leak trong các ca đó. Có một lượt cleanupFAIL và bằng chứng login đồng thời được giữ riêng, không quy kết nguồn request chưa xác định.

Ca gốc task_save/task_id: service thực ném OutOfBoundsException ở pmprojectservice.class.php:122; checksum temporary fixture không đổi. Catch tại pmprojects.module.php:37 bỏ status. Không đổi exception service cho ca này.

Mục tiêu: lookup theo store hiện tại; ID foreign và missing cùng status và cùng thông báo chung; lỗi phạm vi/quyền DomainException→403, resource không tìm thấy OutOfBoundsException→404. **InvalidArgumentException của form pmprojects/pmusers/pmtimesheets giữ200 và thông báo**. Giữ PmProjectDateRangeException/303 và Lưu/Ẩn303. Các validation400/422 đã có ở allocations/reports/import/AJAX không bị đổi ngoài phạm vi này; VERIFY không thêm invalid input400 cho pmprojects.

## Phân loại lỗi — tránh đổi nghiệp vụ ngoài phạm vi

DomainException hiện cũng dùng cho nghiệp vụ như trùng mã/email, khóa ngày, currency và tổng24h. Không được gom tất cả thành lỗi Tenant. Đề xuất một subtype PM nhỏ, `PmScopeDeniedException extends DomainException`, cho các đường quyền/phạm vi hiện đang lẫn với lỗi nghiệp vụ. Controller bắt subtype403, OutOfBoundsException404; InvalidArgumentException và DomainException nghiệp vụ giữ hành vi cũ. Không phân loại bằng so chuỗi thông báo. Đây là phần bổ sung của PLAN chung cần duyệt, chưa tạo class.

Với ID tham chiếu trước đây InvalidArgumentException: chỉ thêm lookup có store_id cho ID số dương trước kiểm tra nghiệp vụ. Ngoài store/không có row→OutOfBoundsException404; row có trong store nhưng không active/không đáp ứng vai trò/membership vẫn theo validator đã duyệt, không tự đổi mọi lỗi đầu vào thành404. Các hạn chế quyền actor rõ ràng dùng DomainException phạm vi/subtype403. Không query tenant khác để phân biệt foreign với missing.

## Phạm vi BUILD đề xuất

| Nguồn | Kết quả trước sửa | Thay đổi đề xuất |
|---|---|---|
|modules/admin/pmprojects.module.php|9cặp FAIL|Tách catch scope403/not-found404 khỏi validation200; GET project cũng xử lý OutOfBounds404 theo mapping đã duyệt. Giữ date-range catch trước và303. Các lỗi nghiệp vụ Domain giữ200 bằng subtype phạm vi rõ ở service.|
|classes/services/pmprojectservice.class.php|member_id/assignee_id/manager_id đang InvalidArgumentException; remove member/task_id đã OutOfBounds|Trước kiểm active/membership/manager role, xác nhận ID tham chiếu có trong store; vắng→OutOfBounds. Tách actor permission/ownership bằng subtype Domain phạm vi. Không đổi số giờ/date/task status hay policy PM luôn là manager; ca manager_id chỉ áp dụng Admin nhận tham chiếu.|
|modules/admin/pmtimesheets.module.php + scope lookup tại service|2cặp FAIL: task_id create/edit trả200|Bắt scope403; chỉ định rõ scope exception ở lookup task được giao. Không đổi số giờ/OT/day locks/cost snapshots/window/concurrency/P3. Các lỗi nhập giờ/ngày/ca giữ200; Domain nghiệp vụ khác giữ hành vi cũ. Các GET id đãPASS không bị sửa nếu không cần cho lỗi mới.|
|modules/admin/pmusers.module.php + DAO scope helpers|18cặp FAIL|Catch scope403/OutOfBounds404. Target user/status/department false-return: xác nhận target scoped bằng DAO/prepared query, thiếu→OutOfBounds; không biến mọi RuntimeException thành404 và không coi zero-row UPDATE là bằng chứng missing. Giữ chính sách soft-delete/khóa/khôi phục hiện tại; không đổi boolean/no-op nghiệp vụ ngoài phạm vi.|
|classes/dao/pmusers.class.php|role_ids/primary_role_id đang InvalidArgumentException|Validate scope các roleIDs và primary_role_id trước DELETE/INSERT roles; foreign/missing→OutOfBounds; role tồn tại cùng store nhưng inactive hoặc primary không nằm trong danh sách vẫn validation200 như cũ. Transaction/audit/permissions giữ nguyên.|
|Phòng ban/đơn giá trong pmusers|department input Domain; rate service đãOutOfBounds|Department reference ngoài store/missing dùng lookup scoped→404. Restore có lookup riêng cho hidden; status/delete giữ semantics cũ. Không sửa rate calculation/locking; service rate đãOutOfBounds, chỉ cần controller trả404.|
|classes/services/pmallocationservice.class.php + modules/admin/pmallocations.module.php + modules/ajax/pmallocations.module.php|2cặp FAIL: GET user_id lọc trả200/rỗng|Thêm scoped user existence guard khi user_id được nhập; foreign/missing cùng not-found và404 ở cả HTML/polling. Giữ cảnh báo allocation và task_estimate_warnings, tổng tải ngày/tuần, history và quyền PM. User cùng store không có allocation vẫn200/rỗng.|
|classes/services/pmreportservice.class.php + modules/admin/pmreports.module.php|10cặp FAIL: user filters và directory project filters/Excel trả200/rỗng|Guard scope ID trước nhánh directory/cost/hours/tasks và trước tạoXLSX; missing/foreign→OutOfBounds và controller404. Giữ checks role/ownership Domain403. Project/user tồn tại trong store nhưng không có số liệu trong kỳ vẫn200/rỗng. Không dùng status active để loại lịch sử đã được phép xem, không đổi tính chi phí.|

Các endpoint Kanban AJAX, cost project filter, import batch và phần allocations đãPASS giữ logic hiện tại; chỉ thêm catch cần thiết cho loại exception mới. Không thêm tham số API bị bỏ qua hiện nay. Không rewrite framework/router hoặc DAO legacy; không DDL/data rewrite; user thật chỉ được đọc khi VERIFY.

## Nơi khác bị ảnh hưởng và test phải đối chiếu

- PmProjectService dùng bởi form POST cũ và Kanban AJAX. TaskID gốc giữ OutOfBounds nên Kanban404 hiện tại không đổi; subtype phạm vi vẫn là Domain nên AJAX catch403 giữ được. Member/manager/assignee có thay đổi loại exception cho ID ngoài store: các service assertions phải đối chiếu đúng lookup mới.
- PmUsers::assignRoles và scope helpers dùng bởi pmusers; giữ import Apply/INSERT1062/collision/named locks. Không chuyển engine hay nới validation.
- PmTimesheetService chỉ đổi nhãn exception phạm vi nếu cần ở lookup task; không BUILD yêu cầu giờ mới. Assertions Domain theo instanceof vẫn tương thích subtype; exact-class assertion phải cập nhật rõ lý do.
- pm_role_http_smoke.php hiện gộp CSRF/foreign/missing/invalid input với kỳ vọng200 ở một số vòng. Tách kỳ vọng theo từng ca: scope403/not-found404, invalid data/CSRF form giữ như cũ. Không bỏ assertions dữ liệu/audit/tracking và không nới status cho qua.
- Đối chiếu pm_projects_smoke.php, pm_users_crud_smoke.php, pm_rates_smoke.php, pm_timesheets_smoke.php, pm_allocations_smoke.php, pm_reports_smoke.php và các HTTP suites; chỉ sửa expectation thực sự liên quan.

## VERIFY và thứ tự sau duyệt

1. BUILD đúng các lookup/catch đã duyệt. Target foreign/missing và same-tenant-unowned không đổi dữ liệu/no audit/no tracking; cùng status/message cho từng cặp foreign/missing.
2. Chạy lại `.tools/php83/php.exe tests/pm_tenant_matrix_smoke.php`; phải80/80cặp PASS,11controls PASS,cleanup/hash persistent PASS. Giữ collecting behavior, không fail-fast trong matrix; báo toàn bộ lỗi rồi exit1 nếu có bất kỳ FAIL. Không dùng retry hay bỏ case để đổi trạng thái.
3. Test các dữ liệu nhập sai của form hiện vẫn200, gồm tên/status/giờ/ngày và primary-role relation hợp lệ cùng tenant nhưng không được chọn; test các lỗi nghiệp vụ Domain giữ hành vi cũ. Task date cùng ngày vẫn được; project date rule/redirect303 và Lưu/Ẩn303 còn nguyên. Không có expectation invalid input400 cho pmprojects.
4. Matrix đạt rồi mới thêm vào tests/pm_regression.ps1 và chạy regression đầy đủ. NếuFAIL quay lại nguồn theo bảng, chưa nâng1.3. Review code/security, cập nhật tài liệu và commit local bản sửa hoàn tất theo workflow.
5. Sau commit runtime/test cuối mới tạo lại manifest/hash từ immutable commit đó; cập nhật generator ref và handoff cùng source commit, không lấy workingtree dở.
6. Chỉ nâng1.3 PASS khi toàn bộ matrix đạt; sau đó chạy1.4,0.2,2.6,2.7,3.6, rồi các lô tiếp theo đúng yêu cầu. P3 và5.1/5.2 vẫn chờA/B/C.

## Deploy009 — tài liệu thôi

Đã thêm order15 trong DEPLOY_LOG.md: phụ thuộc bảng project/member/task của003 (và001–002), trước runtime Phase11 đọc metadata. Chỉ4 ADD COLUMN nullable; không CREATE/UPDATE/MODIFY/DROP/backfill/engine change. Kiểm existence và type/nullability; bỏ qua phần đã có trên đích theo bằng chứng, không chạy lại tự động. Không chạy migration này hoặc migration khác trong lượt này.


## Cập nhật sau duyệt và BUILD — 08/10/2026

Bốn bảng đã được người dùng duyệt. BUILD/VERIFY theo từng nhóm, commit A b70b441, B a3b17e3, C c850158. Matrix cuối trên commit C: 80/80 cặp, 11 control, 35 ca hành vi PASS; regression 56 script PASS; 21 bảng thật không đổi. Các phát hiện/lỗi baseline ở trên được giữ làm lịch sử, không phải trạng thái hiện tại. Xem TEST_REPORT_FULL.md và TENANT_GROUP_A_VERIFY_20261008.md, TENANT_GROUP_B_VERIFY_20261008.md, TENANT_GROUP_C_VERIFY_20261008.md. P3 vẫn chờ A/B/C. Không push/merge/deploy/migration.
