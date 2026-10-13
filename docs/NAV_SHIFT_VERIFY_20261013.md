# Điều hướng và nhãn ca bản chung ngày13/10/2026

## Đã duyệt và BUILD
Người dùng xác định nút Điều hướng như ảnh và duyệt PLAN_TIMESHEET_SHIFT_LABEL_20261013.md.
Nút Điều hướng trước đây bị CSS giấu trên desktop; bỏ giới hạn mobile, hiện ở mọi kích thước. Desktop mặc định mở, mobile mặc định đóng; native details hỗ trợ chuột/bàn phím, không thay quyền/router.
Nhãn mới chỉ chọn Ca sáng/Ca chiều/Ca tối/Ca linh hoạt, có thứ tự cố định. Server từ chối rỗng/tự gõ/quá30/ký tự điều khiển/kiểu sai. Nhãn cũ được giữ nguyên nếu không đổi, hoặc đổi sang nhãn chuẩn. Kiểm tra lại với bản ghi hiện tại sau khóa để không đưa lại nhãn cũ đã bị đổi. Không migration, không UPDATE dữ liệu cũ hàng loạt.
Bảng ngày/ca dùng ngày một dòng, badge ca chuẩn, nhãn lịch sử mở chi tiết escaped. Nhãn không quyết định giờ/OT; không thêm UNIQUE và không random số thứ tự. Cảnh báo/snapshot/rate/CSRF/quyền/khóa ngày giữ nguyên.

## VERIFY đã chạy
- .tools/php83/php.exe tests/pm_shift_label_smoke.php PASS24cases và real service từ chối nhãn tạo mới sai trước transaction; noDBadapter không phát sinh ghi. Case stale kiểm tra helper, không thay thế concurrencyDB.
- tests/smoke_pm_timesheets.php --preview PASS choices/select/canonicalbadge/date/legacyvalue escaped, trạng thái readonly/locked và settings giữ nguyên.
- tests/pm_workspace_verify.ps1 PASS17no-DBscripts,10PHP lint,5manifesttests.
- Lệnh browser từng suite: playwright-cli -s=workspace run-code --filename tests/<name>.js. Vòng21suites exit0/PASS tại .local/navshift-browser-final.log: pm_ui_browser, pm_workspace_browser, pm_account_browser, pm_kanban_browser, pm_login_import_workspace_browser, pm_preview_hub_browser, pm_phase9b_timesheets_browser, pm_phase9b_allocation_browser, pm_phase9b_costs_browser, pm_phase9b_imports_browser, pm_phase9b_buttons_browser, pm_phase9b_theme_browser, pm_phase9b_final_browser, pm_sidebar_overview_browser, pm_confirmation_browser, pm_people_desk_browser, pm_workspace_dashboard_browser, pm_vietnamese_messages_browser, pm_toast_refinement_browser, pm_navigation_all_pages_browser, pm_shift_label_browser.
- Navigation12screens xdesktop/mobile PASS nút visible, keyboard đóng/mở ởdesktop, pointer mobile, sidebar/topbar/tài khoản/logoutPOST+CSRF. Shift browser PASS4choices/required/canonicalbadge/legacy giữ nguyên/escaped details/keyboard/1440/390.
- Xem ảnh phát hiện ngày mobile xuống dòng; bổ sung nowrap và assertion, chạy lại pm_shift_label_browser PASS và xem ảnh xác nhận. Không gọi đó là lượt21mới.
- PHP lint mọi PHP sửa và helper/tests mới PASS. Detector một lần: giữ Inter đã chốt; parser không phân giải CSS theo webroot từ Smarty nên hierarchy được kiểm tra qua browser.
- Một vòng sơ bộ16noDBscripts và navigationPASS trước BUILD ca đã giữ làm lịch sử, không gộp số bài với lượt17cuối.

## Kiểm thử DB chưa chạy
Các fixture tạo mới của audit/cost/timesheet/window/HTTP/matrix/report đổi sang ca chuẩn để vẫn đi tới nghiệp vụ cần kiểm tra. Report formula-injection giữ qua nhãn lịch sử trên fixture sau tạo chuẩn; không bỏ assertion. Những suite DB đó và phase10browser chưa chạy theo yêu cầu không tạoDB.
Chưa kiểm tra create/edit có đăng nhập thật trên host, concurrency nhãn sau chờ khóa, fullDBregression/SQL scope. Host UAT kiểm tra thủ công; không đánh dấu PASS checklist cũ.

## Gói upload một lần
Dùng gói navshift cuối thay các gói trước. Upload toàn bộ nội dung runtime vào đúng webroot có admin.php. Helper classes/services/pmshiftlabelservice.class.php bắt buộc đi cùng controller/template; không upload lẻ template. Asset JS/CSS version20261013-navshift. Giữ config/license/uploads/DB.
Làm mới compiledSmarty/OPcache qua công cụhost vàCtrl+F5. Nút Điều hướng phải hiện trên desktop/mobile; chấm công có select4ca. Dữ liệu mẫu workbook dùng ca phù hợp hoặc Ca linh hoạt, các ca giờ vẫn theo workbook; không cố tạo nhãn tùy ý. Nhãn lịch sử không tự sửa; click Nhãn cũ để xemgốc.
Không migration mới. Gói đã chuẩn bịlocal; khôngpush/merge/deploy/DBtest.
