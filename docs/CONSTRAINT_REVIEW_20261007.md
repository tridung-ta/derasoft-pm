# Rà soát ràng buộc — 07/10/2026

Phạm vi: local tại b8e0774, review code/tài liệu và kiểm thử PM. Dừng sửa giao diện. Không sửa runtime, không truy cập production, không chạy migration. Schema đọc qua information_schema, không đọc dữ liệu cá nhân.

## Phát hiện cần xử lý

1. **P1 — Validation vượt giới hạn schema dc_users.** Local: fullname/email 50, username 30, tel 15. modules/admin/pmusers.module.php:55–63 chỉ chặn tên rỗng/email sai cú pháp, không chặn chiều dài hoặc control characters. modules/admin/pm.module.php:32–47 cũng thiếu giới hạn ở Hồ sơ. classes/services/pmimportservice.class.php:42–44 và 136 cho username 50/fullname 100, vượt schema thật. Dữ liệu vượt cột có thể bị từ chối hoặc cắt tùy SQL mode. Sửa bằng validator dùng chung theo schema thật, áp dụng create/update/profile/import preview/apply, thông báo lỗi theo field/dòng trước ghi. Không cần ALTER.
2. **P2 — Cho tạo định danh đăng nhập mơ hồ.** pmusers.module.php kiểm tra email trùng email và username trùng username riêng; không chặn username trùng email tài khoản khác hoặc email trùng username. PmAuth::findUser (classes/security/pmauth.class.php:77) từ chối khi có hơn một dòng nên không đăng nhập nhầm người, nhưng định danh đó mất khả năng sử dụng. Cần kiểm tra collision chéo khi tạo/sửa/import và xử lý tương tranh; không thay cơ chế fail-closed.
3. **P2 — Test CRUD mirror rộng hơn schema thật.** tests/pm_users_crud_smoke.php:10–15 dùng username 100/email 150/fullname 150/tel 50. Các PASS không chứng minh giới hạn lưu cột thật. Nên dùng CREATE TEMPORARY từ SHOW CREATE TABLE, thêm boundary/overlength/control-character/collision tests qua controller và import.
4. **Chưa chốt quy tắc tên thực tế.** Tên trong ảnh là chuỗi chữ không rỗng, nên được chấp nhận. Không thể kết luận tên giả chỉ từ hình thức chuỗi. Email 123123123@gmail.vn được FILTER_VALIDATE_EMAIL chấp nhận về cú pháp; không xác minh hộp thư tồn tại/quyền sở hữu. Có thể chuẩn hóa khoảng trắng, cấm control characters và giới hạn độ dài. Quy tắc cấm số/ký tự hoặc yêu cầu nhiều từ cần quyết định riêng, tránh loại tên hợp lệ. Không tự bổ sung OTP/email verification.

## Đối chiếu phạm vi và bằng chứng

| Nhóm | Kết quả local | Giới hạn |
|---|---|---|
| Auth/RBAC | PASS password primitives, cùng credential email/username, đổi mật khẩu, role/tenant/session guard | Chưa chạy lại login browser với 4 tài khoản thật |
| Nhân sự/role/rate | CRUD/search/page/soft-delete/role batch/rate owner và overlap PASS | Có các thiếu sót validation ở trên |
| Dự án/task | CRUD/member/Kanban/metadata, ownership và tiến độ PASS | Không thay rule tiến độ dự án đã duyệt |
| Timesheet/OT/audit | Tính lại ngày khi sửa/xóa/chuyển, snapshot/cost, cửa sổ 3 ngày, Admin override/audit PASS | Không thay công thức OT |
| Chi phí | DECIMAL, missing rate/estimate, mixed currency, lifetime và scope PASS | Giá trị thiếu không bị coi là 0 hợp lệ |
| Phân bổ | Ngày/tuần/overlap, ngưỡng theo hiệu lực, tải ngoài dự án, gợi ý và hai connection lock PASS | Gợi ý không xác nhận kỹ năng/lịch chi tiết |
| Báo cáo/Excel | scope/filters/statistics/XLSX round-trip/formula text PASS | Không thêm tỷ lệ hiệu suất; export giới hạn 5000 |
| Import | preview, file limits/security, INSERT/1062, provenance/retry/mutex PASS | Giữ dc_users MyISAM; UNIQUE kiểm tra local, không suy ra production |
| SQL/bảo mật | Prepared EXPLAIN 101 shapes, CSRF/IDOR/XSS/session/DB isolation PASS | Không phải pentest toàn bộ framework legacy |
| UI/Bootstrap | Self-host hash/license PASS, 13 fixture responsive/keyboard/labels, text scaling PASS | Không chỉnh UI; text scaling không thay zoom thật hoặc UAT |
| DB/quy trình | Runner không apply migration; migration additive, không đổi dc_users engine; .git tracked scan không thấy config thật/dump/cache/upload | Các SQL có chữ DROP trong rollback comments không phải thao tác DROP |
| Git/release | main 3dbc06b, develop 953bba8, nhánh local b8e0774 trước báo cáo | main đã có merge release; UI local chưa merge/push. Gói UI 8 file đã tạo, chưa xác nhận operator upload |

## Lệnh đã chạy và kết quả

- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1 — PASS 47 scripts, gồm 73 PM/HR HTTP fixture cases và 101 prepared EXPLAIN shapes. Không apply migration. Một test duplicate INSERT thật bị từ chối 1062; không thêm user, có thể tạo khoảng trống auto-increment theo test note.
- playwright-cli -s=pmlogin run-code --filename=../tests/pm_ui_browser.js từ .local — PASS 13 synthetic fixtures tại 360/390/768/1440, labels/keyboard/no overflow/text scaling 200%.
- playwright-cli -s=pmlogin run-code --filename=../tests/pm_phase9b_final_browser.js — PASS overview scopes/audit keyboard & no-JS/action contrast/report filter & export fields/Inter/tabular/responsive. Không business POST.
- .tools/php83/php.exe .local/constraint_schema_read.php — đọc schema local PASS sau sửa helper giữ reference DB; lần đầu helper bị mysqli closed do đối tượng DB tạm bị hủy, không thay dữ liệu.
- Review các controller users/profile, DAO users, import service, auth, migrations và Git tracked inventory. Đây là rà soát theo yêu cầu PM, không bảo đảm mọi nhánh code legacy đã được chứng minh.

## Hành động tiếp theo

Ưu tiên validator thống nhất + chặn collision chéo + test theo schema thật; giữ giao diện. Không tự sửa dữ liệu đã lưu hoặc tự chạy migration. Chưa ghi mọi ràng buộc đạt, chưa đánh dấu UAT/production PASS. Báo cáo này không thay gói ZIP b8e0774 đã giao.
