# Phase 8 — Reports & Excel

## Status — 2026-10-04

BUILD increment báo cáo/XLSX theo yêu cầu tiếp tục local sau automated verification;
không chờ UAT từng phase. Phase 7 verified; không coi UAT đã hoàn tất.
Nhánh BUILD `feature/pm-phase8-reports-excel`. Báo cáo/XLSX đã có service/HTTP/Smarty/browser PASS;
import preview/staging/Apply đã BUILD / automated VERIFY. Người dùng đã duyệt riêng
UNIQUE(store_id,email); migration 007/008 đã áp dụng local sau backup.
36 regression scripts và browser import PASS; không coi UAT đã hoàn tất.
Chưa đổi engine legacy hoặc triển khai production.

## Báo cáo và export

- Báo cáo giờ cá nhân/nhóm theo from/to inclusive, project và user; giữ timesheet/task lịch sử,
  không tính soft-deleted timesheet. Tenant luôn lấy từ session, prepared query, tránh JOIN nhân đôi.
- Admin toàn tenant; PM chỉ dự án quản lý; Employee/HR chỉ giờ cá nhân trong increment đầu.
  Không tự mở HR tài chính hoặc nhân sự toàn công ty.
- Chi phí reuse PmCostService: stored cost/rate_snapshot, currency guard, estimate có nhãn,
  phân loại department/role hiện tại và cảnh báo thiếu snapshot. Không tự quy đổi tiền.
- Quyền mới tenant-scoped pm.reports.view / pm.reports.export; role gates ở list/detail/export.
  Cost report/export vẫn phải có pm.costs.view và Admin/PM.
- Export XLSX bằng PhpSpreadsheet hiện có, bootstrap theo module tương tự; mọi text dùng
  explicit string để không kích hoạt formula từ dữ liệu =/+/-/@. Giữ precision của tiền DECIMAL.
- HTML escape, CSRF POST export, no-store, filename cố định an toàn, không đưa token/dữ liệu nhạy cảm vào URL/log.
- Giới hạn export 5000 dòng; vượt thì yêu cầu thu hẹp filter, không âm thầm cắt bớt kết quả.
  Bảng có pagination, empty/error và cảnh báo rõ ràng.

## Import nhân sự — quyết định chính thức đã duyệt

dc_users là nguồn tài khoản legacy MyISAM; không thể coi BEGIN/ROLLBACK là atomic khi ghi bảng này.
Không tự đổi ENGINE theo quy tắc chỉ CREATE TABLE / ADD COLUMN.
Người dùng quyết định giữ ENGINE, không chấp nhận ngoại lệ kể cả local.
Apply sẽ xử lý độc lập từng dòng, không yêu cầu transaction toàn file trên dc_users.

- Phần độc lập đã BUILD: Admin-only XLSX template/upload/validate/preview/staging,
  header mapping, lỗi theo dòng, giới hạn 2MB/500 dòng/1 worksheet và ngân sách ZIP giải nén.
- Chặn macro, external links/formula, file sai MIME/extension hoặc chứa trường quyền/mật khẩu;
  không tự cấp Admin/PM, không import password hoặc hash từ Excel.
- Validate toàn bộ trước staging/ghi: dữ liệu trùng trong file và tenant, department/role active,
  định dạng và độ dài. Không dùng username/email từ tenant khác để update.
- Bảng InnoDB import_logs/staging mới versioned, kiểm tra tồn tại, audit actor/tenant và kết quả;
  log không chứa workbook/mật khẩu/PII đầy đủ. Upload ngoài webroot, xóa file tạm khi hoàn tất.
- Điều kiện UNIQUE DB đã xác nhận trước khi code Apply; runtime tiếp tục kiểm tra full UNIQUE
  và fail closed khi thiếu. Preview/staging vẫn ghi rõ chưa áp dụng.

Migration 006 chỉ CREATE TABLE IF NOT EXISTS cho import_logs/staging InnoDB, seed 006 Admin-only;
đã áp dụng local sau backup, chạy idempotent hai lần. Chỉ staging CREATE personnel mới:
username/email đã tồn tại trong tenant là lỗi, role chỉ EMPLOYEE; không update tài khoản cũ.
Staging giữ payload nhân sự trong bảng tenant-scoped; log/audit chỉ metadata, không PII đầy đủ.
Apply INSERT trực tiếp từng dòng; không đổi engine hoặc sửa tài khoản cũ vì trùng email.

### Preflight UNIQUE — kết quả đọc trực tiếp local

`tests/pm_import_email_index_audit.php`: dc_users MyISAM, email VARCHAR(50) nullable,
utf8mb4_unicode_ci. Index email NON_UNIQUE=1, không có UNIQUE(email) hoặc
UNIQUE(store_id,email). 9 dòng, 0 NULL, 0 empty, 0 duplicate email groups toàn DB/theo tenant.
Chỉ báo cáo schema/count, không xuất email/PII. Đây không phải kết quả production.

Migration đã duyệt `007_add_pm_users_email_unique.sql`:
ADD UNIQUE INDEX uq_pm_users_store_email (store_id,email).
Theo kiến trúc multi-tenant, cùng email ở hai store vẫn được phép; trong một store bị DB chặn.
Không đổi ENGINE, cột, collation, index cũ hoặc dữ liệu. NULL vẫn nullable; Apply/preview
không nhận email NULL/rỗng. Tôn trọng so sánh utf8mb4_unicode_ci khi kiểm tra duplicate.
MyISAM ALTER có thể rebuild/khóa bảng lõi, phải backup và có cửa sổ bảo trì.
Nếu trùng thì dừng, không tự sửa dữ liệu. Rollback ứng dụng tắt Apply, giữ constraint;
không tự DROP INDEX. **Đã duyệt và áp dụng LOCAL ngày 04/10/2026**, chạy hai lần PASS;
schema audit sau migration: tenant_email_unique=true, MyISAM, vẫn 9 users.

