# Phase 9b — Bước 3: theme trung tính thống nhất

## Trạng thái — 05/10/2026

PLAN, chưa BUILD. Nhánh feature/pm-phase9b-ui-theme, sau 4be82da.
Thay bước nền tối cũ bằng token mới của PLAN_FINAL Phần 13 cho toàn bộ giao diện PM.
Không tự hiểu yêu cầu lập PLAN là duyệt BUILD; chờ người dùng xác nhận.

## Source đã xác minh

Các trang PM đang nạp css/pmui.css sau stylesheet base. Overview/profile/password/team
dùng shell/sidebar trong pm.tpl.html; các module khác dùng pm-navigation.tpl.html trực
tiếp dưới body. CSP chỉ cho self, nên Google Fonts bên ngoài sẽ bị chặn. Source chưa có
font Inter. Giữ cả hai cấu trúc hiện tại, chuẩn hóa cùng rail bằng CSS, không rewrite router.

## Danh sách file cố định sẽ sửa/tạo

### Runtime

1. css/pmui.css — chỉ file theme chung này: scope .pm-app, Inter, palette, rail,
   cards/inputs/buttons/focus/table/numeric/warning/mobile và modal hiện có.
2. templates/admin/pm-navigation.tpl.html — icon SVG trang trí inline + chữ, current page,
   giữ nguyên n.url/n.label/permission-filtered loop và logout POST.
3. templates/admin/pm.tpl.html — class gốc pm-app, hook số liệu KPI/giờ; không đổi layout KPI.
4. templates/admin/pm-login.tpl.html — class pm-app; đồng bộ font/card/form, không sidebar.
5. templates/admin/pm-users-v2.tpl.html — class pm-app, hook theme; giữ modal Bước 2.
6. templates/admin/pm-projects.tpl.html — class pm-app, hook số liệu; không grid card mới.
7. templates/admin/pm-timesheets.tpl.html — class pm-app, hook bảng giờ/tiền.
8. templates/admin/pm-audit.tpl.html — class pm-app; code/timestamp cùng Inter, không đổi log.
9. templates/admin/pm-costs.tpl.html — class pm-app, hook bảng/KPI numeric/warning;
   không đổi canvas/chart/tabs hoặc polling JS.
10. templates/admin/pm-allocations.tpl.html — class pm-app, hook numeric/warning;
    vẫn bảng hiện tại, không xây lưới tuần trước Bước 4.
11. templates/admin/pm-reports.tpl.html — class pm-app, numeric cells.
12. templates/admin/pm-imports.tpl.html — class pm-app, numeric cells; không stepper mới.
13. templates/admin/pm-rates-panel.tpl.html — num-cell cho rate, giữ toàn bộ form/confirmation.
14. assets/fonts/inter/InterVariable.woff2 (mới) — font Inter variable normal chính thức,
    có tiếng Việt; kiểm tra coverage và checksum, tự host, không request Google Fonts.
15. assets/fonts/inter/OFL.txt (mới) — giấy phép đi kèm font từ nguồn chính thức Inter;
    không đụng thư mục license legacy được bảo vệ.

### Tests và tài liệu

16. tests/smoke_pm_ui.php — root/theme references và fixture navigation đầy đủ cho browser.
17. tests/pm_phase9b_theme_browser.js (mới) — computed theme, font loading/CSP, numeric,
    icon+label/current nav, responsive, contrast, keyboard và screenshots.
18. docs/PHASE9B_STEP3_PLAN.md — PLAN này/trạng thái thực tế.
19. docs/PROGRESS.md — kết quả lệnh thật, ảnh/giới hạn và trạng thái user visual check.

Không thêm file ngoài danh sách nếu chưa bổ sung PLAN. Không sửa stylesheet base,
controller/DAO/routes/permission/schema/business logic, includes/pm_response.inc.php,
js/pmui.js hoặc JS polling/chart. Không thêm Bootstrap/React/shadcn/Tailwind/framework.

## Cách làm cụ thể

- Thêm class .pm-app vào body của các trang đang dùng theme; giữ class pm-users-page.
  Scope override màu/font/theme theo class này, giữ legacy ngoài PM nguyên trạng.
