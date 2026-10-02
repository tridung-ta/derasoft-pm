# Phase 5 — Timesheet, OT & Audit

## Phạm vi BUILD cập nhật 2026-10-02

Theo yêu cầu tiếp tục BUILD và quyết định hoãn workflow duyệt. Tái sử dụng task, membership, rate và RBAC.

- User ghi/sửa/ẩn timesheet của mình, task được giao trong tenant và membership active.
- Cửa sổ sửa/xóa 3 ngày lịch theo Asia/Ho_Chi_Minh: work_date là ngày thứ nhất, khóa từ 00:00 ngày thứ tư. Kiểm tra server cả ngày cũ/mới để không vượt khóa bằng chuyển ngày. User không tạo bù ngày đã khóa hoặc ngày tương lai.
- Admin xem/sửa/xóa mọi timesheet trong tenant; sau khóa có audit action riêng. Admin sửa được task lịch sử đã đổi assignee/ẩn khi giữ nguyên task_id; không đổi owner theo input.
- Ngày làm việc + nhãn ca + giờ + mô tả. Tổng giờ active của một user/ngày tối đa 24.
- Default tạm thời: standard_hours_per_day=8, ot_multiplier=1, cấu hình tenant qua Admin.
- Config không seed vào migration; day ledger snapshot ngưỡng/hệ số khi tạo ngày đầu tiên.
- OT tính tổng ngày qua mọi project, phân bổ regular trước theo id tăng dần; phần còn lại OT.
- Create snapshot rate từ PmRateService; giữ snapshot khi sửa cùng ngày, resolve lại khi chuyển ngày.
- UPDATE/soft delete tính lại cả ngày cũ/mới; audit mọi dòng bị thay đổi do tính lại.
- Cost tính DECIMAL trong MySQL; không dùng float PHP để tính tiền.
- Ledger unique(store_id,user_id,work_date) được khóa trước ghi để serialize cả ngày trống.
- Audit old/new JSON trong transaction InnoDB, không chứa password/config secrets.
- Không Submitted/Approved/Rejected, không endpoint duyệt hoặc reject-cả-ngày. Giữ cột status và permission cũ không sử dụng; workflow duyệt hoãn theo docs/AUDIT.md, không phải phần thiếu Phase 5.
- Audit Viewer read-only, lọc entity/action và phân trang: Admin toàn tenant; PM dự án mình quản lý (cả snapshot trước/sau phải trong scope); HR tạm phòng ban hiện tại active hoặc cá nhân khi chưa có phòng ban. PM/HR không nhận fields đơn giá/chi phí trong JSON. Không mở HR toàn tenant khi chưa chốt phạm vi.

## Verification & UAT

Kiểm thử OT/recalculation/rate/cost, ranh giới 3 ngày và Admin override, ownership/tenant,
audit scope/redaction/filter/pagination/escaping, regression Phase 2–4, PHP 8.3 lint và diff check.
Không tự bổ sung holiday/weekend premium hoặc workflow duyệt.
UAT giao diện local cần tài khoản thật, phân biệt với service/template smoke.

Migration 004 chỉ CREATE TABLE IF NOT EXISTS: tenant settings, day ledger, timesheets,
audit logs; rollback ứng dụng giữ bảng cộng thêm. Seed 002 bổ sung idempotent permission
`pm.audit.view` cho Admin/PM/HR theo tenant, không xóa permission cũ. Chỉ áp dụng derasoft_pm_local sau backup.
