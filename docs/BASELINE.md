# Baseline môi trường và kiểm thử

Ngày ghi nhận: 2026-09-30

## Môi trường local được quan sát

| Thành phần | Trạng thái | Ghi chú |
|---|---|---|
| Source | AVAILABLE | `D:\derasoft-pm` |
| PHP CLI mặc định | AVAILABLE | PHP 8.5.10 NTS; không dùng làm chuẩn nghiệm thu production |
| PHP 8.3 portable | AVAILABLE | PHP 8.3.35 NTS tại `.tools/php83/`, toàn bộ thư mục bị Git ignore |
| MySQL client | AVAILABLE | MySQL client 8.4.11 |
| MySQL server | AVAILABLE | Windows service `MySQL84` đang chạy |
| Local DB connection | PASS | `derasoft_pm_local` trên MySQL 8.4.11; ứng dụng kết nối bằng user local giới hạn database |
| XAMPP/Laragon | NOT FOUND | Không thấy ở các đường dẫn Windows thông dụng |
| Smarty | AVAILABLE | Bản nhúng 4.5.5 |
| Composer vendor | NOT FOUND | `vendor/autoload.php` không tồn tại; Admin chỉ require khi file có mặt |
| Web runtime local | PASS | PHP 8.3 built-in server phục vụ repository tại loopback trong smoke test |
| Web root production | DOCUMENTED ONLY | `/domains/pm.dung.derasoft.com/public_html`; Phase 1 không truy cập production |

## PHP extension

| Extension | Trạng thái |
|---|---|
| mysqli | AVAILABLE |
| mbstring | AVAILABLE |
| openssl | AVAILABLE |
| zip | AVAILABLE |
| fileinfo | AVAILABLE trong PHP 8.3 portable |
| gd | AVAILABLE trong PHP 8.3 portable |
| intl | AVAILABLE trong PHP 8.3 portable |

`fileinfo` cần thiết cho kiểm tra MIME upload/import. `gd` và `intl` cần được xác nhận theo các module sẽ dùng. Môi trường chuẩn để nghiệm thu phải chạy PHP 8.3 với extension tương ứng production.

## Ma trận baseline

| Hạng mục | Trạng thái | Bằng chứng/giới hạn |
|---|---|---|
| Render PM dashboard/profile/password/team | PASS | `php tests/smoke_pm_admin.php` |
| Trang login Admin | PASS | HTTP 200, đúng title, không có fatal error |
| Login sai | PASS | Trả lại trang login và thông báo thất bại, không có fatal error |
| Login thành công | PASS (MANUAL) | Người dùng xác nhận bằng tài khoản thật ngày 2026-10-02 |
| Logout Admin | PASS (MANUAL) | Người dùng xác nhận sau bản sửa `1577ac3` |
| Dashboard dùng dữ liệu thật | PASS (MANUAL) | Dashboard PM hiển thị sau đăng nhập thành công |
| CRUD nhân sự mẫu | NOT TESTED | Cần DB local; không kiểm thử trên production |
| Pagination | SOURCE VERIFIED | `Url::genPager()` và nhiều module list đang sử dụng; chưa test runtime |
| Upload | NOT TESTED | Thiếu DB/web server và `fileinfo` |
| Tracking Data | SOURCE VERIFIED | Bảng/DAO/call site đã xác nhận; không tạo log giả vào dữ liệu import |
| Smarty + PhpSpreadsheet | PASS | PHP 8.3 ghi workbook XLSX tạm thời thành công, không có warning |
| PHP 8.3 lint toàn repository | PARTIAL | 1.401 file được kiểm tra: 1.396 đạt, 5 file legacy/third-party lỗi cú pháp PHP 8.3 |
| Legacy UI bị ẩn | SOURCE VERIFIED | `PM_HIDE_LEGACY` và redirect trong `admin.php`; chưa test URL runtime |

## Điều kiện để hoàn tất runtime baseline

1. Cấu hình web runtime PHP 8.3 tương đương bản portable đã kiểm tra.
2. Tạo virtual host/document root local trỏ tới repository.
3. Import bản sao DB đã ẩn dữ liệu nhạy cảm; dùng credential chỉ nằm trong `includes/config.inc.php` đã bị ignore.
4. Chạy smoke test trên local cho login/logout/dashboard/CRUD/pager/tracking/Excel.
5. Không dùng database hoặc config production để thay cho baseline local.

## Kết quả import local

- Database đích mới: `derasoft_pm_local`.
- MySQL: 8.4.11.
- 76 bảng được import; `dc_users` có 8 dòng.
- Không phát hiện username hoặc email trùng trong cùng `store_id`.
- Không ghi đè database cũ; file dump vẫn bị Git ignore.
- `includes/config.inc.php` local trỏ tới database mới và tiếp tục bị Git ignore.

## Cảnh báo tương thích đã quan sát

PHP 8.5 từng báo deprecated trong PhpSpreadsheet/ZipStream nhúng; chạy lại bằng PHP 8.3 mục tiêu không còn cảnh báo. Đây được ghi nhận là chênh lệch local, không sửa thư viện bên thứ ba trong Phase 1.

Năm file legacy/third-party không parse trên PHP 8.3:

- `classes/PHPMailer/extras/htmlfilter.php`
- `classes/PHPMailer1/PHPMailerAutoload.php`
- `classes/PHPMailer1/extras/htmlfilter.php`
- `classes/PHPMailer1/test_script/index.php`
- `classes/mail/getmxrr.php`

Các file này không thuộc luồng PM hiện tại và không bị xóa. Trước khi tái sử dụng email ở phase sau phải chọn một PHPMailer tương thích, cô lập bản cũ và kiểm thử lại; không được nạp các file lỗi trên PHP 8.3.
