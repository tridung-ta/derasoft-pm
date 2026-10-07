# Database migrations

Thư mục này chứa migration cộng thêm cho DeraSoft PM.

## Quy ước tên

```text
NNN_short_description.sql
```

Ví dụ: `001_create_pm_rbac_tables.sql`.

## Quy tắc an toàn

- Chỉ dùng `CREATE TABLE` mới hoặc `ALTER TABLE ... ADD COLUMN/INDEX` đã được duyệt.
- Không dùng `DROP`, `TRUNCATE`, `RENAME` hoặc `DELETE` dữ liệu legacy.
- Bảng nghiệp vụ PM mới dùng prefix đã xác nhận từ cấu hình, InnoDB và `utf8mb4` khi môi trường hỗ trợ.
- Không ghi cứng tên database, credential, domain hoặc dữ liệu production.
- Migration phải được thử trên bản sao database local đã ẩn dữ liệu nhạy cảm trước khi deploy.
- Mỗi file cần ghi mục đích, điều kiện tiên quyết, kiểm tra sau chạy và rollback note.
- Nếu MySQL/version không hỗ trợ cú pháp idempotent cần thiết, phải có bước kiểm tra schema trước khi chạy.
- Không tự chạy migration khi chỉ đang review source hoặc tài liệu.

## Quy trình

1. Backup database local/staging.
2. Kiểm tra schema và dữ liệu xung đột.
3. Chạy migration trên bản sao local.
4. Xác minh bảng/cột/index và smoke test ứng dụng.
5. Ghi kết quả vào `docs/PROGRESS.md`.
6. Production chỉ được chạy sau UAT, backup và phê duyệt riêng.

Phase 1 chưa có migration SQL và không thay đổi database.
