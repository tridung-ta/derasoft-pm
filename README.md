# DeraSoft PM

Hệ thống quản lý dự án, phân bổ nguồn lực và chấm công được phát triển trên nền DeraSoft PHP + Smarty hiện có.

## Trạng thái

Dự án đang ở giai đoạn thiết lập nền tảng. Giao diện và chức năng DeraCMS cũ được giữ trong mã nguồn nhưng đang bị ẩn thông qua chế độ PM.

## Nguyên tắc phát triển

- Tái sử dụng kiến trúc hiện có, không viết lại framework khi chưa cần thiết.
- Chức năng cũ ngoài phạm vi chỉ được ẩn, không xóa mã nguồn.
- Thay đổi cơ sở dữ liệu phải có phương án sao lưu và không làm mất dữ liệu cũ.
- Không commit cấu hình môi trường, thông tin đăng nhập, license, database dump, cache hoặc file backup.
- Mỗi commit chỉ nên chứa một thay đổi hoàn chỉnh và có thể kiểm tra độc lập.

## Triển khai

Document root trên hosting:

```text
/domains/pm.dung.derasoft.com/public_html
```

Các file cấu hình riêng phải được tạo trực tiếp trên môi trường triển khai và không đưa lên Git:

```text
includes/config.inc.php
license/license.inc.php
```

Sau khi cập nhật template, cần xóa các file đã biên dịch bên trong `templates_c/` nhưng giữ nguyên thư mục.

## Tài liệu

- Kế hoạch chính thức: [`docs/PLAN_FINAL.md`](docs/PLAN_FINAL.md)
- Kết quả audit: [`docs/AUDIT.md`](docs/AUDIT.md)
- Baseline môi trường: [`docs/BASELINE.md`](docs/BASELINE.md)
- Tiến độ theo phase: [`docs/PROGRESS.md`](docs/PROGRESS.md)
- Quy ước migration: [`database/migrations/README.md`](database/migrations/README.md)
