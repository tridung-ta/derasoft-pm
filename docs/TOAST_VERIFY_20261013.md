# Chỉnh toast và tiêu đề ngày 13/10/2026

- Toast thành công thu gọn: chữ 14px, dòng 21px, padding 12px, rộng tối đa 400px. Giữ màu xanh nhẹ, nội dung đầy đủ và role status.
- Nút đóng dùng SVG nét nhỏ, bỏ khung nút lớn, vùng bấm 44x44px, hỗ trợ focus và Enter. Lỗi vẫn persistent và không bị toast che.
- Tiêu đề giới thiệu đổi từ “Hệ thống đã hoàn thành” thành “Sẵn sàng làm việc”. Không thay đổi trạng thái nghiệm thu hoặc nghiệp vụ.
- Template gắn version asset 20261013-toast cho pmui.js/pmworkspace.css, tránh cache cũ trên host.
- Lệnh playwright-cli -s=workspace run-code --filename tests/pm_toast_refinement_browser.js PASS toast 1440/390, font/line/size/overflow/role/SVG và đóng bằng bàn phím, tiêu đề thực qua index không DB. Đã xem hai ảnh screenshot.
- Lần đầu test gọi trực tiếp pm-launch.php bị guard403, không có heading nên timeout. Sửa harness đi qua index; giữ guard, không sửa code sản phẩm để bỏ kiểm tra.
- playwright-cli -s=workspace run-code --filename tests/pm_vietnamese_messages_browser.js PASS hồi quy validation/error/success/không chồng lấn.
- powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1 PASS16 script không DB,7 PHP lint,5 manifest tests.
- node --check js/pmui.js và PHP lint pm-launch.php PASS. Detector một lần chỉ báo font đã chốt; giữ theme theo AGENTS.
- Chưa chạy DB/full regression/host UAT. Không tạo DB, không push/merge/deploy. Gói ngày13 thay thế gói ngày12; cách upload và optional maintenance như hướng dẫn cũ, không migration mới.
