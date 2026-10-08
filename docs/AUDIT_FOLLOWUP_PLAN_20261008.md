# Đối chiếu P3/P4 và PLAN kiểm chứng 11.4 — 08/10/2026

Trạng thái: P4 đã duyệt BUILD với chỉnh sửa; P3 chờ A/B/C, chưa PLAN/chưa BUILD. D2 đã hoàn tất ở 5c0d679. Không push/merge/deploy.

## P3 — yêu cầu giới hạn tổng giờ

Nguồn: người dùng yêu cầu “ở phần chấm công không được điền vượt quá số giờ đã lưu ở quy tắc ví dụ ... 8g 1 ngày ... không được nhập quá 8g”. Đây là yêu cầu giới hạn tổng hours, không phải một yêu cầu “giới hạn tổng giờ OT” riêng. Không được tự gọi yêu cầu này lỗi thời hoặc đã hoàn tất.

Hiện tại docs/PLAN_FINAL.md:86 quy định giờ thường tối đa standard_hours_per_day, phần vượt là OT. classes/services/pmtimesheetservice.class.php::recalculate chặn tổng >24h, sau đó phân chia regular_hours/ot_hours theo snapshot standard_hours của ngày. Ví dụ 8h + 2h được lưu, regular=8/OT=2. Chặn tổng ở8h sẽ làm ngày có ngưỡng8h không phát sinh OT từ việc ghi vượt ngưỡng; ảnh hưởng nghiệp vụ hiện PASS và cần duyệt riêng. Đã gửi câu hỏi lựa chọn chính sách; chưa sửa service. Sau câu trả lời mới lập PLAN cụ thể cho snapshot/ngày cũ/sửa/chuyển ngày/concurrency và test.

## P4 — inventory đường nhật ký đã rà mã

| Đường | Bằng chứng hiện tại | Giới hạn |
|---|---|---|
| login/lock/logout PM | modules/admin/login.module.php:45,55; logout.module.php:18 lưu ID dạng string vào username, IP rỗng | 10 auth module fixtures PASS; giữ hành vi legacy khi PM_HIDE_LEGACY=false |
| pmusers: create/edit/status/roles/rates/departments theo action | modules/admin/pmusers.module.php:74 lưu actor ID, action chứa loại thao tác + target ID, không tên/email/password | Cần assertion bao phủ từng action, không suy từ một action |
| pmprojects: project/member/task form | modules/admin/pmprojects.module.php:30 actor ID, project ID | Cần assertion từng nhánh; task service có audit riêng |
| task create/update/status AJAX/soft-delete | classes/services/pmprojectservice.class.php:157-158 actor_id int; snapshot task không JOIN tên tài khoản | Cần test ID-only explicit cho từng action/no-op; name/description là nội dung task, không phải actor identity |
| timesheet/settings/recalculate/locked overrides | classes/services/pmtimesheetservice.class.php:129-130 actor_id int; snapshot hàng timesheet/settings | Cần assertion tất cả action; không redact nội dung mô tả do người dùng nhập bằng suy luận |
| allocation/capacity | classes/services/pmallocationservice.class.php:40-41 actor_id int, snapshot allocation/capacity | Cần assertion create/update/delete; dữ liệu không JOIN user fullname |
| import stage/apply/provision_password | classes/services/pmimportservice.class.php:72,124,144 actor_id; metadata row/status/user_id, không credentials | Cần test whitelist metadata explicit |
| PM profile/password | modules/admin/pm.module.php không ghi tracking/audit khi thành công | Đây là thiếu coverage/logging cần PLAN nếu bổ sung; không phải đã chứng minh có raw username trong log mới |
| rates/roles/departments DAO/service gọi qua pmusers | Điểm ghi tracking controller sau thành công | Không coi lời gọi service trực tiếp tự có audit; kiểm chứng theo entrypoint thực |
| legacy profile/editorial/manage/system + ajax count | Các module legacy còn ghi username/IP | admin.php:121-122 chặn route admin legacy khi PM_HIDE_LEGACY bật; không sửa/xóa legacy. ajax count thuộc bề mặt legacy riêng, chưa HTTP kiểm chứng/router review đầy đủ; không tuyên bố toàn repository ID-only |
| diagnostic error_log | pm profile/project dùng thông báo chung, không identity | Chưa rà toàn bộ logger/access log hạ tầng; không tự mở rộng chính sách sang production access logs |

