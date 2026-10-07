# Plan Final — Hệ thống Quản lý Dự án & Chấm công

## Nguyên tắc triển khai

Tái sử dụng tối đa kiến trúc DeraSoft và chỉ thêm/sửa đúng nhu cầu PM. Không xóa chức năng cũ, không thay framework, không chạm production khi phát triển. Mỗi phase có nhánh riêng, plan được duyệt trước khi build, commit nhỏ và kiểm thử rõ ràng.

Database chỉ mở rộng bằng bảng/cột mới. Query mới phải prepared, output phải escape, mọi thao tác ghi có CSRF và mọi endpoint có kiểm tra quyền/ownership. Secret, config thật, dump, cache và log không được đưa lên Git.

## Kiến trúc mục tiêu

| Thành phần | Vị trí |
|---|---|
| DAO/Model | `classes/dao/` |
| Admin controller | `modules/admin/` |
| AJAX nội bộ | `modules/ajax/` |
| Smarty view | `templates/admin/` |
| Migration | `database/migrations/` |
| Tài liệu | `docs/` |

Tên miền chức năng mới dùng tiền tố `pm`: role, rate, project, member, task, timesheet, allocation, report, import/export và audit.

## Mô hình dữ liệu đề xuất

- Tái sử dụng bảng user hiện có; chỉ thêm các trường thực sự thiếu sau khi xác nhận schema local.
- Bảng mới dự kiến: departments, roles, user_roles, permissions, role_permissions, hourly_rates, projects, project_members, tasks, timesheets, allocations, system_settings, import_logs.
- Ưu tiên mở rộng Tracking Data hiện có; chỉ tạo audit log mới nếu cấu trúc legacy không thể mở rộng an toàn.
- Timesheet lưu `rate_snapshot`, `regular_hours`, `ot_hours` và `cost` để bảo toàn lịch sử.
- Index trọng yếu theo project, task, user, work_date, allocation date và audit entity.

Đây là thiết kế khái niệm. SQL chỉ được tạo ở phase đã duyệt sau khi audit schema local; Phase 0 không tạo/chạy migration.

## Lộ trình

### Phase 0 — Discovery & Audit

Rà entrypoint, router, auth/session, DAO, Smarty, phân trang, tracking, Excel và schema có thể xác minh. Lập gap analysis, chọn module mẫu, chốt convention và câu hỏi mở. Deliverable: `docs/AUDIT.md`; dừng chờ duyệt.

### Phase 1 — Môi trường & Baseline

Chuẩn hóa môi trường PHP 8.3/local DB copy, baseline test, cấu trúc migration và progress. Không dùng production.

### Phase 2 — Auth, Role & Permission

Thêm RBAC nhiều-nhiều, permission middleware, ownership và nâng cấp mật khẩu MD5 sang `password_hash()` theo cơ chế tương thích ngược/rehash sau login.

### Phase 3 — User, Role & Hourly Rate

CRUD nhân sự, phòng ban, trạng thái/khóa, nhiều role, đơn giá theo user hoặc role có ngày hiệu lực, tìm kiếm và phân trang.

### Phase 4 — Project & Task

CRUD dự án/thành viên/task, giới hạn người được giao, trạng thái và Kanban. PM chỉ quản lý dự án thuộc phạm vi mình.

### Phase 5 — Timesheet, OT & Audit

Ghi công theo task/ngày/ca, tính lại OT theo tổng giờ ngày, lưu rate snapshot/cost và ghi audit cho mọi thay đổi.

### Phase 6 — Cost & Chart

Tổng hợp chi phí thực tế/dự kiến theo nhiều chiều và biểu đồ Chart.js; cập nhật bằng tải lại hoặc polling 30–60 giây.

### Phase 7 — Resource Allocation & Overbooking

Bảng phân bổ tuần, giới hạn giờ theo người, cảnh báo quá tải/trùng thời gian và gợi ý nguồn lực còn trống.

### Phase 8 — Reports & Excel

Báo cáo cá nhân/nhóm/chi phí có bộ lọc; export XLSX; import nhân sự validate/staging
transactional, Apply từng dòng/resumable qua UNIQUE email và nhật ký; giữ dc_users MyISAM
theo quyết định chính thức 04/10/2026, không atomic toàn file.

### Phase 9 — UI/UX, Performance & Security

Hoàn thiện responsive, accessibility và trải nghiệm; dùng EXPLAIN, bỏ N+1, bổ sung index; rà SQLi, XSS, CSRF, IDOR, upload và session.

### Phase 10 — Test, UAT & Release Preparation

Test theo vai trò và nghiệp vụ, UAT trên staging/local copy, lập release note, danh sách file deploy, migration, smoke test và rollback. Production chỉ triển khai sau phê duyệt và backup.

