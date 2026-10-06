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
- Phần 3–6: chưa BUILD; chỉ plan, chưa có PASS/UAT/production claim.
