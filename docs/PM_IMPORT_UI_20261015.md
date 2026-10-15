# Trang import nhân sự — 15/10/2026

## Phạm vi và quyết định

Ba chức năng Tải mẫu, Upload & Preview, Lịch sử Apply có panel riêng. Giữ theme Inter, trung tính, viền rõ. Không thay service/DAO, schema, engine dc_users, điều kiện Apply, quyền hoặc CSRF. Không truy cập host hay database.

Preview gửi multipart cùng controller hiện có bằng fetch, giữ input file trên trang. Stage vẫn POST thường và kiểm tra lại toàn bộ tệp. Không cache workbook trên server, không lưu tệp/mật khẩu vào localStorage. Khi không có JavaScript, các mục vẫn hiện và POST thường hoạt động; cần chọn lại tệp sau Preview.

Apply/resume có thông báo tổng số đã tạo, trùng, lỗi, chưa hoàn tất; lỗi/partial không báo thành công. Cấp mật khẩu thành công có xác nhận User ID và dòng Excel; thất bại không có xác nhận giả. Kiểm tra mật khẩu phía trình duyệt theo UTF-8 byte như service (12–72 byte). Code hiện tại không cấm hai tài khoản dùng cùng mật khẩu. Chưa xác minh việc lưu mật khẩu của tài khoản thứ hai trên DB thật.

## Kiểm chứng local

- `powershell -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1`: PASS 23 script không DB, 30 PHP lint, 6 kiểm tra manifest.
- `php tests/pm_import_password_feedback_preview.php`: PASS render Smarty thật, escape output, fixture success/error.
- `playwright-cli -s=workspace run-code --filename tests/pm_import_tabs_browser.js`: PASS desktop/mobile, tab riêng, bàn phím, giữ tệp qua Preview, lỗi phiên/mạng và multipart Stage. POST được chặn bằng response mô phỏng.
- `playwright-cli -s=workspace run-code --filename tests/pm_phase9b_imports_browser.js`: PASS responsive 360/390/768/1440, phóng chữ 200%, CSRF field, CSP, no-JS, điều kiện nút Apply/resume/password.
- `playwright-cli -s=workspace run-code --filename tests/pm_import_password_feedback_browser.js`: PASS hai dòng dùng cùng mật khẩu giả, byte bounds, thông báo success/error và mở khóa nút sau validation.
- Controller thật chạy với stub service kiểm tra success, exception, CSRF, Apply hoàn tất/partial; không kiểm chứng quyền tích hợp hay ghi DB.
- Rà soát diff: thay đổi chỉ controller/template import và asset riêng; shared pmui, service, DAO, SQL không đổi. Escape, permission, ownership và CSRF hiện hữu được giữ nguyên.
- Impeccable detector báo flat hierarchy do resolve stylesheet tương đối sai từ `templates/admin/css/...`; browser thực tế tải stylesheet và h1 26px trên mobile. Không coi detector là PASS.

## Upload thủ công

Gói chỉ chứa 5 file runtime và hướng dẫn. Sao lưu đúng 2 file cũ trước khi ghi đè:

1. `modules/admin/pmimports.module.php`
2. `templates/admin/pm-imports.tpl.html`

Upload asset mới trước, rồi module, template cuối cùng:

1. `css/pmimports.css` → `public_html/css/`
2. `js/pmimports.js` và `js/pmimportfeedback.js` → `public_html/js/`
3. `modules/admin/pmimports.module.php` → `public_html/modules/admin/`
4. `templates/admin/pm-imports.tpl.html` → `public_html/templates/admin/`

Không upload toàn dự án. Không chạy SQL, không thay cron hoặc cấu hình. Refresh Ctrl+F5 trang `admin.php?op=pmimports`.

Kiểm tra 3 tab; chọn tệp, Preview rồi lưu staging không chọn lại. Với batch đã completed, chỉ bấm Kết quả để kiểm tra cấp mật khẩu; không import/Apply lại để thử giao diện. Tài khoản mới vẫn cần kích hoạt theo quy trình hiện có. Nếu lỗi, chụp thông báo mới (không gửi mật khẩu).

Rollback: phục hồi 2 file đã sao lưu; asset mới không được template cũ gọi. Chưa deploy, chưa chạy integration DB hoặc UAT production.
