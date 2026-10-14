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

### 010 — daily total-hours snapshot
Depends on004; apply schema before P3 runtime. ADD ONLY max_total_hours DECIMAL(4,2) NOT NULL DEFAULT24.00 to dc_pm_timesheet_days; idempotent column guard. Retain existing snapshots/data and engines. Back up approvedtarget and maintain allwriters beforeDDL; no production execution authorized. Applicationrollback keepscolumn/data. Local09/10verified twice; detailed proof docs/P3_BUILD_VERIFY_20261009.md.

## 011 — PM notifications (14/10/2026, chưa thực thi)
011_create_pm_notifications.sql: chỉ CREATE TABLE IF NOT EXISTS dc_pm_notifications, 8 cột và unique event/index recipient. Phụ thuộc user/RBAC/project/task/timesheet migrations001–004. Runtime mới mặc định OFF và fail-safe khi chưa có bảng; chỉ bật sau preflight schema/UAT. Rollback tắt cron+PM_REMINDERS_ENABLED, phục hồi code, giữ nguyên bảng/dữ liệu. Không đổi engine/bảng cũ, không tự chạy SQL. Chi tiết ../docs/PM_CRON_HOST_UAT_20261014.md.

## 012 — notification deadline snapshot (proposal14/10, not executed)
012_add_pm_notification_due_date.sql depends011. ADD COLUMNdue_dateDATE NULL and ADDUNIQUEuq_pm_notification_deadline(store_id,recipient_id,kind,entity_id,due_date);keepuq_pm_notification_event,kind/event_date semantics,legacyNULLrows. No backfill. Existenceguards/mismatchstatus requiremanualreview; no auto repair. Migrationindex may lock: testcopy/backup/maintenance beforehostapproval. KeepfeatureOFF untilnewruntime9columnpreflight. ManualSQL/expected: docs/PM_CRON_012_MANUAL_TEST.md. No agentSQLexecution orBUILD yet.
