# Điều hướng trên thanh đầu trang — 13/10/2026

## Phạm vi
Theo yêu cầu mới, bỏ sidebar khỏi shell PM đang dùng; chuyển nút Điều hướng xuống dưới Tài khoản, góc phải. Menu mặc định đóng trên desktop/mobile, mở bằng chuột hoặc Enter, đóng khi bấm ngoài/Escape. Giữ các link, phân quyền và logout POST/CSRF. Không sửa BE, DB hoặc production. Thông báo top-center 10 giây và nhãn ca đã duyệt giữ nguyên.

## Bằng chứng local
- `powershell -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1`: PASS, 17 scripts không DB, 10 PHP lint, 5 kiểm tra allowlist manifest.
- `playwright-cli -s=workspace run-code --filename tests/<suite>.js`: 21 suite PASS: pm_ui_browser, pm_workspace_browser, pm_account_browser, pm_kanban_browser, pm_login_import_workspace_browser, pm_preview_hub_browser, pm_phase9b_timesheets_browser, pm_phase9b_allocation_browser, pm_phase9b_costs_browser, pm_phase9b_imports_browser, pm_phase9b_buttons_browser, pm_phase9b_theme_browser, pm_phase9b_final_browser, pm_sidebar_overview_browser, pm_confirmation_browser, pm_people_desk_browser, pm_workspace_dashboard_browser, pm_vietnamese_messages_browser, pm_toast_refinement_browser, pm_navigation_all_pages_browser, pm_shift_label_browser.
- Sau kiểm tra ảnh, sửa dropdown mobile tràn phải (left cũ kế thừa) và chừa khoảng phía trên nội dung để nút không che liên kết. Chạy lại `playwright-cli -s=workspace run-code --filename tests/pm_navigation_all_pages_browser.js`: PASS trên 12 trang ở 1440/390px; assertion bounding box dropdown nằm trong viewport, tiêu đề dưới vùng điều khiển, không sidebar, không tràn ngang, keyboard/Escape/outside click và logout CSRF.
- `node --check js/pmui.js`, `git diff --check`: PASS.
- Xem ảnh viewport cuối desktop và menu mobile: nút nằm dưới Tài khoản; menu trong viewport. Ảnh full-page sau focus có cuộn khác vị trí ban đầu, không dùng làm bằng chứng lỗi header.
- Review: chỉ thay shell/style/đóng mở menu và cache version 20261013-headernav. Link/quyền/CSRF nguyên trạng; dữ liệu menu được render theo logic hiện có.

## Giới hạn / host UAT
Các trang browser dùng fixture không DB. Test HTTP/DB và pm_phase10_browser chưa chạy theo chỉ định người dùng; không suy luận production PASS. Không tạo DB, không seed hoặc thay đổi dữ liệu thật.
Upload toàn bộ runtime từ ZIP mới (thay gói navshift trước), làm mới compiled Smarty/OPcache bằng công cụ host và Ctrl+F5. Kiểm tra mọi vai trò, mở/đóng Điều hướng, Tài khoản/Đăng xuất, nhãn ca và thông báo sau lưu.
