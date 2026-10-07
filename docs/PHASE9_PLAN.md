# Phase 9 — UI/UX, Performance & Security

## Status — 2026-10-04

BUILD / automated VERIFY hoàn tất; UI/accessibility và thiết kế batch role đã được duyệt.
Phase 8 đã BUILD / automated VERIFY;
UAT toàn bộ local dành cho người dùng khi hoàn tất dự án, không chờ UAT từng phase.
Nhánh BUILD `feature/pm-phase9-ui-security`, kế thừa working tree Phase 6–8.
ADD INDEX nếu cần phải dừng và xin duyệt migration riêng ngay lúc phát hiện.

## Bằng chứng ban đầu

- `modules/admin/pmusers.module.php` gọi `PmUsers::roles()` trong vòng lặp từng user:
  một trang tối đa 20 user thêm tối đa 20 query role. Cần batch lookup tenant-scoped.
- `templates/admin/pm-users-v2.tpl.html` có các input chỉ dùng placeholder, thiếu label
  riêng; bảng nhân sự chứa nhiều form nên cần kiểm tra overflow và sử dụng bằng bàn phím.
- Dashboard có sidebar riêng; các module nghiệp vụ dùng layout/CSS khác nhau.
  Cần thống nhất điều hướng, trạng thái trang hiện tại và cách quay lại mà giữ quyền menu.
- Đã có browser checks costs/allocations/reports/imports; chưa có matrix browser đầy đủ
  các màn hình/role PM, keyboard/zoom/focus và session-expiry trong một bộ kiểm thử chung.

Đây là kết quả đọc code, chưa phải kết luận benchmark hoặc audit giao diện bằng browser.

## Phạm vi BUILD đề xuất

### Giao diện và accessibility

- Rà login, dashboard, nhân sự/phòng ban/role/rate, dự án/task/Kanban, timesheets,
  audit, costs, allocations/capacity, reports và imports.
- Tái sử dụng Smarty includes và CSS local cho điều hướng/các thành phần dùng chung;
  giữ route/controller/DAO/framework hiện có, không thêm thư viện UI lớn.
- Menu theo permission hiện có; backend vẫn kiểm tra quyền độc lập. Hiển thị trang đang
  mở, liên kết về tổng quan, tiêu đề rõ ràng và skip link tới nội dung chính.
- Bổ sung label, nhóm input, tên nút có ngữ cảnh, focus-visible, thông báo lỗi/thành công
  phù hợp screen reader; kiểm tra contrast, keyboard và reduced-motion.
- Desktop/mobile: không overflow toàn trang; bảng rộng cuộn trong vùng riêng có chỉ dẫn
  và tên vùng. Form không bị cắt; kiểm tra 360/390/768/1440px và zoom 200%.
- Giữ các trạng thái empty/error/read-only/historical/missing rate/mixed currency;
  không chỉ dùng màu để diễn đạt quá tải, khóa hoặc lỗi.
- Giữ filter/pagination khi tải lại và polling; lỗi polling giữ dữ liệu trước đó,
  thông báo quyền/phiên hết hạn rõ ràng. Chu kỳ 60 giây, dừng khi tab ẩn.
- Không đổi nghiệp vụ, phạm vi HR hoặc thêm workflow duyệt timesheet.

### Hiệu năng có bằng chứng

- Thay N+1 role của trang nhân sự bằng prepared batch query cho các ID trên trang,
  lọc store_id, không JOIN làm nhân đôi tổng/count; giữ role chính và quyền chỉnh role.
- Thiết kế batch cần xác nhận trước code: một query lấy toàn bộ user_id/role_id/is_primary
  từ dc_pm_user_roles cho các ID trên trang và đúng store_id, không suy ra primary bằng
  JOIN/order/aggregate ở SQL. PHP GROUP theo user_id; chỉ dòng is_primary=1 được dùng làm
  role chính, không chọn role đầu tiên hoặc role có ID/rate lớn nhất. User không role trả
  danh sách rỗng; không primary thì không tự suy diễn. Nếu dữ liệu có nhiều primary, báo
  bất nhất thay vì âm thầm lấy dòng cuối. Giữ nguyên danh sách role khi hiển thị/chỉnh.
- Đo số query trước/sau trên fixture rollback, kiểm tra 1/20 user không tăng query role
  theo số user. Không tạo nhân sự giả tồn tại trong dc_users MyISAM.
- EXPLAIN các query thực tế: users/filter, audit/pagination, costs/currency guard,
  allocations/week/load, reports/export và imports/history/detail.
- Ghi query shape, index sử dụng và hạn chế dữ liệu local; không suy ra tốc độ production
  từ 9 user hoặc fixture nhỏ. Chỉ tối ưu query khi có bằng chứng và test tương đương.
