# Phase 7 — Resource Allocation & Overbooking

## Status

BUILD / automated VERIFY COMPLETED — tổng thể đã duyệt; hai điểm làm rõ bên dưới
được bổ sung trước BUILD. Kết quả kiểm thử ghi tại docs/PROGRESS.md; UAT chưa xác nhận.
Phase 6 đã BUILD / automated VERIFY; Phase 5–6 chưa có xác nhận UAT.
Nhánh BUILD: `feature/pm-phase7-resource-allocation`, kế thừa thay đổi Phase 6,
giữ nguyên toàn bộ chức năng legacy. Migration/seed đã chạy idempotent trên local sau backup;
chưa commit/push/merge/deploy.

## Phạm vi đề xuất

- Lịch phân bổ theo tuần, thứ Hai đến Chủ nhật, giờ Việt Nam; lọc dự án/người dùng.
- Allocation ghi task, user, ngày, giờ dự kiến và khoảng giờ bắt đầu/kết thúc tùy chọn.
  Giờ là DECIMAL; khoảng giờ nếu có phải khớp thời lượng, không hỗ trợ ca qua đêm trong increment đầu.
- Không tự tạo timesheet, không sửa actual cost/rate_snapshot; allocation là kế hoạch.
- Tạo/sửa/soft delete có CSRF, quyền, kiểm tra task/project/user/membership active cùng tenant
  và audit trước/sau. Task phải được giao cho user được phân bổ.
- Giới hạn tuần lấy `dc_users.weekly_limit_hours` (mặc định 40); giới hạn ngày mặc định 8,
  có cấu hình riêng theo user. Không dùng ngưỡng OT như nguồn giới hạn phân bổ.
- Tổng bằng ngưỡng không quá tải; vượt ngưỡng trả cảnh báo, vẫn cho lưu theo quyền quản lý.
  Không cho giá trị âm/0 hoặc hơn 24 giờ trên một allocation.
- Tổng tải tính từ mọi allocation active của user trong tenant, kể cả dự án ngoài scope PM.
  PM chỉ thấy tổng giờ/busy phục vụ cảnh báo; không lộ tên/ID/task hoặc chi phí dự án ngoài quyền.
- Trùng khoảng giờ dùng khoảng nửa mở [bắt đầu, kết thúc); hai ca liền kề không trùng.
  Allocation chỉ có số giờ không khẳng định có/không trùng thời gian; hiển thị chưa đủ lịch chi tiết.
- Dự án/task đã soft delete không nhận allocation mới; lịch cũ giữ nguyên và gắn nhãn lịch sử,
  không âm thầm làm mất tải đã phân bổ. Cần chốt cách hủy allocation tương lai trong increment sau.
- Gợi ý người còn trống trong membership active của dự án, theo capacity ngày/tuần;
  chỉ là gợi ý, không tự đổi assignee hoặc tự ghi phân bổ, không khẳng định tối ưu kỹ năng.

## Quyền đề xuất cần duyệt

- Permission mới tenant-scoped: `pm.allocations.view`, `pm.allocations.manage`.
- Admin xem/quản lý toàn tenant; PM xem/quản lý dự án phụ trách.
- Employee chỉ đọc allocation của mình; HR chưa mở quản lý phân bổ trước khi chốt phạm vi HR.
- Backend kiểm tra list/detail/save/delete/polling/suggestions, không dựa vào menu.
- Tenant lấy từ session; từ chối cross-tenant, IDOR và actor không active.

## Kiến trúc và database

- Đọc convention `pmprojects`/`pmtimesheets`; DAO tại `classes/dao/pmallocations.class.php`,
  service `classes/services/pmallocationservice.class.php`; controller `modules/admin/pmallocations.module.php`,
  Smarty tại `templates/admin/pm-allocations.tpl.html`; AJAX prefix pm trong allowlist nội bộ hiện có.
- Migration versioned mới chỉ CREATE TABLE: allocations, capacity overrides và khóa theo user/ngày
  nếu cần để serialize mutation; InnoDB, store_id trên bảng/index/unique key.
- Khóa mutation theo user/ngày có thứ tự cố định cho thao tác chuyển người/ngày;
  đọc tải và ghi allocation/audit trong transaction để hai request không bỏ sót cảnh báo.
- Seed permission idempotent cộng thêm, không xóa grant cũ. Chỉ chạy trên local sau backup.
- Rollback an toàn: ẩn route/menu, giữ bảng/dữ liệu/grant; không DROP hoặc xóa lịch sử.
- UI tuần responsive, bảng đọc được trên mobile, cảnh báo quá tải/trùng/thiếu lịch rõ ràng;
  polling 60 giây dừng khi tab ẩn, giữ bộ lọc và dữ liệu cũ khi lỗi. Không thêm thư viện lớn.

## Acceptance / VERIFY

