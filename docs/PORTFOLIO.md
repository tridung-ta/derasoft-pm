# Portfolio notes — DeraSoft PM

## CV summary

**DeraSoft PM — Project & Timesheet Management** · PHP, Smarty, MySQL, custom MVC

Extended an existing DeraSoft application with tenant-scoped RBAC, workforce and project/task management, daily overtime calculation, rate snapshots, automatic timesheet locking and transactional audit trails. Verified service behavior and security boundaries with local automated smoke tests.

**Tiếng Việt:** Phát triển hệ thống quản lý dự án và chấm công trên nền PHP/Smarty/MySQL có sẵn; bổ sung RBAC theo tenant, nhân sự, dự án/task, tính OT theo ngày, snapshot đơn giá, khóa chấm công sau 3 ngày và audit log trong transaction.

Không ghi production-ready, UAT hoàn tất, kết quả hiệu năng hoặc số người dùng khi chưa có bằng chứng. Chi phí dashboard, allocation và reporting vẫn nằm trong roadmap.

## Trình bày trên GitHub

- Giữ repository `derasoft-pm` cho một sản phẩm rõ ràng; các phase là branch, không tạo repo cho từng phase.
- Main trình bày dự án và dẫn tới nhánh BUILD mới nhất; không merge code chưa nghiệm thu chỉ để tăng contribution.
- README nêu mục tiêu, chức năng đã có, quyết định kỹ thuật, trạng thái và cách kiểm thử.
- Banner là đồ họa giới thiệu, không phải screenshot giao diện. Bổ sung screenshot dữ liệu giả sau UAT khi được yêu cầu.
- Profile nên pin 3–6 dự án có vai trò khác nhau. Không đổi tên, xóa hoặc archive repo cũ trong đợt chỉnh DeraSoft PM này.

## Danh sách repo cần phân loại trước khi chỉnh tiếp

| Repo hiện có | Hướng trình bày cần xác minh |
| --- | --- |
| derasoft-pm | Project & timesheet management; đang là dự án portfolio được chỉnh trong đợt này |
| derasoft-editorial | Cần đọc nội dung để viết mô tả riêng, tránh trùng với PM |
| Product-CMS | Cần xác minh phạm vi và phần đóng góp trước khi viết CV |
| BE_MauThietKe / FE_MauThietKe | Có thể trình bày backend/frontend liên quan nếu source xác nhận cùng một sản phẩm |
| KHOA_LUAN_TOT_NGHIEP | Cần tên đề tài và vai trò cá nhân để mô tả chính xác |
| QUANLYTHUVIEN_NHOM8 / VSCODE_QUANLLYTHUVIEN | Repo riêng của dự án cũ; cần đọc source trước khi đề xuất đổi tên/ghim |

## Tạm dừng

Ngày 2026-10-02: chỉ chỉnh phần trình bày repository và danh tính Git. BUILD nghiệp vụ tiếp tục tạm dừng; chờ lệnh mới của người dùng. Workflow duyệt timesheet đã hoãn, UAT chưa xác nhận, chưa deploy production.

## Resume

Người dùng đã yêu cầu tiếp tục ngày 2026-10-02. Yêu cầu trình bày GitHub/commit chuyên nghiệp được lưu trong AGENTS.md; Phase 6 đang PLAN chờ duyệt. Chỉ thêm tính năng mới vào CV sau khi BUILD và VERIFY, không coi kế hoạch là chức năng đã hoàn thành.

## Phase 6 — local BUILD verified

Phase 6 đã BUILD và automated VERIFY trên `feature/pm-phase6-cost-chart`, chưa được push/merge/deploy.
Có thể bổ sung vào CV sau khi nghiệm thu: built a cost dashboard with stored timesheet costs,
DECIMAL estimates, mixed-currency guards, scoped permissions, local Chart.js and visibility-aware 60-second polling.
Evidence: service/HTTP/template/browser acceptance và regression ghi trong docs/PROGRESS.md.
Không ghi UAT hoàn tất hoặc production-ready; link GitHub Phase 6 chỉ thêm sau khi người dùng cho phép push.