- Không tự thêm index, đổi ENGINE hoặc schema legacy. Nếu EXPLAIN cho thấy cần ADD INDEX,
  dừng bước index ngay, gửi báo cáo riêng tại thời điểm phát hiện (không gộp tổng kết phase),
  kèm query/EXPLAIN trước, migration versioned/preflight/backup/rollback và xin duyệt riêng.
  EXPLAIN sau chỉ ghi là kết quả đã chạy nếu index thử được tạo trên môi trường cô lập đã
  được phép; nếu chưa có, ghi rõ chưa chạy, không tự tạo index để lấy kết quả sau trước duyệt.
  Sau migration được duyệt và thực thi local, gửi EXPLAIN trước/sau thật để đối chiếu.
- Không đổi DECIMAL sang float để tổng hợp tiền; không bỏ currency guard, tenant scope,
  khóa mutation hoặc audit để tăng tốc.

### Code và bảo mật

- Trace entrypoints Admin/AJAX/exports/import mutations theo từng role; thử SQLi,
  stored/reflected XSS, CSRF, IDOR/cross-tenant, array input và dữ liệu ngày/ID không hợp lệ.
- Rà login/logout/session: regenerate khi login, strict mode, HttpOnly/SameSite/Secure
  theo HTTPS, tài khoản inactive/soft-deleted và phiên cũ sau thay đổi quyền.
- Rà cache/error/header ở HTML/AJAX/XLSX để tránh lộ dữ liệu; bổ sung header phù hợp sau
  khi kiểm tra tương thích. Chỉ siết CSP sau inventory inline scripts/styles và browser
  verification; không đặt policy làm hỏng Smarty/Chart.js/forms.
- Rà upload budgets/temporary cleanup/XML/ZIP/formula, explicit-string export, password
  provisioning, provenance và batch lock. Giữ dc_users MyISAM và UNIQUE email đã duyệt.
- Sửa các defect được chứng minh trong module PM; legacy chỉ ẩn/guard theo phạm vi PM,
  không xóa chức năng hoặc rewrite toàn hệ thống.
- Log/audit không chứa password/hash/token hoặc raw exception có PII.

## VERIFY / acceptance

1. Browser matrix các màn hình PM desktop/mobile, Admin/PM/HR/Employee theo quyền;
   keyboard/focus/labels/zoom/empty/error/read-only; fixture synthetic phân biệt với
   authenticated HTTP checks. Chụp và xem output sau thay đổi giao diện.
2. N+1 user-role: kết quả role/primary role trước/sau tương đương, query role không tăng
   theo số user trên trang; cross-tenant IDs fail closed, fixture rollback. Fixture user
   có 2–3 role, primary không nhất thiết đứng đầu/cuối; so khớp chính xác từng user toàn bộ
   role_id/is_primary và primary role với N query cũ, không chỉ assert không exception.
3. EXPLAIN và số query được ghi thật; regression double-counting, currency/DECIMAL,
   history, filter/pagination, overbooking và allocation locks không thay đổi.
4. Security negative tests mọi endpoint liên quan: role/tenant/ownership/CSRF/XSS/SQLi,
   malformed input, session/permission expiry, upload/export/password/provenance.
   Phân biệt tests tự động với penetration test độc lập.
5. Polling/visibility/filter/error và chart/table fallback không regress; tải local assets,
   không thêm dependency lớn/CDN động; header tương thích với browser.
6. Chạy `./tests/pm_regression.ps1` và tests Phase 9 mới phù hợp, PHP 8.3 lint,
   JS syntax, code/security review, diff check; sửa lỗi và chạy lại tests liên quan.

## Delivery và giới hạn

Cập nhật PROGRESS, README/PORTFOLIO chỉ theo kết quả đã chạy; có bảng coverage và các ca
chưa kiểm tra. Không ghi UAT PASS, không khẳng định production-ready/performance production.
Giới hạn Phase 8 chưa kill process sau successful MyISAM INSERT trên DB cô lập vẫn giữ rõ;
không dùng mirror test để tuyên bố transaction thật hoặc tự thay ENGINE.
Giữ working tree hiện có. Khi phase hoàn tất BUILD, VERIFY, code/security review và cập nhật
tài liệu, tự tạo một commit local trên nhánh phase theo AGENTS.md; chỉ stage file liên quan
đã kiểm chứng, loại secret/config/dump/cache/log/upload và báo cáo hash/branch/checks.
Không tự push/merge/deploy khi chưa có ủy quyền riêng, không truy cập production.
Sau Phase 9 automated checks đạt, lập phạm vi Phase 10 Test/UAT/Release Preparation;
người dùng UAT toàn local ở cuối, production chỉ sau nghiệm thu và ủy quyền riêng.

## Kết quả thực hiện

41 regression scripts PASS; 62 query shapes EXPLAIN, không ADD INDEX. UI/CSP/polling checks
PASS theo coverage thực tế tại [PHASE9_VERIFY.md](PHASE9_VERIFY.md). PM/HR authenticated
HTTP, browser zoom thật và screen-reader còn thiếu; không ghi UAT/production-ready.
