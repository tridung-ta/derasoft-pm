# Đối chiếu hoàn thành dự án — 07/10/2026

Phạm vi: review local tại commit `3253dba`, nhánh `feature/pm-phase11-launch-copy`. Không sửa runtime, migration, dữ liệu hay truy cập production.

## Kết luận

Các module chính của 10 nhóm yêu cầu đã có, nhưng chưa đủ bằng chứng để tuyên bố hoàn thành toàn bộ. Còn gap chức năng, ràng buộc dữ liệu và kiểm thử nghiệm thu. Không dùng số script PASS thay cho số test case checklist PASS.

| Yêu cầu | Đối chiếu hiện tại |
|---|---|
| 1. Đăng nhập/phân quyền | Email và username dùng chung credential; role, permission, tenant/session guard đã có. Full tenant endpoint matrix, no-role HTTP và refresh quyền cùng phiên còn thiếu bằng chứng đầy đủ. |
| 2. Nhân sự/vai trò/đơn giá | CRUD, khóa, soft delete, tìm kiếm, phân trang, role và rate đã có. Validator độ dài create/update/profile/import chưa thống nhất schema thật; chưa chặn collision chéo email–username. |
| 3. Dự án/task | CRUD, thành viên, tiến độ, Kanban AJAX và POST fallback đã có. Dự án kết thúc sau ngày bắt đầu; task được cùng ngày. |
| 4. Chấm công/OT/audit | Snapshot, tính lại cả ngày, audit đã có. Yêu cầu mới giới hạn tổng giờ theo quy tắc chưa triển khai: hiện giờ chuẩn là ngưỡng OT, tổng ngày tối đa 24 giờ. Cần chốt cách giữ OT trước khi đổi. |
| 5. Chi phí/biểu đồ | Actual/estimate/budget, scope, tiền tệ và cảnh báo thiếu đơn giá đã có. Checklist assertion cost snapshot riêng còn chưa hoàn tất. |
| 6. Phân bổ | Lịch tuần, ngưỡng ngày/tuần, overlap, gợi ý đã có. Cảnh báo riêng task có giờ thực tế vượt ước tính (D2, mục 7.4) chưa BUILD. |
| 7. Báo cáo | Cá nhân/nhóm/chi phí, filters và thống kê đã có; không tính tỷ lệ hiệu suất theo quyết định người dùng. |
| 8. Excel/import | Export, Stage/Apply, lỗi dòng, duplicate/retry đã có. Giữ thiết kế Stage từ chối cả file sai. Giới hạn username/fullname import chưa khớp schema; kiểm tra đủ bốn tiền tố formula còn thiếu. |
| 9. Responsive Bootstrap | Bootstrap 5 tự host, Inter và UI responsive đã có. Chưa chứng minh zoom thật 200%, keyboard toàn luồng và CSP trên mọi fixture. |
| 10. SQL/bảo mật | Prepared query, scope, CSRF, escaping, phân trang đã có; 101 query shapes EXPLAIN chạy đạt. Chưa thay thế full endpoint isolation matrix hoặc pentest. |

## Bằng chứng chạy trong lượt review

Lệnh: `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`.

Kết quả: exit code **0**; output cuối `PASS: 50 regression scripts. No migrations applied by this runner.` Gồm 119 PM/HR HTTP fixture cases và 101 prepared query shapes EXPLAIN. Không thực hiện browser UAT hoặc production test trong lượt này.

Code review: `modules/admin/pmusers.module.php:63–70` kiểm tra email/username riêng, thiếu giới hạn fullname/username; `classes/services/pmimportservice.class.php:42–44,136` cho username 50/fullname 100 trong khi schema đã đối chiếu username 30/fullname 50. `tests/pm_users_crud_smoke.php:11–13` dùng mirror rộng hơn schema thật, nên PASS không chứng minh các giới hạn này. `classes/security/pmauth.class.php:71–83` từ chối identity mơ hồ, không đăng nhập nhầm user; cần ngăn tạo collision từ đầu.

`classes/services/pmtimesheetservice.class.php:122–125` chặn tổng ngày trên 24h và tách OT theo standard_hours. `classes/services/pmallocationservice.class.php:120` cảnh báo tải phân bổ; D2 chưa có.

## Trạng thái checklist và đóng dự án

Giữ nguyên 19 mục CHƯA CHẠY trong `docs/TEST_REPORT_FULL.md`; lượt regression này không tự nâng trạng thái. Số tổng lịch sử 58 PASS/3 FAIL/19 CHƯA CHẠY chưa được đối chiếu cuối ở bước E. Trong đó lỗi fixture 11.5 đã khôi phục dữ liệu và cô lập test ở bước A; không coi đó là lỗi dữ liệu hiện còn tồn tại. Mục 11.4 còn cần review toàn bộ payload log mới theo chính sách ID-only.

Ưu tiên trước đóng dự án: validator theo schema thật và collision chéo; PLAN/duyệt/BUILD D2; chốt giới hạn giờ và OT; hoàn tất các test còn thiếu và đối chiếu E; nghiệm thu bản phát hành cuối. Việc main đã được push không đồng nghĩa nghiệm thu hoàn tất. Bản ZIP mới nhất chưa có xác nhận đã được upload và UAT toàn luồng trên production.