## Audit UI cũ/mới

templates/admin/pm-audit.tpl.html:6 hiển thị #actor_id và snapshots đã escape; classes/dao/pmauditlogs.class.php chỉ đọc dc_pm_audit_logs, không đọc dc_trackings. templates/admin/systemstafflisttracking.tpl.html hiển thị getUsername() trực tiếp; TrackingInfo::getUsername giữ string nguyên trạng nên định dạng username lịch sử hoặc ID mới đều có thể hiển thị. Tuy nhiên màn legacy bị ẩn trong PM và chưa có render fixture hỗn hợp cùng một màn hình. Không kết luận Audit UI PM hiển thị tracking lịch sử raw username. Không đổi hay chuyển dữ liệu lịch sử.

## Kiểm tra thực chạy

Ngày08/10/2026, tại D:/derasoft-pm, chạy .tools/php83/php.exe lần lượt qua harness Promise.allSettled, từng kết quả được kiểm tra:

- tests/pm_tracking_identity_smoke.php — exit0: PASS ten actual login/lock/logout/failure module fixtures; PM account ID only, no new raw IP, historical IP retained, legacy identity preserved; no database access.
- tests/pm_audit_smoke.php — exit0: PASS audit role/tenant/project boundaries, financial redaction, filter validation and fixture rollback; exact-schema temporary users, persistent user rows/engine unchanged.
- tests/smoke_pm_audit.php — exit0: PASS audit Smarty details, escaping, pagination and empty state.

Output đầy đủ: .local/audit-followup-20261008.json. Đây không phải assertion toàn bộ writer; chưa nâng11.4. Hai lượt rg ban đầu báo file không tồn tại/glob Windows không hợp lệ; đã tìm bằng rg --files và chạy lại đúng path. Không dùng kết quả tìm kiếm lỗi làm bằng chứng không có writer.

## PLAN bước tiếp — cần duyệt nếu mở BUILD

1. Bổ sung test cố định kiểm chứng actor ID-only cho các writer PM liệt kê trên: positive/negative, no-op, không password/hash/email/username actor/IP mới trong metadata định danh. Giữ nguyên nội dung nghiệp vụ và lịch sử; không tự redact task name/description.
2. Fixture temporary exact-schema, không UPDATE dc_users thật; kiểm tra persistent hash/engine trước-sau. Không migration.
3. Không test render tracking legacy hỗn hợp theo yêu cầu mới. Không hợp nhất màn/router legacy. Thêm audit profile_update chỉ changed_fields tên trường và password_change không payload; chỉ actor ID, không contact/credential/IP.
4. Đối chiếu đường legacy có thể truy cập dưới PM mode bằng HTTP; chỉ báo phát hiện. Không rewrite hoặc xóa chức năng legacy.
5. Nếu test tìm thấy vi phạm thật, báo file/action và PLAN sửa trước khi BUILD. Nếu thiếu audit profile/password, đề xuất thêm event chỉ actor ID + action, không lưu liên hệ hoặc credential; cần duyệt riêng.
6. Chỉ nâng11.4 sau coverage có bằng chứng và chốt phạm vi chính sách. Hiện giữ60PASS/1FAIL/19CHƯACHẠY. Chưa bắt đầu các19mục vòng tiếp theo.


## Điều chỉnh duyệt P4

PM_HIDE_LEGACY mặc định true tại includes/pm_mode.inc.php; triển khai dự kiến giữ true, không override false. Kiểm tra HTTP ajax count GET/POST qua entrypoint thật, không bật legacy để kiểm thử. Phạm vi11.4 là writer PM qua admin.php/pm_ajax.php khi PM mode bật; metadata định danh actor chỉ ID, không username/contact/credential/IP mới. Snapshot nghiệp vụ vẫn giữ nội dung và ID liên quan; không redact lịch sử hoặc tự phán nội dung task/description là identity actor. Chỉ nâng11.4 khi có assertions riêng cho mỗi writer PM. P3 tuyệt đối chưa PLAN/chưa BUILD trước lựa chọn A/B/C.
