# Phase 4 — Project & Task

BUILD theo yêu cầu tiếp tục của người dùng ngày 2026-10-02, từ roadmap PLAN_FINAL.

## Phạm vi và tiêu chí hoàn thành

- CRUD dự án, thành viên và task; project/task dùng soft delete, membership dùng status.
- Kanban bốn cột: todo, in_progress, review, done; cập nhật trạng thái bằng form POST có CSRF.
- Admin quản lý mọi dự án trong tenant; PM quản lý dự án có manager_id là chính mình.
- Nhân viên chỉ đọc dự án được gán thành viên. Mỗi thao tác cần cả permission và phạm vi project.
- Chỉ gán task cho user active thuộc membership active của chính project và tenant.
- Không gỡ thành viên còn task chưa soft delete được giao; cần giao lại hoặc bỏ người được giao trước.
- Prepared DAO/service, escape output, kiểm tra ngày/số tiền/giờ, transaction khóa project cho mutation.
- Search và phân trang dự án; thêm route/menu vào Admin hiện tại.

## Dữ liệu

Migration 003 chỉ CREATE TABLE IF NOT EXISTS: dc_pm_projects, dc_pm_project_members,
dc_pm_tasks. Mỗi bảng có store_id BIGINT UNSIGNED NOT NULL. Unique code dự án và
membership gồm store_id. Không FK sang bảng user/store MyISAM; service kiểm tra tham chiếu.
DDL được kiểm tra trước và chỉ áp dụng DB derasoft_pm_local. Rollback ứng dụng giữ bảng mới.

## Kiểm tra

Lint; Smarty render dùng template root giống admin.php; schema; project/member/task
service trên DB local; các test ghi dữ liệu sử dụng project fixture mới trong các bảng
InnoDB, rollback; kiểm tra tenant, quyền PM/employee, assignee và soft delete.
UAT đăng nhập bằng tài khoản thật vẫn được ghi riêng, không suy diễn từ smoke test.