## Phase 7 — local BUILD verified

Phase 6–7 đã được tách checkpoint local theo phase; chưa push/merge.
Đã xây dựng lịch phân bổ tuần, time-bound capacity, cảnh báo quá tải/trùng giờ nửa mở,
gợi ý capacity chỉ đọc và permission tenant/project/owner. Mutation và audit transactional;
mutex theo user/ngày đã kiểm tra bằng hai kết nối MySQL.
Service/HTTP/Smarty/synthetic-browser checks PASS; chưa UAT tài khoản thực tế,
chưa benchmark hoặc kiểm thử đầy đủ hai mutation nghiệp vụ đồng thời.
Chỉ công bố link triển khai và đưa vào CV sau khi nghiệm thu/phê duyệt tương ứng.

## Phase 8 — báo cáo/XLSX increment local

Nhánh local `feature/pm-phase8-reports-excel` có báo cáo giờ cá nhân/nhóm và chi phí scoped,
export XLSX explicit string chống formula injection, DECIMAL tiền và mixed-currency guard.
Service/HTTP/Smarty/browser increment đã kiểm thử. Import có XLSX preview/validation/staging,
giới hạn ZIP, chặn macro/DTD/external/formula và rollback batch khi lỗi.
Apply đã BUILD local sau phê duyệt UNIQUE(store_id,email), giữ MyISAM: INSERT/1062 từng dòng,
outcome journal, provenance recovery và mutex chống hai Admin Apply cùng batch.
Tài khoản mới inactive, Admin cấp mật khẩu có CSRF/ownership rồi kích hoạt riêng.
36 regression scripts PASS, browser import desktop/mobile/form POST PASS.
Thành công/crash Apply kiểm thử trên temporary user mirror để rollback fixture; native 1062
kiểm tra trên dc_users thật. Chưa kill process sau successful MyISAM INSERT trên DB cô lập,
chưa UAT hoặc production; không trình bày như kinh nghiệm triển khai production.

## Phase 9 — local BUILD / automated VERIFY

UI/accessibility refinement trên Smarty/MVC hiện có: shared permission-aware navigation,
labels/keyboard focus/skip link và responsive scroll regions; không rewrite framework.
Prepared tenant-scoped batch roles giữ chính xác primary role, 20 query role → 1 trong
fixture rollback. PM session/CSRF/logout và CSP hardening đã kiểm tra local; 41 regression
scripts PASS. EXPLAIN 62 query shapes, không tự thêm index hoặc suy luận hiệu năng production.
Evidence/coverage: PHASE9_VERIFY.md. Authenticated Admin/Employee khác với synthetic browser
fixtures; PM/HR authenticated UI chưa có actor active local. Không ghi UAT/production-ready.
Phase 6–9 có checkpoint commit local theo phase; chưa push/merge/deploy.
CV chỉ mô tả phần mở rộng nền DeraSoft và kết quả kiểm chứng, không nhận toàn bộ legacy là tự viết.

## Phase 10 ? local BUILD / automated VERIFY

Ki?m th? browser ??ng nh?p th?c t? ?? Admin/PM/HR/Employee: 36 ca role/route v? lu?ng
project ? task ? ch?m c?ng 9h ? export XLSX tr?n DB local c? l?p. Regression 41 scripts PASS.
Native MyISAM import ?? ki?m tra d?ng worker PHP sau INSERT r?i resume, kh?ng ??i ENGINE;
kh?ng suy r?ng th?nh ki?m th? m?t ?i?n ho?c crash m?y ch? MySQL.
?? chu?n b? manifest FileZilla, th? t? migration/seed, rollback v? smoke sau deploy.
Fixture ???c v? hi?u h?a/soft delete; kh?ng t?o nh?n s? gi? trong DB hi?n h?nh.
UAT ng??i d?ng, Firefox, zoom tr?nh duy?t th?t v? screen reader c?n ch?; ch?a production.
B?ng ch?ng v? gi?i h?n: [PHASE10_VERIFY.md](PHASE10_VERIFY.md).