- Nền trắng #ffffff, chữ #09090b, card trắng/viền #e4e4e7 bo 8px;
  controls/badge bo 6px. Primary/destructive Bước 1 giữ nguyên, chữ destructive trắng
  là ngoại lệ contrast đã duyệt. Không đổi cấu trúc card/tabs/KPI ở các bước khác.
- Sidebar/nav cùng nền #f4f4f5, active #e4e4e7, icon + nhãn chữ nhìn thấy.
  Icon SVG vẽ trong template theo allowlist route đã có; aria-hidden, focusable=false,
  không ảnh/biểu tượng động từ input, không request icon CDN, không sửa URL/controller.
- Desktop >900px rail trái 260px, scroll độc lập; cả shell overview và module trực tiếp
  có cùng khoảng nội dung. Mobile/tablet <=900px menu wrap/flow, không ẩn mất chức năng,
  không xây drawer JS. Giữ thứ tự Tab, skip link, aria-current và logout CSRF.
- Self-host Inter font-display:swap, normal 100–900. Font fallback sans-serif khi chưa tải,
  nhưng chỉ báo font PASS nếu browser thực sự tải Inter và glyph tiếng Việt được hỗ trợ.
  Body/headings/controls/modal/numbers/pre/code dùng cùng family, không mono riêng.
- .num-cell dùng tabular-nums và căn phải, chỉ gắn ô/khối giờ/tiền, không căn phải
  toàn bộ bảng hoặc nhãn/trạng thái. Không làm tròn/đổi giá trị DECIMAL hay format nghiệp vụ.
  Bảng polling áp qua hook cột/table CSS giữ nguyên khi JS thay tbody, không sửa polling.
- Muted foreground #71717a trên white; ở nền muted nếu không đạt 4.5:1 dùng #52525b.
  Border #e4e4e7 là viền trang trí; input/focus nếu cần tương phản 3:1 sẽ có viền mạnh
  #71717a/focus #09090b, không làm mất accessibility để giữ token mảnh.
- Warning #d97706 dùng accent/viền/icon hoặc nền với chữ đen đạt contrast; không dùng
  làm chữ nhỏ trên white. Chỉ warning nghiệp vụ hiện có được nhấn màu; instructions,
  trạng thái bình thường/import staging dùng trung tính. Không suy diễn cảnh báo mới.
- Giữ notice/error semantics và nội dung; màu lỗi/destructive đỏ không bị đổi thành warning.
  Không nâng token mới thành tín hiệu UAT hoặc đổi logic phân quyền.

## VERIFY / acceptance

1. Smarty previews cho 9 màn hình và login/profile/password/team; fixture nav đủ mục,
   permission menus vẫn qua regression HTTP; không giả fixture là authenticated browser.
2. ./tests/pm_regression.ps1: full 42 scripts; php -l file PHP test đã sửa.
3. Browser Phase 9 matrix 360/390/768/1440, labels/skip/focus/no page overflow,
   reduced motion và text scaling 200%. Chạy lại Bước 1 buttons/Bước 2 modal browser.
4. Theme browser: font Inter actual loaded, font request same-origin, không CSP violation;
   computed colors/radius/nav/numeric alignment; primary/destructive/warning/muted contrast.
   Kiểm tra thay tbody kiểu polling vẫn giữ style numeric theo cột.
5. Chụp/view desktop/mobile overview/users/timesheets/costs, keyboard navigation/menu đầy đủ,
   dialog dưới theme sáng; đọc Console phân biệt lỗi mới và fixture/favicon 404 đã biết.
6. Code/security review + diff check; không đổi form/action/CSRF/ownership/escaping và
   không rò legacy styles. Giữ request/font size hợp lý; không tuyên bố performance tốt
   hơn nếu chưa đo. Không nới CSP hoặc bỏ header để tải font.
7. Cập nhật PROGRESS, commit local thay đổi đã kiểm chứng; báo link xem thử và files.
   Người dùng kiểm tra thị giác và browser zoom 200% thật; không gọi text scaling là zoom.
   Dừng chờ bước tiếp theo, không BUILD Bước 4/push/merge/deploy.

Font là asset release mới: ghi rõ vào PROGRESS; khi chuẩn bị release cuối Phase 9b,
regenerate manifest để có đủ WOFF2/giấy phép và template/CSS, không dùng package Phase 10 cũ.