## Logic nghiệp vụ đã chốt

- Login chuyển tiếp hỗ trợ username hoặc email; seed bốn role hệ thống nhưng schema cho phép mở rộng.
- Rate tại ngày làm việc: ưu tiên rate theo user, sau đó role, nếu thiếu thì giá trị 0 kèm cảnh báo.
- Nếu user có nhiều role và không có rate riêng thì dùng role chính, không dùng role có giá cao nhất.
- OT: giờ thường tối đa theo `standard_hours_per_day` (mặc định 8); phần vượt là OT; thay đổi một dòng phải tính lại toàn bộ ngày của user.
- Overbooking: tổng allocation vượt giới hạn tuần hoặc giới hạn ngày sẽ cảnh báo; ngưỡng phải cấu hình được.
- Xóa nghiệp vụ dùng soft delete và audit.
- Import validate toàn bộ trước staging, báo lỗi theo dòng. Transaction chỉ cho bảng PM
  InnoDB; Apply dc_users MyISAM dùng INSERT/1062, durable intent và mutex, không đổi ENGINE.
- Budget ban đầu dùng VND; giới hạn tuần mặc định 40 giờ nhưng cho phép cấu hình theo user.
- Admin legacy chỉ mở khẩn cấp qua feature flag cục bộ, mặc định tắt.
- Phạm vi HR, quy tắc OT đặc biệt và vòng đời duyệt timesheet còn chờ mentor xác nhận; dùng mặc định an toàn ghi trong `docs/AUDIT.md` nếu cần tiếp tục.

## Definition of Done chung

- PHP lint và test liên quan đạt; có hướng dẫn test tay theo vai trò.
- Không có secret, cache, dump hoặc file ngoài phạm vi trong diff.
- Permission/ownership, CSRF, XSS và SQL injection được kiểm tra.
- Migration cộng thêm, idempotent theo khả năng của MySQL đang dùng, đã thử trên DB copy và có rollback note.
- `docs/PROGRESS.md` được cập nhật trước khi đề nghị merge.

## PHẦN 13. ĐỊNH HƯỚNG GIAO DIỆN (không bắt buộc)

> **Lịch sử thay đổi:** bản đầu (2 ngôn ngữ "Sổ ghi công"/"Buồng điều khiển", token hổ phách/than xám) đã bị **thay thế hoàn toàn** ngày 05/10/2026 theo quyết định dùng 1 hệ thống trung tính kiểu shadcn/ui cho toàn bộ app, thay vì tách FE/Admin. Nếu Phase 9b đã build theo token cũ (`#B98A2E`, `#A6432D`, `#161A20`, `#EEF0EA`, font Lora/IBM Plex), phải dừng và làm lại theo token mới dưới đây trước khi tiếp tục bước nào khác.
>
> Phần này vẫn là **gợi ý định hướng**, không bắt buộc đúng từng pixel — có thể điều chỉnh khi code thực tế nếu giới hạn Bootstrap 5/Smarty hoặc mentor yêu cầu khác.

### 13.1 Nguyên tắc: 1 hệ thống thống nhất, không tách FE/Admin

Khác bản trước, lần này **không tách 2 phong cách riêng cho FE và Admin** — dùng chung 1 bảng màu/font/bo góc cho toàn app (đúng tinh thần shadcn/ui: 1 design system nhất quán). Chỉ khác nhau ở **bố cục** theo mục đích trang: Admin có sidebar + bảng dữ liệu dày, FE (chấm công) đơn giản, ít điều hướng.

### 13.2 Token hệ thống (dùng chung toàn app)

| Token | Giá trị | Vai trò |
|---|---|---|
| `--background` | `#ffffff` | Nền trang |
| `--foreground` | `#09090b` | Chữ chính |
| `--card` / `--card-border` | `#ffffff` / `#e4e4e7` | Nền card, viền mảnh |
| `--muted` | `#f4f4f5` | Nền sidebar, nền header bảng, panel phụ |
| `--muted-foreground` | `#71717a` | Chữ phụ, nhãn, chú thích |
| `--primary` / `--primary-foreground` | `#18181b` / `#fafafa` | Nút hành động chính (nền gần đen, chữ trắng) |
| `--destructive` / `--destructive-foreground` | `#dc2626` / `#fef2f2` | Nút Khóa/Xóa/hành động phá hủy |
| `--warning` | `#d97706` | **Duy nhất 1 màu cảnh báo nghiệp vụ** — overbooking, vượt estimated_hours/ngân sách, mixed currency, thiếu rate. Không dùng cho mục đích khác |
| `--border` | `#e4e4e7` | Viền input, divider |
| `--radius` | `8px` (card/panel), `6px` (nút/input/badge) | Bo góc nhất quán |
| Font | **Inter** — 1 font duy nhất, toàn hệ thống, mọi vùng (tiêu đề, body, số liệu) | Không còn phối 2 font như bản cũ |
| Số liệu giờ/tiền | Inter + `font-variant-numeric: tabular-nums`, `text-align: right` | Giữ số thẳng hàng mà không cần font mono riêng |

