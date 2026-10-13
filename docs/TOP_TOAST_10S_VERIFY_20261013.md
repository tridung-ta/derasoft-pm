# Thông báo phía trên và tự ẩn10giây ngày13/10/2026

Quyết định mới thay thế cách đặt giữa chiều dọc ở gói trước: success toast căn giữa ngang, top80px dưới topbar64px, rộng480px và tối thiểu96pxcao; giới hạn theo màn hình. Chữ16pxdesktop/15pxmobile, title18/17px; padding vừa phải, không backdrop. NútSVG44px, đóng bằng bàn phím hoặc chuột.

Success tự ẩn sau10000ms, kể cả success inline kèm lỗi. Nút đóng hủy timer. Lỗi vẫn persistent, không tự ẩn. Không thay BE/DB/quyền. Template dùng phiên bản asset20261013-top10s.

## Bằng chứng
- playwright-cli -s=workspace run-code --filename tests/pm_toast_refinement_browser.js PASS top80/căn giữa1440/390, chữ16/15,44pxSVG, keyboard dismissal, còn hiện9999ms và bị gỡ10000ms, lỗi vẫn hiện khi success hết hạn. Xem ảnh desktop/mobile.
- Lần test đầu dùng clock chạy tự động nên thời gian từ navigation đến fastForward cộng dồn, assertion9999ms fail. Sửa harness pause clock tại mốc cố định, không đổi timer10000ms hoặc bỏ assertion; chạy lạiPASS.
- pm_vietnamese_messages_browser.js PASS hồi quy tiếng Việt/error/network/JSON và không chồng lấn.
- pm_ui_browser.js PASS fixture các trang PM,360/390/768/1440,keyboard/CSP/200%text scaling.
- pm_navigation_all_pages_browser.js PASS12PMscreens xdesktop/mobile sharedsidebar/topbar/account/menu/logoutPOST+CSRF.
- tests/pm_workspace_verify.ps1 PASS16no-DBscripts,7PHP lint,5manifesttests.
- node --check js/pmui.js/git diff --check PASS. Full20browser trước đó thuộc bản centered; lượt top10s chỉ chạy4suite liên quan, không gộp kết quả cũ thành lượt20mới.

## Bàn giao
Gói top10s mới thay gói centered98e39b0 trước. Upload nội dung runtime vào webroot có admin.php; làm mới compiledSmarty/OPcache bằng công cụhost vàCtrl+F5. ViewSource phải thấy query version20261013-top10s. Khôngmigration mới, khôngghi đèconfig/license/uploads/DB.
DB/fullregression/phiên đăng nhập thật/hostUAT CHƯACHẠY theo quyết định kiểmthủcông. Khôngpush/merge/deploy.
