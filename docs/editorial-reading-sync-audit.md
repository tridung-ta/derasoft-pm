# Editorial Multi-device Reading Sync — Audit & Implementation Plan

Ngày audit: 24/09/2026  
Nhánh: `feature/ten-chuc-nang`

## Kết luận

Chức năng đồng bộ tiến độ đọc đã tồn tại ở mức **PARTIAL**. Website đã lưu `progress` theo tài khoản trong `dc_editorial_user_library`, lấy lại lịch sử ở Không gian đọc và tạo URL `resume` để cuộn tới vị trí cũ. Không cần WebSocket, bảng mới hay thay đổi schema để hoàn thiện phiên bản đầu.

## Luồng hiện tại

- `templates/mpx/js/editorial-library.js` tính phần trăm đọc và gửi `history` tới `/ajax.php`.
- `modules/ajax/editorial_library.module.php` kiểm tra đăng nhập, CSRF và lưu dữ liệu.
- `classes/dao/editorialuserlibrary.class.php` cập nhật `progress`, `activity_at` và payload theo tài khoản.
- `modules/estore/readinghub.module.php` thêm `?resume=<progress>` vào bài đọc dở.
- Trang bài viết chỉ khôi phục vị trí khi người dùng đi từ link có tham số `resume`.

## Vấn đề cần sửa

1. Scroll hiện debounce 1,2 giây nên có thể ghi DB quá thường xuyên; roadmap yêu cầu nhịp khoảng 5–10 giây.
2. DAO cập nhật progress không kiểm tra giá trị đang có; request cũ đến muộn hoặc tab cũ có thể làm lùi tiến độ mới hơn.
3. Request khi tab chuyển sang trạng thái ẩn dùng `fetch` thông thường, có thể bị trình duyệt hủy trước khi hoàn tất.
4. Client tải tối đa 100 lịch sử chỉ để tìm một bài hiện tại, gây thừa dữ liệu.
5. Không gian đọc có phần trăm nhưng chưa thể hiện rõ hành động “Đọc tiếp” và thời điểm đồng bộ gần nhất.

## Kế hoạch triển khai

### Bước 1 — Bảo vệ dữ liệu progress

- Với bản ghi `history`, chỉ tăng progress trong luồng đồng bộ tự động để request cũ không làm lùi tiến độ.
- Giữ nguyên hành vi của `saved` và `watch_later`.
- Không đổi schema và không đụng dữ liệu hiện có.

Commit dự kiến:

`fix(reading): prevent stale progress regression`

### Bước 2 — Tối ưu API và đồng bộ trình duyệt

- Thêm chế độ lấy một bản ghi history theo `item_key`, vẫn ràng buộc đúng tài khoản đăng nhập.
- Giới hạn đồng bộ định kỳ còn tối đa một lần mỗi 5 giây khi có thay đổi.
- Gửi lần cuối với `keepalive` khi tab bị ẩn hoặc trang sắp đóng.
- Không ghi khi progress không đổi.

Commit dự kiến:

`feat(reading): make cross-device progress sync resilient`

### Bước 3 — Làm rõ trải nghiệm đọc tiếp

- Hiện CTA “Đọc tiếp” cho lịch sử đang đọc dở.
- Hiện phần trăm và thời điểm đồng bộ theo giao diện VI/EN/中文.
- Giữ nguyên responsive hiện có, không tạo trang mới.

Commit dự kiến:

`feat(reading-hub): improve continue-reading experience`

## Kiểm thử bắt buộc

- Đăng nhập cùng tài khoản ở hai trình duyệt/thiết bị.
- Đọc một bài tới vị trí mới, chờ đồng bộ rồi mở Không gian đọc trên thiết bị còn lại.
- Bấm “Đọc tiếp” và xác nhận trang cuộn tới gần đúng vị trí.
- Xác nhận tab cũ không làm lùi progress đã ghi bởi thiết bị mới.
- Kiểm tra desktop, tablet, mobile và VI/EN/中文.
- Kiểm tra người chưa đăng nhập không đọc/ghi được lịch sử tài khoản.

## Thay đổi cần phê duyệt trước khi code

- Đổi đồng bộ từ debounce 1,2 giây sang nhịp tối đa một lần mỗi 5 giây.
- Progress lịch sử tự động chỉ tăng, không tự giảm khi người dùng cuộn ngược.
- Thêm API đọc một history item và CTA “Đọc tiếp”; không thay database.
