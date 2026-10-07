# Phase 11 — hoàn thiện phạm vi đề bài

Nhánh: feature/pm-phase11-requirements, bắt đầu từ 65cfbd5 (có bản sửa UI
Phase 9b chưa merge/push). Phát triển local; không áp migration/upload production.
Người dùng yêu cầu ưu tiên hoàn thiện toàn bộ phạm vi ngày 06/10/2026 và duyệt
Bootstrap 5 tự host. Báo cáo chỉ thống kê số liệu, KHÔNG tính tỷ lệ hiệu suất.

## Trình tự và tiêu chí

1. Audit task tạo/sửa/ẩn: snapshot trước/sau, actor/time, transaction nguyên tử;
   Admin trong tenant, PM chỉ dự án phụ trách; HR không mở rộng quyền task audit.
   File: classes/services/pmprojectservice.class.php,
   classes/dao/pmauditlogs.class.php, modules/admin/pmaudit.module.php,
   templates/admin/pm-audit.tpl.html, tests/pm_projects_smoke.php.
2. Dự án/task: khách hàng tùy chọn, ngày bắt đầu task, vai trò thành viên dự án
   tách khỏi role RBAC; ngày hoàn thành để báo cáo lịch sử. Migration ADD COLUMN
   có guard, rollback giữ cột/dữ liệu; không backfill ngày hoàn thành bằng phỏng đoán.
3. Báo cáo: giờ/OT theo tuần, task hoàn thành theo ngày hoàn thành, task quá hạn,
   nhóm/dự án/phòng ban/role/task; actual/estimate đúng currency. Lọc ngày/user/
   dự án/role, paginate. Định nghĩa phạm vi ngày rõ từng bảng; không gộp tiền khác loại.
4. Export XLSX nhân sự, chấm công, dự án/báo cáo: cùng quyền với màn hình tương ứng,
   không xuất password/hash, token; literal text tránh công thức Excel; CSRF/no-store.
5. Bootstrap 5 tự host: dùng bản official pinned/license, nạp trước theme PM,
   giữ Inter và navigation/sidebar, không rewrite DeraSoft; regression responsive,
   accessibility, keyboard, CSP và chức năng JS hiện có.
6. Đối chiếu đủ 10 nhóm: auth, CRUD/roles/rates, projects/tasks, OT/audit, costs,
   allocation, reports, import/export, responsive Bootstrap và SQL/security.

Mỗi phần hoàn chỉnh: code + kiểm thử/regression + review + PROGRESS + commit local.
Không tự push/merge/deploy phần mới. Các công thức/permission ngoài quyết định hiện
có cần hỏi riêng; không suy diễn quyền HR toàn tenant từ từ ngữ đề bài.

## Trạng thái

- Phần 1: hoàn tất local, 42 regression PASS; test audit failure rollback và
  snapshot/create/update/soft-delete, PM scoped task audit, HR deny task audit.
- Phần 2: hoàn tất local metadata/forms; local backup and migration 009 twice PASS;
  43 regression PASS. completed_at unknown for legacy done; preserved on done edit,
  cleared on reopen. Project roles descriptive only, no RBAC elevation.
- Phần 3: báo cáo giờ theo tuần, task hoàn thành/quá hạn/trễ, nhóm theo dự án/
  nhân viên, lọc role và phân tích chi phí theo phòng ban/role/task đã BUILD local.
  Báo cáo dựa trạng thái/phân loại hiện tại; không tự dựng lịch sử assignment.
- Phần 4: hoàn tất local XLSX modes users/projects/hours/tasks/costs;
  literal-text cells, whitelist columns, permission gates, CSRF/no-store.
- Phần 5: Bootstrap 5.3.8 CSS tự host/license/digest; nạp trước theme,
  table-responsive/card/btn, giữ JS hiện có. 9 bộ browser Phase 9b PASS.
  Hoàn thiện form-control/form-select/btn trên template PM ngày 06/10;
  token Bootstrap theo theme Inter, focus/disabled/file input đã kiểm tra browser.
  Không đổi nội dung form, route, controller hoặc database.
- Phần 6: bảng đối chiếu bên dưới; 46 regression PASS, 101 prepared query shapes
  EXPLAIN PASS; browser sidebar và báo cáo mới PASS. Chưa UAT/production Phase 11.
  Rerun 06/10: 14 browser suites PASS; docs/LOCAL_VERIFY_PHASE11.md ghi phạm vi
  và giới hạn thật. Theo yêu cầu mới, kiểm thử tay còn lại chuyển sang sau deploy.

## Đối chiếu 10 nhóm yêu cầu

| Nhóm | Chức năng local và căn cứ kiểm tra |
| --- | --- |
| Auth/RBAC | Email login, password_hash/verify (bcrypt default PHP 8.3), multi-role, controller guards; auth/session HTTP/service tests |
| Nhân sự/rates | Danh sách, tìm kiếm/phân trang, phòng ban, role, rate, khóa/xóa mềm; workforce tests |
| Dự án/task | Khách hàng, ngày kế hoạch, vai trò thành viên, task start/due/priority/status và Kanban; project smoke |
| Chấm công/OT/audit | Ca/giờ, daily OT/snapshot, create/update/soft-delete task audit; timesheet/window/audit tests |
| Chi phí | Actual/estimate/budget/task, Chart.js cột, polling, currency guards; costs service/HTTP/browser |
| Phân bổ | Lưới tuần, giờ/ngày/tuần, trùng giờ/quá tải và capacity suggestions; allocation tests |
| Báo cáo | Tuần/user/OT, task hoàn thành/trễ/quá hạn theo dự án/user, ngày/user/project/role, cost groups; report tests |
| Excel/import | XLSX users/projects/hours/tasks/costs; personnel preview/duplicate/errors/resumable Apply; XLSX/import tests |
| Bootstrap/responsive | Self-host CSS trước Inter theme; sidebar/forms/cards/table-responsive, keyboard/mobile; digest/9 browser suites |
| SQL/security | Prepared queries/EXISTS/tenant scopes, CSRF/escape, paging/caps, existing tenant indexes; 101 EXPLAIN shapes |

Giới hạn được công khai: thống kê thay tỷ lệ hiệu suất theo quyết định người dùng;
assignment/role/department/trạng thái dùng dữ liệu hiện tại, không tái dựng lịch sử.
Task legacy chưa có completed_at không được gán ngày giả. Nhân sự export hiện tại
không áp ngày; dự án export lọc khoảng kế hoạch; hours lọc work_date; task lọc
completion hoặc due_date. Bộ kiểm tra HTTP có Admin/Employee, PM/HR chủ yếu qua
service fixtures rollback. Automated PASS không phải UAT PASS.
