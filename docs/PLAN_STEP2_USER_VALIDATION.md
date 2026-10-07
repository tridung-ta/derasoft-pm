# BƯỚC 2 — PLAN validation và định danh nhân sự

Trạng thái: **ĐÃ DUYỆT VÀ BUILD/VERIFY LOCAL** ngày 07/10/2026; bằng chứng ở cuối TEST_REPORT_FULL.md. Không push/merge/deploy. BƯỚC 3–5 chưa thực hiện.

## Bằng chứng local

Lệnh `.tools/php83/php.exe .local/step2_sql_mode_read.php` exit 0. Truy vấn `SELECT @@SESSION.sql_mode session_mode, @@GLOBAL.sql_mode global_mode` qua connection DB của ứng dụng; cả hai trả:

`ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`.

STRICT_TRANS_TABLES=yes cho SESSION và GLOBAL. information_schema xác nhận dc_users: username=30, fullname=50, email=50, tel=15; các cột có collation utf8mb4_unicode_ci. Chỉ đọc local, không kết luận sql_mode production. Strict mode không thay thế validation trước ghi, đặc biệt với bảng MyISAM và hành vi INSERT nhiều dòng.

## Phạm vi đề nghị duyệt

1. Mở rộng validator PM hiện có theo schema: username tối đa 30, fullname tối đa 50, email tối đa 50, tel tối đa 15 ký tự. Kiểm tra UTF-8 hợp lệ/control characters; không phán tên vô nghĩa hoặc tự xác minh hộp thư. Giữ pattern username import hiện có, giảm max từ 50 xuống 30; không tự áp pattern import cho username legacy đã lưu. Không sửa dữ liệu cũ.
2. Dùng cùng ràng buộc ở Preview/Stage và revalidate Apply (kể cả batch cũ); dùng validator chung ở tạo/sửa nhân sự và PM Hồ sơ để không có đường ghi bỏ qua. Giữ required/password/rule hiện có; điện thoại mới thêm nhân sự vẫn tùy chọn, chỉ số và tối đa 10 số theo quyết định trước, không nới thành 15. Tel≤15 là trần schema, không thay chính sách điện thoại đã duyệt.
3. Không thêm cột tel vào mẫu Excel: HEADERS vẫn 6 cột hiện tại, import ghi tel rỗng. Quy tắc tel dùng ở các form có trường này; khi import có dữ liệu tel nội bộ thì phải kiểm tra trước ghi.
4. Kiểm tra identity theo store_id, so sánh bằng collation DB thực tế: username/email mới không trùng username/email của **user khác**; sửa chính user loại ID đó khỏi kiểm tra. Kiểm tra cả hai chiều chéo và cùng cột, kể cả tài khoản khóa/ẩn theo quy tắc giữ định danh hiện tại. Không tự đổi định danh lịch sử hoặc sửa PmAuth chọn tài khoản khi mơ hồ.
5. Preview/Stage phát hiện collision giữa các dòng và user hiện có, báo rõ dòng/trường. So sánh trong file phải khớp collation DB, không chỉ lowercase PHP. Giữ thiết kế Stage báo lỗi dòng và từ chối cả file nếu có dòng sai.
6. Apply kiểm tra lại collision với user khác ngay trước INSERT; tài khoản được recovery bằng provenance đúng batch/dòng loại chính ID của nó khỏi kiểm tra. Batch cũ vi phạm giới hạn/collision trả failed với reason riêng; không sửa payload hoặc xóa user. Giữ INSERT từng dòng và bắt native 1062 cho email duplicate; không thay bằng SELECT-check-then-INSERT cho duplicate email.
7. Chống hai đường ghi PM cùng tạo collision: dùng named lock theo database/tenant cho thao tác ghi identity của personnel/profile/import Apply và cấp mật khẩu import có kiểm tra identity. Các đường giữ lock cùng thứ tự, timeout có lỗi rõ, release trong finally; kiểm tra collision và ghi diễn ra trong lock. Giữ batch mutex/provenance/retry hiện tại, không đổi engine/DDL. Lock chỉ bảo vệ các đường PM cùng tham gia, không bảo đảm SQL ngoài app/legacy không tham gia lock.

## File/lớp dự kiến

- `classes/services/pmuserinputservice.class.php`: validator field dùng chung, lỗi rõ theo field.
- `classes/services/pmimportservice.class.php`: Preview/Stage/Apply/provision validation và ánh xạ lỗi dòng.
- `classes/dao/pmimports.class.php`, `classes/dao/pmusers.class.php`: prepared identity checks và lock theo convention hiện có; helper PM dùng chung nếu cần tránh lặp.
- `modules/admin/pmusers.module.php`, `modules/admin/pm.module.php`: validation tạo/sửa/Hồ sơ, giữ CSRF/permission và cách hiển thị lỗi.
- Tests liên quan và tài liệu: exact-schema mirror, không UPDATE bảng user thật.

## VERIFY sau khi duyệt và BUILD

- Biên 30/31 username, 50/51 fullname/email, 15/16 tel tại validator; Unicode, control characters, email sai; create phone 10/11 số và có chữ. Không nới chính sách điện thoại.
- XLSX Preview/Stage/Apply: boundary, lỗi theo dòng, cả file bị từ chối; batch cũ vượt schema thất bại an toàn; mẫu/headers giữ nguyên.
- Collision hai chiều chéo/cùng cột, khác user/cùng user, trong file/DB, case/accent theo DB collation, tenant khác, tài khoản khóa/ẩn, provenance recovery; không khóa nhầm chính user.
- Hai connection thật tranh chấp identity qua các đường PM; timeout/release và thứ tự lock không deadlock. Vẫn PASS email INSERT duplicate 1062, retry/crash recovery/batch mutex.
- Bổ sung bắt buộc do người dùng duyệt: quét read-only toàn bộ dc_users (mọi store/status), đối chiếu username/email hai chiều chéo và cùng cột bằng collation DB, chỉ liệt kê store_id/cặp user ID/loại collision nếu có, không tự sửa hoặc khóa. Named lock có namespace database + store_id, không phải một khóa chung toàn database; hai tenant khác nhau dùng tên khóa khác nhau.
- Mirror lấy schema thật thay DDL user smoke rộng; đối chiếu toàn bộ dc_users thật và MyISAM trước/sau, không dữ liệu fixture tồn dư.
- Chạy suites input/users/import/Apply/HTTP/Hồ sơ liên quan, regression và review bảo mật; ghi lệnh/output thật. Không nâng 19 checklist CHƯA CHẠY nếu chưa chạy đúng case.

## Ngoài phạm vi

Không sửa pmtimesheetservice/OT, Audit Log UI hoặc D2 trong bước này. Không migration, sửa SQL mode, rewrite legacy, OTP, xác minh hộp thư, hard delete, chỉnh lịch sử hay push main. Những bước sau vẫn cần PLAN/phê duyệt riêng theo yêu cầu.
