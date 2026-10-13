# Kiểm tra thông báo giữa màn hình và điều hướng ngày 13/10/2026

## Quyết định mới
Thông báo thành công đặt chính giữa viewport, khung rộng 560px và tối thiểu 128px cao; giới hạn theo màn hình. Chữ 18px trên desktop, 16px trên mobile; tiêu đề 20/18px. Nút đóng SVG giữ vùng bấm44px. Quyết định này thay thế yêu cầu toast gọn ở lượt trước. Lỗi vẫn ở trang, không bị success che khi cùng xuất hiện. Không backdrop hoặc chặn form như modal.

## Điều hướng
12 trang local dùng chung sidebar/topbar/tài khoản: Tổng quan, Hồ sơ, Đổi mật khẩu, Nhóm, Nhân sự, Dự án, Chấm công, Nhật ký, Chi phí, Phân bổ, Import, Báo cáo. Menu mobile đóng/mở và logout giữ POST+CSRF.
Source admin.php cấp pmNavigation khi PM_HIDE_LEGACY bật và có PmAccess; pmusers.module.php dùng pm-users-v2. Không sửa các điều kiện này. Ảnh production tại admin.php?op=pm đã có cấu trúc sidebar mới, chưa có bằng chứng root cause toolbar cũ. Không truy cập production.
Một số fixture Smarty cũ chưa cấp pmNavigation nên menu rỗng. Đã thêm helper synthetic Admin chỉ trong tests; không cấp quyền ở runtime. Fixture Import phase8-preview là HTML cũ, test chuyển sang phase9b-import-initial do runner tạo. Không sửa sản phẩm để nới assertion.

## Lệnh và output thật
- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1 PASS16 no-DB scripts,7 PHP lint,5 manifest tests.
- playwright-cli -s=workspace run-code --filename tests/pm_toast_refinement_browser.js PASS tâm X/Y trên1440/390, chữ18/16, SVG44px, keyboard close, role status, không tràn. Đã xem ảnh desktop/mobile.
- playwright-cli -s=workspace run-code --filename tests/pm_navigation_all_pages_browser.js PASS12trang x2viewport sidebar/topbar/tài khoản/toggle/logout POST+CSRF.
- Vòng toàn bộ20browser suites PASS, exit0, log ignored.local/centered-notification-browser.log. Danh sách: pm_ui_browser, pm_workspace_browser, pm_account_browser, pm_kanban_browser, pm_login_import_workspace_browser, pm_preview_hub_browser, pm_phase9b_timesheets_browser, pm_phase9b_allocation_browser, pm_phase9b_costs_browser, pm_phase9b_imports_browser, pm_phase9b_buttons_browser, pm_phase9b_theme_browser, pm_phase9b_final_browser, pm_sidebar_overview_browser, pm_confirmation_browser, pm_people_desk_browser, pm_workspace_dashboard_browser, pm_vietnamese_messages_browser, pm_toast_refinement_browser, pm_navigation_all_pages_browser. Lệnh như trên theo từng file.
- Lần fail: toast mobile104px dưới kỳ vọng tối thiểu, bổ sung min-height128px và chạy lại. Menu Nhân sự rỗng do fixture thiếu pmNavigation; sửa fixture. Import thiếu shell do test chọn file cũ; đổi đúng fixture mới và chạy lại. Giữ toàn bộ assertion.
- Detector stylesheet chạy một lần, chỉ cảnh báo fontInter đã chốt theoAGENTS; không thay theme.
- Kiểm tra gói bằng manifest/CRC/SHA256 sau commit; không upload hoặc chạy SQL.

## Giới hạn
Không tạo DB kiểm thử. Full regression DB, phiên đăng nhập thật, scopeSQL/EXPLAIN và host UAT chưa chạy. Không suy frontend/fixture PASS thành productionPASS. Tài khoản synthetic Admin trong fixture chỉ minh họa menu, không nghiệm thu quyền thực.

## Upload một lần
Dùng gói centered cuối, không dùng lại ZIP compact trước. Backup và upload toàn bộ nội dung runtime vào đúng web root đang phục vụ admin.php. Không ghi đè config/license/uploads/DB. Không migration mới.
Template phải tải css/pmworkspace.css?v=20261013-centered và js/pmui.js phiên bản đã ghi. Làm mới compiled Smarty/OPcache bằng công cụ host cho phép, Ctrl+F5.
Nếu menu vẫn khác: trong View Source kiểm tra pm-work-sidebar và pm-work-topbar. Không có hai chuỗi thì cần đối chiếu web root/template/controller đang chạy. Có HTML nhưng mất menu thì kiểm tra response CSS200, phiên bản asset, lỗiJS và PM_HIDE_LEGACY/quyền. Không tắt kiểm tra quyền để sửa menu.
Bản này được chuẩn bị local; production chưa được triển khai bởi agent.