### Apply đã BUILD sau khi UNIQUE được xác nhận

1. POST/CSRF/Admin/tenant, batch staged hoặc resume batch gián đoạn; lấy connection-owned
   advisory lock theo database/tenant/import_id với timeout 0 trước khi chạy. Worker khác
   không lấy được lock thì từ chối. Kiểm tra/cập nhật import_logs.status='in_progress'.
2. Giữ advisory lock suốt lượt Apply, kể cả giữa các transaction log. Connection chết tự
   nhả lock; batch in_progress còn lại chỉ resume khi lấy được lock, không dựa vào timeout
   tùy ý để bỏ khóa của worker còn chạy. Không reset flag khi chưa xác minh khóa.
3. Log riêng từng source_row trong bảng PM InnoDB versioned mới, unique tenant/batch/row:
   applied/skipped_duplicate/failed, reason_code, user_id khi xác định được, timestamps/attempt.
   Không lưu raw SQL exception, password/hash hoặc PII trong log.
4. Dòng chưa có kết quả bền vững được replay bằng INSERT prepared trực tiếp; bắt mã 1062
   là skipped_duplicate, lỗi khác là failed. Không SELECT kiểm tra trùng rồi mới INSERT.
   Preview kiểm tra trùng chỉ cung cấp phản hồi; không được dùng làm bảo đảm Apply.
5. Trước INSERT ghi durable intent/token/hash payload trong dc_pm_import_results.
   Gián đoạn sau INSERT nhưng trước kết quả: replay bị UNIQUE chặn, không tạo user thứ hai.
   Dấu nguồn trong properties của tài khoản phải khớp token/tenant/batch/source_row mới
   được hoàn tất role và applied/recovered_inactive; tài khoản khác skipped_duplicate.
   Không sửa/cấp role cho account có sẵn chỉ vì trùng email.
6. Không tuyên bố atomic giữa MyISAM INSERT và InnoDB log. Mỗi dòng độc lập, kết quả
   tổng hợp có thể partial/completed; giữ staging để resume có kiểm soát, không sửa payload
   của batch đã bắt đầu. failed có reason_code để retry sau khi nguyên nhân được giải quyết.
7. Luôn release advisory lock trong finally; chỉ cập nhật trạng thái kết thúc khi lượt này
   còn giữ lock. Tests: INSERT race/1062, crash giữa INSERT/log, failed retry, hai Admin,
   stale in_progress recovery và không chạm user/role ngoài batch.

Migration 008 chỉ CREATE TABLE IF NOT EXISTS dc_pm_import_results (InnoDB):
UNIQUE(store_id,import_id,source_row), status pending/applied/skipped_duplicate/failed,
reason_code, user_id nullable, attempt_count, timestamps, provenance_token và payload_sha256.
Giữ payload bất biến từ lần thử đầu; pending là intent nội bộ, không phải kết quả thành công.
Tài khoản mới status=0, password legacy NULL, password_hash ngẫu nhiên không tiết lộ.
Username DB chưa UNIQUE: kiểm tra xung đột sau INSERT; nếu có, giữ tài khoản inactive,
ghi failed/username_collision_inactive; cần quản trị viên xử lý, không tự sửa dữ liệu cũ.
Admin cấp mật khẩu 12–72 byte tại kết quả Apply (POST/CSRF, đúng provenance/applied/inactive,
khóa batch); hash bằng password_hash, không log/echo password/hash. Sau đó kích hoạt qua
Quản lý nhân sự. Không gửi thông tin đăng nhập tự động. Password write trên MyISAM và audit
không atomic: khi lỗi audit, tài khoản vẫn inactive và có thể cấp lại mật khẩu.
Audit Viewer Admin hỗ trợ stage/apply/provision_password; PM/HR giữ scope timesheet cũ.

VERIFY Apply dùng bảng TEMPORARY InnoDB phản chiếu user để rollback nhân sự giả;
đã kiểm tra INSERT/1062, crash sau INSERT và lỗi transaction role/log, retry/hash bất biến,
independent rows, quyền/tenant/IDOR/CSRF, hai connection GET_LOCK và close-release.
Native duplicate INSERT trên dc_users thật trả 1062, không thêm user; auto-increment có thể có gap.
Chưa kiểm tra INSERT MyISAM thành công rồi kill process trên bản DB cô lập riêng;
không coi mirror test là bằng chứng durability/crash vật lý của MyISAM hoặc UAT.

## VERIFY

1. Giờ cá nhân/nhóm, date inclusive/invalid, empty/pagination, lịch sử và không double count.
2. Admin/PM/Employee/HR, cross-tenant và project/user IDOR ở report/export; CSRF và no-store.
3. XLSX round-trip, Unicode, explicit string/formula injection, DECIMAL lớn, missing/mixed currency.
4. Import malformed/oversize/ZIP/formula/header/duplicate/row errors, staging rollback;
   Apply độc lập/resume, native 1062 và mutex, không tuyên bố atomic MyISAM toàn file.
5. Smarty/browser desktop/mobile, filter/empty/error, download/upload trên local.
6. Regression Phase 2–7, PHP 8.3 lint, code/security review, diff check và PROGRESS.

Không thay legacy, không production, không tự commit/push/merge/deploy.
