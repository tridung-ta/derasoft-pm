# Bước D1 — PLAN Kanban AJAX (đã duyệt và BUILD local)

## Mục tiêu

Đổi trạng thái task ngay trên bảng công việc, không tải lại trang khi có JavaScript.
Giữ form POST hiện có và cách thao tác khi tắt JavaScript. Không kéo-thả, không
đổi phạm vi quyền Admin/PM, không migration, không xóa dữ liệu hoặc chức năng cũ.

## File sẽ thay đổi

1. `pm_ajax.php`: mở rộng allowlist với `pmtaskstatus`, chỉ endpoint này nhận POST.
   `pmcosts`/`pmallocations` vẫn GET-only. Kiểm tra session, user active cùng tenant,
   input scalar và CSRF trước thao tác ghi; JSON lỗi chung, no-store, không lộ exception.
2. `modules/ajax/pmtaskstatus.module.php` (mới): nhận project_id/task_id/status;
   gọi service, trả id/status/completed_at tối thiểu; không nhận store_id/actor_id
   từ client. Dùng convention endpoint PM hiện có; không sửa ajax.php legacy.
3. `classes/services/pmprojectservice.class.php`: thêm phương thức đổi riêng status,
   khóa project/task trong transaction, dùng getProject/requireManage và permission
   pm.tasks.manage hiện có. Trích helper nhỏ dùng chung quy tắc completed_at/audit
   với saveTask; không gọi nested transaction. Chỉ cập nhật status/completed_at,
   không ghi đè tên, mô tả, người được giao hoặc estimate từ dữ liệu form cũ.
   Task ẩn, sai project/tenant và PM không phụ trách đều bị từ chối.
4. `modules/admin/pmprojects.module.php`: thêm action POST task_status dùng cùng
   service để hỗ trợ fallback. Tracking mới chỉ ghi ID tài khoản theo chính sách C.
5. `templates/admin/pm-projects.tpl.html`: thêm form trạng thái có label, CSRF,
   project/task ID và nút xác nhận; chỉ hiện khi canEditTasks. Gắn ID/data attributes
   cho cột/card và vùng thông báo; giữ nguyên form sửa/ẩn task.
6. `js/pmkanban.js` (mới): progressive enhancement bằng fetch cùng origin; chỉ di
   chuyển card sau phản hồi thành công, giữ form status đồng bộ. Disable trong lúc
   chờ để tránh gửi đôi; thất bại giữ card/trạng thái cũ, báo lỗi và cho thử lại.
   Dùng textContent, không ghép HTML từ dữ liệu server. Giữ focus sau chuyển cột;
   aria-live thông báo kết quả. Không tự gửi lại POST nếu mạng lỗi sau khi server
   có thể đã ghi thành công; hướng dẫn tải lại để kiểm tra trạng thái.
7. `tests/pm_projects_smoke.php`: kiểm tra trạng thái, completed_at, snapshot/audit,
   no-op và các scope denial bằng fixture rollback; trường task khác không đổi.
8. `tests/pm_role_http_router.php` và `tests/pm_role_http_smoke.php`: bổ sung fallback
   task_status, assert CSRF/ownership/tenant, audit/ID-only tracking. Giữ isolation.
9. `tests/pm_task_status_http_router.php` (mới) và
   `tests/pm_task_status_http_smoke.php` (mới): test endpoint thật qua HTTP với
   connection-local fixtures; allowlist/method/CSRF/scalar/session/inactive,
   Admin/PM hợp lệ, HR/Employee/no-role bị từ chối, task/project sai tenant hoặc ẩn.
   So sánh persistent state trước/sau, không ghi bảng user thật.
10. `tests/pm_kanban_browser.js` (mới): thành công, lỗi mạng/server/auth, gửi đôi,
    focus/keyboard, escape, responsive/zoom và fallback không JavaScript.
11. `tests/pm_regression.ps1`: thêm service/HTTP test liên quan vào runner.
12. `docs/TEST_REPORT_FULL.md`, `docs/PROGRESS.md`, `docs/SKILL_USAGE.md`: ghi lệnh,
    output thực và giới hạn; mục 4.4 chỉ PASS sau thực thi đạt, không đổi 19 mục
    CHƯA CHẠY khác bằng suy luận.

## Xác minh sau khi được duyệt BUILD

- PHP lint các file PHP sửa/mới; chạy service smoke, role HTTP và status HTTP.
- Playwright kiểm tra chuyển cột, lỗi và bàn phím; desktop/mobile, zoom 200%, no-JS.
- Regression toàn PM; review code/security trước commit local tập trung cho D1.
- Không merge/push/deploy. Không gộp cảnh báo vượt estimate D2 vào lần BUILD này.

Skill: feature-development cho luồng service/controller; impeccable cho tương tác
giao diện; playwright-cli cho trình duyệt; code-review trước chốt; git-feature-commit
cho commit local đã hoàn tất. Người dùng đã duyệt PLAN; D1 đã BUILD/VERIFY local
ngày 07/10/2026. Kết quả thực chạy xem docs/TEST_REPORT_FULL.md. Chưa merge/push/deploy.
