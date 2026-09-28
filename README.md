# DeraSoft PM

Hệ thống quản lý dự án, phân bổ nguồn lực và chấm công được phát triển trên nền DeraSoft PHP + Smarty hiện có.

## Nguyên tắc phát triển

- Tái sử dụng kiến trúc và chức năng cũ; không viết lại framework.
- Chức năng cũ không thuộc phạm vi chỉ được ẩn khỏi menu, không xóa mã nguồn.
- Thay đổi cơ sở dữ liệu dùng migration an toàn; không DROP hoặc xóa dữ liệu cũ.
- Không commit file cấu hình môi trường, dump cơ sở dữ liệu, cache hoặc file backup.

Kết quả khảo sát ban đầu nằm tại `docs/AUDIT.md`.

## Cấu hình môi trường

- Sao chép `includes/config.inc.php.example` thành `includes/config.inc.php` và điền thông tin local.
- Thiết lập biến môi trường `RECAPTCHA_SECRET_KEY` nếu sử dụng các form tuyển dụng cũ.
