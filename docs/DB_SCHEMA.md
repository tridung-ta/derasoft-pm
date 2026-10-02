# Database schema baseline

Ngày kiểm tra: 2026-09-30

## Nguồn và giới hạn

Schema được trích từ bản dump local `dung_pm.sql`, SHA-256:

```text
6AF3EED4E833BCA226D851BD9D5DDC2B9E6DBEA7BC4B7713B06ABD7ECAB2541B
```

File dump chứa dữ liệu thật, được `.gitignore` loại khỏi Git và không được sao chép vào tài liệu. Việc đọc ở bước này chỉ dùng các câu `CREATE TABLE`/`ALTER TABLE`; chưa import và chưa thay đổi database.

Dump có 76 bảng, 478 khối `INSERT INTO`, 156 khối `ALTER TABLE`; không phát hiện `DROP DATABASE`, `DROP TABLE`, `TRUNCATE` hoặc `DELETE FROM`.

## Bảng legacy liên quan

### `dc_users`

- Engine: MyISAM; charset `utf8mb4`.
- Primary key: `id` auto increment.
- Index đơn: `username`, `store_id`, `email`, `fullname`.
- Trường có thể tái sử dụng: `store_id`, `username`, `email`, `fullname`, `address`, `tel`, `cell`, `type`, `status`, `properties`, `last_login`.
- `password varchar(50)` đang chứa hash legacy và không đủ cho hash hiện đại dài 255 ký tự.
- `username` và `email` chỉ có index thường, chưa có unique constraint theo store.
- Không có department, weekly limit, password hash mới hoặc cờ buộc đổi mật khẩu.

### `dc_login_times`

- Engine: MyISAM.
- Trường: `uid`, `fail_times`, `last_try`, `last_ip`.
- Có index `uid`, nhưng không unique; application phải tránh tạo nhiều dòng không kiểm soát.

### `dc_trackings`

- Engine: MyISAM.
- Chỉ có `store_id`, `username`, `action`, `date_created`, `ip`.
- Chỉ index `store_id`; thiếu index thời gian/user và thiếu dữ liệu audit có cấu trúc.

### `dc_estores`

- Engine: MyISAM.
- `subdomain` unique; có `domain`, owner và status.
- PM phải tiếp tục mang `store_id` trên dữ liệu thuộc tenant.

## Quyết định thiết kế từ schema thật

1. Giữ `dc_users` làm nguồn tài khoản; không tạo bảng user thay thế.
2. Thêm `password_hash varchar(255) NULL` thay vì sửa/xóa cột `password` legacy.
3. Tái sử dụng `tel`/`cell`, không thêm cột phone trùng nghĩa.
4. Role/permission/project/task/timesheet mới dùng bảng prefix `dc_pm_` và InnoDB.
5. Không tạo foreign key từ bảng InnoDB mới sang các bảng user/store MyISAM; kiểm tra tham chiếu ở application layer và dùng index.
6. Audit PM cần dữ liệu actor/entity/old/new. Ưu tiên một bảng `dc_pm_audit_logs` InnoDB và có thể ghi tóm tắt sang `dc_trackings`; không ép bảng text MyISAM thành audit transactional.
7. Mọi unique key mới có `store_id` khi dữ liệu thuộc tenant, ví dụ `(store_id, code)` hoặc `(store_id, email)` sau khi kiểm tra dữ liệu trùng.

## Kiểm tra bắt buộc sau khi import local

- Xác nhận MySQL version, sql_mode, charset/collation và storage engine support.
- Đếm trùng username/email theo `store_id` trước khi đề xuất unique constraint.
- Xác nhận mapping `status` và `type` từ dữ liệu đang dùng.
- Kiểm tra số dòng và kích thước bốn bảng liên quan.
- Không hiển thị username, email, hash hoặc dữ liệu cá nhân trong log/test report.
