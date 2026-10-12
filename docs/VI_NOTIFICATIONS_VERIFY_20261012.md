# Kiểm tra thông báo tiếng Việt ngày 12/10/2026

## Phạm vi đã sửa
- Thông báo đăng nhập, quyền, phương thức AJAX, đơn giá, vai trò, import, phân bổ, dự án và báo cáo được viết lại bằng tiếng Việt. Giữ exception, mã HTTP và điều kiện nghiệp vụ.
- Lỗi mạng và phản hồi JSON không hợp lệ được chuyển thành thông báo tiếng Việt, không hiện nguyên lỗi kỹ thuật của trình duyệt.
- Validation trình duyệt có thông báo tiếng Việt cho required/email/số/giới hạn/pattern. Giữ lỗi riêng của ứng dụng; xóa lỗi tạm khi người dùng sửa dữ liệu.
- Cảnh báo thiếu đơn giá cũ được dịch khi render và vẫn escape HTML; không cập nhật DB hoặc audit cũ.
- Sửa câu xác nhận gỡ thành viên bị hỏng thành “G? ... kh?i d? ?n ...”.
- Thanh thông báo tăng độ tương phản và viền, cỡ chữ 15px, nhãn trạng thái và xuống dòng. Lỗi không tự biến mất. Nếu đồng thời có lỗi, thành công hiển thị inline để không che lỗi.
- Asset JS/CSS dùng phiên bản 20261012-vi để tránh trình duyệt tiếp tục dùng cache cũ. Không thay thiết kế riêng trang Chi phí.
- maintenance.html là trang tĩnh tiếng Việt tùy chọn, không tự bật/tắt chế độ bảo trì.

## Bằng chứng thực chạy
- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1: PASS 16 script không DB, 7 PHP lint, 5 manifest allowlist tests.
- .tools/php83/php.exe tests/pm_vietnamese_messages_smoke.php: PASS 69 nguồn PM hợp lệ UTF-8, không mojibake; pattern lỗi tiếng Anh cũ được loại; real validator ngày đơn giá từ chối ngày sai/đảo, chấp nhận bằng ngày và có thông báo Việt. Test không kết nối DB.
- PHP lint tất cả file PHP đã thay đổi, node --check cho pmui.js/pmallocations.js/pmcosts.js, python -m py_compile tests/pm_workspace_package.py, git diff --check: PASS.
- playwright-cli -s=workspace run-code --filename tests/pm_vietnamese_messages_browser.js: PASS validation Việt và phục hồi, không ghi đè lỗi custom, endpoint thực 401/404/405 trước bootstrap DB, lỗi mạng/JSON Phân bổ, thanh thông báo và bảo trì 1440/390px. Kiểm tra hình ảnh trước/sau, thêm assertion không chồng lấn.
- Vòng browser bao phủ pm_ui_browser, pm_workspace_browser, pm_account_browser, pm_kanban_browser, pm_login_import_workspace_browser, pm_preview_hub_browser, pm_phase9b_timesheets_browser, pm_phase9b_allocation_browser, pm_phase9b_costs_browser, pm_phase9b_imports_browser, pm_phase9b_buttons_browser, pm_phase9b_theme_browser, pm_phase9b_final_browser, pm_sidebar_overview_browser, pm_confirmation_browser, pm_people_desk_browser, pm_workspace_dashboard_browser và pm_vietnamese_messages_browser. Lệnh như trên theo từng tên file; log cuối: .local/vi-notifications-browser-final.log.
- Detector Impeccable chạy một lần. Cảnh báo Inter được giữ theo AGENTS; parser không phân giải được CSS theo web root trong Smarty nên kết luận hierarchy của detector không dùng làm bằng chứng browser. Không thay theme ngoài phạm vi.

## Lỗi kiểm tra đã khắc phục
- Test mới gọi API p.clock.uninstall không tồn tại. Sửa harness chỉ install clock một lần, không bỏ assertion; chạy lại PASS.
- Một lần sửa harness dùng sai thư mục làm việc nên không tìm thấy file; không thay code sản phẩm, sửa đường dẫn và chạy lại PASS.
- Ảnh đầu cho thấy toast thành công che lỗi trên mobile. Sửa hiển thị inline khi có lỗi và thêm assertion; xác nhận ảnh lần hai không chồng lấn.

## Chưa chạy
Full regression có DB, xác thực có cookie thật, scope SQL/EXPLAIN, ghi dữ liệu và UAT trên host chưa chạy theo yêu cầu không tạo DB kiểm thử. Không tự nâng checklist cũ hoặc nghiệm thu production. Server preview local dùng dữ liệu giả; endpoint chưa đăng nhập được kiểm tra thật và thoát trước DB.

Final browser confirmation: 18/18 suites PASS, exit 0, after feedback overlap fix and asset versioning. No DB connection or production access.

Security review: .tools/php83/php.exe .local/vi-notifications/check_tokens.php PASS: 21 runtime PHP files retain identical executable tokens outside string literals. No permission/HTTP/CSRF/query/exception changes.