### 13.3 Bố cục Admin/PM

Sidebar cố định bên trái, nền `--muted` (`#f4f4f5`), **icon + chữ** (không chỉ icon như bản cũ), mục đang chọn có nền `#e4e4e7`. Bộ lọc (tuần/dự án/phòng ban) luôn ở đầu vùng nội dung.

```
┌──────────┬──────────────────────────────────────┐
│ D DeraSoft│  Lọc: [Tuần này ▾] [Dự án ▾]          │
│──────────│──────────────────────────────────────│
│ Tổng quan │  Dự án           Giờ thực   Chi phí   │
│ Nhân sự   │  Website bán..   142.5h    84,200,000 │
│ Dự án     │  App nội bộ       58.0h    31,900,000 │
│ Chấm công │  CMS khách B      96.0h    52,000,000 │ ← vượt ngưỡng tô #d97706
│ Chi phí   │                                       │
│ Phân bổ   │  ⚠ 3 cảnh báo vượt giờ tuần này        │
│ Báo cáo   │                                       │
└──────────┴──────────────────────────────────────┘
```

Card nền trắng viền `#e4e4e7`, không đổ bóng đậm, bo góc `8px`. Chỉ dòng/ô thực sự vượt ngưỡng mới tô `--warning` — các dòng bình thường giữ tông trung tính.

### 13.4 Bố cục FE (trang chấm công hàng ngày)

Cùng token với Admin, chỉ đơn giản hóa bố cục: 1 card trắng viền mảnh, không sidebar. Giữ 3 điểm nhấn chức năng đã chốt từ bản trước (vẫn hữu ích, chỉ đổi màu):

```
        Thứ Ba, 30 tháng 9        Chuỗi 12 ngày liên tục
        Hôm nay bạn đã làm 6.5h

   [dải 7 cột giờ trong tuần T2→CN, cột hôm nay tô #18181b,
    cột có OT viền #d97706]

  ── ● Website bán hàng · Viết API giỏ hàng ──
     08:00 → 12:00                          4.0h

     + Thêm dòng chấm công          Đã lưu 16:04  (●✓)
  ────────────────────────────
     Tổng hôm nay:  6.5h  (0 OT)
```

1. **Dải 7 cột giờ trong tuần** — cột hôm nay `#18181b`, cột có OT viền `#d97706`.
2. **Chấm màu trước tên dự án** — bảng ánh xạ `project_id → màu` cố định trong DB/config, dùng chung cả Admin.
3. **Con dấu "Đã lưu"** — chỉ hiện sau khi AJAX lưu thành công.

### 13.5 Khi hiện thực bằng Bootstrap 5 + Smarty (Derasoft)
- Override biến SCSS (`$body-bg: #ffffff`, `$font-family-base: 'Inter'`, `$primary: #18181b`, `$danger: #dc2626`, `$border-radius: .5rem`) hoặc viết CSS riêng đè lên — không dùng nguyên theme Bootstrap mặc định.
- Tạo 1 class dùng chung `.num-cell` (`font-variant-numeric: tabular-nums`, `text-align:right`) áp cho mọi ô giờ/tiền.
- Tách riêng 1 file CSS cho theme (`theme-pm.css` hoặc file CSS thật đang dùng — xác nhận lại với Agent, dự án đã có `css/pmui.css` từ Phase 9), nạp sau Bootstrap, scope theo class gốc layout PM.
- Nút hành động chính dùng `--primary` (nền gần đen, chữ trắng); nút phá hủy (Khóa/Xóa) dùng `--destructive` (đỏ) — 2 màu phải khác biệt rõ, không dùng chung 1 màu.
- Font `Inter` tải qua Google Fonts — kiểm tra CSP có chặn không; nếu chặn, self-host.

### 13.6 Được phép thay đổi gì trong lúc code
- Đổi mã hex cụ thể nếu không hợp bộ nhận diện công ty — giữ nguyên **nguyên tắc** (1 hệ thống thống nhất, 1 màu cảnh báo dùng đúng chỗ, primary/destructive khác biệt rõ, số liệu tabular căn phải) quan trọng hơn giữ đúng mã hex.
- Bỏ bớt điểm nhấn (dải tuần, con dấu, chuỗi ngày) nếu không đủ thời gian.
- Đổi bố cục nếu Derasoft đã có sẵn layout admin dùng tốt — ưu tiên tái sử dụng hơn làm mới hoàn toàn.