# Tinh chỉnh hình thức thông báo ngày13/10/2026

Banner trắng, viền xanh nhạt và bóng nhẹ thay hộp xanh lớn. Dấu kiểmSVG trên nền xanh nhẹ bên trái, chữ15px và tiêu đề vừa phải, nút đóng44px riêng bên phải. Rộng440px, cao tối thiểu84px, giới hạn theo viewport. Giữ top80px/căn giữa ngang và tự ẩn success10000ms; lỗi persistent.

playwright-cli -s=workspace run-code --filename tests/pm_toast_refinement_browser.js PASS1440/390, markerSVG, chữ15px, nút44px/keyboard, vị trí top-center/nooverflow, timer9999/10000ms và lỗi không tự ẩn. Đã xem hai ảnh.
pm_vietnamese_messages_browser.js PASS hồi quy thông báo Việt/custom validation/network/JSON/mixed error+success.
powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1 PASS16no-DBscripts,7PHP lint,5manifesttests.
node --check js/pmui.js/git diff --check PASS.
Không thay BE/DB/quyền, không tạo DB hoặc truy cập production. DB/fullregression/hostUAT chưa chạy. Asset version20261013-refined. Gói mới thay top10s trước; uploadruntime và làm mớiSmarty/OPcache/assetcache theo hướng dẫn.
