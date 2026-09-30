# Baseline môi trường và kiểm thử

Ngày ghi nhận: 2026-09-30

## Môi trường local được quan sát

| Thành phần | Trạng thái | Ghi chú |
|---|---|---|
| Source | AVAILABLE | `D:\derasoft-pm` |
| PHP CLI | AVAILABLE | PHP 8.5.10 NTS; production mục tiêu PHP 8.3 nên chưa được xem là môi trường tương đương |
| MySQL client | AVAILABLE | MySQL client 8.4.11 |
| MySQL server | AVAILABLE | Windows service `MySQL84` đang chạy |
| Local DB connection | NOT TESTED | Kết nối localhost bằng root không mật khẩu bị từ chối; không đọc credential thật và chưa có DB copy đã ẩn dữ liệu |
| XAMPP/Laragon | NOT FOUND | Không thấy ở các đường dẫn Windows thông dụng |
| Smarty | AVAILABLE | Bản nhúng 4.5.5 |
| Composer vendor | NOT FOUND | `vendor/autoload.php` không tồn tại; Admin chỉ require khi file có mặt |
| Web root local | NOT CONFIGURED | Repository chưa được cấu hình virtual host/document root local |
| Web root production | DOCUMENTED ONLY | `/domains/pm.dung.derasoft.com/public_html`; Phase 1 không truy cập production |

## PHP extension

| Extension | Trạng thái |
|---|---|
| mysqli | AVAILABLE |
| mbstring | AVAILABLE |
| openssl | AVAILABLE |
| zip | AVAILABLE |
| fileinfo | MISSING |
| gd | MISSING |
| intl | MISSING |

`fileinfo` cần thiết cho kiểm tra MIME upload/import. `gd` và `intl` cần được xác nhận theo các module sẽ dùng. Môi trường chuẩn để nghiệm thu phải chạy PHP 8.3 với extension tương ứng production.

## Ma trận baseline

| Hạng mục | Trạng thái | Bằng chứng/giới hạn |
|---|---|---|
| Render PM dashboard/profile/password/team | PASS | `php tests/smoke_pm_admin.php` |
| Login Admin | NOT TESTED | Cần web server và DB local |
| Logout Admin | NOT TESTED | Cần session web và DB local |
| Dashboard dùng dữ liệu thật | NOT TESTED | Cần DB local |
| CRUD nhân sự mẫu | NOT TESTED | Cần DB local; không kiểm thử trên production |
| Pagination | SOURCE VERIFIED | `Url::genPager()` và nhiều module list đang sử dụng; chưa test runtime |
| Upload | NOT TESTED | Thiếu DB/web server và `fileinfo` |
| Tracking Data | SOURCE VERIFIED | DAO/call site có trong source; chưa ghi dữ liệu local |
| Smarty + PhpSpreadsheet | PASS WITH WARNINGS | Test ghi XLSX thành công; PhpSpreadsheet nhúng phát cảnh báo deprecated trên PHP 8.5 |
| Legacy UI bị ẩn | SOURCE VERIFIED | `PM_HIDE_LEGACY` và redirect trong `admin.php`; chưa test URL runtime |

## Điều kiện để hoàn tất runtime baseline

1. Cài/chọn PHP 8.3 CLI và web runtime tương đương production.
2. Bật tối thiểu `mysqli`, `mbstring`, `openssl`, `zip`, `fileinfo`; bổ sung `gd`/`intl` nếu module sử dụng.
3. Tạo virtual host/document root local trỏ tới repository.
4. Import bản sao DB đã ẩn dữ liệu nhạy cảm; dùng credential chỉ nằm trong `includes/config.inc.php` đã bị ignore.
5. Chạy smoke test trên local cho login/logout/dashboard/CRUD/pager/tracking/Excel.
6. Không dùng database hoặc config production để thay cho baseline local.

## Cảnh báo tương thích đã quan sát

`php tests/smoke_pm_dependencies.php` hoàn thành thành công nhưng PHP 8.5 báo deprecated trong mã thư viện PhpSpreadsheet/ZipStream nhúng. Không sửa thư viện bên thứ ba trong Phase 1. Cần chạy lại bằng PHP 8.3 mục tiêu; nếu PHP 8.3 không cảnh báo thì ghi nhận đây là chênh lệch local, nếu vẫn cảnh báo thì lập kế hoạch nâng thư viện có kiểm soát.