1. CRUD/soft delete, validate ngày/giờ/task/user/membership, audit và rollback fixture.
2. Ngưỡng ngày/tuần mặc định và override, đúng bằng/vượt ngưỡng, tuần qua tháng/năm;
   không double-count khi user có nhiều role/membership.
3. Trùng một phần, bao phủ hoàn toàn theo cả hai chiều, trùng chính xác, tách biệt,
   liền kề ở cả hai đầu (không trùng), thiếu giờ bắt đầu/kết thúc (không khẳng định được), chuyển ngày/user;
   tải từ dự án ngoài scope vẫn cảnh báo mà không lộ chi tiết.
4. Admin/PM/Employee/HR, cross-tenant và IDOR ở mọi endpoint; CSRF mutation;
   historical task/project, escaping HTML/JSON và fail closed.
5. Gợi ý capacity đúng, không tự đổi task; desktop/mobile, empty/error, polling/visibility/filter.
6. Transaction/concurrency trên local, PHP 8.3 lint, regression Phase 2–6,
   security review, diff check; cập nhật PROGRESS và tài liệu portfolio theo kết quả thật.

Không ghi UAT PASS thay người dùng, không tự commit/push/merge/deploy.

## Capacity overrides — schema và thời gian hiệu lực

Chọn **time-bound**, không phải override cố định. Bảng `dc_pm_capacity_overrides`:
`id BIGINT UNSIGNED` PK, `store_id BIGINT UNSIGNED`, `user_id BIGINT UNSIGNED`,
`daily_limit_hours DECIMAL(5,2)`, `weekly_limit_hours DECIMAL(5,2)`,
`effective_from DATE NOT NULL`, `effective_to DATE NULL`, `deleted_at DATETIME NULL`,
`created_by BIGINT UNSIGNED`, `updated_by BIGINT UNSIGNED`, `created_at/updated_at DATETIME`.
Index `(store_id,user_id,effective_from,effective_to)`; NULL effective_to là vô thời hạn.
Ngày hiệu lực inclusive; effective_to phải >= effective_from. Không cho khoảng active
chồng lấn trên cùng user/tenant; bỏ qua bản ghi soft delete và excludeId khi sửa.

Hàm `PmAllocationService::hasOverlappingCapacity(int $userId, string $from, ?string $to, ?int $excludeId)`
đặt tại `classes/services/pmallocationservice.class.php`, được gọi trong `saveCapacity()`
sau khi khóa mutex theo user trong transaction, trước INSERT/UPDATE. Công thức đối xứng:

```sql
(existing.effective_to IS NULL OR existing.effective_to >= new.effective_from)
AND
(new.effective_to IS NULL OR new.effective_to >= existing.effective_from)
```

Prepared SQL bind from/to, không nội suy input. Với từng ngày, dùng override có hiệu lực
ngày đó; nếu không có, daily=8 và weekly=dc_users.weekly_limit_hours.
Giới hạn tuần chọn theo ngày thứ Hai của tuần và ghi nhãn quy tắc này; override giữa tuần
chỉ thay giới hạn ngày kể từ effective_from, ngưỡng tuần mới áp dụng từ thứ Hai kế tiếp.
Quản lý override chỉ dành cho Admin; PM không thay capacity toàn tenant.
Acceptance 2 bổ sung: khoảng hữu hạn/vô hạn, hai chiều bao phủ, ngày biên chung bị overlap,
khoảng liên tiếp khác ngày không overlap, excludeId, soft delete, đổi ngưỡng giữa tuần.

## Allocation — SQL trùng giờ nửa mở

Trong cùng store_id/user_id/work_date, bỏ bản ghi soft delete và excludeId khi sửa:

```sql
existing.start_time IS NOT NULL AND existing.end_time IS NOT NULL
AND new.start_time IS NOT NULL AND new.end_time IS NOT NULL
AND existing.start_time < new.end_time
AND new.start_time < existing.end_time
```

DAO bind new.start_time/new.end_time bằng prepared statement. Ví dụ existing [09:00,12:00):

| New | Kết luận |
| --- | --- |
| [11:00,13:00) hoặc [08:00,10:00) | Trùng một phần |
| [08:00,13:00) | New bao phủ existing, trùng |
| [10:00,11:00) | Existing bao phủ new, trùng |
| [09:00,12:00) | Trùng chính xác |
| [12:00,14:00) hoặc [07:00,09:00) | Liền kề, không trùng |
| [13:00,14:00) | Tách biệt, không trùng |
| Thiếu start hoặc end | Không khẳng định được |

API ghi yêu cầu cả hai giờ hoặc cả hai NULL, từ chối input chỉ có một đầu.
Allocation không có khoảng giờ vẫn đóng góp tổng tải và cảnh báo thiếu lịch chi tiết;
không suy diễn SQL trả false thành kết luận không trùng khi thiếu thông tin.
Toàn bộ các ca trên nằm trong acceptance 3.
